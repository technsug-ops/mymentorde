<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Support\Brand;
use App\Support\PublicTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Anonim ziyaretçi için marka izolasyonu.
 *
 * Canlıda yakalanan hata: yourgermanuni.com giriş sayfasında MentorDE adı ve logosu
 * görünüyordu. Üç ayrı sebebi vardı, üçü de burada kilitleniyor:
 *
 *   1. login.blade.php markayı config('brand')'den değil doğrudan
 *      MarketingAdminSetting'den okuyordu → host bazlı marka hiç uygulanmıyordu.
 *   2. Brand::resolve() boş alanları .env'e düşürüyordu → BRAND_LOGO_URL
 *      (MentorDE logosu) partner firmaya miras kalıyordu.
 *   3. PublicTheme paleti MARKA şirketini değil VERİ şirketini okuyordu → partner
 *      domaininde MentorDE moru render ediliyordu.
 */
class PublicBrandIsolationTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private const PLATFORM_LOGO = 'https://platform.test/mentorde-logo.svg';

    /** .env'de marka dolu bir platform kurulumunu taklit et. */
    private function givenPlatformBrandInEnv(): void
    {
        config([
            'brand.name'      => 'MentorDE',
            'brand.logo_url'  => self::PLATFORM_LOGO,
            'brand.tagline'   => 'Almanya Eğitim Danışmanlığı',
            'brand.phone'     => '+49 000 000',
            'brand.banking'   => ['iban' => 'DE99 PLATFORM', 'currency' => 'EUR'],
        ]);

        // Taban paketi yeniden sabitle — normalde AppServiceProvider::boot yapar.
        Brand::rememberPlatformBase();
    }

    /**
     * Kod'u 'mentorde' olan şirket = platformun kendi şirketi.
     * Migration (`ensureDefaultCompany`) bunu zaten oluşturuyor; sadece domainini bağla.
     */
    private function primaryCompany(): Company
    {
        $company = Company::query()->firstOrNew(['code' => 'mentorde']);

        $company->fill([
            'name' => 'MentorDE',
            'is_active' => true,
            'primary_domain' => 'panel.mentorde.test',
            'public_marketing' => true,
        ])->save();

        Brand::flushCache((int) $company->id);

        return $company;
    }

    // ── 1. Partner firma platformun kimliğini MİRAS ALMAZ ────────────────────

    public function test_partner_company_does_not_inherit_platform_logo_from_env(): void
    {
        $this->givenPlatformBrandInEnv();

        $this->companyB->update(['primary_domain' => 'partner.test']);

        $resolved = Brand::resolve($this->companyB->fresh());

        $this->assertSame('', $resolved['logo_url'], 'Partner firma platformun logosunu miras aldı.');
        $this->assertNotSame('MentorDE', $resolved['name']);
        $this->assertSame('Firma B', $resolved['name'], 'Marka adı boşken şirket adına düşmeli.');
    }

    public function test_partner_company_does_not_inherit_platform_contact_and_banking(): void
    {
        $this->givenPlatformBrandInEnv();

        $resolved = Brand::resolve($this->companyB);

        $this->assertSame('', $resolved['phone']);
        $this->assertSame('', $resolved['tagline']);
        $this->assertSame('', $resolved['banking']['iban']);
        // currency kimlik değil, teknik alan — korunur
        $this->assertSame('EUR', $resolved['banking']['currency']);
    }

    /** SMTP gönderici adresi altyapıdır; boşalırsa mail tamamen kırılır. */
    public function test_mail_from_address_is_not_stripped_for_tenants(): void
    {
        $this->givenPlatformBrandInEnv();
        config(['brand.mail_from_address' => 'noreply@platform.test']);
        Brand::rememberPlatformBase();

        $this->assertSame('noreply@platform.test', Brand::resolve($this->companyB)['mail_from_address']);
    }

    public function test_primary_company_still_inherits_env_brand(): void
    {
        $this->givenPlatformBrandInEnv();

        $resolved = Brand::resolve($this->primaryCompany());

        $this->assertSame('MentorDE', $resolved['name']);
        $this->assertSame(self::PLATFORM_LOGO, $resolved['logo_url']);
        $this->assertSame('DE99 PLATFORM', $resolved['banking']['iban']);
    }

    // ── 2. Giriş sayfası host'un markasını basar ─────────────────────────────

    public function test_login_page_on_partner_host_shows_no_platform_branding(): void
    {
        $this->givenPlatformBrandInEnv();
        $this->primaryCompany();

        $this->companyB->update([
            'brand_name' => 'YourGermanUni',
            'primary_domain' => 'yourgermanuni.test',
            'public_marketing' => false,
        ]);

        $response = $this->get('http://yourgermanuni.test/login');

        $response->assertOk();
        $response->assertSee('YourGermanUni', false);
        $response->assertDontSee('MentorDE', false);
        $response->assertDontSee(self::PLATFORM_LOGO, false);
    }

    public function test_partner_login_page_hides_b2c_acquisition_content(): void
    {
        $this->companyB->update([
            'brand_name' => 'YourGermanUni',
            'primary_domain' => 'yourgermanuni.test',
            'public_marketing' => false,
        ]);

        $response = $this->get('http://yourgermanuni.test/login');

        $response->assertOk();
        $response->assertDontSee('Ücretsiz Başvuru', false);
        $response->assertDontSee('Ücretsiz Hesap Oluştur', false);
        $response->assertDontSee('400+ Alman Üniversitesi', false);
        // Giriş formunun kendisi elbette durmalı
        $response->assertSee('Giriş Yap', false);
    }

    /** B2C tarafı bugünkü haliyle devam etmeli — reklam dahil. */
    public function test_b2c_login_page_keeps_marketing_content(): void
    {
        $this->givenPlatformBrandInEnv();
        $this->primaryCompany();

        $response = $this->get('http://panel.mentorde.test/login');

        $response->assertOk();
        $response->assertSee('MentorDE', false);
        $response->assertSee('Ücretsiz Başvuru', false);
        $response->assertSee('400+ Alman Üniversitesi', false);
    }

    // ── 3. Palet marka şirketinden gelir ─────────────────────────────────────

    public function test_public_theme_follows_the_brand_company_not_the_data_company(): void
    {
        $this->companyB->update(['primary_domain' => 'partner.test']);

        $this->get('http://partner.test/login')->assertOk();

        // Partner bir tercih belirtmediyse nötr palete düşer, MentorDE moruna değil.
        $this->assertSame('navy', PublicTheme::resolve((int) $this->companyB->id)['preset']);
        $this->assertSame('mentorde', PublicTheme::resolve((int) $this->primaryCompany()->id)['preset']);
    }

    // ── 4. Aynı istekte ikinci resolve() önceki firmayı taban almaz ──────────

    public function test_resolving_a_second_company_does_not_inherit_the_first(): void
    {
        $this->givenPlatformBrandInEnv();

        $this->companyA->update(['brand_name' => 'Firma A', 'brand_logo_url' => 'https://a.test/logo.svg']);

        Brand::apply($this->companyA->fresh());
        $this->assertSame('https://a.test/logo.svg', config('brand.logo_url'));

        // İkinci şirket, birincinin logosunu DEVRALMAMALI
        $resolved = Brand::resolve($this->companyB);

        $this->assertSame('', $resolved['logo_url'], 'Marka paketi şirketler arasında sızdı.');
        $this->assertSame('Firma B', $resolved['name']);
    }
}
