<?php

namespace App\Console\Commands;

use App\Models\MarketingReport;
use App\Models\NotificationDispatch;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * v3.0 §10.4 — Zamanlanmış raporlar
 *
 * | Rapor                    | Frekans       | Alıcılar                     |
 * |--------------------------|---------------|------------------------------|
 * | Weekly Marketing Summary | Pzt 09:00     | marketing_admin, manager     |
 * | Monthly Sales Report     | Ayın 1'i 09:00| sales_admin, manager         |
 * | Weekly Scoring Report    | Pzt 09:00     | sales_admin, manager         |
 * | Monthly Attribution      | Ayın 1'i      | marketing_admin, manager     |
 */
class GenerateScheduledReportsCommand extends Command
{
    protected $signature   = 'reports:generate-scheduled {--dry-run : Sadece hangi raporların tetikleneceğini göster}';
    protected $description = 'Zamanlanmış raporları oluştur ve alıcılara bildirim gönder';

    /** @var array<string, array{freq:string, roles:string[], title:string}> */
    private const REPORTS = [
        'weekly_marketing_summary' => [
            'freq'  => 'weekly_monday',
            'roles' => ['marketing_admin', 'manager', 'system_admin'],
            'title' => 'Haftalık Pazarlama Özeti',
        ],
        'monthly_sales_report' => [
            'freq'  => 'monthly_first',
            'roles' => ['sales_admin', 'manager', 'system_admin'],
            'title' => 'Aylık Satış Raporu',
        ],
        'weekly_scoring_report' => [
            'freq'  => 'weekly_monday',
            'roles' => ['sales_admin', 'manager', 'system_admin'],
            'title' => 'Haftalık Lead Scoring Raporu',
        ],
        'monthly_attribution_report' => [
            'freq'  => 'monthly_first',
            'roles' => ['marketing_admin', 'manager', 'system_admin'],
            'title' => 'Aylık Attribution Raporu',
        ],
    ];

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $today   = Carbon::today();
        $isMonday = $today->dayOfWeek === Carbon::MONDAY;
        $isFirst  = $today->day === 1;

        $triggered = 0;

        foreach (self::REPORTS as $code => $config) {
            $shouldRun = match ($config['freq']) {
                'weekly_monday' => $isMonday,
                'monthly_first' => $isFirst,
                default          => false,
            };

            if (! $shouldRun) {
                continue;
            }

            $this->line("  → {$config['title']}");

            if ($dryRun) {
                $triggered++;
                continue;
            }

            // Alıcıları bul
            $recipients = User::query()
                ->whereIn('role', $config['roles'])
                ->where('is_active', true)
                ->get(['id', 'name', 'email']);

            foreach ($recipients as $user) {
                NotificationDispatch::create([
                    'user_id'    => $user->id,
                    'channel'    => 'in_app',
                    'subject'    => $config['title'],
                    'body'       => "{$config['title']} hazırlandı. Dashboard'dan inceleyebilirsiniz.",
                    'status'     => 'pending',
                    'company_id' => $user->company_id ?? null,
                ]);
            }

            // Rapor kaydı oluştur
            MarketingReport::create([
                'report_type' => $code,
                'title'       => $config['title'],
                'status'      => 'generated',
                'period_start'=> $today->copy()->subDays($config['freq'] === 'weekly_monday' ? 7 : 30)->toDateString(),
                'period_end'  => $today->toDateString(),
                'generated_at'=> now(),
            ]);

            $triggered++;
        }

        $mode = $dryRun ? '[DRY-RUN] ' : '';
        $this->info("{$mode}Tetiklenen rapor sayısı: {$triggered}");

        return Command::SUCCESS;
    }
}
