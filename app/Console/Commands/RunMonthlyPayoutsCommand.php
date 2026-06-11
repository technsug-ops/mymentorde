<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\PayoutSetting;
use App\Services\Booking\PayoutService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Aylık otomatik senior payout işlemi.
 *
 * Her ayın payout_day'inde çalıştırılması beklenir (default: ayın 5'i).
 * Kernel'de schedule edilmesi gerekir — örnek:
 *   $schedule->command('payouts:run-monthly')->monthlyOn(5, '03:00');
 *
 * Tek bir companyId verilirse sadece o şirket işlenir; verilmezse tüm aktif şirketler.
 * `--month=YYYY-MM` ile geçmiş bir ayı backfill etmek mümkün.
 */
class RunMonthlyPayoutsCommand extends Command
{
    protected $signature = 'payouts:run-monthly
                            {--company=  : Sadece bu company_id için çalıştır}
                            {--month=    : YYYY-MM, default geçen ay}
                            {--no-stripe : SeniorPayout kayıtları oluştur ama Stripe transfer yapma}';

    protected $description = 'Aylık senior payout hesapla, SeniorPayout kayıtları oluştur ve Stripe transfer çalıştır.';

    public function handle(PayoutService $service): int
    {
        $monthOpt = (string) ($this->option('month') ?: '');
        $month    = $monthOpt
            ? Carbon::createFromFormat('Y-m', $monthOpt)->startOfMonth()
            : now()->subMonthNoOverflow()->startOfMonth();

        $this->info('Aylık payout çalıştırılıyor → periyot: ' . $month->format('Y-m'));

        $companies = $this->loadCompanies();
        if ($companies->isEmpty()) {
            $this->warn('İşlenecek şirket bulunamadı.');
            return self::SUCCESS;
        }

        $totalCreated   = 0;
        $totalSuccess   = 0;
        $totalFailed    = 0;
        $companyResults = [];

        foreach ($companies as $company) {
            $companyId = (int) $company->id;
            $this->line("→ Şirket #{$companyId} — {$company->name}");

            $created = $service->createPayouts($month->copy(), $companyId);
            $createdCount = $created->count();
            $totalCreated += $createdCount;

            if ($createdCount === 0) {
                $this->line("   (eligible payout yok)");
                $companyResults[$companyId] = compact('createdCount') + ['success' => 0, 'failed' => 0];
                continue;
            }

            $this->line("   {$createdCount} payout oluşturuldu");

            $success = 0;
            $failed  = 0;
            $errors  = [];
            if (!$this->option('no-stripe')) {
                $res = $service->processStripeTransfers($companyId);
                $success = (int) $res['success'];
                $failed  = (int) $res['failed'];
                $errors  = $res['errors'] ?? [];
                $totalSuccess += $success;
                $totalFailed  += $failed;
                $this->line("   Stripe: {$success} başarılı, {$failed} başarısız");
            } else {
                $this->line('   --no-stripe → transfer atlandı');
            }

            $companyResults[$companyId] = [
                'createdCount' => $createdCount,
                'success'      => $success,
                'failed'       => $failed,
                'errors'       => $errors,
            ];

            // Notification e-posta (best-effort)
            $this->notifyCompany($companyId, $createdCount, $success, $failed);
        }

        Log::info('payouts.monthly_run_completed', [
            'month'   => $month->format('Y-m'),
            'created' => $totalCreated,
            'success' => $totalSuccess,
            'failed'  => $totalFailed,
            'companies' => $companyResults,
        ]);

        $this->newLine();
        $this->info("TOPLAM: {$totalCreated} payout oluşturuldu, {$totalSuccess} ödendi, {$totalFailed} hata.");

        return self::SUCCESS;
    }

    private function loadCompanies()
    {
        $companyOpt = $this->option('company');
        $q = Company::query()->withoutGlobalScopes();
        if ($companyOpt) {
            $q->where('id', (int) $companyOpt);
        } else {
            // Sadece aktif şirketler
            if (\Illuminate\Support\Facades\Schema::hasColumn('companies', 'is_active')) {
                $q->where('is_active', true);
            }
        }
        return $q->get();
    }

    private function notifyCompany(int $companyId, int $created, int $success, int $failed): void
    {
        try {
            $settings = PayoutSetting::forCompany($companyId);
            $to       = (string) ($settings->notification_email ?: '');
            if ($to === '') {
                return;
            }
            Mail::raw(
                "MentorDE Aylık Senior Payout Raporu\n\n"
                . "Şirket ID: {$companyId}\n"
                . "Oluşturulan payout: {$created}\n"
                . "Başarılı transfer: {$success}\n"
                . "Hata: {$failed}\n",
                function ($m) use ($to): void {
                    $m->to($to)->subject('[MentorDE] Aylık Senior Payout Raporu');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('payouts.notify_failed', ['company_id' => $companyId, 'error' => $e->getMessage()]);
        }
    }
}
