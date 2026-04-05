<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\IpAccessRule;
use App\Models\Permission;
use App\Models\RoleTemplate;
use App\Models\SystemEventLog;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Models\UserTwoFactor;
use App\Services\SecurityAnomalyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemAdminController extends Controller
{
    /** Manager veya system_admin rolüne izin ver */
    private function authorizeAccess(): void
    {
        $role = auth()->user()?->role;
        abort_unless(
            in_array($role, ['manager', 'system_admin'], true),
            403
        );
    }

    private function companyId(): int
    {
        return (int) (auth()->user()?->company_id ?? 0);
    }

    // ─── Dashboard ──────────────────────────────────────────────────────────

    public function dashboard()
    {
        $this->authorizeAccess();
        $cid = $this->companyId();

        $allRoles = [
            'manager', 'senior',
            'system_admin', 'system_staff',
            'operations_admin', 'operations_staff',
            'finance_admin', 'finance_staff',
            'marketing_admin', 'marketing_staff',
            'sales_admin', 'sales_staff',
        ];

        // Kullanıcı sayıları
        $users = User::whereIn('role', $allRoles)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->get(['id', 'role', 'is_active', 'email', 'created_at']);

        $userStats = [
            'total'   => $users->count(),
            'active'  => $users->where('is_active', true)->count(),
            'passive' => $users->where('is_active', false)->count(),
        ];

        // Bu ay yeni kayıt
        $newThisMonth = $users->filter(
            fn ($u) => Carbon::parse($u->created_at)->isCurrentMonth()
        )->count();

        // 2FA durumu
        $userIds     = $users->pluck('id')->all();
        $twoFaCount  = UserTwoFactor::whereIn('user_id', $userIds)
            ->whereNotNull('enabled_at')->count();
        $twoFaPct    = $userStats['total'] > 0
            ? round($twoFaCount / $userStats['total'] * 100)
            : 0;

        // Aktif oturumlar (sessions tablosu — son 30 dk)
        $activeSessionCount = 0;
        $recentSessions     = collect();
        try {
            $cutoff = now()->subMinutes(30)->timestamp;
            $activeSessionCount = DB::table('sessions')
                ->where('last_activity', '>=', $cutoff)
                ->count();
            $recentSessions = DB::table('sessions')
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->where('sessions.last_activity', '>=', $cutoff)
                ->orderByDesc('sessions.last_activity')
                ->limit(10)
                ->get(['users.name', 'users.email', 'users.role',
                       'sessions.ip_address', 'sessions.last_activity']);
        } catch (\Exception) {}

        // Başarısız işler (failed_jobs)
        $failedJobCount = 0;
        $recentFailedJobs = collect();
        try {
            $failedJobCount = DB::table('failed_jobs')->count();
            $recentFailedJobs = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(5)
                ->get(['id', 'connection', 'queue', 'payload', 'failed_at']);
        } catch (\Exception) {}

        // IP Erişim Kuralları
        $ipRuleCount = IpAccessRule::when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->count();
        $activeIpRules = IpAccessRule::when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where('is_active', true)->count();

        // Son 24 saat sistem eventleri
        $recentEventCount = SystemEventLog::when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Son 24 saat denetim kayıtları
        $recentAuditCount = AuditTrail::when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Son 10 sistem olayı
        $recentEvents = SystemEventLog::when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Güvenlik anomalileri
        $anomalies = collect();
        try {
            $anomalies = collect(app(SecurityAnomalyService::class)->detect());
        } catch (\Exception) {}
        $criticalCount = $anomalies->where('severity', 'critical')->count();
        $warningCount  = $anomalies->where('severity', 'warning')->count();

        // Rol bazlı kullanıcı sayısı
        $roleCounts = $users->groupBy('role')->map->count()->sortDesc();

        return view('manager.system.dashboard', compact(
            'userStats', 'newThisMonth',
            'twoFaCount', 'twoFaPct',
            'activeSessionCount', 'recentSessions',
            'failedJobCount', 'recentFailedJobs',
            'ipRuleCount', 'activeIpRules',
            'recentEventCount', 'recentAuditCount',
            'recentEvents', 'anomalies',
            'criticalCount', 'warningCount',
            'roleCounts'
        ));
    }

    // ─── IP Erişim Kuralları ─────────────────────────────────────────────────

    public function ipRules(Request $request)
    {
        $this->authorizeAccess();
        $cid = $this->companyId();

        $rules = IpAccessRule::when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->orderByDesc('id')
            ->get();

        return view('manager.system.ip-rules', compact('rules'));
    }

    public function storeIpRule(Request $request)
    {
        $this->authorizeAccess();
        $cid = $this->companyId();

        $data = $request->validate([
            'rule_type'         => 'required|in:whitelist,blacklist',
            'ip_range'          => 'required|string|max:50',
            'description'       => 'nullable|string|max:200',
            'applies_to_roles'  => 'nullable|array',
            'applies_to_roles.*'=> 'string',
        ]);

        IpAccessRule::create([
            'company_id'       => $cid ?: null,
            'rule_type'        => $data['rule_type'],
            'ip_range'         => $data['ip_range'],
            'description'      => $data['description'] ?? null,
            'applies_to_roles' => $data['applies_to_roles'] ?? [],
            'is_active'        => true,
            'created_by'       => auth()->id(),
        ]);

        return redirect('/manager/system/ip-rules')->with('status', 'IP kuralı eklendi.');
    }

    public function toggleIpRule(IpAccessRule $rule)
    {
        $this->authorizeAccess();
        $rule->update(['is_active' => !$rule->is_active]);
        return back()->with('status', $rule->is_active ? 'Kural aktif edildi.' : 'Kural pasif yapıldı.');
    }

    public function deleteIpRule(IpAccessRule $rule)
    {
        $this->authorizeAccess();
        $rule->delete();
        return back()->with('status', 'IP kuralı silindi.');
    }

    // ─── Güvenlik Paneli ────────────────────────────────────────────────────

    public function securityPanel()
    {
        $this->authorizeAccess();
        $cid = $this->companyId();

        // Güvenlik anomalileri
        $anomalies = collect();
        try {
            $anomalies = collect(app(SecurityAnomalyService::class)->detect());
        } catch (\Exception) {}

        // 2FA durumu
        $allRoles = ['manager','senior','system_admin','system_staff','operations_admin','operations_staff',
                     'finance_admin','finance_staff','marketing_admin','marketing_staff','sales_admin','sales_staff'];
        $users = User::whereIn('role', $allRoles)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->get(['id', 'name', 'email', 'role', 'is_active']);

        $userIds = $users->pluck('id')->all();
        $twoFaRecords = UserTwoFactor::whereIn('user_id', $userIds)->get()->keyBy('user_id');

        $twoFaRows = $users->map(function ($u) use ($twoFaRecords) {
            $rec = $twoFaRecords->get($u->id);
            return (object) [
                'user'       => $u,
                'has_2fa'    => $rec && $rec->enabled_at !== null,
                'enabled_at' => $rec?->enabled_at,
                'last_used'  => $rec?->last_used_at,
            ];
        })->sortByDesc('has_2fa')->values();

        $twoFaEnabled  = $twoFaRows->where('has_2fa', true)->count();
        $twoFaDisabled = $twoFaRows->where('has_2fa', false)->count();
        $twoFaPct      = $users->count() > 0 ? round($twoFaEnabled / $users->count() * 100) : 0;

        // Son 50 kritik audit kaydı
        $criticalEvents = SystemEventLog::when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where(fn ($q) => $q
                ->where('event_type', 'like', 'gdpr%')
                ->orWhere('event_type', 'like', 'vault%')
                ->orWhere('event_type', 'like', 'auth%')
                ->orWhere('event_type', 'like', 'security%')
            )
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('manager.system.security', compact(
            'anomalies', 'twoFaRows', 'twoFaEnabled', 'twoFaDisabled', 'twoFaPct', 'criticalEvents'
        ));
    }

    // ─── Rol Yönetimi ────────────────────────────────────────────────────────

    public function rolesIndex()
    {
        $this->authorizeAccess();
        $cid = $this->companyId();

        // Tüm rol şablonları + izinleri + kullanıcı sayıları
        $templates = RoleTemplate::with('permissions')
            ->withCount(['users as active_user_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('parent_role')
            ->get();

        // Tüm izinler (kategoriye göre gruplanmış)
        $permissions = Permission::orderBy('category')->orderBy('code')->get()
            ->groupBy('category');

        // Tüm iç kullanıcılar + aktif role atamaları
        $internalRoles = [
            'manager','senior','mentor',
            'system_admin','system_staff',
            'operations_admin','operations_staff',
            'finance_admin','finance_staff',
            'marketing_admin','marketing_staff',
            'sales_admin','sales_staff',
        ];
        $users = User::whereIn('role', $internalRoles)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->with(['roleAssignments' => fn ($q) => $q->where('is_active', true)->with('template')])
            ->orderBy('role')->orderBy('name')
            ->get();

        // Rol hiyerarşisi (User::ROLE_GROUPS'dan)
        $roleGroups = User::ROLE_GROUPS;

        // Kullanıcı sayısı per role
        $userCountByRole = $users->groupBy('role')->map->count();

        return view('manager.system.roles.index', compact(
            'templates', 'permissions', 'users', 'roleGroups', 'userCountByRole'
        ));
    }

    public function roleTemplateDetail(RoleTemplate $template)
    {
        $this->authorizeAccess();
        $cid = $this->companyId();

        $template->load('permissions');

        // Bu şablona atanmış aktif kullanıcılar
        $assignments = UserRoleAssignment::where('role_template_id', $template->id)
            ->where('is_active', true)
            ->with('user:id,name,email,role')
            ->orderByDesc('assigned_at')
            ->get();

        // Atanabilecek kullanıcılar (aynı parent_role veya manager)
        $assignableRoles = ['manager'];
        if ($template->parent_role) {
            $assignableRoles[] = $template->parent_role;
            // Staff kardeşleri de ekle
            $group = collect(User::ROLE_GROUPS)->firstWhere('parent', $template->parent_role);
            if ($group) {
                $assignableRoles = array_merge($assignableRoles, $group['children'] ?? []);
            }
        }
        $assignableUsers = User::whereIn('role', array_unique($assignableRoles))
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where('is_active', true)
            ->whereNotIn('id', $assignments->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        // Tüm izinler (checkbox için)
        $allPermissions = Permission::orderBy('category')->orderBy('code')->get();
        $templatePermIds = $template->permissions->pluck('id')->toArray();

        return view('manager.system.roles.detail', compact(
            'template', 'assignments', 'assignableUsers', 'allPermissions', 'templatePermIds'
        ));
    }

    public function storeTemplate(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'parent_role' => 'required|string|max:64',
        ]);

        $code = 'tpl_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($data['name'])) . '_' . time();

        RoleTemplate::create([
            'code'        => $code,
            'name'        => $data['name'],
            'parent_role' => $data['parent_role'],
            'version'     => 1,
            'is_system'   => false,
            'is_active'   => true,
        ]);

        return redirect('/manager/system/roles')->with('status', "'{$data['name']}' şablonu oluşturuldu.");
    }

    public function assignRoleTemplate(Request $request, User $user)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'role_template_id' => 'required|exists:role_templates,id',
        ]);

        $template = RoleTemplate::findOrFail($data['role_template_id']);

        // Zaten aktif atama varsa atla
        $existing = UserRoleAssignment::where('user_id', $user->id)
            ->where('role_template_id', $template->id)
            ->where('is_active', true)
            ->first();

        if (!$existing) {
            UserRoleAssignment::create([
                'user_id'              => $user->id,
                'role_template_id'     => $template->id,
                'assigned_by_user_id'  => auth()->id(),
                'version_applied'      => $template->version,
                'is_active'            => true,
                'assigned_at'          => now(),
            ]);
        }

        return back()->with('status', "{$user->name} kullanıcısına {$template->name} şablonu atandı.");
    }

    public function revokeRoleAssignment(UserRoleAssignment $assignment)
    {
        $this->authorizeAccess();

        $assignment->update([
            'is_active'  => false,
            'revoked_at' => now(),
        ]);

        return back()->with('status', 'Rol ataması iptal edildi.');
    }

    public function updateTemplatePermissions(Request $request, RoleTemplate $template)
    {
        $this->authorizeAccess();

        abort_if($template->is_system, 403, 'Sistem şablonları düzenlenemez.');

        $permIds = $request->input('permission_ids', []);
        $template->permissions()->sync($permIds);

        return back()->with('status', 'Şablon izinleri güncellendi.');
    }

    public function userRoleProfile(User $user)
    {
        $this->authorizeAccess();

        $user->load(['roleAssignments' => fn ($q) => $q->with('template.permissions')]);

        $activeAssignments = $user->roleAssignments->where('is_active', true);
        $revokedAssignments = $user->roleAssignments->where('is_active', false)->sortByDesc('revoked_at')->take(10);

        $effectivePermissions = $user->effectivePermissionCodes();
        $allPermissions = Permission::orderBy('category')->orderBy('code')->get()->groupBy('category');

        // Atanabilecek şablonlar (kullanıcının rolüne uygun + henüz aktif atanmamışlar)
        $assignedTemplateIds = $activeAssignments->pluck('role_template_id')->toArray();
        $availableTemplates = RoleTemplate::where('is_active', true)
            ->where(fn ($q) => $q
                ->where('parent_role', $user->role)
                ->orWhere('parent_role', null)
            )
            ->whereNotIn('id', $assignedTemplateIds)
            ->get();

        return view('manager.system.roles.user-profile', compact(
            'user', 'activeAssignments', 'revokedAssignments',
            'effectivePermissions', 'allPermissions', 'availableTemplates'
        ));
    }
}
