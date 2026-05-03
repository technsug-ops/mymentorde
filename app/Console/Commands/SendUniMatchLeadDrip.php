<?php

namespace App\Console\Commands;

use App\Models\NotificationDispatch;
use App\Models\UniMatchResponse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * UniMatch lead drip campaign.
 *
 * Akış:
 *  - Kullanıcı wizard mid-funnel'da email/phone bıraktı (lead_captured_at)
 *  - Sonuç sayfasına geldi ama "Şimdi kayıt ol" demedi (converted_at NULL)
 *  - X gün sonra: hatırlatma maili
 *
 * 3 kademeli:
 *  - Drip 1: 3 gün sonra → "Sonuçlarını incelemedin, programları gözden geçir"
 *  - Drip 2: 7 gün sonra → "Almanya'ya başvuru için 5 sorulmadık soru"
 *  - Drip 3: 14 gün sonra → "Son şans: danışmanın seninle ücretsiz görüşmek istiyor"
 *
 * Cron: günlük çalıştır (her sabah 09:00).
 */
class SendUniMatchLeadDrip extends Command
{
    protected $signature = 'unimatch:send-lead-drip
        {--dry-run : Sadece raporla, gönderme}';

    protected $description = 'UniMatch lead bırakıp convert etmemiş kullanıcılara drip mail gönderir';

    /** @var array<string, array{days: int, subject: string, template: string}> */
    private const STAGES = [
        'unimatch_drip_1' => [
            'days'     => 3,
            'subject'  => '🎯 Sana özel 10 Almanya programını incelemedin',
            'template' => 'emails.unimatch.drip_1',
        ],
        'unimatch_drip_2' => [
            'days'     => 7,
            'subject'  => '📚 Almanya başvuru sürecinde 5 yaygın hata',
            'template' => 'emails.unimatch.drip_2',
        ],
        'unimatch_drip_3' => [
            'days'     => 14,
            'subject'  => '💬 Danışmanın seninle ücretsiz 30dk görüşmek istiyor',
            'template' => 'emails.unimatch.drip_3',
        ],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $totalSent = 0;
        $totalSkipped = 0;

        foreach (self::STAGES as $templateKey => $cfg) {
            $cutoff = Carbon::now()->subDays($cfg['days']);

            $candidates = UniMatchResponse::query()
                ->whereNotNull('lead_email')
                ->whereNotNull('completed_at') // wizard'ı tamamlamış olsun
                ->whereNull('converted_at')    // ama convert etmemiş
                ->where('lead_captured_at', '<=', $cutoff)
                ->where('lead_consent_marketing', true) // KVKK opt-in
                ->get();

            $this->info("Stage: {$templateKey} ({$cfg['days']} gün) — aday: {$candidates->count()}");

            foreach ($candidates as $response) {
                // Bu lead'e bu stage daha önce gönderildi mi?
                $alreadySent = NotificationDispatch::query()
                    ->where('source_type', $templateKey)
                    ->where('source_id', $response->id)
                    ->exists();

                if ($alreadySent) {
                    $totalSkipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [dry] {$response->lead_email} — {$cfg['subject']}");
                    continue;
                }

                try {
                    Mail::send($cfg['template'], [
                        'response' => $response,
                        'firstName' => $response->lead_first_name ?: 'Merhaba',
                        'recommendations' => array_slice($response->recommendations ?? [], 0, 3),
                        'returnUrl' => url('/uni-match/result?t=' . $response->session_token),
                    ], function ($m) use ($response, $cfg) {
                        $m->to($response->lead_email, $response->lead_first_name)
                          ->subject($cfg['subject']);
                    });

                    NotificationDispatch::create([
                        'user_id'         => null,
                        'company_id'      => $response->company_id,
                        'source_type'     => $templateKey,
                        'source_id'       => $response->id,
                        'channel'         => 'email',
                        'category'        => 'unimatch',
                        'recipient_email' => $response->lead_email,
                        'recipient_name'  => $response->lead_first_name,
                        'subject'         => $cfg['subject'],
                        'status'          => 'sent',
                        'sent_at'         => now(),
                    ]);

                    $totalSent++;
                } catch (\Throwable $e) {
                    $this->warn("  ⚠ {$response->lead_email}: " . $e->getMessage());
                    \Log::warning('UniMatchDrip.send_failed', [
                        'email' => $response->lead_email,
                        'stage' => $templateKey,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($dryRun) {
            $this->warn('--dry-run aktif, mail gönderilmedi.');
        } else {
            $this->info("✅ Drip tamamlandı: {$totalSent} gönderildi, {$totalSkipped} atlandı (zaten gönderilmiş).");
        }

        return self::SUCCESS;
    }
}
