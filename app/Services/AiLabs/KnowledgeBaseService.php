<?php

namespace App\Services\AiLabs;

use App\Models\KnowledgeSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Bilgi havuzu orchestration — kaynakları Gemini File API ile senkronize eder
 * ve AI asistan için context üretir.
 *
 * Davranış:
 *   - PDF kaynakları: Gemini File API'ye yüklenir (file_data reference)
 *   - URL/text kaynakları: inline sistem prompt'a gömülür
 *   - Pasifleşen kaynak: Gemini'den silinir, referanslar temizlenir
 *   - content_hash değişti → eski dosya silinir, yeni upload
 */
class KnowledgeBaseService
{
    public function __construct(
        private GeminiProvider $gemini,
        private UrlContentFetcher $urlFetcher,
    ) {}

    /**
     * Aktif kaynakları (role göre filtrelenmiş) Gemini'ye senkronize et.
     *
     * @return array{synced:int, skipped:int, failed:int, errors:array<int,string>}
     */
    public function syncAllSources(int $companyId): array
    {
        $stats = ['synced' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        // PDF + görsel + URL kaynakları senkronize et
        $sources = KnowledgeSource::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('type', ['pdf', 'image', 'url'])
            ->get();

        foreach ($sources as $source) {
            try {
                $result = $this->syncSource($source);
                if ($result['ok'] ?? false) {
                    if ($result['skipped'] ?? false) {
                        $stats['skipped']++;
                    } else {
                        $stats['synced']++;
                    }
                } else {
                    $stats['failed']++;
                    $stats['errors'][] = "#{$source->id} {$source->title}: " . ($result['error'] ?? 'unknown');
                }
            } catch (\Throwable $e) {
                // Bir kaynak çökse bile diğerlerini işlemeye devam et
                $stats['failed']++;
                $stats['errors'][] = "#{$source->id} {$source->title}: exception - " . \Illuminate\Support\Str::limit($e->getMessage(), 120);
                \Illuminate\Support\Facades\Log::warning('AiLabs sync source failed', [
                    'source_id' => $source->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * URL kaynağının içeriğini fetch eder + content_markdown alanına yazar.
     *
     * @return array{ok:bool, bytes?:int, skipped?:bool, error?:string}
     */
    public function fetchUrlSource(KnowledgeSource $source, bool $force = false): array
    {
        if ($source->type !== 'url' || empty($source->url)) {
            return ['ok' => false, 'error' => 'not_a_url_source'];
        }

        // Zaten çekilmiş + force değilse skip (manuel refetch için force=true)
        if (!$force && !empty($source->content_markdown) && strlen($source->content_markdown) > 200) {
            return ['ok' => true, 'skipped' => true, 'bytes' => strlen($source->content_markdown)];
        }

        $result = $this->urlFetcher->fetch($source->url);
        if (!($result['ok'] ?? false)) {
            return ['ok' => false, 'error' => $result['error'] ?? 'fetch_failed'];
        }

        $content = (string) ($result['content'] ?? '');
        $source->update([
            'content_markdown' => $content,
            'content_hash'     => hash('sha256', $content),
        ]);

        return ['ok' => true, 'bytes' => strlen($content)];
    }

    /**
     * Tek kaynağı senkronize eder:
     *   - PDF / Image → Gemini File API'ye yükler
     *   - URL → içeriği fetch eder, content_markdown'a yazar (inline context'e girer)
     *   - text / document → hiçbir şey yapmaz (zaten inline)
     *
     * @return array{ok:bool, skipped?:bool, file_id?:string, bytes?:int, error?:string}
     */
    public function syncSource(KnowledgeSource $source): array
    {
        if ($source->type === 'url') {
            return $this->fetchUrlSource($source);
        }
        if (in_array($source->type, ['text', 'document'], true)) {
            return ['ok' => true, 'skipped' => true];
        }
        // type === 'pdf' veya 'image' — aşağıdan devam

        if (!$source->file_path) {
            return ['ok' => false, 'error' => 'no_file_path'];
        }

        // Hash değişmemişse ve zaten yüklüyse skip
        if ($source->gemini_file_id && $source->gemini_uploaded_at) {
            // Mevcut dosya hash'i ile karşılaştır
            $currentHash = $this->computeFileHash($source->file_path);
            if ($currentHash && $currentHash === $source->content_hash) {
                return ['ok' => true, 'skipped' => true, 'file_id' => $source->gemini_file_id];
            }
            // Hash değişmiş — önce eski dosyayı sil
            $this->gemini->deleteFile($source->gemini_file_id, $source->company_id);
        }

        $absolutePath = Storage::disk('local')->path($source->file_path);
        if (!is_file($absolutePath)) {
            return ['ok' => false, 'error' => 'file_missing_on_disk: ' . $source->file_path];
        }

        // MIME type dosya uzantısına göre
        $ext = strtolower(pathinfo($source->file_path, PATHINFO_EXTENSION));
        $mimeType = match ($ext) {
            'pdf'  => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        // display_name: Gemini ASCII bekler, UTF-8 güvenli kesim + fancy karakter temizliği
        $cleanTitle = preg_replace('/[^\x20-\x7E]/', '_', $source->title ?? '');
        $cleanTitle = mb_substr((string) $cleanTitle, 0, 40, 'UTF-8');
        $result = $this->gemini->uploadFile(
            $absolutePath,
            $mimeType,
            "kb_{$source->id}_" . $cleanTitle,
            $source->company_id
        );

        if (!($result['ok'] ?? false)) {
            Log::warning('AiLabs sync failed', ['source_id' => $source->id, 'error' => $result['error'] ?? null]);
            return $result;
        }

        $source->update([
            'gemini_file_id'     => $result['file_id'],
            'gemini_file_uri'    => $result['file_uri'],
            'gemini_uploaded_at' => now(),
            'content_hash'       => $this->computeFileHash($source->file_path) ?: $source->content_hash,
        ]);

        return ['ok' => true, 'file_id' => $result['file_id']];
    }

    /**
     * Kaynağı Gemini File API'den sil + DB referanslarını temizle.
     */
    public function desyncSource(KnowledgeSource $source): array
    {
        if (!$source->gemini_file_id) {
            return ['ok' => true, 'skipped' => true];
        }

        $result = $this->gemini->deleteFile($source->gemini_file_id, $source->company_id);

        $source->update([
            'gemini_file_id'     => null,
            'gemini_file_uri'    => null,
            'gemini_uploaded_at' => null,
        ]);

        return $result;
    }

    /**
     * AI asistan için rol-bazlı context hazırla.
     *
     * @return array{
     *   system_context:string,
     *   file_refs:array<int,array{file_uri:string, mime_type:string}>,
     *   source_ids:array<int,int>,
     *   source_count:int
     * }
     */
    public function prepareContext(int $companyId, string $role): array
    {
        $sources = KnowledgeSource::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereJsonContains('visible_to_roles', $role)
            ->orderBy('id')
            ->get();

        $fileRefs = [];
        $textBlocks = [];
        $sourceIds = [];

        foreach ($sources as $s) {
            $sourceIds[] = $s->id;

            if (in_array($s->type, ['pdf', 'image'], true) && $s->gemini_file_uri) {
                $ext = strtolower(pathinfo($s->file_path ?? '', PATHINFO_EXTENSION));
                $mimeType = match ($ext) {
                    'pdf'  => 'application/pdf',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    'webp' => 'image/webp',
                    default => $s->type === 'image' ? 'image/jpeg' : 'application/pdf',
                };
                $fileRefs[] = [
                    'file_uri'  => $s->gemini_file_uri,
                    'mime_type' => $mimeType,
                ];
            } elseif (in_array($s->type, ['text', 'document'], true) && $s->content_markdown) {
                $textBlocks[] = "### Kaynak #{$s->id} — {$s->title}\n" . $s->content_markdown;
            } elseif ($s->type === 'url' && $s->url) {
                $textBlocks[] = "### Kaynak #{$s->id} — {$s->title}\nReferans: {$s->url}"
                    . ($s->content_markdown ? "\n" . $s->content_markdown : '');
            }
        }

        $systemContext = '';
        if (!empty($textBlocks)) {
            $systemContext = "## Bilgi Havuzu (metin kaynakları)\n\n" . implode("\n\n---\n\n", $textBlocks);
        }

        return [
            'system_context' => $systemContext,
            'file_refs'      => $fileRefs,
            'source_ids'     => $sourceIds,
            'source_count'   => count($sourceIds),
        ];
    }

    /**
     * Belirli bir rol için görünür olan aktif kaynakların deterministik hash'i.
     * Cache key'in parçası olur — kaynak set değişince cache otomatik mismatched olur.
     */
    public function sourcesFingerprint(int $companyId, string $role): string
    {
        // Image ve document tipleri de dahil — tüm aktif kaynaklar
        // (zaten aşağıdaki query filtrelemez, content_hash + gemini_file_id + updated_at fingerprint)
        $rows = KnowledgeSource::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereJsonContains('visible_to_roles', $role)
            ->orderBy('id')
            ->get(['id', 'content_hash', 'gemini_file_id', 'updated_at']);

        $parts = $rows->map(fn ($s) => sprintf(
            '%d:%s:%s:%s',
            $s->id,
            $s->content_hash ?: '-',
            $s->gemini_file_id ?: '-',
            optional($s->updated_at)->timestamp ?: 0
        ))->implode('|');

        return hash('sha256', $parts ?: 'empty');
    }

    private function computeFileHash(string $relativePath): ?string
    {
        try {
            $absolute = Storage::disk('local')->path($relativePath);
            if (!is_file($absolute)) {
                return null;
            }
            return hash_file('sha256', $absolute);
        } catch (\Throwable) {
            return null;
        }
    }
}
