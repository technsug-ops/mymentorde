<?php

namespace App\Console\Commands;

use App\Services\UniLogoSyncService;
use Illuminate\Console\Command;

/**
 * HRK Rektorenliste PDF'ten Clearbit logo URL'lerini University.image_path'e yaz.
 *
 * Asıl iş UniLogoSyncService'te — command ve /_deploy/run-pending endpoint
 * aynı service'i çağırır.
 *
 * Kullanım:
 *   php artisan unis:link-logos              # APPLY mode (default)
 *   php artisan unis:link-logos --dry        # sadece preview
 *   php artisan unis:link-logos --min-score=0.7
 */
class LinkUniversityLogosCommand extends Command
{
    protected $signature = 'unis:link-logos
        {--dry : Dry-run; DB\'ye yazmaz, sadece sayım gösterir}
        {--min-score=0.5 : Minimum match score (jaccard)}';

    protected $description = 'HRK PDF\'inden uni → domain mapping ile University.image_path Clearbit URL\'i set eder';

    public function handle(UniLogoSyncService $service): int
    {
        $apply = ! $this->option('dry');
        $minScore = (float) $this->option('min-score');

        $this->info('=== HRK uni logo sync ===');
        $this->line("Mode: " . ($apply ? 'APPLY' : 'DRY-RUN'));
        $this->line("Min score: {$minScore}");
        $this->newLine();

        try {
            $result = $service->syncAll($apply, $minScore);

            foreach ($result['logs'] as $l) {
                $this->line('  · ' . $l);
            }
            $this->newLine();
            $this->info('=== Sonuç ===');
            $this->line("  PDF unis:  {$result['pdf_unis']}");
            $this->line("  Matches:   {$result['matches']}");
            $this->line("  Updated:   {$result['updated']}");
            $this->line("  Skipped:   {$result['skipped']}");

            if (! $apply && $result['updated'] > 0) {
                $this->newLine();
                $this->comment("💡 Gerçekten uygulamak için: php artisan unis:link-logos");
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('FAILED: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
