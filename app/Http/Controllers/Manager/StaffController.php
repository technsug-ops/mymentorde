<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BusinessContract;
use App\Models\RoleTemplate;
use App\Models\StaffKpiTarget;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\StaffKpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    private const STAFF_ROLES = [
        'system_admin', 'system_staff',
        'operations_admin', 'operations_staff',
        'finance_admin', 'finance_staff',
        'marketing_admin', 'marketing_staff',
        'sales_admin', 'sales_staff',
    ];

    // Hiyerarşik katman sırası (küçük = üst)
    private const ROLE_LAYER = [
        'manager'            => 1,
        'system_admin'       => 2,
        'operations_admin'   => 2,
        'finance_admin'      => 2,
        'marketing_admin'    => 2,
        'sales_admin'        => 2,
        'senior'             => 3,
        'system_staff'       => 4,
        'operations_staff'   => 4,
        'finance_staff'      => 4,
        'marketing_staff'    => 4,
        'sales_staff'        => 4,
    ];

    private const LAYER_LABELS = [
        1 => 'Manager',
        2 => 'Admin',
        3 => 'Senior',
        4 => 'Personel',
    ];

    // Katman filtresi → rol listesi
    private const LAYER_FILTER_MAP = [
        'manager' => ['manager'],
        'admin'   => ['system_admin','operations_admin','finance_admin','marketing_admin','sales_admin'],
        'senior'  => ['senior'],
        'personel'=> ['system_staff','operations_staff','finance_staff','marketing_staff','sales_staff'],
    ];

    private const DEPT_MAP = [
        'sistem'    => ['system_admin', 'system_staff'],
        'operasyon' => ['operations_admin', 'operations_staff'],
        'finans'    => ['finance_admin', 'finance_staff'],
        'pazarlama' => ['marketing_admin', 'marketing_staff'],
        'satis'     => ['sales_admin', 'sales_staff'],
    ];

    private const DEPT_LABELS = [
        'sistem'    => 'Sistem',
        'operasyon' => 'Operasyon',
        'finans'    => 'Finans',
        'pazarlama' => 'Pazarlama',
        'satis'     => 'Satış',
    ];

    private const ROLE_LABELS = [
        'system_admin'      => 'Sistem — Yönetici',
        'system_staff'      => 'Sistem — Personel',
        'operations_admin'  => 'Operasyon — Yönetici',
        'operations_staff'  => 'Operasyon — Personel',
        'finance_admin'     => 'Finans — Yönetici',
        'finance_staff'     => 'Finans — Personel',
        'marketing_admin'   => 'Pazarlama — Yönetici',
        'marketing_staff'   => 'Pazarlama — Personel',
        'sales_admin'       => 'Satış — Yönetici',
        'sales_staff'       => 'Satış — Personel',
    ];

    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    private function getDept(string $role): string
    {
        foreach (self::DEPT_MAP as $dept => $roles) {
            if (in_array($role, $roles)) {
                return self::DEPT_LABELS[$dept] ?? $dept;
            }
        }
        return 'Diğer';
    }

    private function isAdmin(string $role): bool
    {
        return str_ends_with($role, '_admin');
    }

    public function index(Request $request)
    {
        $cid         = $this->companyId();
        $layerFilter = $request->query('layer', 'hepsi');
        $search      = trim((string) $request->query('q', ''));

        $allRoles = array_keys(self::ROLE_LAYER);

        $filterRoles = ($layerFilter !== 'hepsi' && isset(self::LAYER_FILTER_MAP[$layerFilter]))
            ? self::LAYER_FILTER_MAP[$layerFilter]
            : $allRoles;

        $staff = User::whereIn('role', $filterRoles)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->when($search !== '', fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->sortBy(fn ($u) => [(self::ROLE_LAYER[$u->role] ?? 9), $u->name])
            ->values();

        // KPI — tüm roller üzerinden
        $all = User::whereIn('role', $allRoles)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->get();

        $kpis = [
            'total'  => $all->count(),
            'active' => $all->where('is_active', true)->count(),
            'passive'=> $all->where('is_active', false)->count(),
        ];

        // Katman sayıları (filtre butonları)
        $layerCounts = [];
        foreach (self::LAYER_FILTER_MAP as $key => $roles) {
            $layerCounts[$key] = $all->whereIn('role', $roles)->count();
        }

        return view('manager.staff.index', compact(
            'staff', 'kpis', 'layerCounts', 'layerFilter', 'search'
        ));
    }

    public function create()
    {
        return view('manager.staff.create', [
            'roleLabels' => self::ROLE_LABELS,
            'deptMap'    => self::DEPT_MAP,
            'deptLabels' => self::DEPT_LABELS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'role'                  => ['required', Rule::in(self::STAFF_ROLES)],
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'role'              => $data['role'],
            'password'          => Hash::make($data['password']),
            'company_id'        => $this->companyId() ?: null,
            'is_active'  => true,
        ]);

        // Kullanıcı rolüne ait varsayılan şablon varsa otomatik ata
        $defaultTemplate = RoleTemplate::where('parent_role', $user->role)
            ->where('is_active', true)
            ->orderBy('is_system') // system=false (özel) şablonlar önce
            ->first();

        if ($defaultTemplate) {
            UserRoleAssignment::create([
                'user_id'             => $user->id,
                'role_template_id'    => $defaultTemplate->id,
                'assigned_by_user_id' => auth()->id(),
                'version_applied'     => $defaultTemplate->version,
                'is_active'           => true,
                'assigned_at'         => now(),
            ]);
        }

        return redirect("/manager/hr/persons/{$user->id}")
            ->with('status', 'Personel başarıyla oluşturuldu.');
    }

    public function leaderboard(Request $request)
    {
        $cid         = $this->companyId();
        $period      = $request->query('period', now()->format('Y-m'));
        $layerFilter = $request->query('layer', 'personel'); // admin | senior | personel
        $deptFilter  = $request->query('dept',  'hepsi');

        $kpiService = app(StaffKpiService::class);
        $isSenior   = $layerFilter === 'senior';

        if ($isSenior) {
            $users = User::where('role', 'senior')
                ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
                ->orderBy('name')
                ->get();

            $seniorEmails = $users->pluck('email')->all();
            $allActuals   = $kpiService->getAllSeniorActuals($period, $seniorEmails);

            $rows = $users->map(function ($s) use ($allActuals, $kpiService) {
                $actuals = $allActuals[$s->email] ?? ['active_students' => 0, 'active_guests' => 0, 'conversions' => 0];
                return (object) [
                    'user'    => $s,
                    'dept'    => '—',
                    'actuals' => $actuals,
                    'target'  => null,
                    'score'   => $kpiService->calcSeniorScore($actuals),
                ];
            })->sortByDesc('score')->values();

            $summary = [
                'avg_students'      => $rows->avg(fn ($r) => $r->actuals['active_students']),
                'avg_guests'        => $rows->avg(fn ($r) => $r->actuals['active_guests']),
                'total_conversions' => $rows->sum(fn ($r) => $r->actuals['conversions']),
                'avg_score'         => $rows->avg('score'),
            ];
            $deptFilter = 'hepsi'; // senior'da departman yok

        } else {
            $layerRoles = self::LAYER_FILTER_MAP[$layerFilter] ?? self::LAYER_FILTER_MAP['personel'];

            $filteredRoles = ($deptFilter !== 'hepsi' && isset(self::DEPT_MAP[$deptFilter]))
                ? array_values(array_intersect($layerRoles, self::DEPT_MAP[$deptFilter]))
                : $layerRoles;

            $users = User::whereIn('role', $filteredRoles ?: $layerRoles)
                ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
                ->orderBy('name')
                ->get();

            $userIds    = $users->pluck('id')->all();
            $allActuals = $kpiService->getAllActuals($period, $userIds);
            $allTargets = $kpiService->getAllTargets($period, $userIds);

            $rows = $users->map(function ($s) use ($allActuals, $allTargets, $kpiService) {
                $actuals = $allActuals[$s->id] ?? ['tasks_done' => 0, 'tickets_resolved' => 0, 'hours_logged' => 0.0];
                $target  = $allTargets[$s->id] ?? null;
                $dept    = match (true) {
                    str_contains($s->role, 'system')     => 'Sistem',
                    str_contains($s->role, 'operations') => 'Operasyon',
                    str_contains($s->role, 'finance')    => 'Finans',
                    str_contains($s->role, 'marketing')  => 'Pazarlama',
                    str_contains($s->role, 'sales')      => 'Satış',
                    default                              => '—',
                };
                return (object) [
                    'user'    => $s,
                    'dept'    => $dept,
                    'actuals' => $actuals,
                    'target'  => $target,
                    'score'   => $kpiService->calcScore($actuals, $target),
                ];
            })->sortByDesc('score')->values();

            $summary = [
                'avg_tasks'   => $rows->avg(fn ($r) => $r->actuals['tasks_done']),
                'avg_tickets' => $rows->avg(fn ($r) => $r->actuals['tickets_resolved']),
                'total_hours' => $rows->sum(fn ($r) => $r->actuals['hours_logged']),
                'avg_score'   => $rows->avg('score'),
            ];
        }

        return view('manager.staff.leaderboard', compact(
            'rows', 'period', 'summary', 'layerFilter', 'deptFilter', 'isSenior'
        ));
    }

    public function performanceDashboard(Request $request)
    {
        $cid         = $this->companyId();
        $period      = $request->query('period', now()->format('Y-m'));
        $layerFilter = $request->query('layer', 'personel');
        $deptFilter  = $request->query('dept',  'hepsi');
        $activeTab   = $request->query('tab', 'leaderboard'); // leaderboard | kpi

        $kpiService = app(StaffKpiService::class);
        $isSenior   = $layerFilter === 'senior';

        if ($isSenior) {
            $users = User::where('role', 'senior')
                ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
                ->orderBy('name')
                ->get();

            $seniorEmails = $users->pluck('email')->all();
            $allActuals   = $kpiService->getAllSeniorActuals($period, $seniorEmails);

            $rows = $users->map(function ($s) use ($allActuals, $kpiService) {
                $actuals = $allActuals[$s->email] ?? ['active_students' => 0, 'active_guests' => 0, 'conversions' => 0];
                return (object) [
                    'user'    => $s,
                    'dept'    => '—',
                    'actuals' => $actuals,
                    'act'     => $actuals,
                    'target'  => null,
                    'score'   => $kpiService->calcSeniorScore($actuals),
                ];
            })->sortByDesc('score')->values();

            $summary = [
                'avg_students'      => $rows->avg(fn ($r) => $r->actuals['active_students']),
                'avg_guests'        => $rows->avg(fn ($r) => $r->actuals['active_guests']),
                'total_conversions' => $rows->sum(fn ($r) => $r->actuals['conversions']),
                'avg_score'         => $rows->avg('score'),
            ];
            $deptFilter = 'hepsi';

        } else {
            $layerRoles = self::LAYER_FILTER_MAP[$layerFilter] ?? self::LAYER_FILTER_MAP['personel'];

            $filteredRoles = ($deptFilter !== 'hepsi' && isset(self::DEPT_MAP[$deptFilter]))
                ? array_values(array_intersect($layerRoles, self::DEPT_MAP[$deptFilter]))
                : $layerRoles;

            $users = User::whereIn('role', $filteredRoles ?: $layerRoles)
                ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
                ->orderBy('name')
                ->get();

            $userIds    = $users->pluck('id')->all();
            $allActuals = $kpiService->getAllActuals($period, $userIds);
            $allTargets = $kpiService->getAllTargets($period, $userIds);

            $rows = $users->map(function ($s) use ($allActuals, $allTargets, $kpiService) {
                $actuals = $allActuals[$s->id] ?? ['tasks_done' => 0, 'tickets_resolved' => 0, 'hours_logged' => 0.0];
                $target  = $allTargets[$s->id] ?? null;
                $dept    = match (true) {
                    str_contains($s->role, 'system')     => 'Sistem',
                    str_contains($s->role, 'operations') => 'Operasyon',
                    str_contains($s->role, 'finance')    => 'Finans',
                    str_contains($s->role, 'marketing')  => 'Pazarlama',
                    str_contains($s->role, 'sales')      => 'Satış',
                    default                              => '—',
                };
                return (object) [
                    'user'    => $s,
                    'dept'    => $dept,
                    'actuals' => $actuals,
                    'act'     => $actuals,
                    'target'  => $target,
                    'score'   => $kpiService->calcScore($actuals, $target),
                ];
            })->sortByDesc('score')->values();

            $summary = [
                'avg_tasks'   => $rows->avg(fn ($r) => $r->actuals['tasks_done']),
                'avg_tickets' => $rows->avg(fn ($r) => $r->actuals['tickets_resolved']),
                'total_hours' => $rows->sum(fn ($r) => $r->actuals['hours_logged']),
                'avg_score'   => $rows->avg('score'),
            ];
        }

        $periods = collect(range(0, 5))->map(fn ($i) => now()->subMonths($i)->format('Y-m'))->all();

        return view('manager.staff.performance', compact(
            'rows', 'period', 'periods', 'summary', 'layerFilter', 'deptFilter', 'isSenior', 'activeTab'
        ));
    }

    public function setKpiTargets(Request $request, User $user)
    {
        $cid = $this->companyId();
        abort_if(
            ($cid > 0 && (int) $user->company_id !== $cid) || !in_array($user->role, self::STAFF_ROLES),
            404
        );

        $data = $request->validate([
            'period'                  => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'target_tasks_done'       => 'required|integer|min:0',
            'target_tickets_resolved' => 'required|integer|min:0',
            'target_hours_logged'     => 'required|numeric|min:0',
        ]);

        StaffKpiTarget::updateOrCreate(
            ['user_id' => $user->id, 'period' => $data['period']],
            [
                'company_id'              => $cid ?: null,
                'target_tasks_done'       => $data['target_tasks_done'],
                'target_tickets_resolved' => $data['target_tickets_resolved'],
                'target_hours_logged'     => $data['target_hours_logged'],
                'set_by_user_id'          => auth()->id(),
            ]
        );

        return redirect("/manager/staff/{$user->id}?period={$data['period']}")
            ->with('status', "{$data['period']} dönemi KPI hedefleri kaydedildi.");
    }

    public function show(User $user)
    {
        $cid = $this->companyId();
        abort_if(
            ($cid > 0 && (int) $user->company_id !== $cid) || !in_array($user->role, self::STAFF_ROLES),
            404
        );

        return view('manager.staff.show', [
            'user'    => $user,
            'dept'    => $this->getDept($user->role),
            'isAdmin' => $this->isAdmin($user->role),
        ]);
    }

    public function edit(User $user)
    {
        $cid = $this->companyId();
        abort_if(
            ($cid > 0 && (int) $user->company_id !== $cid) || !in_array($user->role, self::STAFF_ROLES),
            404
        );

        return view('manager.staff.edit', [
            'user'       => $user,
            'roleLabels' => self::ROLE_LABELS,
            'deptMap'    => self::DEPT_MAP,
            'deptLabels' => self::DEPT_LABELS,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $cid = $this->companyId();
        abort_if(
            ($cid > 0 && (int) $user->company_id !== $cid) || !in_array($user->role, self::STAFF_ROLES),
            404
        );

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'role'      => ['required', Rule::in(self::STAFF_ROLES)],
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name'      => $data['name'],
            'role'      => $data['role'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect("/manager/staff/{$user->id}")
            ->with('status', 'Personel bilgileri güncellendi.');
    }

    public function toggle(User $user)
    {
        $cid = $this->companyId();
        abort_if(
            ($cid > 0 && (int) $user->company_id !== $cid) || !in_array($user->role, self::STAFF_ROLES),
            404
        );

        $user->update(['is_active' => !$user->is_active]);

        $msg = $user->is_active ? 'Personel aktif edildi.' : 'Personel pasif yapıldı.';
        return back()->with('status', $msg);
    }

    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action'   => 'required|in:activate,deactivate',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $cid = $this->companyId();

        $query = User::whereIn('id', $data['user_ids'])
            ->whereIn('role', self::STAFF_ROLES);

        if ($cid > 0) {
            $query->where('company_id', $cid);
        }

        $users = $query->get();

        foreach ($users as $u) {
            $u->update(['is_active' => $data['action'] === 'activate']);
        }

        $count = $users->count();
        $msg   = $data['action'] === 'activate'
            ? "{$count} personel aktif edildi."
            : "{$count} personel pasif yapıldı.";

        return back()->with('status', $msg);
    }
}
