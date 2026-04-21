<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\FieldRuleApproval;
use App\Models\ManagerReport;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentRevenue;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ManagerReportSnapshotCommand extends Command
{
    protected $signature = 'manager:report-snapshot
                            {--type=monthly : weekly|monthly|quarterly|yearly}
                            {--start= : Start date (Y-m-d)}
                            {--end= : End date (Y-m-d)}
                            {--senior= : Filter by senior email}
                            {--sent-to= : Comma-separated recipient emails}';

    protected $description = 'Create a manager dashboard report snapshot';

    public function handle(): int
    {
        $type    = (string) $this->option('type');
        $senior  = (string) $this->option('senior');
        $sentToCsv = (string) $this->option('sent-to');
        $startOpt = (string) ($this->option('start') ?? '');
        $endOpt   = (string) ($this->option('end') ?? '');

        $now = Carbon::now();
        if ($startOpt !== '' && $endOpt !== '') {
            $start = Carbon::parse($startOpt)->startOfDay();
            $end   = Carbon::parse($endOpt)->endOfDay();
        } else {
            if ($type === 'weekly') {
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
            } elseif ($type === 'quarterly') {
                $start = $now->copy()->firstOfQuarter()->startOfDay();
                $end   = $now->copy()->lastOfQuarter()->endOfDay();
            } elseif ($type === 'yearly') {
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
            } else {
                $type  = 'monthly';
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
            }
        }

        $studentRevenueQuery = StudentRevenue::query()->whereBetween('updated_at', [$start, $end]);
        $activeStudents      = (clone $studentRevenueQuery)->count();

        $documentQuery = Document::query()->whereBetween('created_at', [$start, $end]);
        if ($senior !== '') {
            $documentQuery->where('uploaded_by', $senior);
        }
        $studentsWithDocs = $documentQuery->whereNotNull('student_id')->distinct()->count('student_id');

        $outcomeQuery = ProcessOutcome::query()->whereBetween('created_at', [$start, $end]);
        if ($senior !== '') {
            $outcomeQuery->where('added_by', $senior);
        }
        $studentsWithVisibleOutcome = $outcomeQuery
            ->where('is_visible_to_student', true)
            ->whereNotNull('student_id')
            ->distinct()
            ->count('student_id');

        $studentsPendingApproval = FieldRuleApproval::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'pending')
            ->whereNotNull('student_id')
            ->distinct()
            ->count('student_id');

        $conversionRate = $activeStudents > 0
            ? round(($studentsWithVisibleOutcome / $activeStudents) * 100, 1)
            : 0.0;

        $stats = [
            'month_label'       => $start->format('F Y'),
            'monthly_revenue'   => (float) (clone $studentRevenueQuery)->sum('total_earned'),
            'active_students'   => $activeStudents,
            'conversion_rate'   => $conversionRate,
            'pending_approvals' => FieldRuleApproval::query()
                ->where('status', 'pending')
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'archived_approvals' => FieldRuleApproval::query()
                ->where('status', 'archived')
                ->whereBetween('updated_at', [$start, $end])
                ->count(),
            'overdue_outcomes' => ProcessOutcome::query()
                ->whereNotNull('deadline')
                ->where('deadline', '<', $now)
                ->where('is_visible_to_student', false)
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'upcoming_outcomes' => ProcessOutcome::query()
                ->whereBetween('deadline', [$now, $now->copy()->addDays(7)])
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'open_pending_amount' => (float) (clone $studentRevenueQuery)->sum('total_pending'),
        ];

        $activeBase    = max(1, $activeStudents);
        $pendingRate   = min(100, ($stats['pending_approvals'] / $activeBase) * 100);
        $overdueRate   = min(100, ($stats['overdue_outcomes'] / $activeBase) * 100);
        $collectionRate = $stats['monthly_revenue'] > 0
            ? min(100, ($stats['open_pending_amount'] / $stats['monthly_revenue']) * 100)
            : ($stats['open_pending_amount'] > 0 ? 100 : 0);

        $riskScore = (int) round(($pendingRate * 0.35) + ($overdueRate * 0.45) + ($collectionRate * 0.20));
        $riskScore = min(100, max(0, $riskScore));
        $riskLevel = $riskScore >= 60 ? 'critical' : ($riskScore >= 30 ? 'warning' : 'good');

        $stats['risk_score']     = $riskScore;
        $stats['risk_level']     = $riskLevel;
        $stats['risk_breakdown'] = [
            'pending_rate'    => round($pendingRate, 1),
            'overdue_rate'    => round($overdueRate, 1),
            'collection_rate' => round($collectionRate, 1),
        ];

        $funnelBase = max($activeStudents, 1);
        $funnel = [
            ['label' => 'Aktif Öğrenci',   'count' => $activeStudents,             'rate' => $activeStudents > 0 ? 100.0 : 0.0],
            ['label' => 'Belge Yükleyen',  'count' => $studentsWithDocs,           'rate' => round(($studentsWithDocs / $funnelBase) * 100, 1)],
            ['label' => 'Sonuç Açıklandı', 'count' => $studentsWithVisibleOutcome, 'rate' => round(($studentsWithVisibleOutcome / $funnelBase) * 100, 1)],
            ['label' => 'Onay Bekliyor',   'count' => $studentsPendingApproval,    'rate' => round(($studentsPendingApproval / $funnelBase) * 100, 1)],
        ];

        $trendStart = $start->copy()->startOfMonth();
        $trendEnd   = $end->copy()->startOfMonth();
        $trend      = [];
        $points     = 0;
        while ($trendStart->lte($trendEnd) && $points < 12) {
            $bucketStart = $trendStart->copy()->startOfMonth();
            $bucketEnd   = $trendStart->copy()->endOfMonth();
            $trend[] = [
                'label'          => $bucketStart->format('Y-m'),
                'revenue'        => (float) StudentRevenue::query()->whereBetween('updated_at', [$bucketStart, $bucketEnd])->sum('total_earned'),
                'approval_count' => (int) FieldRuleApproval::query()->whereBetween('created_at', [$bucketStart, $bucketEnd])->count(),
            ];
            $trendStart->addMonth();
            $points++;
        }

        $sentTo = collect(explode(',', $sentToCsv))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        $report = ManagerReport::query()->create([
            'report_type'  => $type,
            'period_start' => $start->toDateString(),
            'period_end'   => $end->toDateString(),
            'senior_email' => $senior !== '' ? $senior : null,
            'sent_to'      => $sentTo,
            'stats'        => $stats,
            'funnel'       => $funnel,
            'trend'        => $trend,
            'created_by'   => 'system:artisan',
        ]);

        $this->info("Snapshot olusturuldu. ID: {$report->id} | type: {$type} | period: {$start->toDateString()} - {$end->toDateString()}");

        return 0;
    }
}
