<?php

namespace App\Http\Controllers;

use App\Jobs\ExtractDocumentDataJob;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentUploadToken;
use App\Models\GuestApplication;
use App\Rules\ValidFileMagicBytes;
use App\Services\DocumentNamingService;
use App\Services\DocumentOcrSchemas;
use App\Support\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public belge yükleme — premium "Belge Talep Linki" özelliği.
 *
 * Login gerektirmez; token URL parametresi yetki kanıtı olarak kullanılır.
 * Token tek-kullanımlık ve süreli. Yüklenen dosya target_type'a göre uygun
 * koleksiyona eklenir (guest/student/user/dealer/...).
 *
 * Polymorphic — yeni modül eklerken Token sabitlerine ekleyip burada
 * resolveOwner()'e bir case ekle, gerisi otomatik çalışır.
 */
class PublicDocumentUploadController extends Controller
{
    public function show(Request $request, string $token)
    {
        $row = $this->loadAndScope($token);

        if (!$row->isUsable()) {
            return view('public.document-upload.expired', [
                'token'     => $row,
                'isExpired' => $row->isExpired(),
                'isUsed'    => $row->isExhausted(),
            ]);
        }

        // D5: Multi-doc mode için kalan kategorileri çöz
        $remainingCategories = [];
        if ($row->isMultiCategory()) {
            $remaining = $row->remainingCategoryCodes();
            if (!empty($remaining)) {
                $remainingCategories = DocumentCategory::query()
                    ->whereIn('code', $remaining)
                    ->get(['code', 'name_tr', 'name_de'])
                    ->all();
            }
        }

        return view('public.document-upload.form', [
            'token'               => $row,
            'guestName'           => $this->resolveDisplayName($row),
            'docName'             => $row->category_name ?? $row->category_code,
            'docNameDe'           => $row->document_name_de,
            'message'             => $row->custom_message,
            'expiresAt'           => $row->expires_at,
            'expiresIn'           => $row->expires_at?->diffForHumans(null, ['parts' => 2]),
            'isMulti'             => $row->isMultiCategory(),
            'remainingCategories' => $remainingCategories,
            'uploadedCount'       => (int) $row->used_count,
            'totalCount'          => (int) $row->max_uses,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $row = $this->loadAndScope($token);

        if (!$row->isUsable()) {
            return back()->withErrors(['file' => 'Link süresi dolmuş veya zaten kullanılmış.']);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240', new ValidFileMagicBytes()],
            // D5: Multi-doc'da kullanıcı hangi kategori için yüklediğini belirtir
            'category_code' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        // D5: Multi-doc mode'da category_code formdan gelir, single mode'da token'dan
        $useCategoryCode = $row->category_code;
        if ($row->isMultiCategory()) {
            $useCategoryCode = (string) $request->input('category_code', '');
            $remaining = $row->remainingCategoryCodes();
            if ($useCategoryCode === '' || !in_array($useCategoryCode, $remaining, true)) {
                return back()->withErrors([
                    'category_code' => 'Geçersiz kategori seçimi. Lütfen kalan listeden bir belge türü seçin.',
                ]);
            }
        }

        $category = DocumentCategory::query()->where('code', $useCategoryCode)->first();
        if (!$category) {
            abort(404, 'Belge kategorisi bulunamadı.');
        }

        // Polymorphic resolution — Token modeli storage path ve owner ID'yi
        // target_type'a göre üretir. Yeni modül = Token'a sabit ekle, gerisi otomatik.
        $ownerId    = $row->resolveDocumentOwnerId();
        $storageDir = $row->resolveStorageDir();
        [$firstName, $lastName] = $this->resolveNameParts($row);

        if ($ownerId === '') {
            abort(404, 'Token sahibi çözümlenemedi.');
        }

        $file = $request->file('file');
        $rawExt = strtolower((string) $file->getClientOriginalExtension());
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = in_array($rawExt, $allowed, true) ? $rawExt : 'jpg';

        $stdName = app(DocumentNamingService::class)->buildStandardFileName(
            $ownerId,
            $category->code,
            $firstName,
            $lastName,
            $ext,
        );
        $stored = $file->storeAs($storageDir, $stdName, 'local');

        // OCR uygunluk kontrolü — image/PDF + desteklenen kategori
        $schemas = app(DocumentOcrSchemas::class);
        $ocrSupported = $schemas->isSupported($category->code)
            && in_array(strtolower((string) ($file->getMimeType() ?: '')), [
                'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf',
            ], true);

        $processTags = ['public_upload', 'doc_request_token', 'target:' . $row->target_type];
        if ($ocrSupported) {
            $processTags[] = 'ocr_queued';
        } else {
            $processTags[] = 'ocr_skipped';
        }

        $doc = Document::query()->create([
            'student_id'         => $ownerId,
            'category_id'        => $category->id,
            'process_tags'       => $processTags,
            'original_file_name' => (string) $file->getClientOriginalName(),
            'standard_file_name' => $stdName,
            'storage_path'       => $stored,
            'mime_type'          => (string) ($file->getMimeType() ?: ''),
            'status'             => 'uploaded',
            'uploaded_by'        => 'public:token:' . substr($row->token, 0, 8),
            'extraction_status'  => $ocrSupported ? 'pending' : null,
        ]);
        $doc->forceFill([
            'document_id' => 'DOC-PUB-' . str_pad((string) $doc->id, 6, '0', STR_PAD_LEFT),
            'company_id'  => $row->company_id,
        ])->save();

        // OCR uygunsa async extract'a yolla — yükleme cevabını bloklamasın
        if ($ocrSupported) {
            try {
                ExtractDocumentDataJob::dispatch($doc->id);
            } catch (\Throwable $e) {
                // Queue down olsa bile yükleme başarısız sayılmasın
                Log::warning('public.document-upload: OCR dispatch failed (non-fatal)', [
                    'document_id' => $doc->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $row->markUsed(
            ip: $request->ip(),
            ua: (string) $request->userAgent(),
            documentId: $doc->id,
            categoryCode: $useCategoryCode, // D5: multi-doc'da uploaded listesini artırır
        );
        $row->refresh();

        Log::info('public.document-upload: success', [
            'token_id'    => $row->id,
            'document_id' => $doc->id,
            'target_type' => $row->target_type,
            'target_id'   => $row->target_id,
            'category'    => $useCategoryCode,
            'is_multi'    => $row->isMultiCategory(),
            'remaining'   => $row->isMultiCategory() ? count($row->remainingCategoryCodes()) : 0,
            'ip'          => $request->ip(),
        ]);

        // D5: Multi-doc + hala kalan kategori varsa kullanıcıyı yine form'a yönlendir
        if ($row->isMultiCategory() && $row->isUsable() && !empty($row->remainingCategoryCodes())) {
            return redirect()->route('public.document-upload.show', ['token' => $row->token])
                ->with('success', $category->name_tr . ' başarıyla yüklendi. Lütfen kalan belgeleri de yükleyin.');
        }

        return view('public.document-upload.success', [
            'token'    => $row,
            'docName'  => $category->name_tr ?? $useCategoryCode,
        ]);
    }

    /**
     * Token'ı yükle ve company scope'unu manuel ayarla (login yok).
     */
    private function loadAndScope(string $token): DocumentUploadToken
    {
        $row = DocumentUploadToken::query()
            ->withoutGlobalScope('company')
            ->where('token', $token)
            ->first();

        if (!$row) {
            abort(404, 'Geçersiz link.');
        }

        app()->instance('current_company_id', (int) $row->company_id);

        ModuleAccess::assertEnabled('doc_request', (int) $row->company_id);

        return $row;
    }

    /**
     * Görüntülenecek ad — guest için first_name lookup, diğerleri için
     * token'ın display_name field'ı.
     */
    private function resolveDisplayName(DocumentUploadToken $token): string
    {
        // Guest için legacy lookup (target_display_name dolu değilse first_name'den çek)
        if ($token->isForGuest() && empty($token->target_display_name)) {
            $guest = GuestApplication::query()
                ->withoutGlobalScope('company')
                ->find($token->target_id);
            return trim((string) ($guest->first_name ?? '')) ?: 'Aday Öğrenci';
        }

        $name = trim((string) $token->target_display_name);
        if ($name === '') {
            return DocumentUploadToken::TARGET_TYPES[$token->target_type] ?? 'Kullanıcı';
        }
        return $this->splitName($name)[0] ?: $name;
    }

    /**
     * @return array{0:string,1:string} [first, last]
     */
    private function resolveNameParts(DocumentUploadToken $token): array
    {
        // Guest için legacy: first_name + last_name lookup
        if ($token->isForGuest() && empty($token->target_display_name)) {
            $guest = GuestApplication::query()
                ->withoutGlobalScope('company')
                ->find($token->target_id);
            if ($guest) {
                return [(string) ($guest->first_name ?? ''), (string) ($guest->last_name ?? '')];
            }
        }
        return $this->splitName($token->target_display_name);
    }

    /**
     * "Ad Soyad" string'ini [first, last] olarak ayır.
     * @return array{0:string,1:string}
     */
    private function splitName(?string $fullName): array
    {
        $name = trim((string) $fullName);
        if ($name === '') return ['', ''];
        $parts = preg_split('/\s+/', $name) ?: [];
        $first = (string) array_shift($parts);
        $last  = trim(implode(' ', $parts));
        return [$first, $last];
    }
}
