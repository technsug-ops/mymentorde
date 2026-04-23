<?php

namespace App\Console\Commands;

use App\Models\AuditTrail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * audit_trails tablosunda 90+ gün eski kayıtları JSON.gz olarak dump edip
 * DB'den siler. GDPR 3 yıl retention için yeterli + OLTP tablosu şişmez.
 *
 * Kullanım:
 *   php artisan archive:audit-trails              # 90 gün varsayılan, dry-run değil
 *   php artisan archive:audit-trails --days=180   # 180 gün eski
 *   php artisan archive:audit-trails --dry-run    # kaç satır arşivlenecek göster
 *   php artisan archive:audit-trails --chunk=500  # chunk boyutu
 *
 * Çıktı:
 *   storage/app/backups/audit-trails/YYYY-MM-DD.jsonl.gz
 *
 * Safety:
 *   - Önce dosya başarıyla yazılır, sonra delete (atomic)
 *   - Chunk'lar halinde işlenir (memory friendly)
 *   - withoutOverlapping scheduled — yarım işlem riski yok
 */
class ArchiveAuditTrailsCommand extends Command
{
    protected $signature = 'archive:audit-trails
                            {--days=90 : Bu kadar günden eski kayıtlar arşivlenir}
                            {--chunk=1000 : Chunk boyutu}
                            {--dry-run : Sadece raporla, silme}';

    protected $description = 'audit_trails tablosunda N gün eski kayıtları jsonl.gz olarak arşivler + DB\'den siler';

    public function handle(): int
    {
        $days    = (int) $this->option('days');
        $chunk   = (int) $this->option('chunk');
        $dryRun  = (bool) $this->option('dry-run');
        $cutoff  = now()->subDays($days);

        $total = AuditTrail::where('created_at', '<', $cutoff)->count();

        if ($total === 0) {
            $this->info("Arşivlenecek kayıt yok ({$days} günden eski kayıt bulunamadı).");
            return self::SUCCESS;
        }

        $this->line("Cutoff: {$cutoff->toDateTimeString()}");
        $this->line("Arşivlenecek satır: {$total}");

        if ($dryRun) {
            $this->warn('DRY RUN — hiçbir değişiklik yapılmadı.');
            return self::SUCCESS;
        }

        // Dosya yolu
        $dir = 'backups/audit-trails';
        Storage::disk('local')->makeDirectory($dir);
        $filename = $dir . '/' . now()->format('Y-m-d_His') . "_cutoff-{$cutoff->format('Y-m-d')}.jsonl.gz";
        $absolutePath = Storage::disk('local')->path($filename);

        $gzHandle = gzopen($absolutePath, 'w9');
        if ($gzHandle === false) {
            $this->error('Gzip dosyası açılamadı: ' . $absolutePath);
            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $archivedIds = [];
        $written = 0;

        try {
            AuditTrail::where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById($chunk, function ($rows) use (&$archivedIds, &$written, $gzHandle, $bar) {
                    foreach ($rows as $row) {
                        $json = json_encode($row->toArray(), JSON_UNESCAPED_UNICODE) . "\n";
                        gzwrite($gzHandle, $json);
                        $archivedIds[] = $row->id;
                        $written++;
                        $bar->advance();
                    }
                });
        } catch (\Throwable $e) {
            gzclose($gzHandle);
            @unlink($absolutePath);
            $this->error('Arşivleme başarısız: ' . $e->getMessage());
            return self::FAILURE;
        }

        gzclose($gzHandle);
        $bar->finish();
        $this->newLine();

        $fileSize = filesize($absolutePath);
        $this->info("Dosya yazıldı: {$filename} (" . $this->humanSize($fileSize) . ")");
        $this->info("Satır sayısı: {$written}");

        // DB'den sil — dosya başarıyla yazıldıktan sonra
        if (!empty($archivedIds)) {
            $deleted = 0;
            foreach (array_chunk($archivedIds, 1000) as $idBatch) {
                $deleted += AuditTrail::whereIn('id', $idBatch)->delete();
            }
            $this->info("DB'den silindi: {$deleted} satır");
        }

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
