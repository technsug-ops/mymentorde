<?php

namespace App\Console\Commands;

use App\Models\Marketing\CmsContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mevcut cms_contents satırlarına kategori bazlı sıralı content_code ata
 * (UNI-001, BLOG-014, vb.). ID sırasına göre — eski blog daha küçük numara.
 *
 * - Zaten content_code'u olan satırlara dokunulmaz (idempotent).
 * - Kategori prefix'i CmsContent::CATEGORY_CODE_PREFIXES'tan gelir.
 *
 * Örnek:
 *   php artisan cms:assign-content-codes
 *   php artisan cms:assign-content-codes --dry-run
 *   php artisan cms:assign-content-codes --force  # mevcut kodları sil + yeniden ata
 */
class AssignContentCodes extends Command
{
    protected $signature = 'cms:assign-content-codes
                            {--dry-run : DB değişikliği yapma, sadece neyi atayacağını göster}
                            {--force : Zaten content_code\'u olan satırların kodunu sil + yeniden ata}';

    protected $description = 'Mevcut blog\'lara kategori bazlı sıralı content_code (UNI-001, BLOG-014, vb.) ata';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force  = (bool) $this->option('force');

        if ($force && !$dryRun) {
            $cleared = DB::table('cms_contents')->update(['content_code' => null]);
            $this->warn("--force aktif: {$cleared} satırın content_code\'u sıfırlandı.");
        }

        $rows = CmsContent::query()
            ->when(!$force, fn ($q) => $q->whereNull('content_code'))
            ->orderBy('id')
            ->get(['id', 'category', 'title_tr', 'content_code']);

        if ($rows->isEmpty()) {
            $this->info('İşlenecek satır yok. (--force ile yeniden atayabilirsin.)');
            return self::SUCCESS;
        }

        $this->info('İşlenecek satır sayısı: ' . $rows->count());

        // Kategori bazlı counter — id sırasına göre artar
        $counters = [];
        $assigned = 0;
        foreach ($rows as $row) {
            $prefix = CmsContent::CATEGORY_CODE_PREFIXES[$row->category ?? ''] ?? 'GEN';
            $counters[$prefix] = ($counters[$prefix] ?? 0) + 1;
            $code = $prefix . '-' . str_pad((string) $counters[$prefix], 3, '0', STR_PAD_LEFT);

            $this->line('#' . str_pad((string) $row->id, 4) . ' [' . str_pad((string) ($row->category ?? '-'), 16) . '] → ' . $code . ' — ' . $row->title_tr);

            if (!$dryRun) {
                DB::table('cms_contents')->where('id', $row->id)->update(['content_code' => $code]);
            }
            $assigned++;
        }

        $this->newLine();
        $this->info("Tamamlandı: {$assigned} satıra kod atandı.");
        if ($dryRun) $this->warn('--dry-run aktif, DB değişmedi.');

        // Kategori özet
        $this->newLine();
        $this->info('Kategori dağılımı:');
        foreach ($counters as $prefix => $count) {
            $this->line("  {$prefix} → {$count} kayıt (en son: {$prefix}-" . str_pad((string) $count, 3, '0', STR_PAD_LEFT) . ')');
        }

        return self::SUCCESS;
    }
}
