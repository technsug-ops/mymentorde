<?php

namespace App\Services;

use App\Models\NotificationPreference;

class NotificationPreferenceService
{
    /**
     * Devre dışı bırakılamayan kategoriler.
     * Bu kategoriler her zaman gönderilir; opt-out ayarı dikkate alınmaz.
     */
    public const NON_DISMISSABLE_CATEGORIES = [
        'task_escalation_level3',
        'contract_approved',
        'contract_rejected',
    ];

    /**
     * Belirtilen kullanıcı + kanal + kategori kombinasyonu aktif mi?
     *
     * Kontrol sırası:
     *  1. NON_DISMISSABLE → her zaman true
     *  2. Kanal+kategori için is_enabled=false kayıt varsa → false
     *  3. Kanal için category='*' ve is_enabled=false kayıt varsa → false
     *  4. Kayıt yoksa → varsayılan true
     */
    public function isEnabled(
        ?int    $userId,
        ?string $guestId,
        ?string $studentId,
        string  $channel,
        string  $category,
    ): bool {
        // 1. Devre dışı bırakılamayan kategoriler
        if (in_array($category, self::NON_DISMISSABLE_CATEGORIES, true)) {
            return true;
        }

        $query = NotificationPreference::query()
            ->where('channel', $channel)
            ->where('is_enabled', false);

        // Kimlik filtresi
        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($guestId !== null) {
            $query->where('guest_id', $guestId);
        } elseif ($studentId !== null) {
            $query->where('student_id', $studentId);
        } else {
            return true; // Tanımsız kimlik → izin ver
        }

        // 2. Bu kanal + spesifik kategori için opt-out var mı?
        $specificOptOut = (clone $query)->where('category', $category)->exists();
        if ($specificOptOut) {
            return false;
        }

        // 3. Bu kanal için genel opt-out (category='*') var mı?
        $globalOptOut = (clone $query)->where('category', '*')->exists();
        if ($globalOptOut) {
            return false;
        }

        // 4. Kayıt yok → varsayılan enabled
        return true;
    }

    /**
     * Kullanıcı tercihi kaydet / güncelle (upsert).
     */
    public function setPreference(
        ?int    $userId,
        ?string $guestId,
        ?string $studentId,
        ?int    $companyId,
        string  $channel,
        string  $category,
        bool    $isEnabled,
    ): NotificationPreference {
        // NON_DISMISSABLE kategoriler devre dışı bırakılamaz
        if (!$isEnabled && in_array($category, self::NON_DISMISSABLE_CATEGORIES, true)) {
            $isEnabled = true;
        }

        return NotificationPreference::updateOrCreate(
            [
                'user_id'    => $userId,
                'guest_id'   => $guestId,
                'student_id' => $studentId,
                'channel'    => $channel,
                'category'   => $category,
            ],
            [
                'company_id' => $companyId,
                'is_enabled' => $isEnabled,
            ]
        );
    }

    /**
     * Bir kullanıcının tüm tercihlerini döndürür.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, NotificationPreference>
     */
    public function getPreferences(?int $userId, ?string $guestId, ?string $studentId): \Illuminate\Database\Eloquent\Collection
    {
        $query = NotificationPreference::query();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($guestId !== null) {
            $query->where('guest_id', $guestId);
        } elseif ($studentId !== null) {
            $query->where('student_id', $studentId);
        } else {
            return collect();
        }

        return $query->orderBy('channel')->orderBy('category')->get();
    }
}
