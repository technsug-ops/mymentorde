<?php

namespace App\Http\Controllers\Guest\Concerns;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GuestApplication;
use App\Models\GuestRequiredDocument;
use App\Rules\ValidFileMagicBytes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait GuestDocumentTrait
{
    public function uploadDocument(Request $request)
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            Log::warning('guest.upload: resolveGuest returned null', [
                'user_id'           => (int) ($request->user()?->id ?? 0),
                'user_email'        => (string) ($request->user()?->email ?? ''),
                'user_company_id'   => (int) ($request->user()?->company_id ?? 0),
                'current_company_id'=> app()->bound('current_company_id') ? (int) app('current_company_id') : null,
                'category_code'     => (string) $request->input('category_code', ''),
            ]);
            abort(404, 'Guest kaydi bulunamadi.');
        }

        $data = $request->validate([
            'category_code' => ['required', 'string', 'max:64'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,webp', 'max:10240', new ValidFileMagicBytes()],
        ]);

        $category = DocumentCategory::query()->where('code', $data['category_code'])->first();
        if (!$category) {
            Log::warning('guest.upload: DocumentCategory not found', [
                'category_code' => (string) $data['category_code'],
                'guest_id'      => (int) $guest->id,
            ]);
            abort(404, 'Belge kategorisi bulunamadi: ' . $data['category_code']);
        }
        $file = $request->file('file');
        $ownerId = $this->resolveDocumentOwnerId($guest);
        $allowedDocExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'webp'];
        $rawExt = strtolower((string) $file->getClientOriginalExtension());
        $ext = in_array($rawExt, $allowedDocExts, true) ? $rawExt : 'bin';
        $stdName = app(\App\Services\DocumentNamingService::class)->buildStandardFileName(
            $ownerId,
            $category->code,
            (string) ($guest->first_name ?? ''),
            (string) ($guest->last_name ?? ''),
            $ext,
        );
        $stored = $file->storeAs("guest-documents/{$guest->id}", $stdName, 'local');

        $row = Document::query()->create([
            'student_id' => $ownerId,
            'category_id' => $category->id,
            'process_tags' => ['guest_registration'],
            'original_file_name' => (string) $file->getClientOriginalName(),
            'standard_file_name' => $stdName,
            'storage_path' => $stored,
            'mime_type' => (string) ($file->getMimeType() ?: ''),
            'status' => 'uploaded',
            'uploaded_by' => (string) optional($request->user())->email,
        ]);

        $row->forceFill(['document_id' => 'DOC-GUEST-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT)])->save();

        $docsReady = $this->computeDocsReady($guest, $ownerId);
        $guest->forceFill([
            'docs_ready'     => $docsReady,
            'status_message' => $docsReady
                ? 'Belgeler tamamlandı. İnceleme bekleniyor.'
                : 'Belge yuklendi. Zorunlu checklist tamamlanmadi.',
        ])->save();
        $this->taskAutomationService->ensureGuestDocumentTask($guest, $row);
        $this->eventLogService->log(
            eventType: 'guest_document_uploaded',
            entityType: 'document',
            entityId: (string) $row->id,
            message: "Guest #{$guest->id} belge yukledi.",
            meta: ['document_id' => $row->document_id, 'category_code' => (string) $category->code],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->queueTemplateNotification(
            guest: $guest,
            category: 'guest_document_update',
            sourceType: 'guest_document_uploaded',
            sourceId: (string) $row->id,
            vars: [
                'document_id' => (string) $row->document_id,
                'document_code' => (string) $category->code,
            ]
        );

        // Timeline: tüm zorunlu belgeler tamamlandığında milestone'u işaretle
        if ($docsReady) {
            app(\App\Services\GuestTimelineService::class)->complete($guest, 'docs_upload');
        }

        if ($docsReady) {
            return redirect()->route('guest.registration.documents')->with('docs_complete', true);
        }

        return redirect()->route('guest.registration.documents')->with('status', 'Belge yuklendi.');
    }

    public function deleteDocument(Request $request, Document $document)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');
        $ownerId = $this->resolveDocumentOwnerId($guest);
        abort_if((string) $document->student_id !== $ownerId, 403, 'Bu belge size ait degil.');

        $path = trim((string) ($document->storage_path ?? ''));
        if ($path !== '' && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $document->delete();

        $docsReady = $this->computeDocsReady($guest, $ownerId);
        $guest->forceFill([
            'docs_ready'     => $docsReady,
            'status_message' => $docsReady ? 'Belge silindi, checklist tamam.' : 'Belge silindi, checklist eksik.',
        ])->save();

        return redirect()->route('guest.registration.documents')->with('status', 'Belge silindi.');
    }

    public function previewDocument(Request $request, Document $document): \Illuminate\Http\JsonResponse
    {
        $mime = strtolower((string) ($document->mime_type ?? ''));
        $previewable = str_starts_with($mime, 'image/') || $mime === 'application/pdf';
        abort_if(!$previewable, 422, 'Bu dosya tipi önizlenemez.');

        $path = (string) ($document->storage_path ?? '');
        abort_if($path === '', 404);
        abort_if(!Storage::disk('local')->exists($path), 404, 'Dosya bulunamadı.');

        return response()->json([
            'url'  => route('guest.registration.documents.serve', $document->id),
            'mime' => $mime,
            'name' => $document->title ?? $document->document_code ?? 'Belge',
        ]);
    }

    public function serveDocument(Request $request, Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 403);
        $ownerId = $this->resolveDocumentOwnerId($guest);
        abort_if((string) $document->student_id !== $ownerId, 403, 'Bu belge size ait değil.');

        $path = trim((string) ($document->storage_path ?? ''));
        abort_if($path === '', 404);
        abort_if(!Storage::disk('local')->exists($path), 404, 'Dosya bulunamadı.');

        return Storage::disk('local')->response($path, $document->standard_file_name ?? basename($path));
    }

    private function resolveDocumentOwnerId(GuestApplication $guest): string
    {
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '') {
            return $studentId;
        }
        return 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }

    private function computeDocsReady(GuestApplication $guest, string $ownerId): bool
    {
        $applicationType = trim((string) ($guest->application_type ?? ''));
        if ($applicationType === '') {
            return false;
        }

        $requiredCodes = GuestRequiredDocument::query()
            ->where('application_type', $applicationType)
            ->where('is_active', true)
            ->where('is_required', true)
            ->pluck('category_code')
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->values();

        if ($requiredCodes->isEmpty()) {
            return false;
        }

        $uploadedCodes = Document::query()
            ->where('student_id', $ownerId)
            ->whereIn('status', ['uploaded', 'approved'])
            ->with('category:id,code')
            ->get()
            ->map(fn (Document $d) => strtoupper(trim((string) ($d->category->code ?? ''))))
            ->filter()
            ->unique()
            ->values();

        return $requiredCodes->diff($uploadedCodes)->isEmpty();
    }
}
