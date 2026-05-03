<?php

namespace App\Console\Commands;

use App\Models\NotificationDispatch;
use App\Models\UniMatchResponse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * UniMatch resume reminder.
 *
 * Akış:
 *  - Kullanıcı wizard'ı %25+ doldurmuş (current_step >= 5)
 *  - Email bırakmış (lead_email var)
 *  - Henüz tamamlamamış (completed_at NULL)
 *  - Son 1 saat içinde aktif değil
 *  - Daha önce resume reminder gönderilmemiş
 *
 * → "Wizard'ı bıraktığın yerden devam et" mail.
 *
 * Industry data (HubSpot, Typeform): bu pattern %20-30 conversion artırır.
 *
 * Cron: günlük çalıştır (her saat de mümkün ama 1 günlük yeter).
 */
class SendUniMatchResumeReminder extends Command
{
    protected $signature = 'unimatch:send-resume-reminder
        {--dry-run : Sadece raporla, gönderme}
        {--inactive-hours=2 : En az kaç saat inaktif olmalı}';

    protected $description = 'UniMatch wizard yarıda bıraktığı için lead\'e resume URL maili gönderir';

    private const TEMPLATE_KEY = 'unimatch_resume_reminder';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $inactiveHours = max(1, (int) $this->option('inactive-hours'));

        $cutoff = Carbon::now()->subHours($inactiveHours);

        $candidates = UniMatchResponse::query()
            ->whereNotNull('lead_email')
            ->whereNull('completed_at')
            ->where('current_step', '>=', 5)        // En az %25 ilerlemiş
            ->where('last_active_at', '<=', $cutoff) // İnaktif
            ->where('lead_consent_marketing', true)
            ->get();

        $this->info("Resume reminder adayı: {$candidates->count()} (>={$inactiveHours}h inaktif)");

        $sent = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($candidates as $response) {
            $alreadySent = NotificationDispatch::query()
                ->where('source_type', self::TEMPLATE_KEY)
                ->where('source_id', $response->id)
                ->exists();

            if ($alreadySent) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  [dry] {$response->lead_email} → step {$response->current_step}/19");
                continue;
            }

            try {
                $resumeUrl = url('/uni-match/step/' . $response->current_step . '?t=' . $response->session_token);
                $progressPct = (int) round(($response->current_step / 19) * 100);

                Mail::send('emails.unimatch.resume_reminder', [
                    'firstName'   => $response->lead_first_name ?: 'Merhaba',
                    'currentStep' => $response->current_step,
                    'progressPct' => $progressPct,
                    'resumeUrl'   => $resumeUrl,
                ], function ($m) use ($response, $progressPct) {
                    $m->to($response->lead_email, $response->lead_first_name)
                      ->subject("⏸️ %{$progressPct} doldurmuştun — UniMatch'a devam et");
                });

                NotificationDispatch::create([
                    'user_id'         => null,
                    'company_id'      => $response->company_id,
                    'source_type'     => self::TEMPLATE_KEY,
                    'source_id'       => $response->id,
                    'channel'         => 'email',
                    'category'        => 'unimatch',
                    'recipient_email' => $response->lead_email,
                    'recipient_name'  => $response->lead_first_name,
                    'subject'         => "%{$progressPct} doldurmuştun — UniMatch'a devam et",
                    'status'          => 'sent',
                    'sent_at'         => now(),
                ]);

                $sent++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn("  ⚠ {$response->lead_email}: " . $e->getMessage());
                \Log::warning('UniMatchResume.send_failed', [
                    'email' => $response->lead_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($dryRun) {
            $this->warn('--dry-run aktif, mail gönderilmedi.');
        } else {
            $this->info("✅ Resume reminder: {$sent} gönderildi, {$skipped} atlandı, {$errors} hata.");
        }

        return self::SUCCESS;
    }
}
