<?php

namespace App\Console\Commands;

use App\Models\ManagerReport;
use App\Models\ManagerScheduledReport;
use App\Services\DashboardKPIService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendScheduledReportsCommand extends Command
{
    protected $signature   = 'manager:send-scheduled-reports {--dry-run : Sadece kaç rapor gönderileceğini göster}';
    protected $description = 'Zamanlanmış manager raporlarını oluştur ve email gönder.';

    public function handle(DashboardKPIService $kpi, NotificationService $notif): int
    {
        $today      = now();
        $dayOfWeek  = (int) $today->dayOfWeekIso; // 1=Mon..7=Sun
        $dayOfMonth = (int) $today->day;

        $reports = ManagerScheduledReport::where('is_active', true)
            ->where(function ($q) use ($dayOfWeek, $dayOfMonth): void {
                $q->where(fn ($w) => $w->where('frequency', 'weekly')->where('day_of_week', $dayOfWeek))
                  ->orWhere(fn ($w) => $w->where('frequency', 'monthly')->where('day_of_month', $dayOfMonth));
            })
            ->get();

        if ($this->option('dry-run')) {
            $this->info("Dry-run: {$reports->count()} zamanlanmış rapor bugün gönderilecek.");
            return 0;
        }

        $sent = 0;

        foreach ($reports as $report) {
            $period = $report->frequency === 'weekly'
                ? [now()->subWeek()->startOfDay(), now()->startOfDay()]
                : [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];

            /** @var Carbon $periodStart */
            /** @var Carbon $periodEnd */
            [$periodStart, $periodEnd] = $period;

            $payload = $kpi->managerStatsAndFunnel($periodStart, $periodEnd, $report->senior_filter ?? '');
            $trend   = $kpi->managerTrend($periodStart, $periodEnd);

            $snapshot = ManagerReport::create([
                'report_type'  => $report->report_type,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'senior_email' => $report->senior_filter,
                'sent_to'      => $report->send_to,
                'send_status'  => 'sent',
                'sent_at'      => now(),
                'stats'        => $payload['stats'],
                'funnel'       => $payload['funnel'],
                'trend'        => $trend,
                'created_by'   => 'system:scheduled',
            ]);

            $subject = 'MentorDE ' . ($report->report_type) . ' — '
                . $periodStart->format('d.m') . '-' . $periodEnd->format('d.m.Y');

            foreach ((array) $report->send_to as $email) {
                $notif->send([
                    'channel'         => 'email',
                    'category'        => 'scheduled_report',
                    'recipient_email' => $email,
                    'subject'         => $subject,
                    'body'            => "Rapor oluşturuldu. Dashboard'dan görüntüleyebilirsiniz. (Snapshot #{$snapshot->id})",
                    'source_type'     => 'scheduled_report',
                    'source_id'       => (string) $snapshot->id,
                ]);
            }

            $report->update(['last_sent_at' => now()]);
            $sent++;
        }

        $this->info("Zamanlanmış rapor gönderildi: {$sent} adet.");
        return 0;
    }
}
