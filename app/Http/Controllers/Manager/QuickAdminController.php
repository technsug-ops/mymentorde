<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\SystemEventLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Manager paneline gömülü "hızlı yönetim" endpoint'leri:
 *  - Yeni senior/danışman ekle (sidebar config paneline gitmeden)
 *  - Mevcut kullanıcının rolünü değiştir (geniş rol seti)
 *  - Aday öğrenci (guest application) silme (archive veya hard delete)
 *  - Öğrenci (student assignment) silme (archive veya hard delete)
 *
 * Tüm aksiyonlar system_event_logs'a kaydedilir.
 * Auth: manager.role middleware + permission:config.manage / config.view
 */
class QuickAdminController extends Controller
{
    /** Manager UI'sından atanabilir roller (guest hariç). */
    private const ASSIGNABLE_ROLES = [
        User::ROLE_MANAGER,
        User::ROLE_SENIOR,
        User::ROLE_MENTOR,
        User::ROLE_STUDENT,
        User::ROLE_DEALER,
        User::ROLE_FINANCE_ADMIN,
        User::ROLE_FINANCE_STAFF,
        User::ROLE_OPERATIONS_ADMIN,
        User::ROLE_OPERATIONS_STAFF,
        User::ROLE_SYSTEM_ADMIN,
        User::ROLE_SYSTEM_STAFF,
        User::ROLE_MARKETING_ADMIN,
        User::ROLE_MARKETING_STAFF,
        User::ROLE_SALES_ADMIN,
        User::ROLE_SALES_STAFF,
    ];

    /**
     * POST /manager/quick-admin/senior
     * Yeni senior / mentor / advisor oluştur.
     */
    public function storeSenior(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'role'         => ['nullable', 'string', 'in:senior,mentor'],
            'password'     => ['nullable', 'string', 'min:8', 'max:255'],
            'senior_type'  => ['nullable', 'string', 'max:100'],
            'max_capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $plainPassword = (string) ($data['password'] ?? Str::random(14));
        $role = (string) ($data['role'] ?? User::ROLE_SENIOR);

        $user = User::query()->create([
            'name'                => trim((string) $data['name']),
            'email'               => strtolower(trim((string) $data['email'])),
            'role'                => $role,
            'password'            => Hash::make($plainPassword),
            'senior_type'         => $data['senior_type'] ?? null,
            'max_capacity'        => $data['max_capacity'] ?? 50,
            'auto_assign_enabled' => true,
            'can_view_guest_pool' => true,
            'is_active'           => true,
            'email_verified_at'   => now(),
        ]);

        $this->logEvent('quick_admin.senior_created', [
            'created_user_id' => $user->id,
            'email'           => $user->email,
            'role'            => $role,
        ]);

        return response()->json([
            'ok'                 => true,
            'user'               => $user->only(['id', 'name', 'email', 'role', 'senior_type']),
            'generated_password' => $plainPassword,
            'message'            => "✓ {$role} olarak {$user->name} eklendi. Şifre tek sefer gösterilir.",
        ], 201);
    }

