<?php

namespace App\Console\Commands;

use App\Models\PlatformBroadcast;
use App\Services\Platform\BroadcastService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Platform Owner — Zamanlanmis broadcast'lari gonder.
 *
 *  `php artisan platform:send-scheduled-broadcasts`
 *
 * Schedule: routes/console.php icinde her 5 dakikada bir.
 *
 *  - status = 'scheduled' ve scheduled_for <= now() olan broadcast'lari yakalar
 *  - BroadcastService::send() ile gonderir
 *  - Hata olursa log + ekrana yazar, diger broadcast'a gecer
 */
class SendScheduledBroadcastsCommand extends Command
{
    protected $signature   = 'platform:send-scheduled-broadcasts';
    protected $description = 'Zamanlanmis (scheduled) platform broadcast\'larini gonder';

    public function handle(BroadcastService $broadcasts): int
    {
        $due = PlatformBroadcast::query()
            ->where('status', PlatformBroadcast::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit(50)
            ->get();

        $this->info("Zamanlanmis bekleyen broadcast sayisi: {$due->count()}");

        $ok   = 0;
        $fail = 0;
        foreach ($due as $b) {
            try {
                $count = $broadcasts->send($b);
                $ok++;
                $this->line("  [ok] #{$b->id} \"{$b->title}\" → {$count} alici");
            } catch (Throwable $e) {
                $fail++;
                $this->error("  [fail] #{$b->id} \"{$b->title}\": " . $e->getMessage());
            }
        }

        $this->info("Basarili: {$ok}, Basarisiz: {$fail}");
        return self::SUCCESS;
    }
}
