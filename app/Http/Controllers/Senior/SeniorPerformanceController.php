<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\MarketingTask;
use App\Models\ProcessOutcome;
use App\Models\SeniorPerformanceSnapshot;
use App\Models\SeniorPerformanceTarget;
use App\Models\StudentAppointment;
use App\Models\StudentAssignment;
use App\Support\CsvExportHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeniorPerformanceController extends Controller
{
    private function seniorEmail(Request $request): string
    {
        return strtolower((string) ($request->user()?->email ?? ''));
    }

    private function assignedStudentIds(Request $request): Collection
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        return StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function sidebarStats(Request $request): array
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        $base = StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('lower(senior_email) = ?', [$email]);
        $studentIds = (clone $base)->pluck('student_id')->filter()->unique();
        $today = now()->toDateString();

        return [
            'active_students' => (int) (clone $base)->where('is_archived', false)->count(),
            'pending_guests' => (int) GuestApplication::query()
                ->whereIn('converted_student_id', $studentIds->all())
                ->where('converted_to_student', false)
                ->count(),
            'today_tasks' => (int) MarketingTask::query()
                ->where('assigned_user_id', (int) optional($request->user())->id)
                ->whereNotIn('status', ['done', 'cancelled'])
                ->whereDate('due_date', $today)
                ->count(),
            'today_appointments' => (int) StudentAppointment::query()
                ->whereRaw('lower(senior_email) = ?', [$email])
                ->whereDate('scheduled_at', $today)
                ->count(),
        ];
    }

    private function buildPerformanceReportData(Request $request): array
    {
        $email = $this->seniorEmail($request);
        $studentIds = $this->assignedStudentIds($request);
        $ids = $studentIds->all();

        $assignments = StudentAssignment::query()
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->get(['student_id', 'is_archived', 'risk_level', 'payment_status', 'updated_at']);

        $totalStudents = (int) $assignments->count();
        $activeStudents = (int) $assignments->where('is_archived', false)->count();
        $archivedStudents = (int) $assignments->where('is_archived', true)->count();

        $riskBreakdown = [
            'low' => (int) $assignments->where('risk_level', 'low')->count(),
            'medium' => (int) $assignments->where('risk_level', 'medium')->count(),
            'high' => (int) $assignments->where('risk_level', 'high')->count(),
            'critical' => (int) $assignments->where('risk_level', 'critical')->count(),
        ];

        $outcomeCount = empty($ids) ? 0
            : ProcessOutcome::query()->whereIn('student_id', $ids)->count();
        $outcomeThisMonth = empty($ids) ? 0
            : ProcessOutcome::query()
                ->whereIn('student_id', $ids)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

        $pendingDocApprovals = empty($ids) ? 0
            : Document::query()
                ->whereIn('student_id', $ids)
                ->where('status', 'uploaded')
                ->count();

        $pendingAppointments = empty($ids) ? 0
            : StudentAppointment::query()
                ->whereIn('student_id', $ids)
                ->where('status', 'pending')
                ->count();

        $guestCount = (int) GuestApplication::query()
            ->whereRaw('lower(assigned_senior_email) = ?', [$email])
            ->count();
        $guestConverted = (int) GuestApplication::query()
            ->whereRaw('lower(assigned_senior_email) = ?', [$email])
            ->where('converted_to_student', true)
            ->count();

        $conversionRate = $guestCount > 0
            ? (int) round(($guestConverted / $guestCount) * 100)
            : 0;

        $outcomeByStep = empty($ids) ? collect() : ProcessOutcome::query()
            ->whereIn('student_id', $ids)
            ->selectRaw('process_step, count(*) as cnt')
            ->groupBy('process_step')
            ->orderByDesc('cnt')
            ->get();

        // ── Yeni Analitik Veriler ────────────────────────────────────────────
        $perfStats = app(\App\Services\SeniorPerformanceService::class)->getMyStats($email);

        // Funnel: sözleşme imzalayanlar
        $contractSigned = (int) GuestApplication::query()
            ->whereRaw('lower(assigned_senior_email) = ?', [$email])
            ->whereIn('contract_status', ['signed_uploaded', 'approved'])
            ->count();

        return [
            'totalStudents' => $totalStudents,
            'activeStudents' => $activeStudents,
            'archivedStudents' => $archivedStudents,
            'riskBreakdown' => $riskBreakdown,
            'outcomeCount' => $outcomeCount,
            'outcomeThisMonth' => $outcomeThisMonth,
            'pendingDocApprovals' => $pendingDocApprovals,
            'pendingAppointments' => $pendingAppointments,
            'guestCount' => $guestCount,
            'guestConverted' => $guestConverted,
            'conversionRate' => $conversionRate,
            'contractSigned' => $contractSigned,
            'outcomeByStep' => $outcomeByStep,
            'reportGeneratedAt' => now()->format('Y-m-d H:i:s'),
            'reportSeniorEmail' => $email,
            // Performans servisi verileri
            'uniAccepted' => $perfStats['uni_accepted'],
            'uniRejected' => $perfStats['uni_rejected'],
            'uniTotal' => $perfStats['uni_total'],
            'uniAcceptanceRate' => $perfStats['uni_acceptance_rate'],
            'visaApproved' => $perfStats['visa_approved'],
            'avgProcessDays' => $perfStats['avg_process_days'],
            'systemAvgDays' => $perfStats['system_avg_days'],
        ];
    }

    public function performance(Request $request)
    {
        $email    = $this->seniorEmail($request);
        $period   = now()->format('Y-m');
        $perfData = $this->buildPerformanceReportData($request);

        // 2.5 Performance Target vs Actual
        $target = SeniorPerformanceTarget::where('senior_email', $email)
            ->where('period', $period)->first();

        $ids = $this->assignedStudentIds($request)->all();

        $docsReviewedThisMonth = empty($ids) ? 0 : (int) Document::query()
            ->whereIn('student_id', $ids)
            ->whereIn('status', ['approved', 'rejected'])
            ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $appointmentsThisMonth = (int) StudentAppointment::query()
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $performanceTarget = $target ? [
            'conversions'  => ['target' => $target->target_conversions,  'actual' => $perfData['guestConverted']],
            'outcomes'     => ['target' => $target->target_outcomes,     'actual' => $perfData['outcomeThisMonth']],
            'doc_reviews'  => ['target' => $target->target_doc_reviews,  'actual' => $docsReviewedThisMonth],
            'appointments' => ['target' => $target->target_appointments, 'actual' => $appointmentsThisMonth],
        ] : null;

        $performanceTrend = collect(range(5, 0))->map(function ($monthsAgo) use ($email) {
            $date     = now()->subMonths($monthsAgo);
            $p        = $date->format('Y-m');
            $snapshot = SeniorPerformanceSnapshot::where('senior_email', $email)->where('period', $p)->first();
            return [
                'period'      => $p,
                'label'       => $date->format('M Y'),
                'conversions' => $snapshot->total_conversions ?? 0,
                'outcomes'    => $snapshot->total_outcomes ?? 0,
            ];
        })->values();

        return view('senior.performance', array_merge($perfData, [
            'sidebarStats'      => $this->sidebarStats($request),
            'performanceTarget' => $performanceTarget,
            'performanceTrend'  => $performanceTrend,
            'currentPeriod'     => $period,
        ]));
    }

    public function performanceReportPrint(Request $request)
    {
        return view('senior.performance-report-print', $this->buildPerformanceReportData($request));
    }

    public function performanceReportCsv(Request $request): StreamedResponse
    {
        $data = $this->buildPerformanceReportData($request);
        $filename = 'senior-performance-report-'.now()->format('Ymd_His').'.csv';

        return CsvExportHelper::download($filename, function ($out) use ($data): void {
            fputcsv($out, ['section', 'metric', 'value']);
            fputcsv($out, ['meta', 'generated_at', (string) ($data['reportGeneratedAt'] ?? '')]);
            fputcsv($out, ['meta', 'senior_email', (string) ($data['reportSeniorEmail'] ?? '')]);

            fputcsv($out, ['kpi', 'total_students', (string) ($data['totalStudents'] ?? 0)]);
            fputcsv($out, ['kpi', 'active_students', (string) ($data['activeStudents'] ?? 0)]);
            fputcsv($out, ['kpi', 'archived_students', (string) ($data['archivedStudents'] ?? 0)]);
            fputcsv($out, ['kpi', 'outcome_total', (string) ($data['outcomeCount'] ?? 0)]);
            fputcsv($out, ['kpi', 'outcome_this_month', (string) ($data['outcomeThisMonth'] ?? 0)]);
            fputcsv($out, ['kpi', 'pending_doc_approvals', (string) ($data['pendingDocApprovals'] ?? 0)]);
            fputcsv($out, ['kpi', 'pending_appointments', (string) ($data['pendingAppointments'] ?? 0)]);
            fputcsv($out, ['kpi', 'guest_count', (string) ($data['guestCount'] ?? 0)]);
            fputcsv($out, ['kpi', 'guest_converted', (string) ($data['guestConverted'] ?? 0)]);
            fputcsv($out, ['kpi', 'conversion_rate_percent', (string) ($data['conversionRate'] ?? 0)]);

            foreach ((array) ($data['riskBreakdown'] ?? []) as $risk => $count) {
                fputcsv($out, ['risk', (string) $risk, (string) $count]);
            }

            foreach (($data['outcomeByStep'] ?? collect()) as $row) {
                fputcsv($out, ['outcome_by_step', (string) ($row->process_step ?? '-'), (string) ($row->cnt ?? 0)]);
            }
        });
    }
}
