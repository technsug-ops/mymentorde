<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Support\PartnerTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Powered by techNSUG" künyesi — yazılımı yapan tarafın adı.
 *
 * ── NEDEN TEK KAYNAK ────────────────────────────────────────────────────
 * Bu satır 26 public sayfada geçiyor. Metni dosyalara dağıtmak, sağlayıcı
 * adı bir gün değiştiğinde 26 yerde arama yapmak demekti; biri unutulunca
 * da sayfalar arasında iki farklı isim görünürdü. Ad ve adres
 * `config('brand.vendor')`'dan okunuyor, tek partial basıyor.
 *
 * ⚠ Kiracı markası DEĞİL: `config('brand.name')` şirkete göre değişir
 * (MentorDE / YourGermanUni / partner firma), bu değişmez.
 */
class VendorCreditTest extends TestCase
{
    use RefreshDatabase;

    private function dealer(array $extra = []): Dealer
    {
        return Dealer::create(array_merge([
            'code'             => 'FRE-26-08-0001',
            'name'             => 'Ornek Danisman',
            'dealer_type_code' => 'freelance_danisman',
            'roles'            => [Dealer::ROLE_FREELANCE],
            'is_active'        => true,
            'is_archived'      => false,
            'public_slug'      => 'ornek-danisman',
            'site_enabled'     => true,
            'site_mode'        => Dealer::SITE_MODE_PARTNER,
            'site_show_badge'  => true,
        ], $extra));
    }

    /** Varsayılan sağlayıcı kimliği. */
    public function test_vendor_identity_comes_from_config(): void
    {
        $this->assertSame('techNSUG', config('brand.vendor.name'));
        $this->assertSame('https://www.techns.de', config('brand.vendor.url'));
    }

    /**
     * HER şablon künyeyi basmalı.
     *
     * Şablon listesi koddan okunuyor: yeni tasarım eklendiğinde test onu
     * kendiliğinden kapsar ve künyeyi unutan şablonu isim isim raporlar.
     */
    public function test_every_partner_template_shows_the_vendor_credit(): void
    {
        $dealer  = $this->dealer();
        $missing = [];

        foreach (array_keys(PartnerTemplates::all()) as $key) {
            $dealer->update(['site_template' => $key]);

            $html = $this->get('/p/' . $dealer->public_slug)->assertOk()->getContent();

            if (!str_contains($html, 'techNSUG') || !str_contains($html, 'www.techns.de')) {
                $missing[] = $key;
            }
        }

        $this->assertSame([], $missing, 'Kunye basmayan sablon(lar): ' . implode(', ', $missing));
    }

    /** Eski sabit metin hiçbir şablonda kalmamalı. */
    public function test_no_template_still_credits_the_tenant_brand(): void
    {
        $dealer = $this->dealer();

        foreach (array_keys(PartnerTemplates::all()) as $key) {
            $dealer->update(['site_template' => $key]);

            $this->get('/p/' . $dealer->public_slug)
                ->assertOk()
                ->assertDontSee('Powered by MentorDE', false);
        }
    }

    /**
     * ASIL SINIR: rozet kapalıyken HİÇBİR üçüncü taraf markası basılmaz.
     *
     * Bayiye tam white-label sözü verildi. Sağlayıcı künyesi bu sözün
     * istisnası değil — kendi künyemizi muaf tutsaydık söz sessizce bozulurdu.
     */
    public function test_white_label_hides_the_vendor_credit_too(): void
    {
        $dealer = $this->dealer(['site_show_badge' => false]);

        $this->get('/p/' . $dealer->public_slug)
            ->assertOk()
            ->assertDontSee('techNSUG', false)
            ->assertDontSee('techns.de', false);
    }

    /** MentorDE'nin kendi public sayfaları da künyeyi taşır. */
    public function test_platform_owned_public_pages_show_the_credit(): void
    {
        foreach (['/platform', '/fiyatlar'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('techNSUG', false)
                ->assertSee('www.techns.de', false);
        }
    }

    /**
     * Ad boşsa satır hiç basılmaz — yarım künye ("Powered by" + boşluk) kalmasın.
     *
     * ⚠ Bu HTTP isteğiyle ölçülemez: `Brand::apply()` her istekte
     * `config('brand')`'i kiracıya göre yeniden yazıyor ve testte set edilen
     * değeri eziyor. Bu yüzden partial doğrudan render ediliyor.
     */
    public function test_empty_vendor_name_prints_nothing(): void
    {
        config(['brand.vendor.name' => '']);

        $this->assertSame('', trim(view('partials.vendor-credit')->render()));
    }
}
