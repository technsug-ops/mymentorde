<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS modül toggle yardımcısı.
 *
 * Kullanım:
 *   ModuleAccess::enabled('booking')                // current company için
 *   ModuleAccess::enabled('booking', $companyId)    // belirli company için
 *   ModuleAccess::assertEnabled('booking')          // enabled değilse 404
 *
 * Blade:
 *   @module('booking') ... @endmodule
 */
class ModuleAccess
{
    /** Varsayılan: tüm modüller açık — migration sonrası companies.enabled_modules doldurulur. */
    private const DEFAULT_MODULES = [
        'core',
        'booking',
        'dam',
        'content_hub',
        'dealer',
        'marketing_admin',
        'analytics_hub',
        'doc_builder_ai',
        'contracts_hub',
        'multi_provider_ai',
        'ai_labs',
        'doc_request',     // Premium: tek-kullanımlık link ile aday öğrenciden belge talep
        'page_visibility', // Premium: rol-bazlı sayfa görünürlüğü (manager kontrol panelinden)
        'discount_codes',  // Manager üretir, aday services sayfasında uygular + paylaşım kartı
        'silence_checkin', // Aday/öğrenci timeline sessizliğinde otomatik "süreç aktif" touchpoint
        'application_guides', // APS / Anmeldung / Sperrkonto / VPD / Uni-Assist / Vize rehberleri
        'manager_password_reset', // Manager paneli içinden kullanıcı şifresi sıfırlama (mail gönder)
    ];

    /**
     * Manager admin UI için modül meta — TR label + grup + açıklama.
     * Yeni modül eklendiğinde DEFAULT_MODULES + bu array senkron tutulmalı.
     */
    public const MODULE_META = [
        'core'              => ['label' => 'Çekirdek',                'group' => 'core',     'desc' => 'Login, dashboard, profil — kapatılamaz', 'locked' => true],
        'booking'           => ['label' => 'Booking / Randevu',       'group' => 'gold',     'desc' => 'Calendly-style randevu sistemi (senior + public widget)'],
        'dam'               => ['label' => 'Marka Kütüphanesi (DAM)', 'group' => 'gold',     'desc' => 'Dijital varlık yönetimi — bayi/öğrenci dosya paylaşımı'],
        'content_hub'       => ['label' => 'Keşfet / İçerik',         'group' => 'gold',     'desc' => 'CMS içerik havuzu, öğrenci/aday görür'],
        'multi_provider_ai' => ['label' => 'Çoklu AI Provider',       'group' => 'gold',     'desc' => 'Claude/Gemini/OpenAI birden fazla provider'],
        'doc_builder_ai'    => ['label' => 'Doküman Oluşturucu AI',   'group' => 'gold',     'desc' => 'AI ile motivation letter / CV üretimi'],
        'ai_labs'           => ['label' => 'AI Labs / Asistan',       'group' => 'gold',     'desc' => 'NotebookLM-benzeri bilgi havuzu + AI sorgulama'],
        'contracts_hub'     => ['label' => 'Sözleşmeler Hub',         'group' => 'premium',  'desc' => 'İş sözleşmesi şablonları + dijital imza akışı'],
        'doc_request'       => ['label' => 'Belge Talep Linki',       'group' => 'premium',  'desc' => 'Tek-kullanımlık link ile aday öğrenciden belge talep'],
        'page_visibility'   => ['label' => 'Sayfa Görünürlüğü',       'group' => 'premium',  'desc' => 'Rol bazlı sayfa kapatma — manager kontrolünde'],
        'discount_codes'    => ['label' => 'İndirim Kodları',         'group' => 'premium',  'desc' => 'Hizmet paketlerinde indirim kodu uygulama + paylaşım kartı'],
        'silence_checkin'   => ['label' => 'Sessizlik Checkin',       'group' => 'premium',  'desc' => 'Aday/öğrenci sessizliğinde otomatik "süreç aktif" mesaj'],
        'application_guides'=> ['label' => 'Başvuru Rehberleri',      'group' => 'gold',     'desc' => 'APS / Anmeldung / Sperrkonto / VPD adım adım rehberleri'],
        'manager_password_reset' => ['label' => 'Şifre Sıfırlama',    'group' => 'premium',  'desc' => 'Manager paneli içinden mail ile zorunlu şifre değişim akışı'],
        'dealer'            => ['label' => 'Bayi / Satış Ortağı',     'group' => 'premium',  'desc' => 'Satış ortakları portali + komisyon takibi'],
        'marketing_admin'   => ['label' => 'Marketing Admin',         'group' => 'premium',  'desc' => 'Email drip, A/B test, attribution dashboard'],
        'analytics_hub'     => ['label' => 'Analytics Hub',           'group' => 'premium',  'desc' => 'Platform analytics, performans raporları'],
    ];

