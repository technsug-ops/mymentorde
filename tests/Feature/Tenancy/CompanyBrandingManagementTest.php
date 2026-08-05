<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Platform panelinden white-label marka yönetimi (Faz 4 tamamlayıcısı).
 *
 * Marka kolonları migration'la eklenmişti ama panelde form yoktu — yani
 * gerçek bir firma eklenirken markası girilemiyordu. Bu testler o akışı korur.
 */
class CompanyBrandingManagementTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    public function test_owner_can_set_branding_for_a_company(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'brand_name' => 'A Eğitim Danışmanlık',
                'brand_logo_url' => 'https://cdn.example.test/logo.svg',
                'brand_primary_color' => '#0d9488',
                'primary_domain' => 'a.yourgermanuni.test',
            ])
            ->assertRedirect();

        $company = $this->companyB->fresh();

        $this->assertSame('A Eğitim Danışmanlık', $company->brand_name);
        $this->assertSame('#0d9488', $company->brand_primary_color);
        $this->assertSame('a.yourgermanuni.test', $company->primary_domain);

        // Marka çözümlemesi bu değerleri kullanmalı
        $this->assertSame('A Eğitim Danışmanlık', Brand::resolve($company)['name']);
    }

    /** "https://a.firma.com/" gibi girdiler temizlenmeli — host eşleşmesi aksi halde tutmaz. */
    public function test_domain_input_is_normalized(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'primary_domain' => 'HTTPS://A.YourGermanUni.test/',
            ])
            ->assertRedirect();

        $this->assertSame('a.yourgermanuni.test', $this->companyB->fresh()->primary_domain);
    }

    public function test_domain_cannot_be_claimed_by_two_companies(): void
    {
        $this->companyA->update(['primary_domain' => 'ortak.test']);

        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'primary_domain' => 'ortak.test',
            ])
            ->assertSessionHasErrors('primary_domain');

        $this->assertNull($this->companyB->fresh()->primary_domain);
    }

    public function test_invalid_color_is_rejected(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'brand_primary_color' => 'kirmizi',
            ])
            ->assertSessionHasErrors('brand_primary_color');
    }

    /**
     * Alan boşaltılırsa şirket KENDİ adına döner — platformun markasına DEĞİL.
     *
     * Eskiden platform varsayılanına düşüyordu; canlıda partner domaininde MentorDE
     * adı/logosu görünmesinin sebeplerinden biri buydu.
     */
    public function test_clearing_brand_name_falls_back_to_company_own_name(): void
    {
        $this->companyB->update(['brand_name' => 'Eski Marka']);

        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/branding', ['brand_name' => ''])
            ->assertRedirect();

        $company = $this->companyB->fresh();

        $this->assertNull($company->brand_name);
        $this->assertSame($company->name, Brand::resolve($company)['name']);
        $this->assertNotSame(config('brand.name'), Brand::resolve($company)['name']);
    }

    public function test_owner_can_disable_public_marketing_for_a_company(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'brand_name'       => 'B2B Partner',
                'public_marketing' => '0',
            ])
            ->assertRedirect();

        $company = $this->companyB->fresh();

        $this->assertFalse((bool) $company->public_marketing);
        $this->assertFalse(Brand::resolve($company)['public_marketing']);
    }

    /**
     * Şirket detay ekranı gerçekten render olmalı.
     *
     * Marka + başvuru linki formları buraya elle eklendi; blade'i yalnızca lint'lemek
     * yetmez (değişken eksikse lint geçer, sayfa patlar).
     */
    public function test_company_detail_page_renders_with_the_apply_link(): void
    {
        $this->companyB->update(['slug' => 'firma-b']);

        $this->actingAs($this->owner())
            ->get('/platform/companies/' . $this->companyB->id)
            ->assertOk()
            ->assertSee('/apply/firma-b', false);
    }

    public function test_owner_can_change_the_apply_link_slug(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'slug' => 'abc-egitim',
            ])
            ->assertRedirect();

        $this->assertSame('abc-egitim', $this->companyB->fresh()->slug);
    }

    /** /apply/success gibi sistem adresleri slug olarak alınamaz. */
    public function test_reserved_slugs_are_rejected(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'slug' => 'success',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertNull($this->companyB->fresh()->slug);
    }

    public function test_company_users_cannot_change_branding(): void
    {
        $firmUser = $this->userFor($this->companyB, User::ROLE_MARKETING_ADMIN);

        $response = $this->actingAs($firmUser)
            ->post('/platform/companies/' . $this->companyB->id . '/branding', [
                'brand_name' => 'Yetkisiz Deneme',
            ]);

        $this->assertContains($response->getStatusCode(), [403, 404, 302]);
        $this->assertNotSame('Yetkisiz Deneme', $this->companyB->fresh()->brand_name);
    }

    public function test_new_company_can_be_created_with_branding(): void
    {
        $this->actingAs($this->owner())
            ->post('/platform/companies', [
                'name' => 'Yeni Partner Firma',
                'code' => 'yeni_partner',
                'subscription_tier' => Company::TIER_GOLD,
                'admin_name' => 'Firma Yöneticisi',
                'admin_email' => 'yonetici@yeni-partner.test',
                'admin_password' => 'gizli-sifre-123',
                'brand_name' => 'Yeni Partner',
                'brand_primary_color' => '#ff6600',
                'primary_domain' => 'yeni.yourgermanuni.test',
            ])
            ->assertRedirect();

        $created = Company::query()->where('code', 'yeni_partner')->first();

        $this->assertNotNull($created, 'Şirket oluşturulmadı.');

        // Geçici şifre ilk girişte değiştirilmek zorunda — firmaya e-postayla
        // giden şifre süresiz geçerli kalmamalı.
        $admin = User::query()->withoutGlobalScope('company')
            ->where('email', 'yonetici@yeni-partner.test')->firstOrFail();

        $this->assertTrue(
            (bool) $admin->password_must_change,
            'Yeni firma yoneticisi gecici sifreyle sinirsiz devam edebiliyor.'
        );

        $this->assertSame('Yeni Partner', $created->brand_name);
        $this->assertSame('#ff6600', $created->brand_primary_color);
        $this->assertSame('yeni.yourgermanuni.test', $created->primary_domain);
    }
}
