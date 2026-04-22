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
    ];

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
                $raw = Company::query()->where('id', $companyId)->value('enabled_modules');
                if ($raw === null || $raw === '') {
                    return self::DEFAULT_MODULES;
                }
                $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
                if (!is_array($decoded) || empty($decoded)) {
                    return self::DEFAULT_MODULES;
                }
                return array_values(array_filter(array_map(
                    fn ($v) => strtolower(trim((string) $v)),
                    $decoded
                )));
            }
        );
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
