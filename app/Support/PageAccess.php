<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Premium 'page_visibility' modülü — manager rol-bazlı sayfa görünürlüğünü
 * yönetir. ModuleAccess (şirket-bazlı) yerine bu helper rol × sayfa kontrolü
 * sağlar.
 *
 * Kullanım:
 *   PageAccess::visible('discover', 'guest')        → bool
 *   PageAccess::visible('discover')                  → mevcut user rolüne göre
 *   PageAccess::assertVisible('discover', 'guest')   → 404 if hidden
 *
 * Blade:
 *   @pageVisible('discover') ... @endpageVisible
 *
 * Yokluk durumunda default: TRUE (yeni sayfa eklenince otomatik görünür).
 * Manager UI'dan setting yazılınca DB tutar.
 */
class PageAccess
{
    /**
     * Görünürlüğü kontrol edilebilir sayfaların kataloğu.
     *
     * Yapı:
     *   page_key => [
     *     'label'  => 'Görünüm adı',
     *     'roles'  => ['guest', 'student', 'dealer', 'senior'],
     *     'group'  => 'Bölüm grubu (UI matrix'inde başlık)',
     *   ]
     *
     * Yeni sayfa eklemek = bu listeye satır ekle + ilgili sidebar/route'ta
     * @pageVisible('key') gate'i koy.
     */
    public const PAGES = [
        // ── İçerik ──
        'discover'         => ['label' => 'Keşfet',                'group' => 'İçerik', 'roles' => ['guest', 'student', 'dealer', 'senior']],
        'marketplace'      => ['label' => 'Marketplace',           'group' => 'İçerik', 'roles' => ['guest', 'student']],
        'living_guide'     => ['label' => 'Yaşam Rehberi',         'group' => 'İçerik', 'roles' => ['guest', 'student']],
        'document_guide'   => ['label' => 'Belge Rehberi',         'group' => 'İçerik', 'roles' => ['guest', 'student']],
        'visa_guide'       => ['label' => 'Vize Rehberi',          'group' => 'İçerik', 'roles' => ['guest', 'student']],
        'ai_assistant'     => ['label' => 'AI Asistan',            'group' => 'İçerik', 'roles' => ['guest', 'student', 'senior']],

        // ── İletişim ──
        'messages'         => ['label' => 'Mesajlar',              'group' => 'İletişim', 'roles' => ['guest', 'student', 'dealer', 'senior']],
        'support'          => ['label' => 'Destek / Tickets',      'group' => 'İletişim', 'roles' => ['guest', 'student', 'dealer', 'senior']],
        'announcements'    => ['label' => 'Duyurular',             'group' => 'İletişim', 'roles' => ['guest', 'student', 'dealer', 'senior']],

        // ── Süreç ──
        'process_tracking' => ['label' => 'Süreç Takibi',          'group' => 'Süreç', 'roles' => ['guest', 'student']],
        'appointments'     => ['label' => 'Randevular',            'group' => 'Süreç', 'roles' => ['guest', 'student', 'senior']],
        'calendar'         => ['label' => 'Takvim',                'group' => 'Süreç', 'roles' => ['guest', 'student', 'senior']],
        'todos'            => ['label' => 'Yapılacaklar',          'group' => 'Süreç', 'roles' => ['guest', 'student', 'dealer', 'senior']],

        // ── Bayi'ye özel ──
        'dealer_finance'   => ['label' => 'Komisyon / Finans',     'group' => 'Bayi',   'roles' => ['dealer']],
        'dealer_leads'     => ['label' => 'Lead Yönetimi',         'group' => 'Bayi',   'roles' => ['dealer']],
        'dealer_marketing' => ['label' => 'Pazarlama Materyalleri','group' => 'Bayi',   'roles' => ['dealer']],

        // ── Senior'a özel ──
        'senior_pipeline'  => ['label' => 'Pipeline / Kanban',     'group' => 'Senior', 'roles' => ['senior']],
        'senior_kpi'       => ['label' => 'KPI Performans',        'group' => 'Senior', 'roles' => ['senior']],
    ];

    /**
     * Görünürlük kontrolü yapılan default roller.
     * Staff alt rolleri (system_staff, operations_staff, vb.) gelecekte eklenince
     * `staffRoles()` ile dinamik genişler.
     *
     * @return array<string,string>
     */
    public static function coreRoles(): array
    {
        return [
            'guest'   => 'Aday Öğrenci',
            'student' => 'Öğrenci',
            'dealer'  => 'Bayi',
            'senior'  => 'Eğitim Danışmanı',
        ];
    }

