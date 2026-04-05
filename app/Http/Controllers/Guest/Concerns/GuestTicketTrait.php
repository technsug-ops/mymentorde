<?php

namespace App\Http\Controllers\Guest\Concerns;

use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\GuestTicketReply;
use App\Support\FileUploadRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait GuestTicketTrait
{
    public function storeTicket(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $data = $request->validate([
            'subject'    => ['required', 'string', 'max:180'],
            'message'    => ['required', 'string', 'max:5000'],
            'priority'   => ['nullable', 'in:low,normal,high,urgent'],
            'department' => ['nullable', 'in:advisory,system,finance,auto'],
            'attachment' => FileUploadRules::attachment(),
        ]);

        $department = trim((string) ($data['department'] ?? ''));
        if ($department === '' || $department === 'auto' || !in_array($department, ['advisory', 'system', 'finance'], true)) {
            $department = $this->suggestTicketDepartment(
                (string) ($data['subject'] ?? ''),
                (string) ($data['message'] ?? '')
            );
        }

        $ticketData = [
            'guest_application_id' => (int) $guest->id,
            'subject'              => strip_tags(trim((string) $data['subject'])),
            'message'              => strip_tags(trim((string) $data['message'])),
            'status'               => 'open',
            'priority'             => (string) ($data['priority'] ?? 'normal'),
            'department'           => $department,
            'created_by_email'     => (string) optional($request->user())->email,
            'last_replied_at'      => now(),
            'routed_at'            => now(),
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $ticketData['attachment_path'] = $file->store('ticket-attachments', 'private');
            $ticketData['attachment_name'] = $file->getClientOriginalName();
        }

        $ticket = GuestTicket::query()->create($ticketData);

        $replyData = [
            'guest_ticket_id' => (int) $ticket->id,
            'author_role'     => 'guest',
            'author_email'    => (string) optional($request->user())->email,
            'message'         => strip_tags(trim((string) $data['message'])),
        ];
        if (isset($ticketData['attachment_path'])) {
            $replyData['attachment_path'] = $ticketData['attachment_path'];
            $replyData['attachment_name'] = $ticketData['attachment_name'];
        }
        GuestTicketReply::query()->create($replyData);
        $this->taskAutomationService->ensureGuestTicketTask($guest, $ticket);
        $this->eventLogService->log(
            eventType: 'guest_ticket_opened',
            entityType: 'guest_ticket',
            entityId: (string) $ticket->id,
            message: "Guest #{$guest->id} ticket acti.",
            meta: ['subject' => (string) $ticket->subject, 'priority' => (string) $ticket->priority, 'department' => (string) $ticket->department],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->queueTemplateNotification(
            guest: $guest,
            category: 'guest_ticket_update',
            sourceType: 'guest_ticket_opened',
            sourceId: (string) $ticket->id,
            vars: ['ticket_id' => (string) $ticket->id]
        );

        return redirect()->route('guest.tickets')->with('status', 'Ticket olusturuldu.');
    }

    public function replyTicket(Request $request, GuestTicket $ticket)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');
        abort_if((int) $ticket->guest_application_id !== (int) $guest->id, 403, 'Bu ticket size ait degil.');

        $data = $request->validate([
            'message'    => ['required', 'string', 'max:5000'],
            'attachment' => FileUploadRules::attachment(),
        ]);

        $replyData = [
            'guest_ticket_id' => (int) $ticket->id,
            'author_role'     => 'guest',
            'author_email'    => (string) optional($request->user())->email,
            'message'         => strip_tags(trim((string) $data['message'])),
        ];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $replyData['attachment_path'] = $file->store('ticket-attachments', 'private');
            $replyData['attachment_name'] = $file->getClientOriginalName();
        }

        GuestTicketReply::query()->create($replyData);

        $ticket->forceFill([
            'status' => 'waiting_response',
            'last_replied_at' => now(),
        ])->save();
        $this->eventLogService->log(
            eventType: 'guest_ticket_reply',
            entityType: 'guest_ticket',
            entityId: (string) $ticket->id,
            message: "Guest #{$guest->id} ticket yaniti ekledi.",
            meta: ['status' => 'waiting_response'],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->queueTemplateNotification(
            guest: $guest,
            category: 'guest_ticket_update',
            sourceType: 'guest_ticket_reply',
            sourceId: (string) $ticket->id,
            vars: ['ticket_id' => (string) $ticket->id]
        );

        return redirect()->route('guest.tickets')->with('status', 'Ticket yaniti eklendi.');
    }

    public function closeTicket(Request $request, GuestTicket $ticket)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');
        abort_if((int) $ticket->guest_application_id !== (int) $guest->id, 403, 'Bu ticket size ait degil.');

        $ticket->forceFill([
            'status' => 'closed',
            'last_replied_at' => now(),
            'closed_at' => now(),
        ])->save();
        $this->taskAutomationService->markTasksDoneBySource('guest_ticket_opened', (string) $ticket->id);

        return redirect()->route('guest.tickets')->with('status', "Ticket #{$ticket->id} kapatildi.");
    }

    public function reopenTicket(Request $request, GuestTicket $ticket)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');
        abort_if((int) $ticket->guest_application_id !== (int) $guest->id, 403, 'Bu ticket size ait degil.');

        $ticket->forceFill([
            'status' => 'open',
            'last_replied_at' => now(),
            'closed_at' => null,
        ])->save();
        $this->taskAutomationService->ensureGuestTicketTask($guest, $ticket);

        return redirect()->route('guest.tickets')->with('status', "Ticket #{$ticket->id} yeniden acildi.");
    }

    public function downloadTicketAttachment(GuestTicket $ticket): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_if(!$ticket->attachment_path, 404, 'Bu ticket için ek bulunamadı.');
        abort_if(!Storage::disk('private')->exists((string) $ticket->attachment_path), 404);

        return Storage::disk('private')->download(
            (string) $ticket->attachment_path,
            (string) ($ticket->attachment_name ?? 'attachment')
        );
    }

    private function suggestTicketDepartment(string $subject, string $message): string
    {
        $txt = mb_strtolower(trim($subject.' '.$message));
        if ($txt === '') {
            return 'advisory';
        }

        $rules = [
            'system'   => ['hata', 'error', 'bug', 'login', 'sifre', 'password', 'ekran', 'acilmiyor', '500', '404', 'teknik'],
            'finance'  => ['odeme', 'fatura', 'ucret', 'para', 'banka', 'transfer', 'invoice', 'payment', 'finans', 'hesap', 'taksit', 'borc', 'alacak'],
            'advisory' => ['danisman', 'program', 'bolum', 'okul', 'universite', 'hedef', 'kabul', 'admission', 'kariyer'],
        ];

        $bestDept = 'advisory';
        $bestScore = 0;
        foreach ($rules as $dept => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains($txt, (string) $kw)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDept = $dept;
            }
        }
        return $bestDept;
    }
}
