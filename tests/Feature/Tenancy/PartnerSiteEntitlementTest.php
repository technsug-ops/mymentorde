<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Kurumsal (şablonlu) site yetkisi — iş modelinden ayrı.
 *
 * ── NEDEN AYRI BİR BAYRAK ───────────────────────────────────────────────
 * Şablonlu site bugüne kadar `dealer_type_code === 'b2b_partner'` şartına
 * bağlıydı. Ama o kolon aynı zamanda komisyon kademesini, sözleşme
 * kategorisini ve KPI gruplarını belirliyor. Bir freelance danışmana site
 * açmak için tipini değiştirmek, sitesini verip kazancını bozmak olurdu.
 *
 * `site_mode` bu iki kararı ayırır. Bu testin ASIL ÖLÇTÜĞÜ ŞEY: yetki
 * verildikten sonra bayinin TİPİ ve dolayısıyla komisyon zinciri aynı kaldı.
 */
class PartnerSiteEntitlementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bayi ve manager, ziyaretçi isteğinin düştüğü VARSAYILAN şirkette
     * kurulur — başka bir şirkete koyulsaydı public /p/ isteği onu göremez
     * ve test yetkiyi değil tenant filtresini ölçerdi.
     */
    private function defaultCompanyId(): int
    {
        return (int) Company::query()->where('is_active', true)->orderBy('id')->value('id');
    }

    private function freelancer(array $extra = []): Dealer
    {
        return Dealer::create(array_merge([
            'code'             => 'FRE-26-07-1344',
            'name'             => 'Serbest Danisman',
            'dealer_type_code' => 'freelance_danisman',
            'roles'            => [Dealer::ROLE_FREELANCE],
            'is_active'        => true,
            'is_archived'      => false,
            'public_slug'      => 'serbest-danisman',
            'site_enabled'     => true,
            'site_template'    => 'manyeta',
        ], $extra));
    }

    private function asManager(): self
    {
        $manager = User::create([
            'name'              => 'Test Manager',
            'email'             => 'manager-' . uniqid() . '@example.test',
            'password'          => Hash::make('secret-password'),
            'role'              => User::ROLE_MANAGER,
            'is_active'         => true,
            'email_verified_at' => now(),
            'company_id'        => $this->defaultCompanyId(),
        ]);

        return $this->actingAs($manager)->withSession(['2fa_passed' => true]);
    }

    // ── Public site: hangi sayfa basılıyor ──────────────────────────────────

    /** Yetki yokken davranış DEĞİŞMEMELİ — mevcut bayiler etkilenmesin. */
    public function test_without_entitlement_dealer_keeps_the_single_page_landing(): void
    {
        $dealer = $this->freelancer();

        $this->get('/p/' . $dealer->public_slug)
            ->assertOk()
            ->assertViewIs('public.dealer-landing');
    }

    /** Bayrak açılınca freelance danışman da seçtiği şablonu alır. */
    public function test_entitlement_opens_the_template_site_for_a_freelancer(): void
    {
        $dealer = $this->freelancer(['site_mode' => Dealer::SITE_MODE_PARTNER]);

        $this->get('/p/' . $dealer->public_slug)
            ->assertOk()
            ->assertViewIs('public.partner-templates.manyeta');
    }

    /** b2b_partner bayrağa ihtiyaç duymadan yetkili kalmalı (geriye dönük uyum). */
    public function test_b2b_partner_still_gets_the_template_site_without_the_flag(): void
    {
        $dealer = $this->freelancer([
            'code'             => 'OPE-26-07-0001',
            'dealer_type_code' => 'b2b_partner',
            'roles'            => [Dealer::ROLE_B2B_PARTNER],
            'public_slug'      => 'operasyon-partner',
            'site_mode'        => null,
        ]);

        $this->assertTrue($dealer->usesPartnerSite());

        $this->get('/p/' . $dealer->public_slug)
            ->assertOk()
            ->assertViewIs('public.partner-templates.manyeta');
    }

    // ── Manager anahtarı ────────────────────────────────────────────────────

    /**
     * Kutucuk EKRANDA görünmeli.
     *
     * Yetki modelde ve uç noktada doğru çalışsa bile denetimi basmayan bir
     * sayfa özelliği yok sayar: kullanıcı bakar, bulamaz, "yapılmamış" der.
     */
    public function test_manager_sees_the_entitlement_checkbox_on_a_freelancer(): void
    {
        $dealer = $this->freelancer();

        $this->asManager()
            ->get('/manager/dealers/' . $dealer->code)
            ->assertOk()
            ->assertSee('Kurumsal site')
            ->assertSee('name="site_mode"', false);
    }

    /** Tipi gereği yetkili bayide karar yok — durum bildirimi var. */
    public function test_b2b_partner_shows_status_instead_of_a_checkbox(): void
    {
        $dealer = $this->freelancer([
            'code'             => 'OPE-26-07-0002',
            'dealer_type_code' => 'b2b_partner',
            'roles'            => [Dealer::ROLE_B2B_PARTNER],
            'public_slug'      => 'operasyon-partner-2',
        ]);

        $this->asManager()
            ->get('/manager/dealers/' . $dealer->code)
            ->assertOk()
            ->assertSee('tipi gereği açık')
            ->assertDontSee('name="site_mode"', false);
    }

    /**
     * ASIL GARANTİ: yetki verilirken bayinin TİPİ değişmiyor.
     *
     * Tip değişseydi bayi B2B komisyon kademesine ve sözleşme kategorisine
     * geçerdi — sessizce, sadece sitesi açılsın diye.
     */
    public function test_granting_the_site_does_not_touch_the_commission_tier(): void
    {
        $dealer = $this->freelancer();

        $this->asManager()
            ->post('/manager/dealers/' . $dealer->code . '/mini-site', [
                'public_slug'       => $dealer->public_slug,
                'site_enabled'      => '1',
                'site_mode_present' => '1',
                'site_mode'         => Dealer::SITE_MODE_PARTNER,
            ])
            ->assertRedirect();

        $dealer->refresh();

        $this->assertSame(Dealer::SITE_MODE_PARTNER, $dealer->site_mode);
        $this->assertSame('freelance_danisman', $dealer->dealer_type_code, 'Bayi tipi degismemeliydi');
        $this->assertSame([Dealer::ROLE_FREELANCE], $dealer->roles);
    }

    /** Kutucuk işaretsiz gönderilince yetki kalkar — ekran hepsini basıyor. */
    public function test_unchecking_the_box_revokes_the_entitlement(): void
    {
        $dealer = $this->freelancer(['site_mode' => Dealer::SITE_MODE_PARTNER]);

        $this->asManager()
            ->post('/manager/dealers/' . $dealer->code . '/mini-site', [
                'public_slug'       => $dealer->public_slug,
                'site_enabled'      => '1',
                'site_mode_present' => '1',
            ])
            ->assertRedirect();

        $this->assertNull($dealer->refresh()->site_mode);
    }

    /**
     * Denetimi HİÇ taşımayan istek yetkiye dokunmamalı.
     *
     * "Gönderilmedi = kapat" deseydik, kutucuğu render etmeyen her istek
     * (tipi gereği yetkili bayide kutucuk basılmaz) yetkiyi sessizce silerdi.
     */
    public function test_a_request_without_the_control_leaves_the_entitlement_alone(): void
    {
        $dealer = $this->freelancer(['site_mode' => Dealer::SITE_MODE_PARTNER]);

        $this->asManager()
            ->post('/manager/dealers/' . $dealer->code . '/mini-site', [
                'public_slug'  => $dealer->public_slug,
                'site_enabled' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(Dealer::SITE_MODE_PARTNER, $dealer->refresh()->site_mode);
    }

    /** Tanınmayan değer reddedilmeli — kayıttan önce yakalansın. */
    public function test_unknown_site_mode_is_rejected(): void
    {
        $dealer = $this->freelancer();

        $this->asManager()
            ->post('/manager/dealers/' . $dealer->code . '/mini-site', [
                'public_slug'       => $dealer->public_slug,
                'site_mode_present' => '1',
                'site_mode'         => 'her-sey-acik',
            ])
            ->assertSessionHasErrors('site_mode');

        $this->assertNull($dealer->refresh()->site_mode);
    }
}
