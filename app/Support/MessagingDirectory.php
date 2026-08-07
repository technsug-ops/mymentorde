<?php

namespace App\Support;

use App\Models\Company;
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
 * Firma sınırı KALDIRILMADI. Yalnızca iki grup ekleniyor:
 *
 *   1. Firmanın KENDİ aday/öğrencilerine atanmış danışmanlar
 *      → "üst firmanın tüm personeli" değil, "benim öğrencimle ilgilenen kişi"
 *
 *   2. Hiyerarşide bağlı firmaların YÖNETİCİLERİ (üst ve alt)
 *      → sözleşmeli iş ortakları birbirine yazabilmeli
 *
 * ⚠ KARDEŞ FİRMALAR HER İKİ GRUPTA DA YOK. İki partner birbirini görmez;
 * aynı üst firmaya bağlı olmaları onları birbirinin muhatabı yapmaz.
 * Partner firmalar birbirinden habersiz kalmalı.
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
     * Hiyerarşide bağlı firmaların YÖNETİCİLERİ.
     *
     * Partner ile üst firması sözleşmeli iş ortağı; birbirlerine panelden
     * yazabilmeliler. Yön iki taraflı: üst firma alt firmaların, alt firma
     * üstündekilerin yöneticilerini görür.
     *
     * ⚠ KARDEŞ FİRMALAR DAHİL DEĞİL. İki partner birbirini görmemeli —
     * aynı üst firmaya bağlı olmaları onları birbirinin muhatabı yapmaz.
     * Yalnızca ÜST ve ALT firmalar; yan taraftakiler görünmez.
     *
     * ⚠ Yalnızca yönetici rolü. Bir firmanın tüm personelini diğerine açmak
     * gereğinden fazlası olurdu.
     *
     * @return Collection<int,User>
     */
    public static function hierarchyManagers(int $companyId): Collection
    {
        if ($companyId <= 0) {
            return collect();
        }

        $related = array_merge(
            Company::ancestorIds($companyId),
            Company::descendantIds($companyId)
        );

        $related = array_values(array_unique(array_filter(
            $related,
            static fn ($id): bool => (int) $id !== $companyId
        )));

        if ($related === []) {
            return collect();
        }

        return User::query()
            ->withoutGlobalScope('company')
            ->whereIn('company_id', $related)
            ->where('role', User::ROLE_MANAGER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'email', 'company_id']);
    }

    /**
     * Rehbere eklenecek dış kişilerin id'leri.
     *
     * İki kaynak:
     *   • öğrencilerine atanmış danışmanlar — herkese görünür
     *   • dikey ilişkideki firmaların yöneticileri — YALNIZCA yöneticilere
     *
     * ⚠ Rehber ile izin kuralı aynı şeyi söylemeli. Aksi halde tıklanınca
     * "yetkiniz yok" diyen bir isim listelenirdi (bkz.
     * ConversationService::canStartDmWith).
     *
     * @return list<int>
     */
    public static function reachableOutsideIds(int $companyId, ?string $viewerRole = null): array
    {
        $people = self::assignedAdvisors($companyId);

        if ($viewerRole === User::ROLE_MANAGER) {
            $people = $people->merge(self::hierarchyManagers($companyId));
        }

        return $people
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
