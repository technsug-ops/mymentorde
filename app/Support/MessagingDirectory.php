<?php

namespace App\Support;

use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Kimlerle yazışılabilir — firma sınırının ötesine geçen tek istisna.
 *
 * ── SORUN ────────────────────────────────────────────────────────────────
 * Mesajlaşma tek firmalıydı: rehber yalnızca kendi şirketinin personelini
 * listeliyordu. Partner firmanın öğrencisine danışmanı ÜST firma atıyor,
 * yani danışman başka bir şirkette. Sonuç: partner, öğrencisiyle ilgilenen
 * danışmana panelden yazamıyordu. Adını ve e-postasını görüyor ama
 * ulaşamıyordu.
 *
 * ── ÇÖZÜM VE SINIRI ──────────────────────────────────────────────────────
 * Firma sınırı KALDIRILMADI. Yalnızca şu kişiler ekleniyor: partnerin KENDİ
 * aday ve öğrencilerine atanmış danışmanlar. Yani "üst firmanın tüm
 * personeli" değil, "benim öğrencimle ilgilenen kişi".
 *
 * Bu ayrım önemli: partner firmalar birbirinden habersiz kalmalı ve üst
 * firmanın iç yapısını görmemeli. Atanmış danışman ise zaten partnerin
 * muhatabı — öğrencisinin sürecini o yürütüyor.
 */
final class MessagingDirectory
{
    /**
     * Bu firmanın öğrencilerine atanmış danışmanlar (başka şirkette olabilir).
     *
     * ⚠ Kapsamsız okunuyor: danışman üst firmanın elemanı olduğu için firma
     * kapsamlı sorgu onu HİÇ bulamaz — bu projede tekrar eden hata sınıfı.
     *
     * @return Collection<int,User>
     */
    public static function assignedAdvisors(int $companyId): Collection
    {
        if ($companyId <= 0) {
            return collect();
        }

        $emails = collect()
            ->merge(StudentAssignment::query()
                ->where('company_id', $companyId)
                ->whereNotNull('senior_email')
                ->pluck('senior_email'))
            ->merge(GuestApplication::query()
                ->where('company_id', $companyId)
                ->whereNotNull('assigned_senior_email')
                ->pluck('assigned_senior_email'))
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return collect();
        }

        return User::query()
            ->withoutGlobalScope('company')
            ->whereIn('email', $emails->all())
            ->where('is_active', true)
            // Kendi şirketindekiler zaten rehberde; burada yalnızca DIŞARIDAKİLER.
            ->where('company_id', '!=', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'email', 'company_id']);
    }

    /**
     * Rehbere eklenecek dış kişilerin id'leri.
     *
     * @return list<int>
     */
    public static function reachableOutsideIds(int $companyId): array
    {
        return self::assignedAdvisors($companyId)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