    /** Modül kodlarının kategorize listesini dön (manager UI grupları için). */
    public static function moduleGroups(): array
    {
        return [
            'core'    => 'Çekirdek (kapatılamaz)',
            'gold'    => 'Gold paket modülleri',
            'premium' => 'Premium paket modülleri',
        ];
    }

    /** Tüm bilinen modül kodlarını dön (DEFAULT_MODULES). */
    public static function allModules(): array
    {
        return self::DEFAULT_MODULES;
    }

    public static function enabled(string $module, ?int $companyId = null): bool
    {
        $module = strtolower(trim($module));
        if ($module === '' || $module === 'core') {
            return true;
        }

        $cid = $companyId ?? self::resolveCurrentCompanyId();
        if ($cid <= 0) {
            // Company yoksa (unauth state) core dışı modül kapalı say
            return false;
        }

        $enabled = self::loadEnabledModules($cid);
        return in_array($module, $enabled, true);
    }

    public static function assertEnabled(string $module, ?int $companyId = null): void
    {
        if (!self::enabled($module, $companyId)) {
            abort(404, "Modül '{$module}' bu planda mevcut değil.");
        }
    }

    /** @return array<int,string> */
    public static function enabledModules(?int $companyId = null): array
    {
        $cid = $companyId ?? self::resolveCurrentCompanyId();
        if ($cid <= 0) {
            return ['core'];
        }
        return self::loadEnabledModules($cid);
    }

    /** @return array<int,string> */
    private static function loadEnabledModules(int $companyId): array
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'enabled_modules')) {
            return self::DEFAULT_MODULES;
        }

        return Cache::remember(
            "company:{$companyId}:enabled_modules",
            300,
            function () use ($companyId): array {
                // SaaS tier fallback: company.enabled_modules NULL ise
                // config/subscription_tiers.php'den tier'in modul listesini al.
                // explicit set edilen enabled_modules tier'i ezer (override).
                $cols = ['enabled_modules'];
                if (Schema::hasColumn('companies', 'subscription_tier')) {
                    $cols[] = 'subscription_tier';
                }
                $row = Company::query()->where('id', $companyId)->first($cols);
                $raw = $row?->getAttribute('enabled_modules');

                if ($raw === null || $raw === '' || $raw === '[]') {
                    return self::resolveTierModules($row?->getAttribute('subscription_tier'));
                }

                $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
                if (!is_array($decoded) || empty($decoded)) {
                    return self::resolveTierModules($row?->getAttribute('subscription_tier'));
                }
                return array_values(array_filter(array_map(
                    fn ($v) => strtolower(trim((string) $v)),
                    $decoded
                )));
            }
        );
    }

    /**
     * Tier'a gore izinli modul listesini dondur.
     * config/subscription_tiers.php'deki 'modules' alanini okur.
     * '*' = tum DEFAULT_MODULES, dizi = whitelisted, null/bilinmeyen = sadece core.
     */
    private static function resolveTierModules(?string $tier): array
    {
        $tier = is_string($tier) && $tier !== '' ? strtolower(trim($tier)) : null;
        if ($tier === null) {
            // Tier yoksa eski davranis: hepsi acik (geriye uyum)
            return self::DEFAULT_MODULES;
        }
        $tierConfig = config("subscription_tiers.{$tier}");
        if (!is_array($tierConfig)) {
            return self::DEFAULT_MODULES;
        }
        $modules = $tierConfig['modules'] ?? [];
        if ($modules === '*') {
            return self::DEFAULT_MODULES;
        }
        if (!is_array($modules) || empty($modules)) {
            return ['core'];
        }
        return array_values(array_filter(array_map(
            fn ($v) => strtolower(trim((string) $v)),
            $modules
        )));
    }

    private static function resolveCurrentCompanyId(): int
    {
        if (app()->bound('current_company_id')) {
            return (int) app('current_company_id');
        }
        return 0;
    }

    public static function flushCache(int $companyId): void
    {
        Cache::forget("company:{$companyId}:enabled_modules");
    }
}
