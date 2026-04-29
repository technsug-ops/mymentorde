<?php

namespace App\Console\Commands;

use App\Models\GuestApplication;
use App\Services\SilenceCheckinService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Aday öğrenciler için sessizlik check-in cron'u — günlük 09:30.
 * lead_status NOT IN (converted, lost) ve cadence süresinden uzun sessizlik
 * varsa aday paneline "süreç aktif" touchpoint düşürür.
 */
class SilenceCheckinGuestsCommand extends Command
{
    protected $signature = 'silence:checkin-guests
                            {--dry-run : Sadece raporla, post atma}';

    protected $description = 'Sessizlik check-in: aday öğrencilerin paneline "süreç aktif" touchpoint düşür';

    public function __construct(private readonly SilenceCheckinService $svc)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $guests = GuestApplication::query()
            ->whereNotIn('lead_status', ['converted', 'lost'])
            ->whereNull('silence_checkin_paused_at')
            ->get();

        $posted = 0; $skipped = 0; $errors = 0;

        foreach ($guests as $g) {
            $stage = $this->svc->resolveGuestStage($g);
            if (! $stage) { $skipped++; continue; }

            $decision = $this->svc->shouldPostCheckin($g, $stage);
            if (! $decision['post']) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                'Guest #%d (%s) → stage=%s, sessiz=%dgün, cadence=%d',
                $g->id,
                $g->email ?? '?',
                $stage,
                $decision['days_silent'],
                $decision['cadence'],
            ));

            if ($dryRun) { $posted++; continue; }

            try {
                $this->svc->postCheckin($g, $stage, (int) $decision['days_silent']);
                $posted++;
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('silence.checkin.guest.failed', [
                    'guest_id' => $g->id,
                    'error'    => $e->getMessage(),
                ]);
                $this->error("Guest #{$g->id} post edilemedi: {$e->getMessage()}");
            }
        }

        $tag = $dryRun ? '[dry-run]' : '';
        $this->info("{$tag} Post: {$posted} | Atlandı: {$skipped} | Hata: {$errors}");

        return Command::SUCCESS;
    }
}
