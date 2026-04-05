<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\BusinessContract;
use App\Models\Hr\HrCertification;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrPersonProfile;
use App\Models\RoleTemplate;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\StaffKpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HrPersonController extends Controller
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

    public const ROLE_TYPE_LABELS = [
        'manager'            => 'Manager',
        'senior'             => 'Senior',
        'system_admin'       => 'Sistem — Yönetici',
        'system_staff'       => 'Sistem — Personel',
        'operations_admin'   => 'Operasyon — Yönetici',
        'operations_staff'   => 'Operasyon — Personel',
        'finance_admin'      => 'Finans — Yönetici',
        'finance_staff'      => 'Finans — Personel',
        'marketing_admin'    => 'Pazarlama — Yönetici',
        'marketing_staff'    => 'Pazarlama — Personel',
        'sales_admin'        => 'Satış — Yönetici',
        'sales_staff'        => 'Satış — Personel',
    ];

    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    public function card(Request $request, User $user)
    {
        $cid = $this->companyId();
        abort_if(
            $cid > 0 && (int) $user->company_id !== $cid,
            404
        );
        abort_unless(in_array($user->role, self::ALL_EMPLOYEE_ROLES), 404);

        $profile = HrPersonProfile::firstOrNew(['user_id' => $user->id]);
        $year    = now()->year;

        // İzinler
        $leaves       = HrLeaveRequest::where('user_id', $user->id)->orderByDesc('start_date')->get();
        $usedLeave    = $profile->exists ? $profile->usedLeaveDays($year) : 0;
        $quota        = $profile->annual_leave_quota ?? 14;
        $remaining    = max(0, $quota - $usedLeave);

        // Sertifikalar
        $certs = HrCertification::where('user_id', $user->id)->orderByDesc('issue_date')->get();

        // KPI (sadece staff için)
        $isStaff   = in_array($user->role, self::STAFF_ROLES);
        $kpiActuals  = null;
        $kpiTargets  = null;
        $kpiPeriod   = $request->query('period', now()->format('Y-m'));
        if ($isStaff) {
            $kpiSvc     = app(StaffKpiService::class);
            $kpiActuals = $kpiSvc->getActuals($user->id, $kpiPeriod);
            $kpiTargets = $kpiSvc->getTargets($user->id, $kpiPeriod);
        }

        // Sözleşmeler (staff + senior)
        $showContracts = $isStaff || $user->role === 'senior';
        $contracts = $showContracts
            ? BusinessContract::where('user_id', $user->id)->latest()->take(5)->get()
            : collect();

        // Rol değişiklik geçmişi
        $roleAudits = DB::table('role_change_audits')
            ->where('target_type', 'user')
            ->where('target_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // KPI Trend (son 6 ay)
        $kpiTrend = null;
        if ($isStaff) {
            $kpiSvc   = app(StaffKpiService::class);
            $kpiTrend = collect(range(5, 0))->map(function ($i) use ($user, $kpiSvc) {
                $p = now()->subMonths($i)->format('Y-m');
                $act = $kpiSvc->getActuals($user->id, $p);
                $tgt = $kpiSvc->getTargets($user->id, $p);
                return [
                    'period' => $p,
                    'score'  => $kpiSvc->calcScore($act, $tgt),
                    'act'    => $act,
                ];
            });
        }

        $roleLabel = self::ROLE_TYPE_LABELS[$user->role] ?? $user->role;
        $activeTab = $request->query('tab', 'profile');

        // Blade içi DB sorgusu yerine controller'dan pas — N+1 önlemi
        $roleTemplates = RoleTemplate::where('is_active', true)->orderBy('parent_role')->get();

        return view('manager.hr.persons.card', compact(
            'user', 'profile', 'leaves', 'usedLeave', 'quota', 'remaining',
            'certs', 'isStaff', 'kpiActuals', 'kpiTargets', 'kpiPeriod',
            'showContracts', 'contracts', 'roleLabel', 'year', 'activeTab',
            'roleAudits', 'kpiTrend', 'roleTemplates'
        ));
    }

    public function kpiDashboard(Request $request)
    {
        $cid    = $this->companyId();
        $period = $request->query('period', now()->format('Y-m'));

        $employees = User::whereIn('role', self::STAFF_ROLES)
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $userIds = $employees->pluck('id')->all();

        $kpiSvc  = app(StaffKpiService::class);
        $actuals = $kpiSvc->getAllActuals($period, $userIds);
        $targets = $kpiSvc->getAllTargets($period, $userIds);

        $rows = $employees->map(function ($emp) use ($actuals, $targets, $kpiSvc) {
            $act    = $actuals->get($emp->id) ?? ['tasks_done' => 0, 'tickets_resolved' => 0, 'hours_logged' => 0];
            $target = $targets->get($emp->id);
            $score  = $kpiSvc->calcScore($act, $target);
            return [
                'user'   => $emp,
                'act'    => $act,
                'target' => $target,
                'score'  => $score,
            ];
        })->sortByDesc('score')->values();

        // Son 3 ay seçici için
        $periods = collect(range(0, 5))->map(fn($i) => now()->subMonths($i)->format('Y-m'))->all();

        return view('manager.hr.kpi-dashboard', compact('rows', 'period', 'periods'));
    }

    public function toggleActive(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $user->company_id !== $cid, 404);

        $user->update(['is_active' => !$user->is_active]);

        return redirect("/manager/hr/persons/{$user->id}?tab=account")
            ->with('status', $user->is_active ? 'Kullanıcı aktif edildi.' : 'Kullanıcı pasif yapıldı.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $user->company_id !== $cid, 404);

        $data = $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user->update(['password' => Hash::make($data['new_password'])]);

        return redirect("/manager/hr/persons/{$user->id}?tab=account")
            ->with('status', 'Şifre başarıyla sıfırlandı.');
    }

    public function addTemplate(Request $request, User $user)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $user->company_id !== $cid, 404);

        $data = $request->validate(['role_template_id' => 'required|integer|exists:role_templates,id']);

        $tpl = RoleTemplate::where('id', $data['role_template_id'])->where('is_active', true)->firstOrFail();

        // Prevent duplicate active assignment
        $exists = UserRoleAssignment::where('user_id', $user->id)
            ->where('role_template_id', $tpl->id)
            ->where('is_active', true)
            ->exists();

        if (!$exists) {
            UserRoleAssignment::create([
                'user_id'          => $user->id,
                'role_template_id' => $tpl->id,
                'assigned_by'      => auth()->id(),
                'assigned_at'      => now(),
                'is_active'        => true,
            ]);
        }

        return redirect("/manager/hr/persons/{$user->id}?tab=roles")
            ->with('status', "'{$tpl->name}' şablonu eklendi.");
    }

    public function revokeTemplate(Request $request, User $user, UserRoleAssignment $assignment)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $user->company_id !== $cid, 404);
        abort_if($assignment->user_id !== $user->id, 404);

        $assignment->update(['is_active' => false]);

        return redirect("/manager/hr/persons/{$user->id}?tab=roles")
            ->with('status', 'Şablon kaldırıldı.');
    }

    public function seniorTransferForm(Request $request)
    {
        $cid = $this->companyId();

        $seniors = User::where('role', 'senior')
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Count assigned guests per senior
        $assignedCounts = DB::table('guest_applications')
            ->whereIn('assigned_senior_email', $seniors->pluck('email'))
            ->groupBy('assigned_senior_email')
            ->selectRaw('assigned_senior_email, count(*) as cnt')
            ->pluck('cnt', 'assigned_senior_email');

        return view('manager.hr.senior-transfer', compact('seniors', 'assignedCounts'));
    }

    public function seniorTransferExecute(Request $request)
    {
        $cid = $this->companyId();

        $data = $request->validate([
            'from_senior_id' => 'required|integer|exists:users,id',
            'to_senior_id'   => 'required|integer|exists:users,id|different:from_senior_id',
        ]);

        $from = User::where('id', $data['from_senior_id'])->where('role', 'senior')->firstOrFail();
        $to   = User::where('id', $data['to_senior_id'])->where('role', 'senior')->firstOrFail();

        if ($cid > 0) {
            abort_if((int)$from->company_id !== $cid || (int)$to->company_id !== $cid, 403);
        }

        $count = DB::table('guest_applications')
            ->where('assigned_senior_email', $from->email)
            ->update([
                'assigned_senior_email' => $to->email,
                'assigned_at'           => now(),
                'assigned_by'           => (string) auth()->id(),
            ]);

        return redirect('/manager/hr/senior-transfer')
            ->with('status', "{$count} başvuru {$from->name} → {$to->name} devredildi.");
    }

    public function updateProfile(Request $request, User $user)
    {
        $cid = $this->companyId();
        abort_if($cid > 0 && (int) $user->company_id !== $cid, 404);

        $data = $request->validate([
            'hire_date'               => 'nullable|date',
            'position_title'          => 'nullable|string|max:120',
            'phone'                   => 'nullable|string|max:30',
            'emergency_contact_name'  => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'annual_leave_quota'      => 'required|integer|min:0|max:60',
            'notes'                   => 'nullable|string|max:1000',
        ]);

        HrPersonProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($data, ['company_id' => $cid ?: null])
        );

        return redirect("/manager/hr/persons/{$user->id}?tab=profile")
            ->with('status', 'Profil güncellendi.');
    }
}
