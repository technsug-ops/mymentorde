<?php

namespace App\Services;

use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class StudentGuestResolver
{
    /**
     * Her öğrenci sayfa yüklemesinde 4'e kadar sorgu tetiklenir.
     * Sonuç 30 saniyelik request-arası cache ile korunur.
     * - null sonuç cache'lenmez (henüz eşleşme yok → bir sonraki istekte tekrar denensin)
     * - reconcileLegacyLinks() çalıştıktan SONRA cache yazılır (veri tutarlılığı)
     */
    public function resolveForUser(?User $user): ?GuestApplication
    {
        if (!$user) {
            return null;
        }

        $userId    = (int) ($user->id ?? 0);
        $studentId = trim((string) ($user->student_id ?? ''));
        $email     = strtolower(trim((string) ($user->email ?? '')));

        // Cache key: user_id bazlı, 30 sn TTL
        $cacheKey = 'sgr_uid_' . $userId;

        if ($userId > 0 && Cache::has($cacheKey)) {
            $cachedId = Cache::get($cacheKey);
            $cached   = GuestApplication::find($cachedId);
            if ($cached) {
                return $cached;
            }
            // Cache'deki ID artık geçersiz — temizle ve yeniden çöz
            Cache::forget($cacheKey);
        }

        $resolved = null;

        if ($userId > 0) {
            $resolved = GuestApplication::query()
                ->where('guest_user_id', $userId)
                ->where('converted_to_student', true)
                ->latest('id')
                ->first();
        }

        if (!$resolved && $studentId !== '') {
            $resolved = GuestApplication::query()
                ->where('converted_student_id', $studentId)
                ->where('converted_to_student', true)
                ->latest('id')
                ->first();
        }

        if (!$resolved && $email !== '') {
            $resolved = GuestApplication::query()
                ->where('email', strtolower($email))
                ->where('converted_to_student', true)
                ->latest('id')
                ->first();
        }

        if (!$resolved) {
            $resolved = $this->resolveLegacyCandidate($userId, $studentId, $email);
        }

        if ($resolved) {
            $this->reconcileLegacyLinks($resolved, $userId, $studentId);
            // Reconciliation tamamlandıktan sonra cache'e yaz (sadece ID tutuyoruz)
            if ($userId > 0) {
                Cache::put($cacheKey, $resolved->id, 30);
            }
        }

        return $resolved;
    }

    private function resolveLegacyCandidate(int $userId, string $studentId, string $email): ?GuestApplication
    {
        if ($userId > 0) {
            $byGuestUser = GuestApplication::query()
                ->where('guest_user_id', $userId)
                ->latest('id')
                ->first();
            if ($byGuestUser) {
                return $byGuestUser;
            }
        }

        if ($studentId !== '') {
            $byStudentId = GuestApplication::query()
                ->where('converted_student_id', $studentId)
                ->latest('id')
                ->first();
            if ($byStudentId) {
                return $byStudentId;
            }
        }

        if ($email !== '') {
            $emailMatches = GuestApplication::query()
                ->where('email', strtolower($email))
                ->latest('id')
                ->limit(2)
                ->get();

            if ($emailMatches->count() === 1) {
                return $emailMatches->first();
            }
        }

        return null;
    }

    private function reconcileLegacyLinks(GuestApplication $guest, int $userId, string $studentId): void
    {
        $updates = [];

        if (!$guest->converted_to_student) {
            $updates['converted_to_student'] = true;
        }

        if ($userId > 0 && (int) ($guest->guest_user_id ?? 0) === 0) {
            $updates['guest_user_id'] = $userId;
        }

        $currentStudentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '' && $currentStudentId === '') {
            $updates['converted_student_id'] = $studentId;
        }

        if ($updates !== []) {
            $guest->forceFill($updates)->save();
        }
    }
}
