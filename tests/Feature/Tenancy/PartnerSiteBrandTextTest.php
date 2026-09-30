<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Partner sitesindeki marka adı + etiket partnerin kendi elinde olmalı.
 *
 * ── NEREDEN ÇIKTI ───────────────────────────────────────────────────────
 * Sol üstteki ad ve hero etiketi ("X · Almanya Eğitim Danışmanlığı") bayinin
 * kayıt adından + sabit metinden geliyordu; partnerin değiştirebileceği bir
 * alan yoktu. Kayıt adını (`name`) açmak çözüm değil: o ad rapor, komisyon
 * ve sözleşmede geçer. Bu yüzden ayrı `site_brand_name` / `site_tagline`.
 */
class PartnerSiteBrandTextTest extends TestCase
{
    use RefreshDatabase;

    private function partner(array $extra = []): Dealer
    {
        return Dealer::create(array_merge([
            'code'             => 'OPE-26-09-0001',
            'name'             => 'Kayit Adi Ltd',
            'dealer_type_code' => 'b2b_partner',
            'roles'            => [Dealer::ROLE_B2B_PARTNER],
            'is_active'        => true,
            'is_archived'      => false,
            'public_slug'      => 'kayit-adi',
            'site_enabled'     => true,
            'site_template'    => 'aurora',
        ], $extra));
    }

    /** Alan boşken davranış değişmemeli — mevcut siteler etkilenmesin. */
    public function test_defaults_to_registered_name_and_default_tagline(): void
    {
        $this->partner();

        $this->get('/p/kayit-adi')
            ->assertOk()
            ->assertSee('Kayit Adi Ltd · Almanya Eğitim Danışmanlığı', false);
    }

    public function test_custom_brand_name_and_tagline_replace_the_defaults(): void
    {
        $this->partner([
            'site_brand_name' => 'Parlak Akademi',
            'site_tagline'    => 'Avusturya ve Almanya Danışmanlığı',
        ]);

        $this->get('/p/kayit-adi')
            ->assertOk()
            ->assertSee('Parlak Akademi · Avusturya ve Almanya Danışmanlığı', false)
            ->assertDontSee('Kayit Adi Ltd')
            ->assertDontSee('Almanya Eğitim Danışmanlığı');
    }

    /** Partner kendi panelinden kaydedebilmeli; kayıt adı değişmemeli. */
    public function test_partner_saves_the_fields_without_touching_the_registered_name(): void
    {
        $dealer = $this->partner();
        $user = User::create([
            'name'              => 'Partner User',
            'email'             => 'partner-' . uniqid() . '@example.test',
            'password'          => Hash::make('secret-password'),
            'role'              => User::ROLE_DEALER,
            'dealer_code'       => $dealer->code,
            'is_active'         => true,
            'email_verified_at' => now(),
            'company_id'        => (int) Company::query()->where('is_active', true)->orderBy('id')->value('id'),
        ]);

        // Alanlar formda gerçekten görünmeli — görünmeyen alan "yok" sayılır.
        $this->actingAs($user)->withSession(['2fa_passed' => true])
            ->get('/dealer/mini-site')
            ->assertOk()
            ->assertSee('name="site_brand_name"', false)
            ->assertSee('name="site_tagline"', false);

        $this->actingAs($user)->withSession(['2fa_passed' => true])
            ->post('/dealer/mini-site', [
                'public_slug'     => 'kayit-adi',
                'site_template'   => 'aurora',
                'site_brand_name' => 'Parlak Akademi',
                'site_tagline'    => 'Yeni Slogan',
            ])
            ->assertRedirect('/dealer/mini-site');

        $dealer->refresh();
        $this->assertSame('Parlak Akademi', $dealer->site_brand_name);
        $this->assertSame('Yeni Slogan', $dealer->site_tagline);
        $this->assertSame('Kayit Adi Ltd', $dealer->name, 'Kayit adi degismemeliydi');

        // Bayi panelinin sol menüsü de görünen adı basar (kullanıcı adını değil).
        $this->actingAs($user)->withSession(['2fa_passed' => true])
            ->get('/dealer/mini-site')
            ->assertOk()
            ->assertSee('text-overflow:ellipsis;">Parlak Akademi</div>', false);
    }

    /** Öğrencinin gördüğü başvuru formu şeridi de görünen adı kullanır. */
    public function test_apply_form_banner_uses_the_display_name(): void
    {
        $this->partner(['site_brand_name' => 'Parlak Akademi']);

        $this->get('/apply/partner/OPE-26-09-0001')
            ->assertOk()
            ->assertSee('<strong>Parlak Akademi</strong> ile işbirliği başvurusu', false)
            ->assertDontSee('Kayit Adi Ltd');
    }
}
