<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SilenceCheckinService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Öğrenciler için sessizlik check-in cron'u — günlük 09:35.
 * Stage tespiti: visa > uni_assist > general (kompozit, StudentVisaApplication
 * ve StudentUniversityApplication üzerinden).
 */
class SilenceCheckinStudentsCommand extends Command
{
    protected $signature = 'silence:checkin-students
                            {--dry-run : Sadece raporla, post atma}';

    protected $description = 'Sessizlik check-in: öğrencilerin paneline "süreç aktif" touchpoint düşür';

    public function __construct(private readonly SilenceCheckinService $svc)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $students = User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->whereNull('silence_checkin_paused_at')
            ->get();

        $posted = 0; $skipped = 0; $errors = 0;

        foreach ($students as $u) {
            $stage = $this->svc->resolveStudentStage($u);
            if (! $stage) { $skipped++; continue; }

            $decision = $this->svc->shouldPostCheckin($u, $stage);
            if (! $decision['post']) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                'Student #%d (%s) → stage=%s, sessiz=%dgün, cadence=%d',
                $u->id,
                $u->email ?? '?',
                $stage,
                $decision['days_silent'],
                $decision['cadence'],
            ));

            if ($dryRun) { $posted++; continue; }

            try {
                $this->svc->postCheckin($u, $stage, (int) $decision['days_silent']);
                $posted++;
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('silence.checkin.student.failed', [
                    'student_id' => $u->id,
                    'error'      => $e->getMessage(),
                ]);
                $this->error("Student #{$u->id} post edilemedi: {$e->getMessage()}");
            }
        }

        $tag = $dryRun ? '[dry-run]' : '';
        $this->info("{$tag} Post: {$posted} | Atlandı: {$skipped} | Hata: {$errors}");

        return Command::SUCCESS;
    }
}
