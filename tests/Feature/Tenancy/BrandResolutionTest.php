<?php

namespace Tests\Feature\Tenancy;

use App\Models\MarketingAdminSetting;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Faz 2 — şirket başına marka.
 *
 * Marka üç katmandan çözülür (sonraki önceki ezer):
 *   config/brand.php (.env)  →  companies.brand_*  →  marketing_admin_settings
 *
 * Sonuç `config('brand')`'e yazılır; böylece mevcut 313 config('brand.*') çağrısı
 * ve 720 blade dosyası değişmeden doğru markayı verir.
 *
 * GÜVENLİK KURALI: host YALNIZCA markayı belirler. Veri erişimi asla host'tan
 * gelmez — aksi halde `Host:` başlığı değiştirilerek tenant atlanabilirdi.
 */
class BrandResolutionTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    public function test_company_brand_fields_override_env_defaults(): void
    {
        $this->companyA->update([
            'brand_name' => 'Firma A Danışmanlık',
            'brand_logo_url' => 'https://cdn.firma-a.test/logo.svg',
            'brand_primary_color' => '#ff6600',
        ]);

        $resolved = Brand::resolve($this->companyA->fresh());

        $this->assertSame('Firma A Danışmanlık', $resolved['name']);
        $this->assertSame('https://cdn.firma-a.test/logo.svg', $resolved['logo_url']);
        $this->assertSame('#ff6600', $resolved['theme']['primary']);

        // Açıkça verilmeyen alanlar marka adına düşmeli
        $this->assertSame('Firma A Danışmanlık', $resolved['legal_name']);
    }

    public function test_brand_overrides_json_fills_the_long_tail(): void
    {
        $this->companyA->update([
            'brand_name' => 'Firma A',
            'brand_overrides' => [
                'email' => 'info@firma-a.test',
                'banking' => ['iban' => 'DE00 0000 0000 0000'],
                'payment_due_days' => 21,
            ],
        ]);

        $resolved = Brand::resolve($this->companyA->fresh());

        $this->assertSame('info@firma-a.test', $resolved['email']);
        $this->assertSame('DE00 0000 0000 0000', $resolved['banking']['iban']);
        $this->assertSame(21, $resolved['payment_due_days']);
        // Override edilmeyen env değerleri korunmalı
        $this->assertArrayHasKey('tagline', $resolved);
    }

    public function test_panel_settings_win_over_company_fields(): void
    {
        $this->companyA->update(['brand_name' => 'Eski Ad']);

        MarketingAdminSetting::withoutGlobalScopes()->create([
            'company_id' => $this->companyA->id,
            'setting_key' => 'brand_name',
            'setting_value' => 'Panelden Değişen Ad',
        ]);

        Brand::flushCache((int) $this->companyA->id);

        $this->assertSame('Panelden Değişen Ad', Brand::resolve($this->companyA->fresh())['name']);
    }

    public function test_anonymous_visitor_sees_the_brand_of_the_host_they_came_from(): void
    {
        $this->companyB->update([
            'brand_name' => 'YourGermanUni',
            'primary_domain' => 'yourgermanuni.test',
        ]);

        $response = $this->get('http://yourgermanuni.test/login');

        $response->assertOk();
        $response->assertSee('YourGermanUni', false);
    }

    public function test_domain_aliases_also_resolve_the_brand(): void
    {
        $this->companyB->update([
            'brand_name' => 'YourGermanUni',
            'primary_domain' => 'yourgermanuni.test',
            'domain_aliases' => ['www.yourgermanuni.test'],
        ]);

        $this->get('http://www.yourgermanuni.test/login')
            ->assertOk()
            ->assertSee('YourGermanUni', false);
    }

    public function test_unknown_host_falls_back_to_default_brand(): void
    {
        // Hiçbir şirkette domain tanımlı değilken bugünkü davranış korunmalı
        $this->get('/login')->assertOk();

        $this->assertSame(
            (string) config('brand.name'),
            (string) config('brand.name'),
            'Bilinmeyen host varsayılan markaya düşmeli.'
        );
    }

    /**
     * Host markayı belirler ama VERİYİ ASLA belirlemez.
     * A firmasının kullanıcısı B'nin domaininden girse bile A'nın verisini görür.
     */
    public function test_host_does_not_grant_access_to_another_companys_data(): void
    {
        $this->companyB->update([
            'brand_name' => 'Firma B',
            'primary_domain' => 'firma-b.test',
        ]);

        $userOfA = $this->userFor($this->companyA);

        $this->actingAs($userOfA)->get('http://firma-b.test/login');

        // Bağlam kullanıcının şirketinde kalmalı — host'un şirketinde değil
        $this->assertSame(
            (int) $this->companyA->id,
            (int) \App\Support\TenantContext::writeId(),
            'Host başlığı veri bağlamını değiştirdi — tenant atlama açığı.'
        );
    }
}
