<?php

namespace App\Services;

use App\Models\FieldRuleApproval;
use App\Models\GuestApplication;
use App\Models\ManagerReport;
use App\Models\ProcessOutcome;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class DashboardPayloadService
{
    public function __construct(
        private readonly DashboardKPIService $kpi,
        private readonly StaffKpiService $staffKpi,
    ) {}

    public function build(Carbon $monthStart, Carbon $monthEnd, string $selectedSenior, array $snapshotFilters = []): array
    {
        $now = Carbon::now();

        // ── Cached KPIs (DashboardKPIService — 5 dakika TTL) ─────────────────
        ['stats' => $stats, 'funnel' => $funnel] = $this->kpi->managerStatsAndFunnel($monthStart, $monthEnd, $selectedSenior);
        $seniorPerformance  = $this->kpi->managerSeniorPerformance($monthStart, $monthEnd, $selectedSenior);
        $trend              = $this->kpi->managerTrend($monthStart, $monthEnd);
        $seniors            = $this->kpi->managerSeniors($selectedSenior);
        ['taskOverview' => $taskOverview, 'taskDepartmentOverview' => $taskDepartmentOverview] = $this->kpi->managerTaskOverview();
        $messageOverview    = $this->kpi->managerMessageOverview();
        $previewSuggestions = $this->kpi->managerPreviewSuggestions($seniors);

        // ── Non-cached: gerçek zamanlı listeler ──────────────────────────────
        $pendingApprovals = FieldRuleApproval::query()
            ->where('status', 'pending')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->latest()
            ->limit(8)
            ->get(['id', 'rule_id', 'student_id', 'triggered_field', 'created_at']);

        $pendingContracts = GuestApplication::query()
            ->whereIn('contract_status', ['requested', 'signed_uploaded'])
            ->latest('contract_requested_at')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'contract_status', 'contract_requested_at', 'assigned_senior_email']);

        $overdueOutcomesQuery = ProcessOutcome::query()
            ->whereNotNull('deadline')
            ->where('deadline', '<', $now)
            ->where('is_visible_to_student', false)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->latest('deadline');
        if ($selectedSenior !== '') {
            $overdueOutcomesQuery->where('added_by', $selectedSenior);
        }
        $overdueOutcomes = $overdueOutcomesQuery
            ->limit(8)
            ->get(['id', 'student_id', 'process_step', 'outcome_type', 'deadline', 'added_by']);

        // ── Açık Lise Uyarısı ────────────────────────────────────────────────
        $acikLiseGuests = GuestApplication::query()
            ->where('registration_form_draft->high_school_type', 'acik_lise')
            ->whereNull('archived_at')
            ->latest()
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'lead_status', 'created_at', 'assigned_senior_email']);

        // ── Presets (statik hesaplama) ────────────────────────────────────────
        $presets = [
            'this_month' => [
                'label'      => 'Bu Ay',
                'start_date' => $now->copy()->startOfMonth()->toDateString(),
                'end_date'   => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_30_days' => [
                'label'      => 'Son 30 Gun',
                'start_date' => $now->copy()->subDays(29)->toDateString(),
                'end_date'   => $now->copy()->toDateString(),
            ],
            'this_quarter' => [
                'label'      => 'Bu Ceyrek',
                'start_date' => $now->copy()->firstOfQuarter()->toDateString(),
                'end_date'   => $now->copy()->lastOfQuarter()->toDateString(),
            ],
        ];

        // ── Snapshot raporları (paginated + filtreli — cache uygun değil) ────
        $snapshotType       = (string) ($snapshotFilters['snapshot_type'] ?? '');
        $snapshotStart      = (string) ($snapshotFilters['snapshot_start'] ?? '');
        $snapshotEnd        = (string) ($snapshotFilters['snapshot_end'] ?? '');
        $snapshotSendStatus = (string) ($snapshotFilters['snapshot_send_status'] ?? '');

        $recentReportsQuery = ManagerReport::query();
        if ($snapshotType !== '') {
            $recentReportsQuery->where('report_type', $snapshotType);
        }
        if ($snapshotStart !== '') {
            $recentReportsQuery->whereDate('period_start', '>=', $snapshotStart);
        }
        if ($snapshotEnd !== '') {
            $recentReportsQuery->whereDate('period_end', '<=', $snapshotEnd);
        }
        if ($snapshotSendStatus !== '') {
            $recentReportsQuery->where('send_status', $snapshotSendStatus);
        }
        $recentReports = $recentReportsQuery
            ->latest()
            ->paginate(10, ['id', 'report_type', 'period_start', 'period_end', 'senior_email', 'sent_to', 'send_status', 'sent_at', 'created_by', 'created_at'])
            ->withQueryString();

        // ── Staff Metrics (bu ay) ─────────────────────────────────────────────
        $staffRoles = [
            'system_admin', 'system_staff',
            'operations_admin', 'operations_staff',
            'finance_admin', 'finance_staff',
            'marketing_admin', 'marketing_staff',
            'sales_admin', 'sales_staff',
        ];
        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $allStaff = User::whereIn('role', $staffRoles)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->get(['id', 'name', 'email', 'role', 'is_active']);

        $staffPeriod   = now()->format('Y-m');
        $staffUserIds  = $allStaff->pluck('id')->all();
        $staffActuals  = $this->staffKpi->getAllActuals($staffPeriod, $staffUserIds);
        $staffTargets  = $this->staffKpi->getAllTargets($staffPeriod, $staffUserIds);

        $staffRows = $allStaff->map(function ($s) use ($staffActuals, $staffTargets) {
            $actuals = $staffActuals[$s->id] ?? ['tasks_done' => 0, 'tickets_resolved' => 0, 'hours_logged' => 0.0];
            $target  = $staffTargets[$s->id] ?? null;
            return (object) ['user' => $s, 'actuals' => $actuals, 'score' => $this->staffKpi->calcScore($actuals, $target)];
        })->sortByDesc('score')->values();

        $staffMetrics = [
            'total'         => $allStaff->count(),
            'active'        => $allStaff->where('is_active', true)->count(),
            'passive'       => $allStaff->where('is_active', false)->count(),
            'period'        => $staffPeriod,
            'total_tasks'   => $staffRows->sum(fn ($r) => $r->actuals['tasks_done']),
            'total_tickets' => $staffRows->sum(fn ($r) => $r->actuals['tickets_resolved']),
            'total_hours'   => $staffRows->sum(fn ($r) => $r->actuals['hours_logged']),
            'avg_score'     => $staffRows->avg('score') ?? 0,
            'top3'          => $staffRows->take(3)->values(),
        ];

        // ── Ops log durumu (dosya okuması — cache uygun değil) ───────────────
        $opsStatus = [
            'scheduler'      => $this->buildLogStatus('scheduler.log',       'Running [',                    3),
            'mvp_smoke'      => $this->buildLogStatus('mvp-smoke.log',       'MVP smoke SONUC:',            30),
            'api_regression' => $this->buildLogStatus('api-regression.log',  'API regression smoke SONUC:', 30),
            'self_heal'      => $this->buildLogStatus('self-heal.log',       'ops:self-heal SONUC:',        30),
            'critical_check' => $this->buildLogStatus('critical-check.log',  'ops:critical-check SONUC:',  30),
        ];

        $riskyStudents = $this->kpi->riskyStudents($cid, 5);

        // ── Manager Analytics (audit gap fix) ────────────────────────────────
        $managerAnalytics = [];
        try {
            // Dealer risk skoru — son 30 gün aktivitesiz dealer'lar
            $dealerRisk = \App\Models\Dealer::where('is_active', true)->where('is_archived', false)
                ->get(['code', 'name', 'dealer_type_code', 'created_at'])
                ->map(function ($d) {
                    $lastLead = \App\Models\GuestApplication::where('dealer_code', $d->code)->latest()->value('created_at');
                    $daysSince = $lastLead ? (int) $lastLead->diffInDays(now()) : (int) $d->created_at->diffInDays(now());
                    $totalLeads = \App\Models\GuestApplication::where('dealer_code', $d->code)->count();
                    $convertedLeads = \App\Models\GuestApplication::where('dealer_code', $d->code)
                        ->where(fn ($q) => $q->whereNotNull('converted_student_id')->orWhere('lead_status', 'converted'))->count();
                    return [
                        'code' => $d->code, 'name' => $d->name, 'type' => $d->dealer_type_code,
                        'days_inactive' => $daysSince, 'total_leads' => $totalLeads, 'converted' => $convertedLeads,
                        'risk' => $daysSince > 30 ? 'high' : ($daysSince > 14 ? 'medium' : 'low'),
                    ];
                })->sortByDesc('days_inactive')->values()->all();
            $managerAnalytics['dealerRisk'] = $dealerRisk;

            // Gelir tahmini (basit projeksiyon — son 3 ay ortalaması × 3)
            $revenueHistory = [];
            for ($m = 2; $m >= 0; $m--) {
                $ms = now()->subMonths($m)->startOfMonth();
                $me = now()->subMonths($m)->endOfMonth();
                $earned = (float) \App\Models\DealerStudentRevenue::whereBetween('updated_at', [$ms, $me])->sum('total_earned');
                $revenueHistory[] = $earned;
            }
            $avgMonthly = count($revenueHistory) > 0 ? array_sum($revenueHistory) / count($revenueHistory) : 0;
            $managerAnalytics['revenueForecast'] = [
                'last3months' => $revenueHistory,
                'avg_monthly' => round($avgMonthly, 2),
                'forecast_90d' => round($avgMonthly * 3, 2),
            ];

            // Toplam platform metrikleri
            $managerAnalytics['platformTotals'] = [
                'total_guests'   => \App\Models\GuestApplication::count(),
                'total_students' => \App\Models\GuestApplication::whereNotNull('converted_student_id')->count(),
                'total_dealers'  => \App\Models\Dealer::where('is_active', true)->count(),
                'total_seniors'  => User::where('role', 'senior')->where('is_active', true)->count(),
                'total_revenue'  => round((float) \App\Models\DealerStudentRevenue::sum('total_earned'), 2),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Manager analytics failed', ['error' => $e->getMessage()]);
        }

        return [
            'stats'                  => $stats,
            'funnel'                 => $funnel,
            'seniorPerformance'      => $seniorPerformance,
            'filters'                => [
                'start_date'   => $monthStart->toDateString(),
                'end_date'     => $monthEnd->toDateString(),
                'senior_email' => $selectedSenior,
            ],
            'seniors'                => $seniors,
            'pendingApprovals'       => $pendingApprovals,
            'pendingContracts'       => $pendingContracts,
            'overdueOutcomes'        => $overdueOutcomes,
            'trend'                  => $trend,
            'opsStatus'              => $opsStatus,
            'previewSuggestions'     => $previewSuggestions,
            'recentReports'          => $recentReports,
            'presets'                => $presets,
            'taskOverview'           => $taskOverview,
            'taskDepartmentOverview' => $taskDepartmentOverview,
            'acikLiseGuests'         => $acikLiseGuests,
            'messageOverview'        => $messageOverview,
            'snapshotFilters'        => [
                'snapshot_type'        => $snapshotType,
                'snapshot_start'       => $snapshotStart,
                'snapshot_end'         => $snapshotEnd,
                'snapshot_send_status' => $snapshotSendStatus,
            ],
            'staffMetrics'           => $staffMetrics,
            'riskyStudents'          => $riskyStudents,
            'managerAnalytics'       => $managerAnalytics,
        ];
    }

    private function buildLogStatus(string $filename, string $resultMarker, int $staleThresholdHours): array
    {
        $path = storage_path('logs/' . $filename);
        if (! File::exists($path)) {
            return [
                'exists'      => false,
                'last_at'     => null,
                'age_hours'   => null,
                'is_stale'    => true,
                'last_result' => null,
                'is_fail'     => false,
            ];
        }

        $lastModified = Carbon::createFromTimestamp(File::lastModified($path));
        $ageHours     = $lastModified->diffInHours(now());
        $lines        = preg_split('/\r\n|\r|\n/', (string) File::get($path)) ?: [];
        $tail         = array_slice($lines, -80);

        $lastResult = null;
        foreach (array_reverse($tail) as $line) {
            if (str_contains($line, $resultMarker . ' PASS')) {
                $lastResult = 'PASS';
                break;
            }
            if (str_contains($line, $resultMarker . ' FAIL')) {
                $lastResult = 'FAIL';
                break;
            }
        }

        return [
            'exists'      => true,
            'last_at'     => $lastModified->toDateTimeString(),
            'age_hours'   => $ageHours,
            'is_stale'    => $ageHours >= $staleThresholdHours,
            'last_result' => $lastResult,
            'is_fail'     => $lastResult === 'FAIL',
        ];
    }
}
