<?php

namespace App\Console\Commands;

use App\Jobs\SendReviewRequestEmailJob;
use App\Models\PublicBooking;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Marketplace Phase 7 — Son 1-3 saat icinde sonlanmis bookingler icin
 * review davetiyesi gonderim job'larini dispatch eder.
 *
 * Schedule kernel.php icinde hourly() ile cagirilir.
 *
 *   - WHERE ends_at < now() - 60dk AND ends_at > now() - 25 saat
 *   - WHERE status IN (confirmed, completed)
 *   - WHERE review_request_sent_at IS NULL
 *
 * Idempotent: ayni booking icin Job iki kez gonderse bile job icinde
 * review_request_sent_at kontrolu var.
 */
class QueueReviewRequestsCommand extends Command
{
    protected $signature = 'reviews:queue-requests {--dry-run : Sadece say, gonderme}';

    protected $description = 'Son 25 saatte tamamlanan bookingler icin review davetiye job\'larini queue\'ya gonderir.';

    public function handle(): int
    {
        $now      = CarbonImmutable::now();
        $earliest = $now->subHours(25);   // 25 saat ote
        $latest   = $now->subMinutes(60); // En az 60dk gecmis

        $query = PublicBooking::query()
            ->withoutGlobalScopes()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereNull('review_request_sent_at')
            ->whereBetween('ends_at', [$earliest, $latest]);

        $count = (int) $query->count();
        $this->info("Queue adayı: {$count} booking");

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $dispatched = 0;
        $query->chunkById(200, function ($bookings) use (&$dispatched): void {
            foreach ($bookings as $b) {
                if (empty($b->invitee_email)) {
                    continue;
                }
                SendReviewRequestEmailJob::dispatch((int) $b->id);
                $dispatched++;
            }
        });

        $this->info("Dispatch edilen: {$dispatched}");
        return self::SUCCESS;
    }
}
