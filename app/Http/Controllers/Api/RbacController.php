<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RoleChangeAudit;
use App\Models\RoleTemplate;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\NotificationService;
use App\Support\SystematicInput;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RbacController extends Controller
{
    public function permissions()
    {
        return Permission::query()->orderBy('category')->orderBy('code')->get();
    }

    public function createPermission(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:120', 'unique:permissions,code'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $code = SystematicInput::permissionCode((string) $data['code'], 'code');
        $category = trim((string) ($data['category'] ?? '')) !== ''
            ? SystematicInput::category((string) $data['category'], 'category', 80)
            : null;

        $permission = Permission::query()->create([
            'code' => $code,
            'category' => $category,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'is_system' => false,
        ]);

        $this->audit($request, 'permission.create', 'permission', (string) $permission->id, [
            'code' => $permission->code,
        ]);

        return response()->json($permission, 201);
    }

    public function templates()
    {
        return RoleTemplate::query()
            ->withCount('permissions')
            ->with(['permissions:id,code'])
            ->orderBy('parent_role')
            ->orderBy('name')
            ->get()
            ->map(function (RoleTemplate $tpl) {
                $arr = $tpl->toArray();
                $arr['permission_codes'] = collect($arr['permissions'] ?? [])->pluck('code')->values()->all();
                return $arr;
            })
            ->values();
    }

    public function createTemplate(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80', 'unique:role_templates,code'],
            'name' => ['required', 'string', 'max:120'],
            'parent_role' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $code = SystematicInput::codeLower((string) $data['code'], 'code', 80);
        $parentRole = trim((string) ($data['parent_role'] ?? '')) !== ''
            ? SystematicInput::codeLower((string) $data['parent_role'], 'parent_role')
            : null;

        $tpl = RoleTemplate::query()->create([
            'code' => $code,
            'name' => trim((string) $data['name']),
            'parent_role' => $parentRole,
            'version' => 1,
            'is_system' => false,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $this->audit($request, 'template.create', 'role_template', (string) $tpl->id, [
            'code' => $tpl->code,
            'parent_role' => $tpl->parent_role,
        ]);

        return response()->json($tpl, 201);
    }

    public function updateTemplate(Request $request, RoleTemplate $roleTemplate)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_role' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'bump_version' => ['nullable', 'boolean'],
        ]);

        $update = [
            'name' => trim((string) $data['name']),
            'parent_role' => trim((string) ($data['parent_role'] ?? '')) !== ''
                ? SystematicInput::codeLower((string) $data['parent_role'], 'parent_role')
                : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
        if ((bool) ($data['bump_version'] ?? false)) {
            $update['version'] = max(1, ((int) $roleTemplate->version) + 1);
        }
        $roleTemplate->update($update);

        $this->audit($request, 'template.update', 'role_template', (string) $roleTemplate->id, $update);

        return response()->json($roleTemplate->fresh());
    }

    public function syncTemplatePermissions(Request $request, RoleTemplate $roleTemplate)
    {
        $data = $request->validate([
            'permission_codes' => ['required', 'array'],
            'permission_codes.*' => ['string', 'max:120'],
        ]);

        $codes = collect($data['permission_codes'])
            ->map(fn ($v) => SystematicInput::permissionCode((string) $v, 'permission_codes'))
            ->filter()
            ->unique()
            ->values();

        $permissionIds = Permission::query()
            ->whereIn('code', $codes->all())
            ->pluck('id')
            ->values()
            ->all();

        $roleTemplate->permissions()->sync($permissionIds);
        $roleTemplate->update(['version' => ((int) $roleTemplate->version) + 1]);

        $this->audit($request, 'template.permissions.sync', 'role_template', (string) $roleTemplate->id, [
            'permission_codes' => $codes->all(),
            'new_version' => $roleTemplate->version,
        ]);

        return response()->json([
            'template_id' => $roleTemplate->id,
            'version' => $roleTemplate->version,
            'permission_count' => count($permissionIds),
        ]);
    }

    public function assignments(Request $request)
    {
        $query = UserRoleAssignment::query()
            ->with(['user:id,name,email,role', 'template:id,code,name,parent_role,version'])
            ->orderByDesc('id');

        $active = $request->query('active');
        if ($active !== null && $active !== '') {
            $query->where('is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        return $query->limit(300)->get();
    }

    public function assignTemplate(Request $request)
    {
        $data = $request->validate([
            'user_email' => ['required', 'email'],
            'template_code' => ['required', 'string', 'max:80'],
            'replace_active' => ['nullable', 'boolean'],
        ]);
        $templateCode = SystematicInput::codeLower((string) $data['template_code'], 'template_code', 80);

        $user = User::query()
            ->where('email', strtolower((string) $data['user_email']))
            ->firstOrFail();
        $template = RoleTemplate::query()
            ->where('code', $templateCode)
            ->firstOrFail();

        if ((bool) ($data['replace_active'] ?? true)) {
            UserRoleAssignment::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'revoked_at' => now(),
                ]);
        }

        $assignment = UserRoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_template_id' => $template->id,
            'assigned_by_user_id' => optional($request->user())->id,
            'version_applied' => (int) $template->version,
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        $this->audit($request, 'assignment.create', 'user_role_assignment', (string) $assignment->id, [
            'user_id' => $user->id,
            'template_id' => $template->id,
            'template_code' => $template->code,
        ]);

        try {
            app(NotificationService::class)->send([
                'channel'     => 'in_app',
                'category'    => 'rbac_change',
                'user_id'     => $user->id,
                'subject'     => 'Yetki Güncelleme',
                'body'        => "Rol şablonunuz güncellendi: {$template->name}",
                'source_type' => 'rbac_assignment',
                'source_id'   => (string) $assignment->id,
                'company_id'  => (int) ($user->company_id ?? 0),
            ]);
        } catch (\Throwable) {
            // bildirim hatası assignment'ı engellemez
        }

        return response()->json($assignment->load(['user:id,name,email,role', 'template:id,code,name,version']), 201);
    }

    public function revokeAssignment(Request $request, UserRoleAssignment $userRoleAssignment)
    {
        $userRoleAssignment->update([
            'is_active' => false,
            'revoked_at' => now(),
        ]);

        $this->audit($request, 'assignment.revoke', 'user_role_assignment', (string) $userRoleAssignment->id, []);

        return response()->json($userRoleAssignment->fresh());
    }

    public function effectivePermissions(User $user)
    {
        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'effective_permissions' => $user->effectivePermissionCodes(),
        ]);
    }

    public function permissionUsageReport(): \Illuminate\Http\JsonResponse
    {
        $allPermissions = Permission::all();
        $assignedCounts = UserRoleAssignment::all()
            ->flatMap(fn ($a) => array_merge(
                is_array($a->permission_codes) ? $a->permission_codes : [],
                is_array($a->custom_permissions) ? $a->custom_permissions : []
            ))
            ->countBy()
            ->all();

        $report = $allPermissions->map(fn ($p) => [
            'code'           => $p->code,
            'label'          => $p->label,
            'category'       => $p->category,
            'assigned_count' => $assignedCounts[$p->code] ?? 0,
            'unused'         => ($assignedCounts[$p->code] ?? 0) === 0,
        ])->sortBy('assigned_count');

        return response()->json(['permissions' => $report->values()]);
    }

    private function audit(Request $request, string $action, string $targetType, string $targetId, array $payload): void
    {
        RoleChangeAudit::query()->create([
            'actor_user_id' => optional($request->user())->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload' => $payload,
        ]);
    }
}
