<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\EventLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeniorManagementController extends Controller
{
    private const ADVISORY_ROLES = ['senior', 'mentor'];

    public function index()
    {
        $rows = User::query()
            ->whereIn('role', self::ADVISORY_ROLES)
            ->orderBy('name')
            ->get([
                'id',
                'senior_code',
                'name',
                'email',
                'role',
                'senior_type',
                'max_capacity',
                'auto_assign_enabled',
                'can_view_guest_pool',
                'is_active',
                'created_at',
            ]);

        $activeCountsBySenior = StudentAssignment::query()
            ->where('is_archived', false)
            ->whereNotNull('senior_email')
            ->selectRaw('senior_email, COUNT(*) as total')
            ->groupBy('senior_email')
            ->pluck('total', 'senior_email');

        return $rows->map(function (User $user) use ($activeCountsBySenior) {
            $count = (int) ($activeCountsBySenior[$user->email] ?? 0);
            $capacity = (int) ($user->max_capacity ?? 0);
            $capacityPercent = $capacity > 0 ? (int) floor(($count / $capacity) * 100) : null;

            return [
                'id' => $user->id,
                'senior_code' => $user->senior_code,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'senior_type' => $user->senior_type,
                'max_capacity' => $user->max_capacity,
                'auto_assign_enabled' => (bool) $user->auto_assign_enabled,
                'can_view_guest_pool' => (bool) $user->can_view_guest_pool,
                'is_active' => (bool) $user->is_active,
                'active_student_count' => $count,
                'capacity_percent' => $capacityPercent,
                'is_over_capacity' => $capacity > 0 ? $count > $capacity : false,
                'created_at' => $user->created_at,
            ];
        })->values();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['nullable', 'string', 'in:senior,mentor'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'senior_type' => ['nullable', 'string', 'max:100'],
            'max_capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'auto_assign_enabled' => ['nullable', 'boolean'],
            'can_view_guest_pool' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plainPassword = (string) ($data['password'] ?? Str::random(14));
        $role = in_array((string) ($data['role'] ?? 'senior'), self::ADVISORY_ROLES, true)
            ? (string) $data['role']
            : 'senior';
        $generatedCode = $this->generateAdvisoryCode($role);

        $user = User::query()->create([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'role' => $role,
            'senior_code' => $generatedCode['code'],
            'senior_internal_sequence' => $generatedCode['internal_sequence'],
            'senior_type' => $data['senior_type'] ?? null,
            'max_capacity' => $data['max_capacity'] ?? null,
            'auto_assign_enabled' => (bool) ($data['auto_assign_enabled'] ?? true),
            'can_view_guest_pool' => (bool) ($data['can_view_guest_pool'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'password' => $plainPassword,
        ]);

        return response()->json([
            'user' => $user->only(['id', 'senior_code', 'name', 'email', 'role', 'created_at']),
            'generated_password' => $plainPassword,
        ]);
    }

    public function destroy(User $user)
    {
        if (!$this->isAdvisoryRole($user->role)) {
            abort(422, 'Sadece senior/mentor kayitlari silinebilir.');
        }

        $activeAssignments = StudentAssignment::query()
            ->where('senior_email', $user->email)
            ->where('is_archived', false)
            ->count();
        if ($activeAssignments > 0) {
            abort(422, "Bu seniora bagli {$activeAssignments} aktif ogrenci atamasi var. Once atamalari degistir.");
        }

        $user->delete();

        return response()->json(['ok' => true]);
    }

    public function update(User $user, Request $request)
    {
        if (!$this->isAdvisoryRole($user->role)) {
            abort(422, 'Sadece senior/mentor kayitlari guncellenebilir.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['nullable', 'string', 'in:senior,mentor'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'senior_type' => ['nullable', 'string', 'max:100'],
            'max_capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'auto_assign_enabled' => ['nullable', 'boolean'],
            'can_view_guest_pool' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'role' => in_array((string) ($data['role'] ?? $user->role), self::ADVISORY_ROLES, true)
                ? (string) ($data['role'] ?? $user->role)
                : $user->role,
            'senior_type' => $data['senior_type'] ?? null,
            'max_capacity' => $data['max_capacity'] ?? null,
            'auto_assign_enabled' => (bool) ($data['auto_assign_enabled'] ?? true),
            'can_view_guest_pool' => (bool) ($data['can_view_guest_pool'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
        if (!empty($data['password'])) {
            $payload['password'] = (string) $data['password'];
        }

        if (($payload['is_active'] ?? true) === false) {
            $activeAssignments = StudentAssignment::query()
                ->where('senior_email', $user->email)
                ->where('is_archived', false)
                ->count();
            if ($activeAssignments > 0) {
                abort(422, "Bu seniora bagli {$activeAssignments} aktif ogrenci var. Once devret, sonra pasife al.");
            }
        }

        $user->update($payload);

        return response()->json($user->fresh()->only([
            'id',
            'senior_code',
            'name',
            'email',
            'role',
            'senior_type',
            'max_capacity',
            'auto_assign_enabled',
            'can_view_guest_pool',
            'is_active',
            'created_at',
        ]));
    }

    public function resetPassword(User $user)
    {
        if (!$this->isAdvisoryRole($user->role)) {
            abort(422, 'Sadece senior/mentor kayitlarinin sifresi sifirlanabilir.');
        }

        $newPassword = Str::random(14);
        $user->update(['password' => $newPassword]);

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'generated_password' => $newPassword,
        ]);
    }

    private function generateAdvisoryCode(string $role): array
    {
        $year = now()->format('y');
        $month = now()->format('m');
        $prefix = $role === 'mentor' ? 'MTR' : 'SNR';
        $base = "{$prefix}-{$year}-{$month}";
        $nextSequence = ((int) User::query()->whereIn('role', self::ADVISORY_ROLES)->max('senior_internal_sequence')) + 1;

        do {
            $suffix = strtoupper(Str::random(4));
            $suffix = preg_replace('/[^A-Z0-9]/', 'X', $suffix) ?: 'X'.strtoupper(Str::random(3));
            $candidate = "{$base}-{$suffix}";
        } while (User::query()->where('senior_code', $candidate)->exists());

        return [
            'code' => $candidate,
            'internal_sequence' => $nextSequence,
        ];
    }

    public function transfer(User $user, Request $request)
    {
        if (!$this->isAdvisoryRole($user->role)) {
            abort(422, 'Sadece senior/mentor kayitlarindan devretme yapilabilir.');
        }

        $data = $request->validate([
            'target_senior_email' => ['nullable', 'email'],
            'target_email' => ['nullable', 'email'],
        ]);
        $targetEmail = trim((string) ($data['target_senior_email'] ?? $data['target_email'] ?? ''));
        if ($targetEmail === '') {
            abort(422, 'Hedef senior/mentor email zorunlu.');
        }

        $target = User::query()
            ->whereIn('role', self::ADVISORY_ROLES)
            ->where('is_active', true)
            ->where('email', $targetEmail)
            ->first();
        if (!$target) {
            abort(422, 'Hedef senior/mentor bulunamadi veya aktif degil.');
        }
        if ($target->email === $user->email) {
            abort(422, 'Kaynak ve hedef senior/mentor ayni olamaz.');
        }

        $sourceCount = StudentAssignment::query()
            ->where('senior_email', $user->email)
            ->where('is_archived', false)
            ->count();
        if ($sourceCount < 1) {
            return response()->json(['moved' => 0]);
        }

        if ($target->max_capacity) {
            $targetCurrent = StudentAssignment::query()
                ->where('senior_email', $target->email)
                ->where('is_archived', false)
                ->count();
            if (($targetCurrent + $sourceCount) > (int) $target->max_capacity) {
                abort(422, "Hedef kapasite yetersiz: {$targetCurrent}/{$target->max_capacity} + {$sourceCount} devredilecek.");
            }
        }

        $moved = StudentAssignment::query()
            ->where('senior_email', $user->email)
            ->where('is_archived', false)
            ->update(['senior_email' => $target->email]);

        app(EventLogService::class)->log(
            'senior_transfer',
            'user',
            (string) $user->id,
            "Senior transfer: {$user->email} → {$target->email} ({$moved} öğrenci)",
            [
                'source_email' => $user->email,
                'target_email' => $target->email,
                'moved_count'  => $moved,
                'ip'           => $request->ip(),
            ],
            $request->user()?->email,
        );

        return response()->json([
            'moved' => $moved,
            'source' => $user->email,
            'target' => $target->email,
        ]);
    }

    private function isAdvisoryRole(?string $role): bool
    {
        return in_array((string) $role, self::ADVISORY_ROLES, true);
    }
}