    /**
     * Manager altındaki staff rolleri (admin altı). İleride büyürse otomatik
     * matrix'e dahil olur.
     *
     * @return array<string,string>
     */
    public static function staffRoles(): array
    {
        return [
            'system_staff'      => 'Sistem Personeli',
            'operations_staff'  => 'Operasyon Personeli',
            'finance_staff'     => 'Finans Personeli',
            'marketing_staff'   => 'Pazarlama Personeli',
            'sales_staff'       => 'Satış Personeli',
        ];
    }

    /**
     * Tüm yönetilebilir roller (matrix UI bunu kullanır).
     * @return array<string,string>
     */
    public static function allRoles(): array
    {
        return array_merge(self::coreRoles(), self::staffRoles());
    }

    /**
     * Sayfa rolüne görünüyor mu?
     * - Modül kapalı = false (premium gate)
     * - DB'de kayıt varsa onu kullan
     * - Yoksa default TRUE
     */
    public static function visible(string $pageKey, ?string $role = null, ?int $companyId = null): bool
    {
        $cid  = $companyId ?? self::resolveCurrentCompanyId();
        $role = $role ?? self::resolveCurrentRole();

        if ($cid <= 0 || $role === '') {
            return true; // unauth state — kısıtlama yok
        }

        // Modül kapalı ise her şey default açık (paywalled değil — sadece manager UI yok)
        if (!ModuleAccess::enabled('page_visibility', $cid)) {
            return true;
        }

        $map = self::loadVisibilityMap($cid);
        $key = $role . '|' . $pageKey;

        return $map[$key] ?? true;
    }

    public static function assertVisible(string $pageKey, ?string $role = null, ?int $companyId = null): void
    {
        if (!self::visible($pageKey, $role, $companyId)) {
            abort(404);
        }
    }

    /**
     * Manager toggle endpoint için: tek bir setting kaydet.
     */
    public static function setVisibility(int $companyId, string $role, string $pageKey, bool $isVisible, ?int $byUserId = null): void
    {
        if (!Schema::hasTable('role_page_visibility')) return;

        DB::table('role_page_visibility')->updateOrInsert(
            ['company_id' => $companyId, 'role' => $role, 'page_key' => $pageKey],
            [
                'is_visible'         => $isVisible ? 1 : 0,
                'updated_by_user_id' => $byUserId,
                'updated_at'         => now(),
                'created_at'         => now(),
            ]
        );

        self::flushCache($companyId);
    }

    /**
     * Toplu güncelleme (manager matrix submit için).
     *
     * @param  array<int,array{role:string,page_key:string,is_visible:bool}>  $rows
     */
    public static function setBulk(int $companyId, array $rows, ?int $byUserId = null): void
    {
        if (!Schema::hasTable('role_page_visibility')) return;

        DB::transaction(function () use ($companyId, $rows, $byUserId) {
            foreach ($rows as $r) {
                DB::table('role_page_visibility')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'role'       => (string) $r['role'],
                        'page_key'   => (string) $r['page_key'],
                    ],
                    [
                        'is_visible'         => !empty($r['is_visible']) ? 1 : 0,
                        'updated_by_user_id' => $byUserId,
                        'updated_at'         => now(),
                        'created_at'         => now(),
                    ]
                );
            }
        });
        self::flushCache($companyId);
    }

    /**
     * @return array<string,bool>  ['{role}|{page_key}' => bool]
     */
    private static function loadVisibilityMap(int $companyId): array
    {
        return Cache::remember(
            "page_visibility:{$companyId}",
            300,
            function () use ($companyId): array {
                if (!Schema::hasTable('role_page_visibility')) return [];

                return DB::table('role_page_visibility')
                    ->where('company_id', $companyId)
                    ->get(['role', 'page_key', 'is_visible'])
                    ->mapWithKeys(fn ($r) => [$r->role . '|' . $r->page_key => (bool) $r->is_visible])
                    ->all();
            }
        );
    }

    public static function flushCache(int $companyId): void
    {
        Cache::forget("page_visibility:{$companyId}");
    }

    private static function resolveCurrentCompanyId(): int
    {
        if (app()->bound('current_company_id')) {
            return (int) app('current_company_id');
        }
        $user = auth()->user();
        return (int) ($user?->company_id ?? 0);
    }

    private static function resolveCurrentRole(): string
    {
        $user = auth()->user();
        return (string) ($user?->role ?? '');
    }
}
