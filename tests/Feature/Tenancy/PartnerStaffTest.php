<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use App\Models\UserTwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Firmanın kendi kullanıcılarını yönetmesi + pakete bağlı kullanıcı sınırı.
 *
 * Bir firmada tek kişi olması gerçekçi değil; sadece yöneticiyle değil başka
 * kişilerle de irtibat gerekebiliyor. Sınırı paket belirliyor — öğrenci ve
 * aday bu sayıya GİRMEZ, onlar müşteri.
 */
class PartnerStaffTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    private function partnerManager(string $tier = Company::TIER_GOLD): User
    {
        $this->companyB->update([
            'panel_mode'        => Company::PANEL_PARTNER,
            'subscription_tier' => $tier,
        ]);
        Company::flushPanelModeCache();

        return $this->userFor($this->companyB, User::ROLE_MANAGER);
    }

    // ── Ekleme ──────────────────────────────────────────────────────────────

    public function test_partner_can_add_a_colleague(): void
    {
        $manager = $this->partnerManager();

        $this->asStaff($manager)
            ->post('/manager/users', [
                'name'  => 'Ikinci Yonetici',
                'email' => 'ikinci@novavia.test',
                'role'  => User::ROLE_MANAGER,
            ])
            ->assertRedirect();

        $created = User::withoutGlobalScope('company')->where('email', 'ikinci@novavia.test')->first();

        $this->assertNotNull($created, 'Kullanici olusmadi.');
        $this->assertSame($this->companyB->id, (int) $created->company_id, 'Kullanici yanlis firmaya yazildi.');
        $this->assertTrue((bool) $created->is_active);
    }

    /**
     * Danışman rolü buradan açılamaz.
     *
     * Danışman operasyonu yürüten firmanın elemanı; partner kendi danışmanını
     * yaratabilseydi "danışmanı üst firma atar" kuralı delinirdi.
     */
    public function test_partner_cannot_create_an_advisor(): void
    {
        $manager = $this->partnerManager();

        $this->asStaff($manager)
            ->post('/manager/users', [
                'name'  => 'Sahte Danisman',
                'email' => 'danisman@novavia.test',
                'role'  => User::ROLE_SENIOR,
            ])
            ->assertSessionHasErrors('role');

        $this->assertNull(User::withoutGlobalScope('company')->where('email', 'danisman@novavia.test')->first());
    }

    // ── Kota ────────────────────────────────────────────────────────────────

    /** Paket sınırı dolunca yeni kullanıcı eklenemez. */
    public function test_user_limit_is_enforced_per_tier(): void
    {
        // Trial: users_max = 2. Yönetici zaten 1 kişi.
        $manager = $this->partnerManager(Company::TIER_TRIAL);

        $this->asStaff($manager)->post('/manager/users', [
            'name' => 'Ikinci', 'email' => 'ikinci@novavia.test', 'role' => User::ROLE_MANAGER,
        ])->assertRedirect();

        $this->assertSame(2, $this->companyB->fresh()->staffUserCount());

        // Üçüncü kişi sınırı aşar.
        $this->asStaff($manager)
            ->post('/manager/users', [
                'name' => 'Ucuncu', 'email' => 'ucuncu@novavia.test', 'role' => User::ROLE_MANAGER,
            ])
            ->assertSessionHasErrors('limit');

        $this->assertNull(User::withoutGlobalScope('company')->where('email', 'ucuncu@novavia.test')->first());
    }

    /** Premium sınırsız. */
    public function test_premium_tier_has_no_user_limit(): void
    {
        $this->companyB->update(['subscription_tier' => Company::TIER_PREMIUM]);

        $this->assertNull($this->companyB->fresh()->userLimit());
        $this->assertTrue($this->companyB->fresh()->canAddStaffUser());
    }

    /**
     * Öğrenci ve aday kotayı TÜKETMEZ.
     *
     * Aksi halde müşteri kazandıkça firma kendi ekibini büyütemez hale
     * gelirdi — sınırın amacı bu değil.
     */
    public function test_students_and_guests_do_not_consume_the_quota(): void
    {
        $this->companyB->update(['subscription_tier' => Company::TIER_TRIAL]);

        $this->userFor($this->companyB, User::ROLE_STUDENT);
        $this->userFor($this->companyB, User::ROLE_GUEST);

        $this->assertSame(0, $this->companyB->fresh()->staffUserCount());
        $this->assertTrue($this->companyB->fresh()->canAddStaffUser());
    }

    // ── Sınırlar ────────────────────────────────────────────────────────────

    public function test_partner_cannot_touch_another_companys_user(): void
    {
        $manager  = $this->partnerManager();
        $stranger = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post("/manager/users/{$stranger->id}/reset")
            ->assertNotFound();
    }

    /** Kendini kilitlemek: firmada erişimi olan kimse kalmazdı. */
    public function test_user_cannot_deactivate_themselves(): void
    {
        $manager = $this->partnerManager();

        $this->asStaff($manager)
            ->post("/manager/users/{$manager->id}/toggle")
            ->assertStatus(422);

        $this->assertTrue((bool) $manager->fresh()->is_active);
    }

    // ── 2FA sıfırlama ───────────────────────────────────────────────────────

    /**
     * 2FA yalnızca zorunlu yönlendirmeyle BİR KEZ kurulabiliyordu; telefonunu
     * değiştiren kullanıcının panelde çıkış yolu yoktu. Sıfırlayınca kayıt
     * silinir ve Require2FA kullanıcıyı yeni QR'a götürür.
     */
    public function test_two_factor_can_be_reset_with_the_password(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $manager->forceFill(['password' => Hash::make('dogru-sifre')])->save();

        UserTwoFactor::create([
            'user_id'    => $manager->id,
            'secret'     => encrypt('GIZLI'),
            'enabled_at' => now(),
        ]);

        $this->asStaff($manager)
            ->post('/manager/account/2fa/reset', ['current_password' => 'dogru-sifre'])
            ->assertRedirect(route('2fa.setup'));

        $this->assertSame(0, UserTwoFactor::where('user_id', $manager->id)->count());
    }

    /** Şifre olmadan sıfırlanamaz — yoksa korumanın kendisi saldırı yüzeyi olurdu. */
    public function test_two_factor_reset_requires_the_correct_password(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $manager->forceFill(['password' => Hash::make('dogru-sifre')])->save();

        UserTwoFactor::create([
            'user_id'    => $manager->id,
            'secret'     => encrypt('GIZLI'),
            'enabled_at' => now(),
        ]);

        $this->asStaff($manager)
            ->post('/manager/account/2fa/reset', ['current_password' => 'yanlis'])
            ->assertSessionHasErrors('current_password');

        $this->assertSame(1, UserTwoFactor::where('user_id', $manager->id)->count());
    }
}
