<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CompanyServiceExtra;
use App\Models\CompanyServicePackage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Hizmet kataloğu — hangi firma hangi paketleri ve fiyatları görüyor.
 *
 * ── NEDEN VAR ───────────────────────────────────────────────────────────
 * Paketler `config/service_packages.php` içinde koda gömülüydü ve 35 ayrı
 * noktadan okunuyordu. Firmalar kendi hizmetlerini fiyatlandırabilsin diye
 * veritabanına taşındı; ama okuma noktalarının hiçbiri veri şeklini bilmek
 * zorunda kalmasın diye hepsi bu tek kapıdan geçiyor.
 *
 * ⚠ TEK KAPI OLMASI ÖNEMLİ. Bir okuma noktası doğrudan config okumaya devam
 * ederse orada eski fiyat görünür ve bu HİÇBİR YERDE fark edilmez — para
 * söz konusu olduğu için en pahalı sessiz hata türü.
 *
 * ── MİRAS ───────────────────────────────────────────────────────────────
 *   firmanın kendi kataloğu → üst firmalar (yakından uzağa) → config
 *
 * Kısmi miras YOK: bir firma kendi paketlerini tanımladıysa listenin
 * TAMAMI onundur. Yarısı kendisinden yarısı üstünden gelseydi, üstte
 * silinen bir paketin altta yaşamaya devam etmesi gibi anlaşılmaz durumlar
 * çıkardı.
 *
 * ── FİYAT GEÇMİŞİ ───────────────────────────────────────────────────────
 * Katalog değişince eski sözleşmeler etkilenmez: seçilen paketin adı ve
 * fiyatı aday kaydına anlık kopya olarak yazılıyor
 * (`selected_package_price`), sözleşme tutarı da ayrıca sabitleniyor.
 */
final class ServiceCatalog
{
    /** Aktif paketler — sıralı. */
    public static function packages(?int $companyId = null): Collection
    {
        return self::resolvePackages($companyId)
            ->filter(fn (array $row): bool => (bool) ($row['is_active'] ?? true))
            ->sortBy('sort_order')
            ->values();
    }

    /** Aktif ek hizmetler — sıralı. */
    public static function extras(?int $companyId = null): Collection
    {
        return self::resolveExtras($companyId)
            ->filter(fn (array $row): bool => (bool) ($row['is_active'] ?? true))
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * Hizmet kategorileri.
     *
     * Kategoriler firmaya göre DEĞİŞMİYOR: bunlar yapısal gruplar (üniversite,
     * vize, finans…), fiyat ya da içerik değil. Firmaya açmak, aynı hizmetin
     * farklı firmalarda farklı başlık altında görünmesi demek olurdu.
     */
    public static function categories(?int $companyId = null): Collection
    {
        return collect(config('service_packages.service_categories', []));
    }

    /**
     * Koda göre paket — yoksa null.
     *
     * ⚠ PASİF PAKETLER DE BULUNUR. Bu arama çoğunlukla GEÇMİŞ bir seçimi
     * çözmek için yapılıyor (aday şu paketi seçmişti). Satıştan kaldırılan
     * paket aramada bulunamazsa eski sözleşmeler ve ödeme hatırlatmaları
     * "paket yok" diye boş kalırdı. Listeleme (`packages()`) pasifi elemeye
     * devam ediyor — orada amaç satış.
     */
    public static function findPackage(string $code, ?int $companyId = null): ?array
    {
        if (trim($code) === '') {
            return null;
        }

        return self::resolvePackages($companyId)->firstWhere('code', $code);
    }

    /** Koda göre ek hizmet — pasif olanlar dahil (geçmiş seçimler için). */
    public static function findExtra(string $code, ?int $companyId = null): ?array
    {
        if (trim($code) === '') {
            return null;
        }

        return self::resolveExtras($companyId)->firstWhere('code', $code);
    }

    /**
     * Seçilen paket + ek hizmetlerin katalog tutarı (EUR).
     *
     * Bu hesap üç yerde birebir kopyalanmıştı (ödeme hatırlatma komutu, aynı
     * işin controller'ı, sözleşme metni). Katalog artık firmaya göre değiştiği
     * için kopyaların birinin unutulması, aynı adaya iki farklı tutar
     * gösterilmesi demekti — bu yüzden tek yere alındı.
     *
     * ⚠ Bu SORULAN fiyattır, sözleşme tutarı değil. Sözleşmede elle
     * sabitlenmiş bir tutar varsa finans onu kullanır; buraya bakmaz.
     *
     * @param mixed $extras `selected_extra_services` alanı (dizi bekleniyor)
     */
    public static function quote(?string $packageCode, mixed $extras, ?int $companyId = null): float
    {
        $pkg = self::findPackage((string) $packageCode, $companyId);
        $total = (float) ($pkg['price_amount'] ?? 0);

        foreach (is_array($extras) ? $extras : [] as $x) {
            $found = self::findExtra((string) ($x['code'] ?? ''), $companyId);
            $total += (float) ($found['price_amount'] ?? 0);
        }

        return $total;
    }

    /**
     * Çözülen paketlerin tamamı — pasifler dahil.
     *
     * Yönetim ekranı için: kopyalarken pasif paketler de gelmeli, yoksa o
     * paketi seçmiş eski adayların tutarı çözülemez hâle gelir.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public static function allPackages(?int $companyId = null): Collection
    {
        return self::resolvePackages($companyId);
    }

    /** @return Collection<int,array<string,mixed>> */
    public static function allExtras(?int $companyId = null): Collection
    {
        return self::resolveExtras($companyId);
    }

    /**
     * Bu firma kendi kataloğunu SİLSE hangi paket kodlarını devralırdı?
     *
     * "Mirasa dön" işleminden önce sorulan soru: kullanımdaki bir kod mirasta
     * yoksa dönüş, o kayıtların tutarını çözülemez hâle getirir.
     *
     * @return list<string>
     */
    public static function inheritedPackageCodes(int $companyId): array
    {
        foreach (self::ancestors($companyId) as $ancestorId) {
            $rows = CompanyServicePackage::withoutGlobalScope('company')
                ->where('company_id', $ancestorId)
                ->pluck('code');

            if ($rows->isNotEmpty()) {
                return $rows->map(fn ($c): string => (string) $c)->all();
            }
        }

        return collect(config('service_packages.packages', []))
            ->pluck('code')
            ->map(fn ($c): string => (string) $c)
            ->all();
    }

    /**
     * Bu firmanın KENDİ kataloğu var mı? (miras almıyor mu)
     *
     * Yönetim ekranı "kendi tanımın mı, üstten mi geliyor" ayrımını
     * gösterebilsin diye.
     */
    public static function hasOwnCatalog(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }

        return CompanyServicePackage::withoutGlobalScope('company')
            ->where('company_id', $companyId)->exists();
    }

