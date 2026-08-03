<?php

namespace App\Support;

use App\Models\Company;
use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Şirket başına marka çözümleme.
 *
 * ÜÇ KATMAN (sonraki öncekini ezer):
 *   1. config/brand.php      — .env varsayılanları (son çare)
 *   2. companies.brand_*     — şirketin marka paketi (platform sahibi tanımlar)
 *   3. marketing_admin_*     — şirketin panelden değiştirdiği değerler (mevcut sistem)
 *
 * Sonuç ÇALIŞMA ANINDA `config('brand')`'e geri yazılır. Bunun anlamı:
 * mevcut 313 `config('brand.*')` çağrısı, 720 blade dosyası, PortalTheme,
 * PublicTheme ve mail şablonları HİÇ DEĞİŞMEDEN doğru markayı verir.
 * Yeni helper öğrenmek ya da view composer eklemek gerekmez.
 *
 * `config:cache` ile uyumlu: runtime config() set'i yüklü repository'yi değiştirir,
 * önbellek dosyasına dokunmaz.
 */
final class Brand
{
    /** Bu istek için markayı şirkete göre yeniden bağla. */
    public static function apply(?Company $company): void
    {
        if (!$company) {
            return;
        }

        config(['brand' => self::resolve($company)]);
    }

    /**
     * Şirketin çözülmüş marka paketi.
     *
     * @return array<string,mixed>
     */
    public static function resolve(Company $company): array
    {
        $base = (array) config('brand', []);

        $resolved = Cache::remember(
            self::cacheKey((int) $company->id),
            600,
            static fn (): array => array_replace_recursive(
                self::fromCompany($company),
                self::fromSettings((int) $company->id),
            )
        );

        return array_replace_recursive($base, $resolved);
    }

    /**
     * Şirket kaydındaki marka alanları → config/brand.php şekline dönüştür.
     *
     * @return array<string,mixed>
     */
    private static function fromCompany(Company $company): array
    {
        $out = [];

        // brand_overrides config/brand.php'nin tam şeklini taklit eder (banka, hukuki,
        // sosyal, mail kimliği, ödeme günleri...) — önce o, sonra düz kolonlar biner.
        $overrides = $company->getAttribute('brand_overrides');
        if (is_string($overrides)) {
            $overrides = json_decode($overrides, true);
        }
        if (is_array($overrides)) {
            $out = $overrides;
        }

        $name = trim((string) ($company->getAttribute('brand_name') ?? ''));
        if ($name !== '') {
            $out['name'] = $name;
            // legal_name/short_name açıkça verilmediyse marka adına düşsün
            $out['legal_name'] ??= $name;
            $out['short_name'] ??= $name;
        }

        $logo = trim((string) ($company->getAttribute('brand_logo_url') ?? ''));
        if ($logo !== '') {
            $out['logo_url'] = $logo;
        }

        $color = trim((string) ($company->getAttribute('brand_primary_color') ?? ''));
        if ($color !== '') {
            $out['theme']['primary'] = $color;
        }

        $domain = trim((string) ($company->getAttribute('primary_domain') ?? ''));
        if ($domain !== '') {
            $out['website'] = 'https://' . $domain;
        }

        return $out;
    }

    /**
     * marketing_admin_settings'teki marka anahtarları (manager panelden düzenlenir).
     * Mevcut sistem — AppServiceProvider'daki view composer da bunu okuyordu.
     *
     * @return array<string,mixed>
     */
    private static function fromSettings(int $companyId): array
    {
        try {
            $rows = MarketingAdminSetting::query()
                ->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereIn('setting_key', ['brand_name', 'brand_logo_url', 'ai_labs_brand_name'])
                ->pluck('setting_value', 'setting_key');
        } catch (\Throwable) {
            // Tablo henüz yok (migration) ya da DB erişilemez — sessizce geç.
            return [];
        }

        $out = [];

        $name = trim((string) ($rows['brand_name'] ?? ''));
        if ($name !== '') {
            $out['name'] = $name;
            $out['legal_name'] ??= $name;
            $out['short_name'] ??= $name;
        }

        $logo = trim((string) ($rows['brand_logo_url'] ?? ''));
        if ($logo !== '') {
            $out['logo_url'] = $logo;
        }

        $aiLabs = trim((string) ($rows['ai_labs_brand_name'] ?? ''));
        if ($aiLabs !== '') {
            $out['ai_labs_name'] = $aiLabs;
        }

        return $out;
    }

    public static function cacheKey(int $companyId): string
    {
        return "brand:resolved:{$companyId}";
    }

    /** Şirketin markası değiştiğinde çağır (Company observer / ThemeController). */
    public static function flushCache(int $companyId): void
    {
        Cache::forget(self::cacheKey($companyId));
    }
}
