<?php

namespace App\Services\DigitalAsset;

use App\Models\DigitalAsset;
use App\Models\DigitalAssetFolder;
use App\Models\User;
use App\Services\DocumentNamingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalAssetService
{
    public function __construct(
        private readonly DigitalAssetThumbnailService $thumbnails,
        private readonly DocumentNamingService $naming,
    ) {
    }

    public function store(UploadedFile $file, ?int $folderId, User $user, array $meta = []): DigitalAsset
    {
        $maxBytes = (int) config('dam.max_size_bytes', 50 * 1024 * 1024);
        if ($file->getSize() > $maxBytes) {
            throw new RuntimeException('Dosya çok büyük. Maksimum: ' . round($maxBytes / 1024 / 1024) . ' MB');
        }

        $folder = null;
        if ($folderId) {
            // Sadece company scope içindeki klasör seçilebildiğinden emin ol
            $folder = DigitalAssetFolder::query()->findOrFail($folderId);
        }

        $companyId = (int) ($user->company_id ?? (app()->bound('current_company_id') ? app('current_company_id') : 0));

        // ── Şirket başına kota kontrolü ────────────────────────────────────
        $this->assertQuotaAvailable($companyId, (int) $file->getSize());

        $uuid       = (string) Str::uuid();
        $extension  = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $folderSlug = $folder?->slug ?: '_root_';
        $safeName   = $this->safeFilename(pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename   = $uuid . '_' . $safeName . '.' . $extension;
        $dir        = sprintf('digital-assets/%d/%s', $companyId, $folderSlug);

        // Diske yaz (private local disk)
        $stored = $file->storeAs($dir, $filename, 'local');
        if ($stored === false) {
            throw new RuntimeException('Dosya yüklenemedi.');
        }

        $mime     = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $category = $this->categorize($mime, $extension);

        // ── Görünen ad — her zaman standart şemaya çevrilir ───────────────
        // Kullanıcı hiçbir şey düşünmesin. İndirilince orijinal ad kullanılır
        // (download() metodu original_filename'i serve eder).
        $displayName = $this->buildStandardName(
            $folder?->slug,
            pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME),
            $extension
        );

        $asset = DigitalAsset::query()->create([
            'folder_id'         => $folderId,
            'uuid'              => $uuid,
            'name'              => $displayName,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $mime,
            'extension'         => $extension,
            'size_bytes'        => (int) $file->getSize(),
            'disk'              => 'local',
            'path'              => $stored,
            'category'          => $category,
            'tags'              => $meta['tags'] ?? null,
            'description'       => $meta['description'] ?? null,
            'metadata'          => $meta['metadata'] ?? null,
            'created_by'        => $user->id,
        ]);

        // ── Stable doc kodu (DocumentNamingService formatı) ──────────────
        $asset->doc_code = $this->naming->buildDocumentId((int) $asset->id);
        $asset->save();

        // Thumbnail (best-effort, sessiz başarısızlık)
        try {
            $thumbPath = $this->thumbnails->generate($asset);
            if ($thumbPath) {
                $asset->thumbnail_path = $thumbPath;
                $asset->save();
            }
        } catch (\Throwable $e) {
            Log::warning('DAM thumbnail generation failed', ['asset_id' => $asset->id, 'error' => $e->getMessage()]);
        }

        $this->forgetPopularTagsCache($companyId);

        return $asset;
    }

    /**
     * Harici link asset'i oluştur (Google Drive, YouTube, Dropbox, vb.)
     */
    public function storeLink(string $url, ?int $folderId, User $user, array $meta = []): DigitalAsset
    {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Geçerli bir URL girilmedi.');
        }
        if (!preg_match('#^https?://#i', $url)) {
            throw new RuntimeException('URL http:// veya https:// ile başlamalıdır.');
        }

        $folder = null;
        if ($folderId) {
            $folder = DigitalAssetFolder::query()->findOrFail($folderId);
        }

        $companyId = (int) ($user->company_id ?? (app()->bound('current_company_id') ? app('current_company_id') : 0));

        // Ad: kullanıcı verdiyse onu kullan, yoksa URL'den hostname/path
        $displayName = trim($meta['name'] ?? '');
        if ($displayName === '') {
            $displayName = $this->buildLinkDisplayName($url);
        }

        // Kategori: kullanıcı verdiyse onu, yoksa URL'den otomatik tahmin
        $category = !empty($meta['category'])
            ? (string) $meta['category']
            : $this->categorizeLink($url);

        $asset = DigitalAsset::query()->create([
            'folder_id'        => $folderId,
            'uuid'             => (string) Str::uuid(),
            'source_type'      => 'link',
            'external_url'     => $url,
            'name'             => $displayName,
            'original_filename'=> $displayName,
            'mime_type'        => null,
            'extension'        => null,
            'size_bytes'       => 0,
            'disk'             => null,
            'path'             => null,
            'category'         => $category,
            'tags'             => $meta['tags'] ?? null,
            'description'      => $meta['description'] ?? null,
            'created_by'       => $user->id,
        ]);

        $asset->doc_code = $this->naming->buildDocumentId((int) $asset->id);
        $asset->save();

        $this->forgetPopularTagsCache($companyId);

        return $asset;
    }

    private function buildLinkDisplayName(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: 'link';
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return $host;
        }
        $last = basename($path);
        return $host . ' / ' . mb_substr($last, 0, 60);
    }

    private function categorizeLink(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        // Video platformları
        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')
            || str_contains($host, 'vimeo.com') || str_contains($host, 'dailymotion.com')
            || str_contains($host, 'wistia.com')) {
            return 'video';
        }
        // Doküman platformları
        if (str_contains($host, 'docs.google.com') || str_contains($host, 'sheets.google.com')
            || str_contains($host, 'slides.google.com') || str_contains($host, 'notion.so')
            || str_contains($host, 'office.com') || str_contains($host, 'sharepoint.com')
            || str_contains($host, 'dropbox.com/scl') || str_ends_with($path, '.pdf')) {
            return 'document';
        }
        // Görsel
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/', $path)) {
            return 'image';
        }
        // Ses
        if (str_contains($host, 'soundcloud.com') || str_contains($host, 'spotify.com')
            || preg_match('/\.(mp3|wav|ogg|m4a)$/', $path)) {
            return 'audio';
        }
        return 'other';
    }

    public function update(DigitalAsset $asset, array $meta): DigitalAsset
    {
        $tagsChanged = array_key_exists('tags', $meta);

        $asset->fill(array_filter([
            'name'        => $meta['name']        ?? null,
            'description' => $meta['description'] ?? null,
            'tags'        => $meta['tags']        ?? null,
            'is_pinned'   => array_key_exists('is_pinned', $meta) ? (bool) $meta['is_pinned'] : null,
        ], fn ($v) => $v !== null));
        $asset->save();

        if ($tagsChanged) {
            $this->forgetPopularTagsCache((int) $asset->company_id);
        }
        return $asset;
    }

    public function delete(DigitalAsset $asset): void
    {
        // Soft delete model; fiziksel dosya garbage collector ile temizlenebilir (opsiyonel ileride)
        $companyId = (int) $asset->company_id;
        $asset->delete();
        $this->forgetPopularTagsCache($companyId);
    }

    private function forgetPopularTagsCache(int $companyId): void
    {
        Cache::forget("dam_popular_tags_c{$companyId}");
    }

    public function move(DigitalAsset $asset, ?int $newFolderId): DigitalAsset
    {
        if ($newFolderId) {
            DigitalAssetFolder::query()->findOrFail($newFolderId);
        }
        $asset->folder_id = $newFolderId;
        $asset->save();
        return $asset;
    }

    public function download(DigitalAsset $asset, User $user): StreamedResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        // Link asset → harici URL'ye yönlendir + sayaç artır
        if ($asset->source_type === 'link') {
            if (empty($asset->external_url)) {
                throw new RuntimeException('Link adresi tanımsız.');
            }
            $asset->increment('download_count');
            $asset->forceFill(['last_downloaded_at' => now()])->save();
            return redirect()->away($asset->external_url);
        }

        if (!Storage::disk($asset->disk)->exists($asset->path)) {
            throw new RuntimeException('Dosya sunucuda bulunamadı.');
        }

        $asset->increment('download_count');
        $asset->forceFill(['last_downloaded_at' => now()])->save();

        return Storage::disk($asset->disk)->download(
            $asset->path,
            $asset->original_filename ?: ($asset->name . '.' . $asset->extension)
        );
    }

    /**
     * Inline preview — sayaç artırmaz, browser'da göstermek için.
     * Sadece güvenli MIME'lar (image/*, application/pdf) inline döner.
     */
    public function preview(DigitalAsset $asset): \Symfony\Component\HttpFoundation\Response
    {
        if ($asset->source_type === 'link') {
            throw new RuntimeException('Link asset için preview yok.');
        }
        if (!Storage::disk($asset->disk)->exists($asset->path)) {
            throw new RuntimeException('Dosya sunucuda bulunamadı.');
        }

        $mime = (string) ($asset->mime_type ?: 'application/octet-stream');
        $safePrefixes = ['image/', 'application/pdf', 'text/plain'];
        $isSafe = false;
        foreach ($safePrefixes as $p) {
            if (str_starts_with($mime, $p)) { $isSafe = true; break; }
        }
        if (!$isSafe) {
            throw new RuntimeException('Bu dosya türü inline gösterilemez.');
        }

        return Storage::disk($asset->disk)->response($asset->path, $asset->name, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes((string) $asset->name) . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    /**
     * Kota kontrolü — şirket için izin verilen toplam DAM boyutunu aşarsa hata.
     */
    private function assertQuotaAvailable(int $companyId, int $incomingBytes): void
    {
        $quota = (int) config('dam.max_storage_per_company', 0);
        if ($quota <= 0) {
            return; // sınırsız
        }

        $used = (int) DigitalAsset::query()
            ->forCompany($companyId)
            ->sum('size_bytes');

        if (($used + $incomingBytes) > $quota) {
            $usedMb  = round($used / 1024 / 1024, 1);
            $quotaMb = round($quota / 1024 / 1024, 1);
            throw new RuntimeException("DAM kotanız doldu ({$usedMb} MB / {$quotaMb} MB). Önce bazı dosyaları silin.");
        }
    }

    private function safeFilename(string $name): string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            return 'dosya';
        }
        // 80 karakterle sınırla — disk adı çok uzun olmasın
        return mb_substr($slug, 0, 80);
    }

    /**
     * Standart görünen ad üretir — kullanıcı checkbox'ı işaretlediğinde.
     * Format: {klasor-slug}_{YYYYMMDD}_{orijinal-slug}.{ext}
     * Kök klasör için: kok_{YYYYMMDD}_{orijinal-slug}.{ext}
     */
    private function buildStandardName(?string $folderSlug, string $originalBase, string $extension): string
    {
        $folderPart   = $folderSlug ?: 'kok';
        $datePart     = now()->format('Ymd');
        $originalPart = $this->safeFilename($originalBase);
        return sprintf('%s_%s_%s.%s', $folderPart, $datePart, $originalPart, $extension);
    }

    private function categorize(string $mime, string $ext): string
    {
        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        if (in_array($mime, ['application/pdf', 'application/msword', 'text/plain', 'text/csv'], true)
            || str_contains($mime, 'officedocument')
            || in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx','pdf','txt','csv','rtf'], true)) {
            return 'document';
        }
        if (in_array($mime, ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'], true)
            || in_array($ext, ['zip','rar','7z','tar','gz'], true)) {
            return 'archive';
        }
        return 'other';
    }
}
