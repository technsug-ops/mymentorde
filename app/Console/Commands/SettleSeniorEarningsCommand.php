<?php

namespace App\Console\Commands;

use App\Models\SeniorEarning;
use Illuminate\Console\Command;

/**
 * Marketplace Phase 5 — Senior earning settle command.
 *
 * 24 saat geçmiş ve hala status=recorded olan earning'leri status=available'a yükselt.
 * Bu pencere, müşterinin iade hakkına sahip olduğu dilimdir; sonrasında senior'un payout'a hakkı doğar.
 *
 * Schedule: günde 1 kez, gece 02:30 (dailyAt).
 * Manuel: `php artisan senior:earnings:settle --dry-run` ile önce test.
 */
class SettleSeniorEarningsCommand extends Command
{
    protected $signature = 'senior:earnings:settle
                            {--dry-run : List earnings that would be settled, without writing}
                            {--hours=24 : Settle window in hours (default 24)}';

    protected $description = 'Marks senior earnings as "available" once the cancellation window has passed';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $hours  = max(1, (int) $this->option('hours'));

        $cutoff = now()->subHours($hours);

        $query = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->pending()
            ->whereNotNull('recorded_at')
            ->where('recorded_at', '<=', $cutoff);

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Settlenecek earning yok (cutoff: ' . $cutoff->toIso8601String() . ').');
            return self::SUCCESS;
        }

        $this->info(sprintf('%d earning settle penceresinde — cutoff %s', $total, $cutoff->toIso8601String()));

        if ($dryRun) {
            $query->take(50)->get(['id', 'senior_user_id', 'senior_payout_cents', 'currency', 'recorded_at'])
                ->each(function ($e) {
                    $this->line(sprintf(
                        '  #%d  senior=%d  payout=%.2f %s  recorded=%s',
                        $e->id, $e->senior_user_id, $e->senior_payout_cents / 100, $e->currency, $e->recorded_at
                    ));
                });
            $this->warn('Dry-run — DB güncellenmedi.');
            return self::SUCCESS;
        }

        // Batch update — chunk ile aşırı bellek tüketimini engelle
        $settled = 0;
        $skipped = 0;
        $query->orderBy('id')->chunkById(200, function ($chunk) use (&$settled, &$skipped) {
            foreach ($chunk as $earning) {
                if ($earning->markAvailable()) {
                    $settled++;
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Settled: {$settled}, skipped: {$skipped} (status drift).");
        return self::SUCCESS;
    }
}
