<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentUploadToken;
use App\Models\GuestApplication;
use App\Rules\ValidFileMagicBytes;
use App\Services\DocumentNamingService;
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

        return view('public.document-upload.form', [
            'token'      => $row,
            'guestName'  => $this->resolveDisplayName($row),
            'docName'    => $row->category_name ?? $row->category_code,
            'docNameDe'  => $row->document_name_de,
            'message'    => $row->custom_message,
            'expiresAt'  => $row->expires_at,
            'expiresIn'  => $row->expires_at?->diffForHumans(null, ['parts' => 2]),
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
        ]);

        $category = DocumentCategory::query()->where('code', $row->category_code)->first();
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

        $doc = Document::query()->create([
            'student_id'         => $ownerId,
            'category_id'        => $category->id,
            'process_tags'       => ['public_upload', 'doc_request_token', 'target:' . $row->target_type],
            'original_file_name' => (string) $file->getClientOriginalName(),
            'standard_file_name' => $stdName,
            'storage_path'       => $stored,
            'mime_type'          => (string) ($file->getMimeType() ?: ''),
            'status'             => 'uploaded',
            'uploaded_by'        => 'public:token:' . substr($row->token, 0, 8),
        ]);
        $doc->forceFill([
            'document_id' => 'DOC-PUB-' . str_pad((string) $doc->id, 6, '0', STR_PAD_LEFT),
            'company_id'  => $row->company_id,
        ])->save();

        $row->markUsed(
            ip: $request->ip(),
            ua: (string) $request->userAgent(),
            documentId: $doc->id,
        );

        Log::info('public.document-upload: success', [
            'token_id'    => $row->id,
            'document_id' => $doc->id,
            'target_type' => $row->target_type,
            'target_id'   => $row->target_id,
            'category'    => $row->category_code,
            'ip'          => $request->ip(),
        ]);

        return view('public.document-upload.success', [
            'token'    => $row,
            'docName'  => $row->category_name ?? $row->category_code,
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
