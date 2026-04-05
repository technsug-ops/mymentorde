<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\HrAttendance;
use App\Models\Hr\HrCertification;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrPersonProfile;
use App\Models\Hr\HrSalaryProfile;
use App\Models\User;
use Carbon\Carbon;

class HrDashboardController extends Controller
{
    private const ALL_EMPLOYEE_ROLES = [
        'manager',
        'senior',
        'system_admin', 'system_staff',
        'operations_admin', 'operations_staff',
        'finance_admin', 'finance_staff',
        'marketing_admin', 'marketing_staff',
        'sales_admin', 'sales_staff',
    ];

    private const STAFF_ROLES = [
        'system_admin', 'system_staff',
        'operations_admin', 'operations_staff',
        'finance_admin', 'finance_staff',
        'marketing_admin', 'marketing_staff',
        'sales_admin', 'sales_staff',
    ];

    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    public function index()
    {
        $cid = $this->companyId();

        $allEmployees = User::whereIn('role', self::ALL_EMPLOYEE_ROLES)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->get(['id', 'name', 'role', 'is_active']);

        $counts = [
            'total'    => $allEmployees->count(),
            'active'   => $allEmployees->where('is_active', true)->count(),
            'passive'  => $allEmployees->where('is_active', false)->count(),
            'staff'    => $allEmployees->whereIn('role', self::STAFF_ROLES)->count(),
            'senior'   => $allEmployees->where('role', 'senior')->count(),
            'manager'  => $allEmployees->where('role', 'manager')->count(),
        ];

        $userIds = $allEmployees->pluck('id')->all();

        // Bekleyen izin talepleri
        $pendingLeaves = HrLeaveRequest::whereIn('user_id', $userIds)
            ->where('status', 'pending')
            ->with('user:id,name,role')
            ->latest()
            ->limit(10)
            ->get();

        // 30 gün içinde sona erecek sertifikalar
        $expiringSoon = HrCertification::whereIn('user_id', $userIds)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', Carbon::today())
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays(30))
            ->with('user:id,name')
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        // Bugün izinli olanlar
        $today = Carbon::today()->toDateString();
        $onLeaveToday = HrLeaveRequest::whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with('user:id,name')
            ->get();

        // Bu ay onaylı izin özeti
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd   = Carbon::now()->endOfMonth()->toDateString();
        $monthLeaves = HrLeaveRequest::whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '>=', $monthStart)
            ->whereDate('start_date', '<=', $monthEnd)
            ->get();
        $monthLeaveDays  = $monthLeaves->sum('days_count');
        $monthLeaveCount = $monthLeaves->count();

        // Departman dağılımı
        $deptDist = [];
        foreach ($allEmployees as $emp) {
            $dept = match (true) {
                str_contains($emp->role, 'system')     => 'Sistem',
                str_contains($emp->role, 'operations') => 'Operasyon',
                str_contains($emp->role, 'finance')    => 'Finans',
                str_contains($emp->role, 'marketing')  => 'Pazarlama',
                str_contains($emp->role, 'sales')      => 'Satış',
                $emp->role === 'senior'                => 'Senior',
                $emp->role === 'manager'               => 'Manager',
                default                                => 'Diğer',
            };
            $deptDist[$dept] = ($deptDist[$dept] ?? 0) + 1;
        }
        arsort($deptDist);

        // Bu hafta devam özeti
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $weekAttendance = HrAttendance::whereIn('user_id', $userIds)
            ->whereDate('work_date', '>=', $weekStart)
            ->get();
        $attStats = [
            'present' => $weekAttendance->where('status', 'present')->count(),
            'late'    => $weekAttendance->where('status', 'late')->count(),
            'absent'  => $weekAttendance->where('status', 'absent')->count(),
            'total'   => $weekAttendance->count(),
        ];

        // Bordro profili olmayan staff çalışanları
        $staffIds      = $allEmployees->whereIn('role', self::STAFF_ROLES)->pluck('id')->all();
        $withSalary    = HrSalaryProfile::whereIn('user_id', $staffIds)->where('is_active', true)->pluck('user_id')->toArray();
        $noSalaryCount = count(array_diff($staffIds, $withSalary));

        // Son 90 gün işe başlayanlar (hire_date)
        $recentHires = HrPersonProfile::whereIn('user_id', $userIds)
            ->whereNotNull('hire_date')
            ->whereDate('hire_date', '>=', Carbon::today()->subDays(90))
            ->with('user:id,name,role')
            ->orderByDesc('hire_date')
            ->limit(5)
            ->get();

        // Yaklaşan işe başlama yıl dönümleri (30 gün)
        $allProfiles = HrPersonProfile::whereIn('user_id', $userIds)
            ->whereNotNull('hire_date')
            ->with('user:id,name')
            ->get();

        $nowDate = Carbon::today();
        $upcomingAnniversaries = $allProfiles
            ->map(function ($p) use ($nowDate) {
                $ann = Carbon::parse($p->hire_date)->setYear($nowDate->year);
                if ($ann->lt($nowDate)) {
                    $ann->addYear();
                }
                $daysLeft = $nowDate->diffInDays($ann);
                $years    = $ann->year - Carbon::parse($p->hire_date)->year;
                return (object) [
                    'profile'  => $p,
                    'ann_date' => $ann,
                    'days_left' => $daysLeft,
                    'years'    => $years,
                ];
            })
            ->filter(fn ($a) => $a->days_left <= 30)
            ->sortBy('days_left')
            ->values();

        return view('manager.hr.dashboard', compact(
            'counts', 'pendingLeaves', 'expiringSoon', 'onLeaveToday',
            'monthLeaveDays', 'monthLeaveCount',
            'deptDist', 'attStats',
            'noSalaryCount', 'recentHires', 'upcomingAnniversaries'
        ));
    }
}
