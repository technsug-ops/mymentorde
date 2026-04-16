<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\ManagerDashboardTrait;
use App\Models\ConsentRecord;
use App\Models\DataRetentionPolicy;
use App\Models\DealerStudentRevenue;
use App\Models\GuestApplication;
use App\Models\GuestFeedback;
use App\Models\ManagerRequest;
use App\Models\NotificationDispatch;
use App\Models\ScheduledNotification;
use App\Models\StudentFeedback;
use App\Models\StudentRevenue;
use App\Models\SystemEventLog;
use App\Services\DashboardKPIService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagerAnalyticsController extends Controller
{
    use ManagerDashboardTrait;

    public function __construct(private readonly DashboardKPIService $kpi) {}

    public function revenueAnalytics(Request $request)
    {
        [$start, $end, $senior] = $this->resolveFilters($request);

        // Paket bazlı gelir
        $byPackage = StudentRevenue::query()
            ->join('guest_applications', 'student_revenues.student_id', '=', 'guest_applications.converted_student_id')
            ->whereBetween('student_revenues.updated_at', [$start, $end])
            ->selectRaw('guest_applications.selected_package_code as package,
                SUM(student_revenues.total_earned) as earned,
                SUM(student_revenues.total_pending) as pending,
                COUNT(*) as student_count')
            ->groupBy('guest_applications.selected_package_code')
            ->get();

        // Senior bazlı gelir
        $bySenior = StudentRevenue::query()
            ->join('student_assignments', 'student_revenues.student_id', '=', 'student_assignments.student_id')
            ->whereBetween('student_revenues.updated_at', [$start, $end])
            ->selectRaw('student_assignments.senior_email,
                SUM(student_revenues.total_earned) as earned,
                SUM(student_revenues.total_pending) as pending,
                COUNT(*) as student_count')
            ->groupBy('student_assignments.senior_email')
            ->get();

        // Aylık trend (son 12 ay)
        $monthlyTrend = collect(range(11, 0))->map(function (int $ago) {
            $m = now()->subMonths($ago);
            return [
                'month'   => $m->format('Y-m'),
                'label'   => $m->format('M Y'),
                'earned'  => (float) StudentRevenue::whereBetween('updated_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('total_earned'),
                'pending' => (float) StudentRevenue::whereBetween('updated_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('total_pending'),
            ];
        });

        $totalEarned       = (float) StudentRevenue::whereBetween('updated_at', [$start, $end])->sum('total_earned');
        $totalPending      = (float) StudentRevenue::whereBetween('updated_at', [$start, $end])->sum('total_pending');
        $totalPackagePrice = (float) StudentRevenue::whereBetween('updated_at', [$start, $end])->sum('package_total_price');
        $collectionRate    = $totalPackagePrice > 0 ? round($totalEarned / $totalPackagePrice * 100, 1) : 0;

        $dealerCommissions = DealerStudentRevenue::whereBetween('created_at', [$start, $end])
            ->selectRaw('SUM(total_earned) as total_commission, COUNT(DISTINCT dealer_id) as dealer_count')
            ->first();

        return view('manager.revenue-analytics', [
            'byPackage'         => $byPackage,
            'bySenior'          => $bySenior,
            'monthlyTrend'      => $monthlyTrend,
            'collectionRate'    => $collectionRate,
            'totalEarned'       => $totalEarned,
            'totalPending'      => $totalPending,
            'totalPackagePrice' => $totalPackagePrice,
            'dealerCommissions' => $dealerCommissions,
            'filters'           => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'senior_email' => $senior],
        ]);
    }

    public function notificationStats(Request $request)
    {
        $stats = [
            'total_sent_30d'   => NotificationDispatch::where('status', 'sent')->where('sent_at', '>=', now()->subDays(30))->count(),
            'total_failed_30d' => NotificationDispatch::where('status', 'failed')->where('created_at', '>=', now()->subDays(30))->count(),
            'pending'          => NotificationDispatch::where('status', 'pending')->count(),
            'by_channel'       => NotificationDispatch::where('created_at', '>=', now()->subDays(30))
                ->selectRaw('channel, status, COUNT(*) as cnt')->groupBy('channel', 'status')->get(),
            'by_category'      => NotificationDispatch::where('created_at', '>=', now()->subDays(30))
                ->selectRaw('category, COUNT(*) as cnt')->groupBy('category')->orderByDesc('cnt')->limit(10)->get(),
            'scheduled_active' => ScheduledNotification::where('is_active', true)->count(),
        ];

        if ($request->expectsJson()) {
            return response()->json($stats);
        }

        return view('manager.notification-stats', compact('stats'));
    }

    public function seniorPerformance(Request $request)
    {
        [$start, $end] = $this->resolveFilters($request);

        $seniors = \App\Models\User::where('role', 'senior')->orderBy('name')->get(['id', 'email', 'name']);
        $seniorEmails = $seniors->pluck('email')->all();

        // Per senior metrics
        $rows = $seniors->map(function ($senior) use ($start, $end) {
            $email = (string) $senior->email;

            $leads = GuestApplication::where('assigned_senior_email', $email)
                ->whereBetween('created_at', [$start, $end])
                ->get(['id', 'lead_status', 'converted_student_id', 'created_at', 'converted_at']);

            $leadCount    = $leads->count();
            $converted    = $leads->where('lead_status', 'converted')->count();
            $convPct      = $leadCount > 0 ? round($converted / $leadCount * 100, 1) : 0;
            $studentIds   = $leads->pluck('converted_student_id')->filter()->values()->all();

            $activeStudents = \App\Models\StudentAssignment::where('senior_email', $email)
                ->where('is_archived', false)
                ->count();

            $revenue = empty($studentIds) ? 0
                : (float) StudentRevenue::whereIn('student_id', $studentIds)->sum('total_earned');

            // Feedback (senior-type OR general) for this senior's students
            $feedbackStudentIds = \App\Models\StudentAssignment::where('senior_email', $email)
                ->pluck('student_id')->all();
            $feedbackRating = 0;
            $feedbackCount = 0;
            if (!empty($feedbackStudentIds)) {
                $fb = StudentFeedback::whereIn('student_id', $feedbackStudentIds)
                    ->whereIn('feedback_type', ['senior', 'general'])
                    ->where('rating', '>', 0)
                    ->whereBetween('created_at', [$start, $end])
                    ->get(['rating']);
                $feedbackCount = $fb->count();
                $feedbackRating = $feedbackCount > 0 ? round($fb->avg('rating'), 2) : 0;
            }

            $tasks = \App\Models\MarketingTask::where('assigned_user_id', $senior->id)
                ->whereBetween('created_at', [$start, $end])
                ->get(['status', 'due_date', 'completed_at']);
            $tasksTotal = $tasks->count();
            $tasksDone  = $tasks->where('status', 'done')->count();
            $tasksOverdue = $tasks->filter(fn ($t) => $t->status !== 'done' && $t->status !== 'cancelled'
                && $t->due_date && \Carbon\Carbon::parse($t->due_date)->lt(now()))->count();
            $taskCompletionPct = $tasksTotal > 0 ? round($tasksDone / $tasksTotal * 100, 1) : 0;

            // Avg days to convert (for this senior's converted leads)
            $avgDays = 0;
            $convertedLeads = $leads->filter(fn ($g) => $g->converted_at && $g->created_at);
            if ($convertedLeads->isNotEmpty()) {
                $avgDays = round($convertedLeads->avg(fn ($g) => \Carbon\Carbon::parse($g->created_at)->diffInDays(\Carbon\Carbon::parse($g->converted_at))), 1);
            }

            // Composite score (0-100): 40% conversion + 30% feedback + 30% task completion
            $score = round(($convPct * 0.4) + ($feedbackRating * 20 * 0.3) + ($taskCompletionPct * 0.3), 1);

            return [
                'id'              => $senior->id,
                'name'            => $senior->name,
                'email'           => $email,
                'leadCount'       => $leadCount,
                'converted'       => $converted,
                'convPct'         => $convPct,
                'activeStudents'  => $activeStudents,
                'revenue'         => $revenue,
                'feedbackRating'  => $feedbackRating,
                'feedbackCount'   => $feedbackCount,
                'tasksTotal'      => $tasksTotal,
                'tasksDone'       => $tasksDone,
                'tasksOverdue'    => $tasksOverdue,
                'taskCompletionPct' => $taskCompletionPct,
                'avgDaysToConvert' => $avgDays,
                'score'           => $score,
            ];
        })->sortByDesc('score')->values();

        // Aggregate KPIs
        $totalSeniors = $rows->count();
        $avgConvPct = $rows->where('leadCount', '>', 0)->avg('convPct') ?: 0;
        $totalRevenueAll = $rows->sum('revenue');
        $topPerformer = $rows->first();

        return view('manager.senior-performance', [
            'rows'         => $rows,
            'totalSeniors' => $totalSeniors,
            'avgConvPct'   => round($avgConvPct, 1),
            'totalRevenue' => $totalRevenueAll,
            'topPerformer' => $topPerformer,
            'filters'      => [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
            ],
        ]);
    }

    public function ticketAnalytics(Request $request)
    {
        [$start, $end] = $this->resolveFilters($request);
        $department = $request->query('department', 'all');
        $priority = $request->query('priority', 'all');

        $query = \App\Models\GuestTicket::whereBetween('created_at', [$start, $end]);
        if ($department !== 'all') {
            $query->where('department', $department);
        }
        if ($priority !== 'all') {
            $query->where('priority', $priority);
        }
        $all = $query->get([
            'id', 'subject', 'status', 'priority', 'department', 'assigned_user_id',
            'created_at', 'first_response_at', 'closed_at', 'sla_due_at', 'sla_hours',
        ]);

        // KPIs
        $total = $all->count();
        $openCount = $all->whereIn('status', ['open', 'in_progress'])->count();
        $resolvedCount = $all->whereIn('status', ['resolved', 'closed'])->count();

        // Avg first response (hours)
        $responded = $all->filter(fn ($t) => $t->first_response_at && $t->created_at);
        $avgFirstResponseH = $responded->count() > 0
            ? round($responded->avg(fn ($t) => \Carbon\Carbon::parse($t->created_at)->diffInHours(\Carbon\Carbon::parse($t->first_response_at), false)), 1)
            : 0;

        // Avg resolution time (days)
        $closed = $all->filter(fn ($t) => $t->closed_at && $t->created_at);
        $avgResolutionDays = $closed->count() > 0
            ? round($closed->avg(fn ($t) => \Carbon\Carbon::parse($t->created_at)->diffInDays(\Carbon\Carbon::parse($t->closed_at), false)), 1)
            : 0;

        // SLA breach
        $slaItems = $all->filter(fn ($t) => !empty($t->sla_due_at));
        $slaBreach = $slaItems->filter(fn ($t) => $t->closed_at
            ? \Carbon\Carbon::parse($t->closed_at)->gt(\Carbon\Carbon::parse($t->sla_due_at))
            : \Carbon\Carbon::parse($t->sla_due_at)->lt(now()))->count();
        $slaBreachPct = $slaItems->count() > 0 ? round($slaBreach / $slaItems->count() * 100, 1) : 0;

        // Breakdowns
        $byStatus = $all->groupBy('status')->map->count()->sortDesc();
        $byPriority = $all->groupBy('priority')->map->count()->sortDesc();
        $byDept = $all->filter(fn ($t) => !empty($t->department))->groupBy('department')->map->count()->sortDesc();

        // By assignee
        $assignedIds = $all->pluck('assigned_user_id')->filter()->unique()->values()->all();
        $userMap = \App\Models\User::whereIn('id', $assignedIds)->pluck('name', 'id');
        $byAssignee = $all->filter(fn ($t) => !empty($t->assigned_user_id))
            ->groupBy('assigned_user_id')
            ->map(function ($grp, $uid) use ($userMap) {
                $closed = $grp->filter(fn ($t) => $t->closed_at);
                return [
                    'name'      => $userMap[$uid] ?? ('User #' . $uid),
                    'total'     => $grp->count(),
                    'open'      => $grp->whereIn('status', ['open', 'in_progress'])->count(),
                    'resolved'  => $grp->whereIn('status', ['resolved', 'closed'])->count(),
                    'avgRespH'  => $grp->filter(fn ($t) => $t->first_response_at && $t->created_at)->count() > 0
                        ? round($grp->filter(fn ($t) => $t->first_response_at && $t->created_at)
                            ->avg(fn ($t) => \Carbon\Carbon::parse($t->created_at)->diffInHours(\Carbon\Carbon::parse($t->first_response_at), false)), 1)
                        : 0,
                ];
            })
            ->sortByDesc('total');

        // Daily trend
        $trendDays = collect(range(29, 0))->map(function (int $ago) use ($all) {
            $d = now()->subDays($ago);
            return [
                'label' => $d->format('d.m'),
                'count' => $all->filter(fn ($t) => optional($t->created_at)->isSameDay($d))->count(),
            ];
        });

        // Recent tickets
        $recent = $all->sortByDesc('created_at')->take(15)->values()
            ->map(fn ($t) => (object) [
                'id'        => $t->id,
                'subject'   => $t->subject,
                'status'    => $t->status,
                'priority'  => $t->priority,
                'created_at'=> $t->created_at,
                'assignee'  => $t->assigned_user_id ? ($userMap[$t->assigned_user_id] ?? '-') : null,
            ]);

        $departmentOptions = \App\Models\GuestTicket::distinct()->whereNotNull('department')->pluck('department')->values();

        return view('manager.ticket-analytics', [
            'total'              => $total,
            'openCount'          => $openCount,
            'resolvedCount'      => $resolvedCount,
            'avgFirstResponseH'  => $avgFirstResponseH,
            'avgResolutionDays'  => $avgResolutionDays,
            'slaBreachPct'       => $slaBreachPct,
            'slaBreach'          => $slaBreach,
            'slaTotal'           => $slaItems->count(),
            'byStatus'           => $byStatus,
            'byPriority'         => $byPriority,
            'byDept'             => $byDept,
            'byAssignee'         => $byAssignee,
            'trendDays'          => $trendDays,
            'recent'             => $recent,
            'departmentOptions'  => $departmentOptions,
            'filters'            => [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
                'department' => $department,
                'priority'   => $priority,
            ],
        ]);
    }

    public function conversionFunnel(Request $request)
    {
        [$start, $end] = $this->resolveFilters($request);
        $sourceFilter = $request->query('source', 'all');

        // Stage mapping: her lead_status kendi level'ine atanır.
        // Daha ilerideki level kullanıcı, öncekileri de "geçmiştir" varsayımı (cumulative).
        $statusToLevel = [
            'new'               => 1,
            'contacted'         => 1,
            'evaluating'        => 2,
            'meeting_scheduled' => 2,
            'in_progress'       => 3,
            'docs_pending'      => 3,
            'contract_signed'   => 4,
            'converted'         => 5,
        ];
        $stageDef = [
            1 => ['key' => 'lead',      'label' => 'Yeni Lead',    'icon' => '👤', 'color' => '#3b82f6'],
            2 => ['key' => 'qualified', 'label' => 'Nitelikli',    'icon' => '✅', 'color' => '#8b5cf6'],
            3 => ['key' => 'inproc',    'label' => 'Süreç İçinde', 'icon' => '⚙️', 'color' => '#f59e0b'],
            4 => ['key' => 'signed',    'label' => 'Sözleşme',     'icon' => '📝', 'color' => '#06b6d4'],
            5 => ['key' => 'student',   'label' => 'Öğrenci',      'icon' => '🎓', 'color' => '#16a34a'],
            6 => ['key' => 'paid',      'label' => 'Ödeme Aktif',  'icon' => '💶', 'color' => '#0891b2'],
        ];

        $query = GuestApplication::whereBetween('created_at', [$start, $end]);
        if ($sourceFilter !== 'all') {
            $query->where('lead_source', $sourceFilter);
        }
        $all = $query->get([
            'id', 'lead_status', 'lead_source', 'lost_reason', 'lost_note',
            'converted_student_id', 'created_at', 'converted_at', 'assigned_senior_email',
        ]);

        // ── Funnel stage counts (cumulative) ──────────────────────────────
        $stageCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
        $studentIds = [];
        foreach ($all as $g) {
            $lvl = $statusToLevel[$g->lead_status] ?? 0;
            if ($lvl === 0) {
                continue;
            }
            for ($i = 1; $i <= $lvl; $i++) {
                $stageCounts[$i]++;
            }
            if ($g->converted_student_id) {
                $studentIds[] = $g->converted_student_id;
            }
        }
        $stageCounts[6] = empty($studentIds) ? 0 : StudentRevenue::whereIn('student_id', $studentIds)
            ->where('total_earned', '>', 0)
            ->distinct('student_id')
            ->count('student_id');

        $topCount = $stageCounts[1] ?: 1;
        $funnel = [];
        foreach ($stageDef as $level => $def) {
            $count = $stageCounts[$level] ?? 0;
            $pctTotal = $topCount > 0 ? round($count / $topCount * 100, 1) : 0;
            $prevCount = $level > 1 ? ($stageCounts[$level - 1] ?? 0) : $topCount;
            $pctStep = $prevCount > 0 ? round($count / $prevCount * 100, 1) : 0;
            $funnel[$level] = array_merge($def, [
                'count'    => $count,
                'pctTotal' => $pctTotal,
                'pctStep'  => $pctStep,
            ]);
        }

        $overallConv = $topCount > 0 ? round($stageCounts[5] / $topCount * 100, 1) : 0;

        // ── Source breakdown ──────────────────────────────────────────────
        $bySource = $all->groupBy(fn ($g) => $g->lead_source ?: 'unknown')
            ->map(function ($grp) use ($statusToLevel, $studentIds) {
                $sc = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
                foreach ($grp as $g) {
                    $lvl = $statusToLevel[$g->lead_status] ?? 0;
                    if ($lvl === 0) {
                        continue;
                    }
                    for ($i = 1; $i <= $lvl; $i++) {
                        $sc[$i]++;
                    }
                }
                $total = $grp->count();
                return [
                    'total'      => $total,
                    'converted'  => $sc[5],
                    'convPct'    => $total > 0 ? round($sc[5] / $total * 100, 1) : 0,
                    'stages'     => $sc,
                ];
            })
            ->sortByDesc('total');

        // ── Senior breakdown ──────────────────────────────────────────────
        $bySenior = $all->filter(fn ($g) => !empty($g->assigned_senior_email))
            ->groupBy('assigned_senior_email')
            ->map(function ($grp) use ($statusToLevel) {
                $converted = $grp->where('lead_status', 'converted')->count();
                $total = $grp->count();
                return [
                    'total'     => $total,
                    'converted' => $converted,
                    'convPct'   => $total > 0 ? round($converted / $total * 100, 1) : 0,
                ];
            })
            ->sortByDesc('convPct');

        // ── Lost reasons ──────────────────────────────────────────────────
        $lostReasons = $all->filter(fn ($g) => !empty($g->lost_reason))
            ->groupBy('lost_reason')
            ->map(fn ($grp) => $grp->count())
            ->sortDesc();

        // ── Avg time from new → converted (days) ──────────────────────────
        $convertedItems = $all->filter(fn ($g) => $g->converted_at && $g->created_at);
        $avgDaysToConvert = $convertedItems->count() > 0
            ? round($convertedItems->avg(fn ($g) => \Carbon\Carbon::parse($g->created_at)->diffInDays(\Carbon\Carbon::parse($g->converted_at))), 1)
            : 0;

        // ── Revenue from converted leads (period) ─────────────────────────
        $totalRevenue = empty($studentIds) ? 0 : (float) StudentRevenue::whereIn('student_id', $studentIds)->sum('total_earned');

        // ── Daily new lead trend (last 30d, regardless of filter) ─────────
        $leadTrend = collect(range(29, 0))->map(function (int $ago) {
            $d = now()->subDays($ago);
            return [
                'label' => $d->format('d.m'),
                'count' => GuestApplication::whereDate('created_at', $d->toDateString())->count(),
            ];
        });

        $sourceOptions = GuestApplication::selectRaw('DISTINCT lead_source')
            ->whereNotNull('lead_source')
            ->pluck('lead_source')
            ->values();

        return view('manager.conversion-funnel', [
            'funnel'           => $funnel,
            'stageDef'         => $stageDef,
            'overallConv'      => $overallConv,
            'totalLeads'       => $topCount,
            'totalRevenue'     => $totalRevenue,
            'avgDaysToConvert' => $avgDaysToConvert,
            'bySource'         => $bySource,
            'bySenior'         => $bySenior,
            'lostReasons'      => $lostReasons,
            'leadTrend'        => $leadTrend,
            'sourceOptions'    => $sourceOptions,
            'filters'          => [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
                'source'     => $sourceFilter,
            ],
        ]);
    }

    public function feedbackAnalytics(Request $request)
    {
        [$start, $end, $senior] = $this->resolveFilters($request);
        $source = $request->query('source', 'all');        // all|guest|student
        $typeFilter = $request->query('type', 'all');      // all|genel|süreç|danışman|portal
        $stepFilter = $request->query('step', 'all');      // all|application_prep|...

        $all = $this->collectFeedback($start, $end, $source, $typeFilter, $stepFilter);

        // ── KPI ────────────────────────────────────────────────────────────
        $total = $all->count();
        $avgRating = round((float) $all->where('rating', '>', 0)->avg('rating'), 2);
        $npsScores = $all->where('nps_score', '>=', 0)->where('nps_score', '<=', 10)->pluck('nps_score');
        $promoters  = $npsScores->filter(fn ($s) => $s >= 9)->count();
        $passives   = $npsScores->filter(fn ($s) => $s >= 7 && $s <= 8)->count();
        $detractors = $npsScores->filter(fn ($s) => $s <= 6)->count();
        $npsDen = $npsScores->count();
        $nps = $npsDen > 0 ? round((($promoters - $detractors) / $npsDen) * 100, 1) : 0;
        $commentsCount = $all->filter(fn ($f) => trim((string) ($f->comment ?? '')) !== '')->count();

        // Rating distribution (1..5)
        $ratingDist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($all as $f) {
            $r = (int) ($f->rating ?? 0);
            if ($r >= 1 && $r <= 5) {
                $ratingDist[$r]++;
            }
        }

        // Aggregate by feedback_type
        $byType = $all->groupBy('feedback_type')->map(fn ($g) => [
            'count'     => $g->count(),
            'avgRating' => round((float) $g->where('rating', '>', 0)->avg('rating'), 2),
            'avgNps'    => round((float) $g->where('nps_score', '>=', 0)->avg('nps_score'), 1),
        ])->sortKeys();

        // Aggregate by process_step
        $byStep = $all->filter(fn ($f) => !empty($f->process_step))
            ->groupBy('process_step')
            ->map(fn ($g) => [
                'count'     => $g->count(),
                'avgRating' => round((float) $g->where('rating', '>', 0)->avg('rating'), 2),
                'avgNps'    => round((float) $g->where('nps_score', '>=', 0)->avg('nps_score'), 1),
                'label'     => GuestFeedback::STEP_LABELS[$g->first()->process_step] ?? $g->first()->process_step,
            ])->sortByDesc('count');

        // Daily trend (30 days)
        $trendDays = collect(range(29, 0))->map(function (int $ago) use ($all) {
            $d = now()->subDays($ago);
            $items = $all->filter(fn ($f) => optional($f->created_at)->isSameDay($d));
            return [
                'date'      => $d->format('Y-m-d'),
                'label'     => $d->format('d.m'),
                'count'     => $items->count(),
                'avgRating' => round((float) $items->where('rating', '>', 0)->avg('rating'), 2),
            ];
        });

        // Recent comments (25)
        $recentComments = $all->filter(fn ($f) => trim((string) ($f->comment ?? '')) !== '')
            ->sortByDesc('created_at')
            ->take(25)
            ->values();

        // Lowest-rated steps (improvement priorities)
        $improvementAreas = $byStep->filter(fn ($s) => $s['count'] >= 2)
            ->sortBy('avgRating')
            ->take(3);

        return view('manager.feedback-analytics', [
            'total'            => $total,
            'avgRating'        => $avgRating,
            'nps'              => $nps,
            'promoters'        => $promoters,
            'passives'         => $passives,
            'detractors'       => $detractors,
            'npsDen'           => $npsDen,
            'commentsCount'    => $commentsCount,
            'ratingDist'       => $ratingDist,
            'byType'           => $byType,
            'byStep'           => $byStep,
            'trendDays'        => $trendDays,
            'recentComments'   => $recentComments,
            'improvementAreas' => $improvementAreas,
            'filters'          => [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
                'source'     => $source,
                'type'       => $typeFilter,
                'step'       => $stepFilter,
            ],
            'stepLabels'       => GuestFeedback::STEP_LABELS,
        ]);
    }

    public function feedbackExport(Request $request): StreamedResponse
    {
        [$start, $end] = $this->resolveFilters($request);
        $source = $request->query('source', 'all');
        $typeFilter = $request->query('type', 'all');
        $stepFilter = $request->query('step', 'all');

        $all = $this->collectFeedback($start, $end, $source, $typeFilter, $stepFilter)
            ->sortByDesc('created_at');

        $filename = 'feedback_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($all) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tarih', 'Kaynak', 'Kullanici', 'Tur', 'Sureç Adimi', 'Rating (1-5)', 'NPS (0-10)', 'Yorum']);
            foreach ($all as $f) {
                fputcsv($out, [
                    optional($f->created_at)->format('Y-m-d H:i'),
                    $f->_source ?? '',
                    $f->_owner ?? '',
                    $f->feedback_type ?? '',
                    GuestFeedback::STEP_LABELS[$f->process_step ?? ''] ?? ($f->process_step ?? ''),
                    $f->rating ?? '',
                    $f->nps_score ?? '',
                    trim((string) ($f->comment ?? '')),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function collectFeedback($start, $end, string $source, string $type, string $step): \Illuminate\Support\Collection
    {
        $guest = collect();
        $student = collect();

        if ($source === 'all' || $source === 'guest') {
            $q = GuestFeedback::whereBetween('created_at', [$start, $end]);
            if ($type !== 'all') {
                $q->where('feedback_type', $type);
            }
            if ($step !== 'all') {
                $q->where('process_step', $step);
            }
            $guest = $q->orderByDesc('created_at')->limit(5000)->get()->each(function ($row) {
                $row->_source = 'guest';
                $row->_owner = 'Guest #' . $row->guest_application_id;
            });
        }

        if ($source === 'all' || $source === 'student') {
            $q = StudentFeedback::whereBetween('created_at', [$start, $end]);
            if ($type !== 'all') {
                $q->where('feedback_type', $type);
            }
            if ($step !== 'all') {
                $q->where('process_step', $step);
            }
            $student = $q->orderByDesc('created_at')->limit(5000)->get()->each(function ($row) {
                $row->_source = 'student';
                $row->_owner = $row->student_id ?? '';
            });
        }

        return $guest->merge($student);
    }

    public function gdprDashboard(Request $request)
    {
        return view('manager.gdpr-dashboard', [
            'pendingErasures'   => ManagerRequest::where('request_type', 'gdpr_erasure')
                ->whereIn('status', ['pending', 'in_review'])->count(),
            'completedErasures' => ManagerRequest::where('request_type', 'gdpr_erasure')
                ->where('status', 'done')->count(),
            'recentExports'     => SystemEventLog::where('event_type', 'gdpr.data_export')
                ->where('created_at', '>=', now()->subDays(30))->count(),
            'piiAccessLogs'     => SystemEventLog::where('event_type', 'gdpr.pii_access')
                ->where('created_at', '>=', now()->subDays(7))->count(),
            'retentionPolicies' => DataRetentionPolicy::where('is_active', true)->get(),
            'consentStats'      => [
                'total'   => ConsentRecord::count(),
                'active'  => ConsentRecord::whereNull('revoked_at')->count(),
                'revoked' => ConsentRecord::whereNotNull('revoked_at')->count(),
            ],
        ]);
    }
}
