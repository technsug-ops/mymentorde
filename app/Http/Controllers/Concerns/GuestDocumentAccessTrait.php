<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use App\Models\GuestApplication;
use App\Services\DocumentNamingService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Guest belge erişimi: önizleme (serve), tekli indirme, toplu ZIP.
 * Senior ve Manager portalları tarafından ortak kullanılır.
 */
trait GuestDocumentAccessTrait
{
    private function resolveGuestDocOwnerId(GuestApplication $guest): string
    {
        $sid = trim((string) ($guest->converted_student_id ?? ''));
        return $sid !== '' ? $sid : 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * standard_file_name yoksa DocumentNamingService kuralına göre üret.
     * Format: {CATEGORY}_{OWNER}_{DATE}_{Ad_ilk_harf}_{Soyad}.{ext}
     */
    private function resolveStandardName(Document $doc, ?GuestApplication $guest = null): string
    {
        $std = trim((string) ($doc->standard_file_name ?? ''));
        if ($std !== '') {
            return $std;
        }

        $categoryCode = trim((string) ($doc->category->code ?? 'DOC'));
        $studentId = trim((string) ($doc->student_id ?? 'STD'));
        $ext = pathinfo((string) ($doc->original_file_name ?? ''), PATHINFO_EXTENSION) ?: 'pdf';

        $firstName = '';
        $lastName = '';
        if ($guest) {
            $firstName = (string) ($guest->first_name ?? '');
            $lastName = (string) ($guest->last_name ?? '');
        }

        return app(DocumentNamingService::class)->buildStandardFileName($studentId, $categoryCode, $firstName, $lastName, $ext);
    }

    private function guestDocuments(GuestApplication $guest)
    {
        return Document::where('student_id', $this->resolveGuestDocOwnerId($guest))
            ->with('category')
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Serve file inline (PDF / image preview).
     */
    public function guestDocumentServe(GuestApplication $guest, Document $document): StreamedResponse
    {
        $this->authorizeGuestAccess($guest);
        $ownerId = $this->resolveGuestDocOwnerId($guest);
        abort_if((string) $document->student_id !== $ownerId, 403, 'Bu belge bu başvuruya ait değil.');

        $path = trim((string) ($document->storage_path ?? ''));
        abort_if($path === '', 404, 'Dosya yolu bulunamadı.');
        abort_if(!Storage::disk('local')->exists($path), 404, 'Dosya bulunamadı.');

        $mime = strtolower((string) ($document->mime_type ?? ''));
        $previewable = str_starts_with($mime, 'image/') || $mime === 'application/pdf';
        abort_if(!$previewable, 422, 'Bu dosya tipi önizlenemez.');

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * Download single file with standard_file_name.
     */
    public function guestDocumentDownload(GuestApplication $guest, Document $document): StreamedResponse
    {
        $this->authorizeGuestAccess($guest);
        $ownerId = $this->resolveGuestDocOwnerId($guest);
        abort_if((string) $document->student_id !== $ownerId, 403, 'Bu belge bu başvuruya ait değil.');

        $path = trim((string) ($document->storage_path ?? ''));
        abort_if($path === '', 404, 'Dosya yolu bulunamadı.');
        abort_if(!Storage::disk('local')->exists($path), 404, 'Dosya bulunamadı.');

        return Storage::disk('local')->download($path, $this->resolveStandardName($document, $guest));
    }

    /**
     * Download all guest documents as ZIP.
     */
    public function guestDocumentsZip(GuestApplication $guest): StreamedResponse
    {
        $this->authorizeGuestAccess($guest);
        $documents = $this->guestDocuments($guest);
        abort_if($documents->isEmpty(), 404, 'İndirilecek belge bulunamadı.');

        $ownerId = $this->resolveGuestDocOwnerId($guest);
        $zipName = $ownerId . '_belgeler_' . now()->format('Ymd_His') . '.zip';
        $tmpPath = storage_path('app/temp/' . $zipName);

        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        $zip = new ZipArchive();
        abort_if($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true, 500, 'ZIP oluşturulamadı.');

        $usedNames = [];
        foreach ($documents as $doc) {
            $storagePath = trim((string) ($doc->storage_path ?? ''));
            if ($storagePath === '' || !Storage::disk('local')->exists($storagePath)) {
                continue;
            }

            $entryName = $this->resolveStandardName($doc, $guest);

            // Aynı isimli dosya varsa sayaç ekle
            if (isset($usedNames[$entryName])) {
                $usedNames[$entryName]++;
                $ext = pathinfo($entryName, PATHINFO_EXTENSION);
                $base = pathinfo($entryName, PATHINFO_FILENAME);
                $entryName = $base . '_' . $usedNames[$entryName] . '.' . $ext;
            } else {
                $usedNames[$entryName] = 1;
            }

            $zip->addFile(Storage::disk('local')->path($storagePath), $entryName);
        }

        $zip->close();

        return response()->streamDownload(function () use ($tmpPath) {
            readfile($tmpPath);
            @unlink($tmpPath);
        }, $zipName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Company-scoped guest erişim kontrolü.
     * Her controller kendi mantığına göre override edebilir.
     */
    abstract protected function authorizeGuestAccess(GuestApplication $guest): void;
}
