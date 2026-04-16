<?php

namespace App\Http\Controllers;

use App\Models\GuestTicket;
use App\Models\MarketingTask;
use App\Models\CompanyBulletin;
use App\Models\BulletinRead;
use App\Models\StudentPayment;
use App\Models\StudentRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StaffDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cid  = (int) ($user?->company_id ?? 0);
        $uid  = (int) ($user?->id ?? 0);

        $cacheKey = "staff_dashboard_{$uid}";
        $data = Cache::remember($cacheKey, 120, function () use ($uid, $cid) {
            $today = now()->toDateString();

            // Bugünkü görevler
            $todayTasks = MarketingTask::where('assigned_user_id', $uid)
                ->whereNotIn('status', ['done', 'cancelled'])
                ->whereDate('due_date', $today)
                ->orderBy('due_date')
                ->limit(10)
                ->get(['id', 'title', 'status', 'priority', 'due_date']);

            // Gecikmiş görevler
            $overdueTasks = MarketingTask::where('assigned_user_id', $uid)
                ->whereNotIn('status', ['done', 'cancelled'])
                ->whereDate('due_date', '<', $today)
                ->orderBy('due_date')
                ->limit(5)
                ->get(['id', 'title', 'status', 'priority', 'due_date']);

            // Açık ticketlar
            $openTickets = GuestTicket::whereIn('status', ['open', 'in_progress'])
                ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
                ->latest()
                ->limit(5)
                ->get(['id', 'subject', 'status', 'priority', 'created_at']);

            // Okunmamış bültenler + reactions
            $readIds = BulletinRead::where('user_id', $uid)->pluck('bulletin_id');
            $unreadBulletins = CompanyBulletin::with('reactions')
                ->where(fn($q) => $q->whereNull('company_id')->orWhere('company_id', $cid))
                ->where('published_at', '<=', now())
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->whereNotIn('id', $readIds)
                ->orderByDesc('is_pinned')->orderByDesc('published_at')
                ->limit(5)
                ->get();
            // Kullanıcının mevcut reaksiyonları (bu kullanıcı hangi emoji'yi seçmiş)
            $myReactions = \App\Models\BulletinReaction::where('user_id', $uid)
                ->whereIn('bulletin_id', $unreadBulletins->pluck('id'))
                ->pluck('emoji', 'bulletin_id')
                ->toArray();

            return compact('todayTasks', 'overdueTasks', 'openTickets', 'unreadBulletins', 'myReactions');
        });

        // KPI sayıları
        $kpi = [
            'today'   => $data['todayTasks']->count(),
            'overdue' => $data['overdueTasks']->count(),
            'tickets' => $data['openTickets']->count(),
            'unread'  => $data['unreadBulletins']->count(),
        ];

        $financeData = null;
        if (in_array($user?->role, ['finance_admin', 'finance_staff'])) {
            $financeData = Cache::remember("staff_finance_{$cid}", 180, function () use ($cid) {
                $base = StudentPayment::when($cid > 0, fn($q) => $q->where('company_id', $cid));

                $overduePayments = (clone $base)->overdue()
                    ->orderBy('due_date')
                    ->limit(10)
                    ->get(['id', 'invoice_number', 'student_id', 'amount_eur', 'due_date', 'status']);

                $pendingPayments = (clone $base)->pending()
                    ->orderBy('due_date')
                    ->limit(10)
                    ->get(['id', 'invoice_number', 'student_id', 'amount_eur', 'due_date', 'status']);

                $recentPaid = (clone $base)->paid()
                    ->orderByDesc('paid_at')
                    ->limit(5)
                    ->get(['id', 'invoice_number', 'student_id', 'amount_eur', 'paid_at']);

                $totalPendingEur  = (clone $base)->pending()->sum('amount_eur');
                $totalOverdueEur  = (clone $base)->overdue()->sum('amount_eur');
                $totalPaidThisMonth = (clone $base)->paid()
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount_eur');

                return compact('overduePayments', 'pendingPayments', 'recentPaid',
                               'totalPendingEur', 'totalOverdueEur', 'totalPaidThisMonth');
            });
        }

        return view('staff.dashboard', array_merge($data, ['kpi' => $kpi, 'user' => $user, 'financeData' => $financeData]));
    }
}
