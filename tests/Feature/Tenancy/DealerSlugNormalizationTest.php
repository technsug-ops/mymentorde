<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Dealer;
use App\Models\User;
use App\Support\DealerSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Mini-site adresi: kullanıcının yazdığını REDDETME, hizala.
 *
 * ── NEREDEN ÇIKTI ───────────────────────────────────────────────────────
 * Alanın etiketi "Slug (/p/...)" olduğu için kullanıcı `/p/yigitdanismanlik`
 * yazdı; `pattern` niteliği yüzünden tarayıcı kendi dilinde ("Deine Eingabe
 * muss mit dem geforderten Format übereinstimmen") hata verdi. Türkçe harf
 * yazınca da aynı duvar. Alan iki denemede iki kez tökezletti.
 *
 * Bu testin ölçtüğü şey: makul her yazım kabul edilip doğru slug'a dönüyor.
 */
class DealerSlugNormalizationTest extends TestCase
{
    use RefreshDatabase;

    private function dealer(): Dealer
    {
        return Dealer::create([
            'code'             => 'FRE-26-07-1344',
            'name'             => 'Yigit Danismanlik',
            'dealer_type_code' => 'freelance_danisman',
            'roles'            => [Dealer::ROLE_FREELANCE],
            'is_active'        => true,
            'is_archived'      => false,
        ]);
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
            'company_id'        => (int) Company::query()->where('is_active', true)->orderBy('id')->value('id'),
        ]);

        return $this->actingAs($manager)->withSession(['2fa_passed' => true]);
    }

    /** Kullanıcının gerçekten yazdığı biçimler. */
    public static function inputs(): array
    {
        return [
            'sade'                => ['yigitdanismanlik',        'yigitdanismanlik'],
            'p oneki'             => ['/p/yigitdanismanlik',     'yigitdanismanlik'],
            'p oneki egik ciziksiz' => ['p/yigitdanismanlik',    'yigitdanismanlik'],
            'iki yaninda cizgi'   => ['/yigitdanismanlik/',      'yigitdanismanlik'],
            'turkce harf'         => ['/yigitdanısmanlık/',      'yigitdanismanlik'],
            'bosluk + buyuk harf' => ['Yigit Danismanlik',       'yigit-danismanlik'],
            'tam adres'           => ['https://panel.mentorde.com/p/yigit-danismanlik', 'yigit-danismanlik'],
            'onizleme baglantisi' => ['/p/yigit-danismanlik?preview=1', 'yigit-danismanlik'],
            'turkce hepsi'        => ['Çiğdem Öğrenci Şubesi',   'cigdem-ogrenci-subesi'],
        ];
    }

    /**
     * @dataProvider inputs
     */
    public function test_normalizer_aligns_what_the_user_typed(string $typed, string $expected): void
    {
        $this->assertSame($expected, DealerSlug::normalize($typed), "Girdi: {$typed}");
    }

    /** Boş girdi null döner — çağıran "değiştirme" olarak yorumlar. */
    public function test_empty_input_returns_null(): void
    {
        $this->assertNull(DealerSlug::normalize(''));
        $this->assertNull(DealerSlug::normalize('   '));
        $this->assertNull(DealerSlug::normalize('/p/'));
    }

    /** ASIL GARANTİ: manager formuna "/p/..." yazmak artık kaydediyor. */
    public function test_manager_can_save_a_slug_typed_with_the_prefix(): void
    {
        $dealer = $this->dealer();

        $this->asManager()
            ->post('/manager/dealers/' . $dealer->code . '/mini-site', [
                'public_slug' => '/p/yigitdanısmanlık',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('yigitdanismanlik', $dealer->refresh()->public_slug);
    }

    /** Hizalama doğrulamayı KALDIRMAZ: rezerve yol hâlâ reddedilir. */
    public function test_reserved_slug_is_still_rejected(): void
    {
        $dealer = $this->dealer();

        $this->asManager()
            ->post('/manager/dealers/' . $dealer->code . '/mini-site', [
                'public_slug' => '/p/Manager',
            ])
            ->assertSessionHasErrors('public_slug');

        $this->assertNull($dealer->refresh()->public_slug);
    }

    /** Benzersizlik de duruyor — başkasının adresi alınamaz. */
    public function test_duplicate_slug_is_still_rejected(): void
    {
        $this->dealer()->update(['public_slug' => 'yigitdanismanlik']);

        $other = Dealer::create([
            'code'             => 'FRE-26-07-9999',
            'name'             => 'Baska Danisman',
            'dealer_type_code' => 'freelance_danisman',
            'is_active'        => true,
            'is_archived'      => false,
        ]);

        $this->asManager()
            ->post('/manager/dealers/' . $other->code . '/mini-site', [
                'public_slug' => '/p/Yigitdanısmanlık',
            ])
            ->assertSessionHasErrors('public_slug');

        $this->assertNull($other->refresh()->public_slug);
    }
}
