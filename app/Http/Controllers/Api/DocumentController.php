<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\DocumentNamingService;
use App\Services\DocumentTagService;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function index()
    {
        return Document::query()->with('category')->latest()->limit(50)->get();
    }

    public function previewName(Request $request, DocumentNamingService $naming, DocumentTagService $tagService)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'category_code' => ['required', 'string', 'max:64'],
            'process_tags' => ['nullable', 'array'],
            'extension' => ['nullable', 'string', 'max:10'],
        ]);

        $tags = $tagService->normalize($data['process_tags'] ?? []);
        [$firstName, $lastName] = $this->resolvePersonName($data['student_id']);

        return response()->json([
            'standard_file_name' => $naming->buildStandardFileName(
                $data['student_id'],
                $data['category_code'],
                $firstName,
                $lastName,
                $data['extension'] ?? 'pdf'
            ),
            'process_tags' => $tags,
        ]);
    }

    public function store(Request $request, DocumentNamingService $naming, DocumentTagService $tagService, TaskAutomationService $taskAutomation)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'category_code' => ['required', 'string', 'max:64'],
            'process_tags' => ['nullable', 'array'],
            'original_file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:128'],
            'storage_path' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $category = DocumentCategory::query()->where('code', $data['category_code'])->firstOrFail();
        $tags = $tagService->normalize($data['process_tags'] ?? []);

        $ext = pathinfo($data['original_file_name'], PATHINFO_EXTENSION) ?: 'pdf';
        [$firstName, $lastName] = $this->resolvePersonName($data['student_id']);
        $row = Document::create([
            'student_id' => $data['student_id'],
            'category_id' => $category->id,
            'process_tags' => $tags,
            'original_file_name' => $data['original_file_name'],
            'standard_file_name' => $naming->buildStandardFileName($data['student_id'], $data['category_code'], $firstName, $lastName, $ext),
            'storage_path' => $data['storage_path'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'status' => $data['status'] ?? 'uploaded',
            'uploaded_by' => (string) optional($request->user())->email,
        ]);

        $row->update(['document_id' => $naming->buildDocumentId((int) $row->id)]);
        $taskAutomation->ensureStudentDocumentTask($row->fresh());
        if ((string) ($row->status ?? '') === 'approved') {
            $taskAutomation->markTasksDoneBySource('student_document_uploaded', (string) $row->id);
            $taskAutomation->markTasksDoneBySource('guest_document_uploaded', (string) $row->id);
        }

        return response()->json($row->fresh()->load('category'), Response::HTTP_CREATED);
    }

    public function approve(Request $request, Document $document, TaskAutomationService $taskAutomation)
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $document->update([
            'status' => 'approved',
            'approved_by' => (string) optional($request->user())->email ?: 'system',
            'approved_at' => now(),
            'review_note' => trim((string) ($data['review_note'] ?? '')) ?: null,
        ]);

        $taskAutomation->markTasksDoneBySource('student_document_uploaded', (string) $document->id);
        $taskAutomation->markTasksDoneBySource('guest_document_uploaded', (string) $document->id);

        return response()->json($document->fresh()->load('category'));
    }

    public function reject(Request $request, Document $document)
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $reviewNote = trim((string) ($data['review_note'] ?? ''));

        $document->update([
            'status'      => 'rejected',
            'approved_by' => (string) optional($request->user())->email ?: 'system',
            'approved_at' => now(),
            'review_note' => $reviewNote ?: null,
        ]);

        $this->dispatchRejectionNotification(
            $document,
            $reviewNote,
            optional($request->user())->email ?: 'system'
        );

        return response()->json($document->fresh()->load('category'));
    }

    /**
     * student_id'den kişi adını çöz (Guest veya StudentAssignment).
     * @return array{0: string, 1: string} [firstName, lastName]
     */
    private function resolvePersonName(string $studentId): array
    {
        if (str_starts_with($studentId, 'GST-')) {
            $numericId = (int) ltrim(substr($studentId, 4), '0');
            $guest = \App\Models\GuestApplication::find($numericId);
            return [(string) ($guest->first_name ?? ''), (string) ($guest->last_name ?? '')];
        }

        $assignment = \App\Models\StudentAssignment::where('student_id', $studentId)->first();
        return [(string) ($assignment->first_name ?? ''), (string) ($assignment->last_name ?? '')];
    }

    /**
     * Belge reddedildiğinde ilgili student/guest kullanıcısına in_app bildirim gönder.
     */
    private function dispatchRejectionNotification(Document $document, string $reviewNote, string $triggeredBy): void
    {
        $studentId = (string) $document->student_id;
        $userId    = null;

        if (str_starts_with($studentId, 'GST-')) {
            $numericId = ltrim(substr($studentId, 4), '0') ?: '0';
            $guest     = \App\Models\GuestApplication::find((int) $numericId);
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
}
