<?php

namespace App\Console\Commands;

use App\Models\CustomerHealthScore;
use App\Services\Platform\CustomerHealthService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Platform Owner — Customer Health Score Recompute.
 *
 * `php artisan platform:recompute-health [--alerts]`
 *
 * Schedule: routes/console.php icinde dailyAt('06:00').
 *
 * Davranis:
 *   - Tum aktif company'leri tek tek hesaplar (CustomerHealthService::recomputeAll())
 *   - --alerts flag ile critical (<20) olanlari Platform Owner email'ine bildirir.
 *   - last_risk_alert_sent_at son 24 saatte gonderilmisse o company atlanir (spam'i onler).
 */
class RecomputeCustomerHealthCommand extends Command
{
    protected $signature = 'platform:recompute-health
                            {--alerts : Kritik durumdaki sirketler icin Platform Owner email uyari gonder}';

    protected $description = 'Tum aktif sirketler icin customer health score yeniden hesaplar (+ opsiyonel risk alarmi)';

    public function handle(CustomerHealthService $health): int
    {
        $sendAlerts = (bool) $this->option('alerts');

        $this->info('Customer Health recompute basliyor...');
        $ok = $health->recomputeAll();
        $this->info("Hesaplama tamamlandi: {$ok} sirket.");

        if (!$sendAlerts) {
            return self::SUCCESS;
        }

        return $this->dispatchAlerts();
    }

    protected function dispatchAlerts(): int
    {
        $ownerEmail = (string) Config::get('mail.platform_owner', env('PLATFORM_OWNER_EMAIL', ''));
        if ($ownerEmail === '') {
            $this->warn('PLATFORM_OWNER_EMAIL/config(mail.platform_owner) bos — alert atlandi.');
            return self::SUCCESS;
        }

        $critical = CustomerHealthScore::query()
            ->with('company')
            ->critical()
            ->get();

        if ($critical->isEmpty()) {
            $this->info('Kritik durumda sirket yok — alert gonderilmedi.');
            return self::SUCCESS;
        }

        $now      = CarbonImmutable::now();
        $sent     = 0;
        $skipped  = 0;

        foreach ($critical as $cs) {
            if ($cs->last_risk_alert_sent_at && $cs->last_risk_alert_sent_at->gt($now->subDay())) {
                $skipped++;
                continue;
            }

            $name  = $cs->company?->name ?? "Sirket #{$cs->company_id}";
            $flags = implode(', ', (array) ($cs->risk_flags ?? []));
            $body  = "MentorDE Platform — Kritik Saglik Skoru Uyarisi\n\n"
                . "Sirket: {$name}\n"
                . "Skor: {$cs->overall_score}/100 ({$cs->scoreLabel()})\n"
                . "Trend: {$cs->trendLabel()}\n"
                . "Risk Bayraklari: " . ($flags ?: '—') . "\n"
                . "Detay: " . route('platform.customer-health.show', $cs->company_id) . "\n";

            try {
                Mail::raw($body, function ($mail) use ($ownerEmail, $name): void {
                    $mail->to($ownerEmail)
                        ->subject("[Platform Risk] {$name} — Kritik Saglik Skoru");
                });
                $cs->last_risk_alert_sent_at = $now;
                $cs->save();
                $sent++;
            } catch (Throwable $e) {
                Log::warning('[CustomerHealth] alert mail failed', [
                    'company_id' => $cs->company_id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $this->info("Alert gonderildi: {$sent}, atlandi (24h cooldown): {$skipped}");
        return self::SUCCESS;
    }
}
