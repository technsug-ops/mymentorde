<?php

namespace App\Console\Commands;

use App\Models\University;
use Illuminate\Console\Command;

/**
 * HRK Rektorenliste PDF'ten çıkarılan uni → domain mapping kullanılarak
 * University.image_path'i Clearbit Logo API URL'i ile doldurur.
 *
 * Önkoşul: scripts/parse-rektorenliste.php çalıştırılmış ve
 *          scripts/match-unis-preview.php sonucu üretmiş olmalı.
 *
 * Kullanım:
 *   php artisan unis:link-logos              # dry-run, sadece preview
 *   php artisan unis:link-logos --apply      # DB'ye yaz
 *   php artisan unis:link-logos --apply --min-score=0.7    # sadece yüksek skorlu
 */
class LinkUniversityLogosCommand extends Command
{
    protected $signature = 'unis:link-logos
        {--apply : Sadece dry-run yapma, DB\'ye gerçekten yaz}
        {--min-score=0.5 : Minimum match score (jaccard), bunun altı atlanır}
        {--clear : Önce mevcut image_path\'leri temizle (sadece clearbit URL\'leri)}';

    protected $description = 'HRK PDF mapping\'inden Clearbit logo URL\'lerini University.image_path\'e yazar';

    public function handle(): int
    {
        $resultPath = storage_path('app/hrk-match-result.json');
        if (! file_exists($resultPath)) {
            $this->error('hrk-match-result.json bulunamadı. Önce scripts/match-unis-preview.php çalıştır.');
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($resultPath), true);
        $matches = $data['matches'] ?? [];
        $minScore = (float) $this->option('min-score');
        $apply = (bool) $this->option('apply');
        $clear = (bool) $this->option('clear');

        $filtered = array_filter($matches, fn ($m) => ($m['score'] ?? 0) >= $minScore);
        $this->info('=== Plan ===');
        $this->line('  Toplam match: ' . count($matches));
        $this->line('  Filtre (>= ' . $minScore . '): ' . count($filtered));
        $this->line('  Mode: ' . ($apply ? 'APPLY (DB değişecek)' : 'DRY-RUN (sadece preview)'));
        $this->newLine();

        if ($clear && $apply) {
            $cleared = University::query()
                ->where('image_path', 'like', 'https://logo.clearbit.com/%')
                ->update(['image_path' => null]);
            $this->warn("→ {$cleared} mevcut clearbit URL silindi.");
            $this->newLine();
        }

        $updated = 0;
        $skipped = 0;
        $notFound = 0;
        $bar = $this->output->createProgressBar(count($filtered));
        $bar->start();

        foreach ($filtered as $m) {
            $uni = University::find($m['db_id']);
            if (! $uni) {
                $notFound++;
                $bar->advance();
                continue;
            }

            $clearbitUrl = 'https://logo.clearbit.com/' . $m['domain'];

            if ($uni->image_path === $clearbitUrl) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if ($apply) {
                // Sadece image_path boşsa veya zaten clearbit URL'iyse override et
                // (manuel yüklenmiş custom image_path'i bozma)
                if ($uni->image_path === null || str_starts_with($uni->image_path, 'https://logo.clearbit.com/')) {
                    $uni->update(['image_path' => $clearbitUrl]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                // Dry-run sayım
                if ($uni->image_path === null || str_starts_with($uni->image_path, 'https://logo.clearbit.com/')) {
                    $updated++;
                } else {
                    $skipped++;
                }
            }

            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->info('=== Sonuç ===');
        $this->line("  Güncellenen: {$updated}");
        $this->line("  Atlanan (zaten doğru veya custom path): {$skipped}");
        $this->line("  Üni bulunamadı: {$notFound}");

        if (! $apply && $updated > 0) {
            $this->newLine();
            $this->comment("💡 Gerçekten uygulamak için: php artisan unis:link-logos --apply");
        }

        return self::SUCCESS;
    }
}
