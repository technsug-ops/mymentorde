<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cms_media_library') || !Schema::hasTable('digital_assets')) {
            return;
        }

        $now = now();

        // Aktif company'leri çek
        $companies = Schema::hasTable('companies')
            ? DB::table('companies')->where('is_active', true)->pluck('id')
            : collect([null]);

        $imported = 0;
        $skipped  = 0;

        foreach ($companies as $companyId) {
            // ── 1. "Pazarlama Medyası" sistem klasörü (idempotent) ──────────
            $rootFolderId = DB::table('digital_asset_folders')
                ->where('company_id', $companyId)
                ->where('slug', 'pazarlama-medyasi')
                ->whereNull('parent_id')
                ->value('id');

            if (!$rootFolderId) {
                $rootFolderId = DB::table('digital_asset_folders')->insertGetId([
                    'company_id'  => $companyId,
                    'parent_id'   => null,
                    'name'        => 'Pazarlama Medyası',
                    'slug'        => 'pazarlama-medyasi',
                    'path'        => '/pazarlama-medyasi',
                    'depth'       => 0,
                    'description' => 'CMS Media Library içeriklerinin DAM içindeki kök klasörü.',
                    'icon'        => 'megaphone',
                    'is_system'   => true,
                    'created_by'  => null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }

            // ── 2. cms_media_library kayıtlarını import et ─────────────────
            // cms_media_library'nin company_id kolonu yok — tüm kayıtları her
            // company'ye duplicate etmek istemiyoruz, sadece ilk company'ye
            // (varsayılan) bağlayalım.
            if ($companyId !== null && $companies->first() !== $companyId) {
                continue;
            }

            $rows = DB::table('cms_media_library')->orderBy('id')->get();
            foreach ($rows as $row) {
                $exists = DB::table('digital_assets')
                    ->where('legacy_source', 'cms_media_library')
                    ->where('legacy_source_id', $row->id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $ext = strtolower(pathinfo($row->file_name, PATHINFO_EXTENSION) ?: 'bin');
                $cat = $this->categorizeFromMime((string) $row->mime_type, $ext);

                DB::table('digital_assets')->insert([
                    'company_id'         => $companyId,
                    'folder_id'          => $rootFolderId,
                    'uuid'               => (string) Str::uuid(),
                    'name'               => $row->file_name,
                    'original_filename'  => $row->file_name,
                    'mime_type'          => $row->mime_type,
                    'extension'          => $ext,
                    'size_bytes'         => (int) $row->file_size_bytes,
                    'disk'               => 'local',
                    'path'               => $row->file_url, // referans (file_url tutuluyor)
                    'thumbnail_path'     => $row->thumbnail_url,
                    'category'           => $cat,
                    'tags'               => $row->tags,      // zaten JSON
                    'description'        => $row->alt_text,
                    'metadata'           => json_encode([
                        'width'  => $row->width,
                        'height' => $row->height,
                    ]),
                    'download_count'     => 0,
                    'last_downloaded_at' => null,
                    'is_pinned'          => false,
                    'legacy_source'      => 'cms_media_library',
                    'legacy_source_id'   => $row->id,
                    'created_by'         => $row->uploaded_by,
                    'created_at'         => $row->created_at ?? $now,
                    'updated_at'         => $row->updated_at ?? $now,
                ]);
                $imported++;
            }
        }

        \Illuminate\Support\Facades\Log::info("DAM import — cms_media_library: {$imported} taşındı, {$skipped} mevcut.");
    }

    public function down(): void
    {
        if (Schema::hasTable('digital_assets')) {
            DB::table('digital_assets')->where('legacy_source', 'cms_media_library')->delete();
        }
        if (Schema::hasTable('digital_asset_folders')) {
            DB::table('digital_asset_folders')->where('slug', 'pazarlama-medyasi')->where('is_system', true)->delete();
        }
    }

    private function categorizeFromMime(string $mime, string $ext): string
    {
        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        if ($mime === 'application/pdf' || str_contains($mime, 'officedocument')
            || in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx','pdf','txt','csv'], true)) {
            return 'document';
        }
        if (in_array($ext, ['zip','rar','7z','tar','gz'], true)) return 'archive';
        return 'other';
    }
};
