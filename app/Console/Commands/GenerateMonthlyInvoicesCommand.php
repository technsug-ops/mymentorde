<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Platform\BillingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Platform Billing — Aylik fatura uretimi.
 *
 * `php artisan billing:generate-monthly [--period=YYYY-MM] [--dry-run] [--send]`
 *
 * Schedule: routes/console.php icinde monthlyOn(1, '03:00').
 *
 * Davranis:
 *   - is_active=true ve mrr_eur > 0 olan tum company'leri tarar
 *   - Trial tier (mrr=0) ATLA — fatura kesilmez
 *   - Belirtilen donem icin fatura zaten varsa atlar
 *   - --send: olusturulan faturalari direkt mail ile gonderir (status='sent')
 *   - --dry-run: hicbir sey yazmaz, sadece kac fatura kesilecegini gosterir
 *
 * Overdue kontrolu de burada yapilir — 30+ gunluk 'sent'leri overdue'ye cevirir.
 */
class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature   = 'billing:generate-monthly
                              {--period= : Donem (YYYY-MM, varsayilan: bu ay)}
                              {--dry-run : Sadece simulasyon, fatura olusturma}
                              {--send : Olusturulan faturalari mail ile gonder}';

    protected $description = 'Aktif company\'ler icin aylik abonelik faturalarini olustur (+ overdue kontrol)';

    public function handle(BillingService $billing): int
    {
        $periodInput = (string) $this->option('period');
        $dryRun      = (bool) $this->option('dry-run');
        $autoSend    = (bool) $this->option('send');

        try {
            $period = $periodInput !== ''
                ? Carbon::createFromFormat('Y-m', $periodInput)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (Throwable $e) {
            $this->error("Geçersiz period: '{$periodInput}' — YYYY-MM bekleniyor.");
            return self::INVALID;
        }

        $this->info("Faturalama donemi: {$period->format('m/Y')} (" . ($dryRun ? 'DRY-RUN' : 'LIVE') . ')');

        // ── 1) Overdue kontrol — 30+ gun once gonderilmis 'sent'leri overdue yap
        if (!$dryRun) {
            $overdue = $billing->checkOverdue();
            if ($overdue > 0) {
                $this->warn("Overdue isaretlendi: {$overdue} fatura");
            }
        }

        // ── 2) Aktif ve mrr>0 company'leri al
        $companies = Company::query()
            ->where('is_active', true)
            ->where('mrr_eur', '>', 0)
            ->whereNotIn('subscription_tier', [Company::TIER_TRIAL])
            ->get();

        $this->info("Toplam aday company: {$companies->count()}");

        $created  = 0;
        $skipped  = 0;
        $failed   = 0;
        $sent     = 0;

        foreach ($companies as $company) {
            if ($billing->invoiceExistsFor($company, $period)) {
                $skipped++;
                $this->line("  [skip] {$company->name} — fatura zaten var");
                continue;
            }

            if ($dryRun) {
                $created++;
                $this->line("  [DRY] {$company->name} ({$company->subscription_tier}) — €" . number_format((float) $company->mrr_eur, 2));
                continue;
            }

            try {
                $invoice = $billing->generateInvoiceFor($company, $period);
                $created++;
                $this->line("  [ok] {$invoice->invoice_number} → {$company->name} (€" . number_format((float) $invoice->total_eur, 2) . ' brut)');

                if ($autoSend) {
                    if ($billing->sendInvoice($invoice)) {
                        $sent++;
                    } else {
                        $this->warn("    [send-fail] {$invoice->invoice_number} — mail gonderimi basarisiz (billing_email eksik?)");
                    }
                }
            } catch (Throwable $e) {
                $failed++;
                $this->error("  [fail] {$company->name}: " . $e->getMessage());
            }
        }

        $this->info('───────────────────────────────────────');
        $this->info("Olusturulan: {$created}");
        $this->info("Atlanan   : {$skipped}");
        if ($autoSend) {
            $this->info("Gonderilen: {$sent}");
        }
        if ($failed > 0) {
            $this->error("Basarisiz : {$failed}");
        }

        return self::SUCCESS;
    }
}
