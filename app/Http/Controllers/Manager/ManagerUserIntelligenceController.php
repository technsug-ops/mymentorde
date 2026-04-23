<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\Analytics\UserActivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * User Activity Intelligence — guest + student platform aktivite dashboardu.
 *
 * Route prefix: /manager/user-intelligence
 * Access: admin panel roles (manager, system_admin vb.) — senior erişmez
 */
class ManagerUserIntelligenceController extends Controller
{
    public function index(Request $request, UserActivityService $svc): View
    {
        $this->ensureAdmin($request);
        $cid = $this->companyId();

        // Period selector — ?days=7|15|30|90|180|365 veya ?from=&to=
        $days = $this->resolveDays($request);
        $customRange = null;
        if ($request->filled('from') && $request->filled('to')) {
            try {
                $from = \Carbon\Carbon::parse($request->input('from'))->startOfDay();
                $to   = \Carbon\Carbon::parse($request->input('to'))->endOfDay();
                if ($to->gte($from)) {
                    $days = max(1, (int) $from->diffInDays($to));
                    $customRange = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
                }
            } catch (\Throwable) {}
        }

        // Campaign impact — ?event_date=Y-M-D&window=7
        $campaign = null;
        if ($request->filled('event_date')) {
            try {
                $win = max(1, min(90, (int) $request->input('window', 7)));
                $campaign = $svc->campaignImpact($cid, $request->input('event_date'), $win);
            } catch (\Throwable $e) {
                $campaign = ['error' => 'Geçersiz tarih formatı'];
            }
        }

        // Sort — ?sort=activity|lead_score|last_activity|name|questions
        $sortOptions = ['activity', 'lead_score', 'last_activity', 'name', 'questions'];
        $sort = $request->input('sort', 'activity');
        if (!in_array($sort, $sortOptions, true)) $sort = 'activity';

        return view('manager.user-intelligence.index', [
            'overview'              => $svc->overviewStats($cid),
            'tiers'                 => $svc->engagementTiers($cid),
            'top_users'             => $svc->topActiveUsers($cid, $days, 20, $sort),
            'dormant_alerts'        => $svc->dormantAlerts($cid, 40, 14, 20),
            'student_dormant'       => $svc->studentDormantAlerts($cid, 14, 20),
            'daily_trend'           => $svc->dailyTrend($cid, $days),
            'selected_days'         => $days,
            'custom_range'          => $customRange,
            'selected_sort'         => $sort,
            'campaign'              => $campaign,
            'campaign_event_date'   => $request->input('event_date'),
            'campaign_window'       => (int) $request->input('window', 7),
        ]);
    }

    private function resolveDays(Request $request): int
    {
        $allowed = [7, 15, 30, 90, 180, 365];
        $requested = (int) $request->input('days', 30);
        return in_array($requested, $allowed, true) ? $requested : 30;
    }

    public function guest(Request $request, UserActivityService $svc, int $guestId): View
    {
        $this->ensureAdmin($request);
        $cid = $this->companyId();

        $guest = GuestApplication::where('id', $guestId)
            ->where('company_id', $cid)
            ->firstOrFail();

        return view('manager.user-intelligence.guest', [
            'guest'    => $guest,
            'timeline' => $svc->guestTimeline($guestId),
        ]);
    }

    public function student(Request $request, UserActivityService $svc, int $studentId): View
    {
        $this->ensureAdmin($request);
        $cid = $this->companyId();

        $student = User::where('id', $studentId)
            ->where('company_id', $cid)
            ->where('role', 'student')
            ->firstOrFail();

        // Student timeline — appointments, documents, payments, AI
        $appointments = \App\Models\StudentAppointment::where('student_id', $studentId)
            ->orderByDesc('scheduled_at')
            ->limit(20)
            ->get(['id', 'scheduled_at', 'status', 'cancelled_at']);
        $payments = \App\Models\StudentPayment::where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'invoice_number', 'amount_eur', 'status', 'due_date', 'paid_at', 'created_at']);
        $audits = \App\Models\AuditTrail::where('user_id', $studentId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['action', 'entity_type', 'created_at', 'ip_address']);

        return view('manager.user-intelligence.student', [
            'student'      => $student,
            'appointments' => $appointments,
            'payments'     => $payments,
            'audits'       => $audits,
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array((string) $user->role, \App\Models\User::ADMIN_PANEL_ROLES, true)) {
            abort(403, 'User Intelligence sadece yöneticilere açıktır.');
        }
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }
}
