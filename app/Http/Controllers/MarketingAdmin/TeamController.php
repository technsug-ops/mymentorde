<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\MarketingTeam;
use App\Models\Permission;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

class TeamController extends Controller
{
    public function index()
    {
        $teams = MarketingTeam::query()
            ->with(['user:id,name,email,role,is_active'])
            ->orderBy('id')
            ->get();

        $catalog = $this->permissionCatalog();

        return view('marketing-admin.team.index', [
            'pageTitle' => 'Ekip Yonetimi',
            'title' => 'Marketing Team',
            'teams' => $teams,
            'permissionCatalog' => $catalog,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function invite(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:'.implode(',', $this->roleOptions())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:120'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        $created = false;
        $tempPassword = null;
        if (!$user) {
            $created = true;
            $tempPassword = Str::random(14);
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($tempPassword),
                'role' => $data['role'],
                'is_active' => true,
            ]);
        } else {
            $user->update([
                'name' => $data['name'],
                'role' => $data['role'],
            ]);
        }

        $permissions = $this->normalizePermissions((array) ($data['permissions'] ?? []));
        if ($permissions === []) {
            $permissions = $this->defaultPermissionsByRole($data['role']);
        }

        MarketingTeam::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['role' => $data['role'], 'permissions' => $permissions]
        );

        $message = $created
            ? "Kullanici davet edildi. Gecici sifre: {$tempPassword}"
            : 'Kullanici rolu/yetkileri guncellendi.';

        return $this->responseFor($request, [
            'ok' => true,
            'created' => $created,
            'temp_password' => $tempPassword,
            'user_id' => (string) $user->id,
        ], $message, Response::HTTP_CREATED);
    }

    public function updatePermissions(Request $request, string $userId)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:120'],
            'role' => ['nullable', 'string', 'in:'.implode(',', $this->roleOptions())],
        ]);

        $team = MarketingTeam::query()->where('user_id', $userId)->firstOrFail();
        $user = User::query()->findOrFail($userId);

        $role = (string) ($data['role'] ?? $team->role ?? $user->role ?? User::ROLE_MARKETING_STAFF);
        $permissions = $this->normalizePermissions((array) ($data['permissions'] ?? []));
        if ($permissions === []) {
            $permissions = $this->defaultPermissionsByRole($role);
        }

        $team->update([
            'role' => $role,
            'permissions' => $permissions,
        ]);

        if ((string) $user->role !== $role) {
            $user->update(['role' => $role]);
        }

        return $this->responseFor($request, [
            'ok' => true,
            'team' => $team->fresh(),
        ], 'Ekip kullanicisi guncellendi.');
    }

    public function remove(Request $request, string $userId)
    {
        $data = $request->validate([
            'action' => ['nullable', 'string', 'in:remove,deactivate'],
        ]);

        $action = (string) ($data['action'] ?? 'remove');
        $team   = MarketingTeam::query()->where('user_id', $userId)->firstOrFail();
        $user   = User::query()->find($userId);

        $team->delete();

        $message = 'Ekip kaydi silindi.';

        if ($user) {
            if ($action === 'deactivate') {
                $updateData = ['is_active' => false];
                if (in_array((string) $user->role, $this->roleOptions(), true)) {
                    $updateData['role'] = User::ROLE_STUDENT;
                }
                $user->update($updateData);
                $message = 'Kullanici ekipten cikarildi ve hesabi pasif yapildi.';
            } else {
                // remove: only downgrade role
                if (in_array((string) $user->role, $this->roleOptions(), true)) {
                    $user->update(['role' => User::ROLE_STUDENT]);
                }
                $message = 'Kullanici ekipten cikarildi.';
            }
        }

        return $this->responseFor($request, ['ok' => true], $message);
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }

        return redirect('/mktg-admin/team')->with('status', $statusMessage);
    }

    private function roleOptions(): array
    {
        return [
            User::ROLE_MARKETING_ADMIN,
            User::ROLE_SALES_ADMIN,
            User::ROLE_MARKETING_STAFF,
            User::ROLE_SALES_STAFF,
        ];
    }

    private function normalizePermissions(array $permissions): array
    {
        return collect($permissions)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function defaultPermissionsByRole(string $role): array
    {
        $defaults = (array) (User::ROLE_DEFAULT_PERMISSION_CODES[$role] ?? []);

        return collect($defaults)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function permissionCatalog(): Collection
    {
        $rows = Permission::query()
            ->orderByRaw('COALESCE(category, ?)', ['genel'])
            ->orderBy('code')
            ->get(['code', 'category', 'description']);

        if ($rows->isEmpty()) {
            $fallback = collect(User::ROLE_DEFAULT_PERMISSION_CODES)
                ->flatten()
                ->map(fn ($code) => [
                    'code' => (string) $code,
                    'category' => 'genel',
                    'description' => null,
                ])
                ->unique('code')
                ->values();

            return $fallback->groupBy('category');
        }

        return $rows->map(fn ($row) => [
            'code' => (string) $row->code,
            'category' => (string) ($row->category ?: 'genel'),
            'description' => $row->description,
        ])->groupBy('category');
    }
}
