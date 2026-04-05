<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\ManagerDashboardTrait;
use App\Models\ConsentRecord;
use App\Models\DataRetentionPolicy;
use App\Models\DealerStudentRevenue;
use App\Models\GuestApplication;
use App\Models\ManagerRequest;
use App\Models\NotificationDispatch;
use App\Models\ScheduledNotification;
use App\Models\StudentRevenue;
use App\Models\SystemEventLog;
use App\Services\DashboardKPIService;
use Illuminate\Http\Request;

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
