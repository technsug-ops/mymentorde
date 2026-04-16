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
            ->selectRaw('SUM(commission_amount) as total_commission, COUNT(DISTINCT dealer_code) as dealer_count')
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
