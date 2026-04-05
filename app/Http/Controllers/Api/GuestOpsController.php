<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\GuestTicketReply;
use App\Services\EventLogService;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;

class GuestOpsController extends Controller
{
    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService
    ) {
    }

    public function tickets(Request $request)
    {
        $companyId = $this->currentCompanyId();
        $guestId = (int) $request->query('guest_id', 0);
        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));

        return GuestTicket::query()
            ->with(['guestApplication:id,first_name,last_name,email', 'replies' => fn ($q) => $q->latest()->limit(10)])
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when($guestId > 0, fn ($q) => $q->where('guest_application_id', $guestId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($priority !== '', fn ($q) => $q->where('priority', $priority))
            ->latest()
            ->limit(100)
            ->get();
    }

    public function updateTicketStatus(Request $request, GuestTicket $guestTicket)
    {
        $this->assertTicketCompanyAccess($guestTicket);
        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,waiting_response,closed'],
        ]);

        $newStatus = (string) $data['status'];
        $guestTicket->update([
            'status' => $newStatus,
            'last_replied_at' => now(),
            'closed_at' => $newStatus === 'closed' ? now() : null,
        ]);

        if ($newStatus === 'closed') {
            $this->taskAutomationService->markTasksDoneBySource('guest_ticket_opened', (string) $guestTicket->id);
        } else {
            $guest = GuestApplication::query()->find((int) $guestTicket->guest_application_id);
            if ($guest) {
                $this->taskAutomationService->ensureGuestTicketTask($guest, $guestTicket);
            }
        }

        $this->eventLogService->log(
            eventType: 'guest_ticket_status_updated',
            entityType: 'guest_ticket',
            entityId: (string) $guestTicket->id,
            message: "Ticket #{$guestTicket->id} durumu {$newStatus} yapildi.",
            meta: ['status' => $newStatus],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guestTicket->company_id ?: 0)
        );

        return response()->json($guestTicket->fresh()->load('guestApplication:id,first_name,last_name,email'));
    }

    public function replyTicket(Request $request, GuestTicket $guestTicket)
    {
        $this->assertTicketCompanyAccess($guestTicket);
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'author_role' => ['nullable', 'in:manager,senior,system'],
            'status_after_reply' => ['nullable', 'in:open,in_progress,waiting_response,closed'],
        ]);

        $authorRole = (string) ($data['author_role'] ?? 'manager');
        GuestTicketReply::query()->create([
            'company_id' => $guestTicket->company_id,
            'guest_ticket_id' => (int) $guestTicket->id,
            'author_role' => $authorRole,
            'author_email' => (string) optional($request->user())->email,
            'message' => trim((string) $data['message']),
        ]);

        $statusAfter = (string) ($data['status_after_reply'] ?? 'waiting_response');
        $firstResponseAt = $guestTicket->first_response_at;
        if (!$firstResponseAt && in_array($authorRole, ['manager', 'senior', 'system'], true)) {
            $firstResponseAt = now();
        }
        $guestTicket->update([
            'status' => $statusAfter,
            'last_replied_at' => now(),
            'first_response_at' => $firstResponseAt,
            'closed_at' => $statusAfter === 'closed' ? now() : null,
        ]);

        if ($statusAfter === 'closed') {
            $this->taskAutomationService->markTasksDoneBySource('guest_ticket_opened', (string) $guestTicket->id);
        }

        $this->eventLogService->log(
            eventType: 'guest_ticket_replied',
            entityType: 'guest_ticket',
            entityId: (string) $guestTicket->id,
            message: "Ticket #{$guestTicket->id} manager/senior yaniti eklendi.",
            meta: ['status_after_reply' => $statusAfter, 'author_role' => $authorRole],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guestTicket->company_id ?: 0)
        );

        return response()->json($guestTicket->fresh()->load(['guestApplication:id,first_name,last_name,email', 'replies' => fn ($q) => $q->latest()->limit(10)]));
    }

    public function documents(Request $request)
    {
        $companyId = $this->currentCompanyId();
        $guestId = (int) $request->query('guest_id', 0);
        $status = trim((string) $request->query('status', ''));
        $categoryCode = trim((string) $request->query('category_code', ''));

        return Document::query()
            ->with('category:id,code,name_tr')
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when($guestId > 0, function ($q) use ($guestId): void {
                $guest = GuestApplication::query()->find($guestId);
                if (!$guest) {
                    $q->whereRaw('1=0');
                    return;
                }
                if ($this->currentCompanyId() > 0 && (int) ($guest->company_id ?? 0) !== $this->currentCompanyId()) {
                    $q->whereRaw('1=0');
                    return;
                }
                $ownerIds = [$this->guestOwnerId($guest)];
                if (trim((string) ($guest->converted_student_id ?? '')) !== '') {
                    $ownerIds[] = (string) $guest->converted_student_id;
                }
                $q->whereIn('student_id', $ownerIds);
            }, function ($q): void {
                $q->where('student_id', 'like', 'GST-%');
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($categoryCode !== '', function ($q) use ($categoryCode): void {
                $q->whereHas('category', fn ($cq) => $cq->where('code', $categoryCode));
            })
            ->latest()
            ->limit(100)
            ->get();
    }

    public function decideDocument(Request $request, Document $document)
    {
        $this->assertDocumentCompanyAccess($document);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $decision = (string) $data['decision'];
        $reviewNote = trim((string) ($data['review_note'] ?? ''));
        if ($decision === 'reject' && $reviewNote === '') {
            abort(422, 'Ret icin inceleme notu zorunludur.');
        }

        $newStatus = $decision === 'approve' ? 'approved' : 'rejected';
        $document->update([
            'status' => $newStatus,
            'approved_by' => (string) optional($request->user())->email ?: 'system',
            'approved_at' => now(),
            'review_note' => $reviewNote !== '' ? $reviewNote : null,
        ]);

        if ($newStatus === 'approved') {
            $this->taskAutomationService->markTasksDoneBySource('student_document_uploaded', (string) $document->id);
            $this->taskAutomationService->markTasksDoneBySource('guest_document_uploaded', (string) $document->id);
        }

        if ($newStatus === 'rejected') {
            $this->dispatchRejectionNotification($document, $reviewNote, optional($request->user())->email ?: 'system');
        }

        $this->eventLogService->log(
            eventType: 'guest_document_decision',
            entityType: 'document',
            entityId: (string) $document->id,
            message: "Belge #{$document->id} {$newStatus} yapildi.",
            meta: ['decision' => $decision, 'status' => $newStatus, 'note' => $reviewNote],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($document->company_id ?: 0)
        );

        return response()->json($document->fresh()->load('category'));
    }

    private function dispatchRejectionNotification(Document $document, string $reviewNote, string $triggeredBy): void
    {
        $studentId = (string) $document->student_id;
        $userId    = null;

        if (str_starts_with($studentId, 'GST-')) {
            $numericId = ltrim(substr($studentId, 4), '0') ?: '0';
            $guest     = GuestApplication::find((int) $numericId);
            $userId    = ($guest && $guest->guest_user_id) ? (int) $guest->guest_user_id : null;
        } else {
            $found  = \App\Models\User::query()->where('student_id', $studentId)->value('id');
            $userId = $found ? (int) $found : null;
        }

        if (! $userId) {
            return;
        }

        $body = "«{$document->original_file_name}» belgeniz incelendi ve reddedildi."
            . ($reviewNote !== '' ? "\n\nNeden: {$reviewNote}" : '')
            . "\n\nLütfen belgeler sayfasından ilgili belgeyi yeniden yükleyin.";

        app(\App\Services\NotificationService::class)->send([
            'channel'      => 'in_app',
            'category'     => 'document_rejected',
            'user_id'      => $userId,
            'student_id'   => $studentId,
            'subject'      => 'Belgeniz reddedildi',
            'body'         => $body,
            'source_type'  => 'document',
            'source_id'    => (string) $document->id,
            'triggered_by' => $triggeredBy,
        ]);
    }

    private function guestOwnerId(GuestApplication $guest): string
    {
        return 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }

    private function currentCompanyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }

    private function assertTicketCompanyAccess(GuestTicket $ticket): void
    {
        $companyId = $this->currentCompanyId();
        if ($companyId > 0 && (int) ($ticket->company_id ?? 0) !== $companyId) {
            abort(404);
        }
    }

    private function assertDocumentCompanyAccess(Document $document): void
    {
        $companyId = $this->currentCompanyId();
        if ($companyId > 0 && (int) ($document->company_id ?? 0) !== $companyId) {
            abort(404);
        }
    }
}
