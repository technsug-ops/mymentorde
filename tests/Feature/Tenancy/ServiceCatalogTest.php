<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\CompanyServiceExtra;
use App\Models\CompanyServicePackage;
use App\Support\ServiceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Hizmet kataloğu — firma kendi paketini ve fiyatını tanımlayabilir.
 *
 *   firmanın kendi kataloğu → üst firmalar (yakından uzağa) → config
 *
 * Burada korunan asıl şey PARA: yanlış firmanın fiyat listesi çözülürse
 * aday yanlış tutarı görür, sözleşme yanlış tutarla yazılır ve bu hiçbir
 * ekranda hata gibi görünmez.
 */
class ServiceCatalogTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function package(Company $company, string $code, float $amount, array $attributes = []): CompanyServicePackage
    {
        return CompanyServicePackage::create(array_merge([
            'company_id'   => $company->id,
            'code'         => $code,
            'title'        => 'Paket ' . $code,
            'price_amount' => $amount,
            'currency'     => 'EUR',
            'is_active'    => true,
            'sort_order'   => 1,
        ], $attributes));
    }

    private function extra(Company $company, string $code, float $amount, array $attributes = []): CompanyServiceExtra
    {
        return CompanyServiceExtra::create(array_merge([
            'company_id'   => $company->id,
            'code'         => $code,
            'title'        => 'Hizmet ' . $code,
            'price_amount' => $amount,
            'currency'     => 'EUR',
            'is_active'    => true,
            'sort_order'   => 1,
        ], $attributes));
    }

    public function test_kendi_katalogu_olmayan_firma_config_fiyatini_gorur(): void
    {
        $codes = ServiceCatalog::packages((int) $this->companyA->id)->pluck('code')->all();

        $this->assertSame(
            collect(config('service_packages.packages'))->where('is_active', true)->pluck('code')->sort()->values()->all(),
            collect($codes)->sort()->values()->all()
        );
    }

    public function test_firma_kendi_fiyatini_tanimlayinca_config_yerine_onu_gorur(): void
    {
        $this->package($this->companyA, 'pkg_start', 4900);

        $packages = ServiceCatalog::packages((int) $this->companyA->id);

        $this->assertCount(1, $packages);
        $this->assertSame(4900.0, (float) $packages->first()['price_amount']);
    }

    /**
     * Kısmi miras YOK — yarısı kendinden yarısı üstünden gelseydi, üstte
     * silinen bir paket altta yaşamaya devam ederdi.
     */
    public function test_kendi_katalogu_olan_firma_ust_firmanin_paketlerini_karistirmaz(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        $this->package($this->companyA, 'pkg_ust', 1000);
        $this->package($this->companyB, 'pkg_alt', 2000);

        $codes = ServiceCatalog::packages((int) $this->companyB->id)->pluck('code')->all();

        $this->assertSame(['pkg_alt'], $codes);
    }

    public function test_katalogu_olmayan_alt_firma_ust_firmanin_fiyatini_devralir(): void
    {
        $this->companyB->update(['parent_company_id' => $this->companyA->id]);
        Company::flushHierarchyCache();

        $this->package($this->companyA, 'pkg_ust', 7500);

        $packages = ServiceCatalog::packages((int) $this->companyB->id);

        $this->assertSame(['pkg_ust'], $packages->pluck('code')->all());
        $this->assertSame(7500.0, (float) $packages->first()['price_amount']);
        $this->assertSame(
            (int) $this->companyA->id,
            (int) ServiceCatalog::inheritedFrom((int) $this->companyB->id)?->id
        );
    }

    /** Yatay izolasyon: kardeş firmanın fiyatı asla görünmez. */
    public function test_kardes_firmanin_katalogu_gorunmez(): void
    {
        $this->package($this->companyA, 'pkg_a', 1000);

        $this->assertNull(ServiceCatalog::packages((int) $this->companyB->id)->firstWhere('code', 'pkg_a'));
    }

    /**
     * Satıştan kaldırılan paket listelenmez ama ARAMADA bulunur — aksi hâlde
     * o paketi seçmiş eski adayların tutarı sıfırlanırdı.
     */
    public function test_pasif_paket_listelenmez_ama_gecmis_secim_icin_bulunur(): void
    {
        $this->package($this->companyA, 'pkg_kaldirilan', 3000, ['is_active' => false]);
        $this->package($this->companyA, 'pkg_guncel', 4000);

        $companyId = (int) $this->companyA->id;

        $this->assertSame(['pkg_guncel'], ServiceCatalog::packages($companyId)->pluck('code')->all());
        $this->assertSame(3000.0, (float) ServiceCatalog::findPackage('pkg_kaldirilan', $companyId)['price_amount']);
    }

    public function test_quote_paket_ve_ek_hizmetleri_firmanin_fiyatiyla_toplar(): void
    {
        $this->package($this->companyA, 'pkg_start', 5000);
        $this->extra($this->companyA, 'ext_vize', 300);
        $this->extra($this->companyA, 'ext_konut', 200);

        $total = ServiceCatalog::quote(
            'pkg_start',
            [['code' => 'ext_vize'], ['code' => 'ext_konut']],
            (int) $this->companyA->id
        );

        $this->assertSame(5500.0, $total);
    }

    /**
     * Aynı paket kodu iki firmada farklı fiyatlı olabilir; hesap her zaman
     * kaydın kendi firmasına göre yapılmalı.
     */
    public function test_ayni_kod_farkli_firmada_farkli_tutar_uretir(): void
    {
        $this->package($this->companyA, 'pkg_start', 5000);
        $this->package($this->companyB, 'pkg_start', 9000);

        $this->assertSame(5000.0, ServiceCatalog::quote('pkg_start', [], (int) $this->companyA->id));
        $this->assertSame(9000.0, ServiceCatalog::quote('pkg_start', [], (int) $this->companyB->id));
    }

    public function test_bilinmeyen_kod_tutari_sifir_yapar_hata_firlatmaz(): void
    {
        $this->assertSame(0.0, ServiceCatalog::quote('yok_boyle_paket', [['code' => 'yok']], (int) $this->companyA->id));
        $this->assertNull(ServiceCatalog::findPackage('', (int) $this->companyA->id));
    }

    public function test_kategoriler_firmaya_gore_degismez(): void
    {
        $this->package($this->companyA, 'pkg_start', 5000);

        $this->assertSame(
            collect(config('service_packages.service_categories'))->pluck('key')->all(),
            ServiceCatalog::categories((int) $this->companyA->id)->pluck('key')->all()
        );
    }
}
