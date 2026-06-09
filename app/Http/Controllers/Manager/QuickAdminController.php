<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetByManagerMail;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\SystemEventLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
    /**
     * Manager UI'sından atanabilir roller — tüm portal rolleri.
     * Manager istediği herkesin rolünü istediği role çevirebilir.
     */
    private const ASSIGNABLE_ROLES = [
        User::ROLE_MANAGER,
        User::ROLE_SENIOR,
        User::ROLE_MENTOR,
        User::ROLE_STUDENT,
        User::ROLE_GUEST,
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
     * Soft-deleted user (örn. silinen aday öğrenci) varsa restore + güncelle.
     */
    public function storeSenior(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            // Sadece aktif (not soft-deleted) kullanıcılarda unique
            'email'        => ['required', 'email:rfc', 'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->whereNull('deleted_at')],
            'role'         => ['nullable', 'string', 'in:senior,mentor'],
            'password'     => ['nullable', 'string', 'min:8', 'max:255'],
            'senior_type'  => ['nullable', 'string', 'max:100'],
            'max_capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $plainPassword = (string) ($data['password'] ?? Str::random(14));
        $role = (string) ($data['role'] ?? User::ROLE_SENIOR);
        $email = strtolower(trim((string) $data['email']));

        // Eski silinmiş (soft-deleted) bir user varsa restore et + güncelle.
        $trashed = User::onlyTrashed()->where('email', $email)->first();
        if ($trashed) {
            $trashed->restore();
            $trashed->update([
                'name'                => trim((string) $data['name']),
                'role'                => $role,
                'password'            => Hash::make($plainPassword),
                'senior_type'         => $data['senior_type'] ?? null,
                'max_capacity'        => $data['max_capacity'] ?? 50,
                'auto_assign_enabled' => true,
                'can_view_guest_pool' => true,
                'is_active'           => true,
                'email_verified_at'   => now(),
                'student_id'          => null,
            ]);
            $user = $trashed;
        } else {
            $user = User::query()->create([
                'name'                => trim((string) $data['name']),
                'email'               => $email,
                'role'                => $role,
                'password'            => Hash::make($plainPassword),
                'senior_type'         => $data['senior_type'] ?? null,
                'max_capacity'        => $data['max_capacity'] ?? 50,
                'auto_assign_enabled' => true,
                'can_view_guest_pool' => true,
                'is_active'           => true,
                'email_verified_at'   => now(),
            ]);
        }

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
            // Kalıcı silme — ilgili user da forceDelete (email tekrar kullanılabilsin)
            $email = $app->email;
            $app->forceDelete();
            if ($email) {
                // Aynı email'li guest user kaydı varsa gerçek silinir (soft delete değil)
                // → email bir daha senior/portal user olarak kayıt için müsait kalır
                User::query()->withTrashed()
                    ->where('email', $email)
                    ->where('role', User::ROLE_GUEST)
                    ->forceDelete();
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
                // Aynı email'li student/guest user da gerçek silinir → email yeniden kullanılabilir
                User::query()->withTrashed()
                    ->where('email', $email)
                    ->whereIn('role', [User::ROLE_STUDENT, User::ROLE_GUEST])
                    ->forceDelete();
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

    /**
     * DELETE /manager/quick-admin/senior/{id}?mode=archive|force
     * Senior/mentor silme — User kaydı + StudentAssignment ilişkileri.
     *
     * Senior = User with role=senior|mentor. Atanmış öğrencisi varsa:
     * - archive: user.is_active = false (soft deactivate) — atamaları korunur
     * - force: User::forceDelete() + assignment.senior_email NULL set
     */
    public function deleteSenior(Request $request, int $id): JsonResponse
    {
        $mode = $request->query('mode', 'archive');
        $user = User::query()->withTrashed()->find($id);
        if (! $user || ! in_array($user->role, [User::ROLE_SENIOR, User::ROLE_MENTOR], true)) {
            return response()->json(['ok' => false, 'message' => 'Senior/Mentor bulunamadı.'], 404);
        }

        $email = $user->email;
        $name  = $user->name ?: $email;

        // Bu senior'a atanmış öğrenci/aday öğrenci sayısı
        $assignedStudentCount = StudentAssignment::query()
            ->where('senior_email', $email)
            ->where('is_archived', false)
            ->count();
        $assignedGuestCount = GuestApplication::query()
            ->where('assigned_senior_email', $email)
            ->whereNull('converted_student_id')
            ->count();

        if ($mode === 'force') {
            // Atamaları sıfırla — senior_email NULL → öğrenciler "atanmamış" olur, sonra başkasına atanabilir
            StudentAssignment::query()
                ->where('senior_email', $email)
                ->update(['senior_email' => null]);
            GuestApplication::query()
                ->where('assigned_senior_email', $email)
                ->update(['assigned_senior_email' => null, 'assigned_at' => null]);

            $user->forceDelete(); // gerçek silme — email tekrar kullanılabilir

            $this->logEvent('quick_admin.senior_force_deleted', [
                'user_id'              => $id,
                'email'                => $email,
                'name'                 => $name,
                'released_assignments' => $assignedStudentCount + $assignedGuestCount,
            ]);
            return response()->json([
                'ok'      => true,
                'mode'    => 'force',
                'message' => "🔥 Senior '{$name}' kalıcı olarak silindi. {$assignedStudentCount} öğrenci + {$assignedGuestCount} aday atamasız bırakıldı.",
            ]);
        }

        // Archive — is_active=false (soft deactivate). Atamaları korunur ama login engellenir.
        $user->update(['is_active' => false]);
        $this->logEvent('quick_admin.senior_archived', [
            'user_id'             => $id,
            'email'               => $email,
            'name'                => $name,
            'active_assignments'  => $assignedStudentCount + $assignedGuestCount,
        ]);
        return response()->json([
            'ok'      => true,
            'mode'    => 'archive',
            'message' => "🗑 Senior '{$name}' pasif edildi (login engellendi). {$assignedStudentCount} öğrenci + {$assignedGuestCount} aday ataması korundu.",
        ]);
    }

    /**
     * DELETE /manager/quick-admin/dealer/{id}?mode=archive|force
     * Dealer silme — Dealer kaydı + ilgili user.
     *
     * - archive: Dealer.is_archived=true (geri alınabilir)
     * - force: Dealer::forceDelete() + User::forceDelete() — email tekrar kullanılabilir
     */
    public function deleteDealer(Request $request, int $id): JsonResponse
    {
        $mode = $request->query('mode', 'archive');
        $dealer = \App\Models\Dealer::query()->withTrashed()->find($id);
        if (! $dealer) {
            return response()->json(['ok' => false, 'message' => 'Bayi bulunamadı.'], 404);
        }

        $email = $dealer->email;
        $code  = $dealer->code;
        $name  = $dealer->name ?: $code;

        // Bu dealer'a ait lead sayısı (silinince leadler dealer'sız kalır)
        $leadCount = GuestApplication::query()->where('dealer_code', $code)->count();

        if ($mode === 'force') {
            // GuestApplication.dealer_code NULL set — dealer'siz kalsınlar
            GuestApplication::query()->where('dealer_code', $code)
                ->update(['dealer_code' => null]);

            $dealer->forceDelete(); // gerçek silme (SoftDeletes bypass)
            if ($email) {
                // Aynı email'li dealer user'ı da force delete → email yeniden kullanılabilir
                User::query()->withTrashed()
                    ->where('email', $email)
                    ->where('role', User::ROLE_DEALER)
                    ->forceDelete();
            }

            $this->logEvent('quick_admin.dealer_force_deleted', [
                'dealer_id'      => $id,
                'code'           => $code,
                'email'          => $email,
                'name'           => $name,
                'released_leads' => $leadCount,
            ]);
            return response()->json([
                'ok'      => true,
                'mode'    => 'force',
                'message' => "🔥 Bayi '{$name}' ({$code}) kalıcı olarak silindi. {$leadCount} lead dealer'sız bırakıldı.",
            ]);
        }

        // Archive — is_archived=true (geri alınabilir, leadler bağlı kalır)
        $dealer->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => auth()->user()?->email,
            'is_active'   => false,
        ]);
        $this->logEvent('quick_admin.dealer_archived', [
            'dealer_id' => $id,
            'code'      => $code,
            'email'     => $email,
            'name'      => $name,
        ]);
        return response()->json([
            'ok'      => true,
            'mode'    => 'archive',
            'message' => "🗑 Bayi '{$name}' ({$code}) arşivlendi. {$leadCount} lead bağlı kaldı, gerekirse geri alınabilir.",
        ]);
    }

    /**
     * POST /manager/quick-admin/reset-password
     * Mail göndermeden manuel şifre sıfırlama. Yeni şifre cevapta plain
     * dönülür — manager tek sefer görür, öğrenciye manuel iletir.
     *
     * Kullanım: mail teslim sorunu olan kullanıcılar, halen aktif olan
     * unutmuş öğrenciler vs. için.
     */
    public function resetUserPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'        => ['required', 'email:rfc', 'max:255'],
            'name'         => ['nullable', 'string', 'max:255'],
            'default_role' => ['nullable', 'string', 'in:guest,student'],
        ]);

        $email = strtolower(trim((string) $data['email']));
        // Soft-deleted dahil ara — silinmişse restore et
        $user  = User::query()->withTrashed()->where('email', $email)->first();
        // Manager şifreyi GÖRMEZ — backend tarafında üretilir, sadece mail'le gider
        $tempPassword = Str::random(12);
        $created = false;

        if (! $user) {
            $user = User::query()->create([
                'name'                 => trim((string) ($data['name'] ?? $email)),
                'email'                => $email,
                'role'                 => (string) ($data['default_role'] ?? User::ROLE_GUEST),
                'password'             => Hash::make($tempPassword),
                'password_must_change' => true,
                'is_active'            => true,
                'email_verified_at'    => now(),
            ]);
            $created = true;
        } else {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->update([
                'password'             => Hash::make($tempPassword),
                'password_must_change' => true,
                'email_verified_at'    => $user->email_verified_at ?? now(),
                'is_active'            => true,
            ]);
        }

        // Mail kullanıcıya gönderilir — manager şifreyi görmez
        $mailSent = true;
        $mailError = null;
        try {
            Mail::to($user->email)->send(new PasswordResetByManagerMail(
                name: $user->name ?: $user->email,
                email: $user->email,
                tempPassword: $tempPassword,
                loginUrl: url('/login'),
            ));
        } catch (\Throwable $e) {
            $mailSent = false;
            $mailError = $e->getMessage();
            Log::error('Password reset mail failed', [
                'email' => $user->email,
                'error' => $mailError,
            ]);
        }

        $this->logEvent($created ? 'quick_admin.user_created_pwd' : 'quick_admin.password_reset', [
            'user_id'   => $user->id,
            'email'     => $user->email,
            'role'      => $user->role,
            'created'   => $created,
            'mail_sent' => $mailSent,
        ]);

        if (! $mailSent) {
            return response()->json([
                'ok'      => false,
                'message' => "Şifre değişti ama MAIL GÖNDERİLEMEDİ: {$mailError}. "
                           . "Kullanıcı şu an giriş yapamaz — info@panel.mentorde.com ile iletişime geçmesi gerekir.",
            ], 502);
        }

        $msg = $created
            ? "✓ Hesap oluşturuldu + geçici şifre mail ile {$user->email} adresine gönderildi. Kullanıcı giriş yapınca şifresini yeniden belirleyecek."
            : "✓ Geçici şifre mail ile {$user->email} adresine gönderildi. Kullanıcı giriş yapınca şifresini yeniden belirleyecek.";

        return response()->json([
            'ok'      => true,
            'created' => $created,
            'user'    => $user->only(['id', 'name', 'email', 'role']),
            // generated_password İSTEYEREK döndürülmüyor — manager şifreyi görmez
            'message' => $msg,
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