    /** Kataloğu miras alınan firma — kendisiyse null. */
    public static function inheritedFrom(int $companyId): ?Company
    {
        if ($companyId <= 0 || self::hasOwnCatalog($companyId)) {
            return null;
        }

        foreach (self::ancestors($companyId) as $ancestorId) {
            if (self::hasOwnCatalog($ancestorId)) {
                return Company::query()->withoutGlobalScope('company')->find($ancestorId);
            }
        }

        return null;
    }

    // ── İç işleyiş ──────────────────────────────────────────────────────────

    /** @return Collection<int,array<string,mixed>> */
    private static function resolvePackages(?int $companyId): Collection
    {
        foreach (self::chain($companyId) as $candidate) {
            $rows = CompanyServicePackage::withoutGlobalScope('company')
                ->where('company_id', $candidate)
                ->get();

            if ($rows->isNotEmpty()) {
                return $rows->map(fn (CompanyServicePackage $p): array => $p->toCatalogArray());
            }
        }

        return collect(config('service_packages.packages', []));
    }

    /** @return Collection<int,array<string,mixed>> */
    private static function resolveExtras(?int $companyId): Collection
    {
        foreach (self::chain($companyId) as $candidate) {
            $rows = CompanyServiceExtra::withoutGlobalScope('company')
                ->where('company_id', $candidate)
                ->get();

            if ($rows->isNotEmpty()) {
                return $rows->map(fn (CompanyServiceExtra $e): array => $e->toCatalogArray());
            }
        }

        return collect(config('service_packages.extra_services', []));
    }

    /**
     * Aranacak şirketler: kendisi, sonra üst firmalar.
     *
     * @return list<int>
     */
    private static function chain(?int $companyId): array
    {
        $companyId = $companyId ?? self::currentCompanyId();

        if ($companyId <= 0) {
            return [];
        }

        return array_merge([$companyId], self::ancestors($companyId));
    }

    /** @return list<int> */
    private static function ancestors(int $companyId): array
    {
        try {
            return Company::ancestorIds($companyId);
        } catch (\Throwable $e) {
            // Hiyerarşi okunamazsa katalog çökmesin — config'e düşülür.
            Log::warning('ServiceCatalog ancestor lookup failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private static function currentCompanyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }
}
