<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Platform konsolundan firmaya panel kullanıcısı açma.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Konsol mevcut hesapları listeliyor ve şifrelerini sıfırlayabiliyordu ama
 * YENİ hesap açamıyordu. Firma kurulurken tek yönetici oluşuyor; o hesap
 * silinirse ya da firma kullanıcısız kalırsa şirkete girmenin hiçbir yolu
 * kalmıyordu — başvuru linki de personelsiz firmada 404 veriyor.
 */
class PlatformStaffCreationTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    public function test_owner_can_create_a_manager_for_a_company(): void
    {
        $this->actingAs($this->owner())
            ->post("/platform/companies/{$this->companyB->id}/staff", [
                'name'  => 'YourGermanUni Yonetici',
                'email' => 'yonetici@yourgermanuni.test',
            ])
            ->assertRedirect();

        $created = User::withoutGlobalScope('company')
            ->where('email', 'yonetici@yourgermanuni.test')
            ->first();

        $this->assertNotNull($created, 'Kullanici olusmadi.');
        $this->assertSame($this->companyB->id, (int) $created->company_id, 'Yanlis firmaya yazildi.');
        $this->assertSame(User::ROLE_MANAGER, (string) $created->role);
        $this->assertTrue((bool) $created->password_must_change, 'Ilk giriste sifre degisimi zorunlu olmali.');
    }

    /**
     * Geçici şifre TEK SEFER, ekranda gösterilir.
     *
     * Mail gönderimine güvenilmiyor: bu ekranın varlık sebebi zaten firmanın
     * hesabına erişemiyor olması. Şifre kaydedilemezse kurtarma yine kilitlenir.
     */
    public function test_temporary_password_is_shown_once(): void
    {
        $this->actingAs($this->owner())
            ->post("/platform/companies/{$this->companyB->id}/staff", [
                'name'  => 'Yeni Yonetici',
                'email' => 'yeni@yourgermanuni.test',
            ])
            ->assertSessionHas('status');

        $this->assertStringContainsString('Geçici şifre', session('status'));
    }

    /** Aynı e-posta iki hesapta olamaz — giriş kimliği global tekil. */
    public function test_duplicate_email_is_rejected(): void
    {
        $existing = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($this->owner())
            ->post("/platform/companies/{$this->companyB->id}/staff", [
                'name'  => 'Cakisan',
                'email' => $existing->email,
            ])
            ->assertSessionHasErrors('email');
    }

    /** Paket sınırı burada da geçerli — konsoldan sessizce aşılamaz. */
    public function test_tier_user_limit_is_enforced(): void
    {
        // Trial: users_max = 2
        $this->companyB->update(['subscription_tier' => Company::TIER_TRIAL]);
        $this->userFor($this->companyB, User::ROLE_MANAGER);
        $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->actingAs($this->owner())
            ->post("/platform/companies/{$this->companyB->id}/staff", [
                'name'  => 'Ucuncu',
                'email' => 'ucuncu@yourgermanuni.test',
            ])
            ->assertSessionHasErrors('staff');

        $this->assertNull(
            User::withoutGlobalScope('company')->where('email', 'ucuncu@yourgermanuni.test')->first()
        );
    }

    /** Platform sahibi olmayan bu kapıyı hiç açamaz. */
    public function test_non_owner_cannot_create_staff(): void
    {
        $manager = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($manager)
            ->withSession(['2fa_passed' => true])
            ->post("/platform/companies/{$this->companyB->id}/staff", [
                'name'  => 'Izinsiz',
                'email' => 'izinsiz@yourgermanuni.test',
            ]);

        $this->assertNull(
            User::withoutGlobalScope('company')->where('email', 'izinsiz@yourgermanuni.test')->first(),
            'Platform sahibi olmayan kullanici hesap acti.'
        );
    }
}