    /**
     * GET /manager/quick-admin/user-by-email?email=...
     * Email ile mevcut kullanıcıyı bul (rol değiştirme öncesi arama).
     */
    public function findUserByEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $email = strtolower(trim((string) $data['email']));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'ok'      => false,
                'found'   => false,
                'message' => 'Bu email ile kayıtlı kullanıcı bulunamadı.',
            ], 404);
        }

        return response()->json([
            'ok'    => true,
            'found' => true,
            'user'  => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'is_active'  => (bool) $user->is_active,
                'created_at' => $user->created_at?->format('d.m.Y'),
            ],
        ]);
    }

    /**
     * POST /manager/quick-admin/assign-role
     * Mevcut kullanıcının rolünü değiştir.
     */
    public function assignRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', 'string', 'in:' . implode(',', self::ASSIGNABLE_ROLES)],
        ]);

        $user = User::query()->findOrFail((int) $data['user_id']);

        if ($user->role === User::ROLE_GUEST) {
            return response()->json([
                'ok'      => false,
                'message' => 'Aday öğrenciler için bu işlem geçerli değil. Önce kullanıcıyı student\'a dönüştür.',
            ], 422);
        }

        $oldRole = $user->role;
        $newRole = (string) $data['role'];

        if ($oldRole === $newRole) {
            return response()->json([
                'ok'      => true,
                'message' => 'Kullanıcı zaten bu role sahip.',
                'user'    => $user->only(['id', 'name', 'email', 'role']),
            ]);
        }

        $user->update(['role' => $newRole]);

        $this->logEvent('quick_admin.role_changed', [
            'user_id'  => $user->id,
            'email'    => $user->email,
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ]);

        return response()->json([
            'ok'      => true,
            'user'    => $user->only(['id', 'name', 'email', 'role']),
            'message' => "✓ {$user->name}: {$oldRole} → {$newRole}",
        ]);
    }

    /**
     * DELETE /manager/quick-admin/guest/{id}?mode=archive|force
     * Aday öğrenci silme (archive yumuşak, force kalıcı).
     */
    public function deleteGuest(Request $request, int $id): JsonResponse
    {
        $mode = $request->query('mode', 'archive');
        $app = GuestApplication::query()->withTrashed()->find($id);
        if (! $app) {
            return response()->json(['ok' => false, 'message' => 'Aday öğrenci bulunamadı.'], 404);
        }

        if ($mode === 'force') {
            // Kalıcı silme — ilgili user kayıtlar nullable FK'lar ile orphan kalır
            $email = $app->email;
            $app->forceDelete();
            // Aynı email'li user hesabı varsa onu da soft delete et (kullanıcı login olmasın)
            if ($email) {
                User::query()->where('email', $email)->where('role', User::ROLE_GUEST)->delete();
            }
            $this->logEvent('quick_admin.guest_force_deleted', [
                'guest_id' => $id,
                'email'    => $email,
            ]);
            return response()->json([
                'ok'      => true,
                'mode'    => 'force',
                'message' => '🔥 Aday öğrenci kalıcı olarak silindi.',
            ]);
        }

        // Archive (soft delete)
        if (! $app->trashed()) {
            $app->delete();
        }
        $this->logEvent('quick_admin.guest_archived', ['guest_id' => $id, 'email' => $app->email]);
        return response()->json([
            'ok'      => true,
            'mode'    => 'archive',
            'message' => '🗑 Aday öğrenci arşivlendi. Gerekirse geri alınabilir.',
        ]);
    }

    /**
     * DELETE /manager/quick-admin/student/{id}?mode=archive|force
     * Öğrenci silme — student_assignments + ilgili user.
     */
    public function deleteStudent(Request $request, int $id): JsonResponse
    {
        $mode = $request->query('mode', 'archive');
        $assignment = StudentAssignment::query()->withTrashed()->find($id);
        if (! $assignment) {
            return response()->json(['ok' => false, 'message' => 'Öğrenci kaydı bulunamadı.'], 404);
        }

        $email = $assignment->guest_email ?? null;

        if ($mode === 'force') {
            $assignment->forceDelete(); // gerçek silme (SoftDeletes bypass)
            if ($email) {
                User::query()->where('email', $email)
                    ->whereIn('role', [User::ROLE_STUDENT, User::ROLE_GUEST])
                    ->delete();
            }
            $this->logEvent('quick_admin.student_force_deleted', [
                'assignment_id' => $id,
                'email'         => $email,
            ]);
            return response()->json([
                'ok'      => true,
                'mode'    => 'force',
                'message' => '🔥 Öğrenci kalıcı olarak silindi (assignment + user).',
            ]);
        }

        // Archive — is_archived flag set
        $assignment->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
        $this->logEvent('quick_admin.student_archived', ['assignment_id' => $id, 'email' => $email]);
        return response()->json([
            'ok'      => true,
            'mode'    => 'archive',
            'message' => '🗑 Öğrenci arşivlendi.',
        ]);
    }

    private function logEvent(string $event, array $meta): void
    {
        try {
            $actor = auth()->user();
            SystemEventLog::query()->create([
                'company_id'  => (int) ($actor?->company_id ?? 1),
                'event_type'  => $event,
                'entity_type' => 'user',
                'entity_id'   => (string) ($meta['user_id'] ?? $meta['guest_id'] ?? $meta['assignment_id'] ?? $meta['created_user_id'] ?? ''),
                'message'     => ($meta['email'] ?? '') . ' · ' . $event,
                'meta'        => $meta,
                'actor_email' => $actor?->email ?? 'system',
            ]);
        } catch (\Throwable $e) {
            // Audit log fail edince işi durdurmayalım
            report($e);
        }
    }
}
