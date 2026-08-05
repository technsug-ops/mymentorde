<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Firma panel hesabı için şifre sıfırlama.
 *
 * NEDEN VAR: firma geçici şifresini kaybederse tek çıkış /forgot-password idi
 * ve o da ilgili posta kutusuna erişim gerektiriyordu. Yeni partner devreye
 * alınırken tıkanma noktası oluyordu.
 *
 * ── SINIR ────────────────────────────────────────────────────────────────
 * Yalnızca PANEL hesapları. Öğrenci ve aday hesaplarının şifresi platform
 * sahibi tarafından sıfırlanamaz — onlar müşterinin müşterisidir, hesap
 * ilişkisi bizimle değil firmayladır.
 *
 * Impersonation'dan farkı: şifre sıfırlama SESSİZ DEĞİLDİR. Eski şifre
 * çalışmaz olur ve firma fark eder. Hesap kurtarma meşrudur; veriyi gizlice
 * okumak değildir.
 */
class StaffPasswordResetTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    private function url(int $companyId, int $userId): string
    {
        return "/platform/companies/{$companyId}/staff/{$userId}/reset-password";
    }

    // ── Temel akış ──────────────────────────────────────────────────────────

    public function test_owner_can_reset_a_company_manager_password(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $before = $manager->password;

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $manager->id))
            ->assertRedirect();

        $fresh = User::query()->withoutGlobalScopes()->find($manager->id);

        $this->assertNotSame($before, $fresh->password, 'Sifre degismedi.');
    }

    /** Yeni şifre ilk girişte değiştirilmek ZORUNDA. */
    public function test_reset_forces_a_password_change_on_next_login(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $manager->id))
            ->assertRedirect();

        $this->assertTrue(
            (bool) User::query()->withoutGlobalScopes()->find($manager->id)->password_must_change
        );
    }

    /** Yeni şifre bir kez gösterilmeli — aksi halde iletilemez. */
    public function test_new_password_is_shown_once(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $manager->id))
            ->assertRedirect();

        $status = (string) session('status');

        $this->assertStringContainsString($manager->email, $status);
        $this->assertMatchesRegularExpression('/[A-Za-z0-9]{10,}/', $status, 'Yeni sifre mesajda yok.');
    }

    // ── Sınırlar ────────────────────────────────────────────────────────────

    /**
     * ÖĞRENCİ hesabının şifresi sıfırlanamaz — müşterinin müşterisi.
     */
    public function test_student_password_cannot_be_reset(): void
    {
        $student = $this->userFor($this->companyB, User::ROLE_STUDENT);
        $before = $student->password;

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $student->id))
            ->assertSessionHasErrors('password');

        $this->assertSame(
            $before,
            User::query()->withoutGlobalScopes()->find($student->id)->password,
            'Ogrenci sifresi degistirildi.'
        );
    }

    public function test_guest_account_password_cannot_be_reset(): void
    {
        $guest = $this->userFor($this->companyB, User::ROLE_GUEST);

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $guest->id))
            ->assertSessionHasErrors('password');
    }

    /** Kullanıcı başka şirkete aitse dokunulamaz. */
    public function test_cannot_reset_a_user_from_another_company(): void
    {
        $otherManager = $this->userFor($this->companyA, User::ROLE_MANAGER);
        $before = $otherManager->password;

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $otherManager->id))
            ->assertSessionHasErrors('password');

        $this->assertSame(
            $before,
            User::query()->withoutGlobalScopes()->find($otherManager->id)->password
        );
    }

    // ── Yetki ───────────────────────────────────────────────────────────────

    public function test_company_users_cannot_reset_passwords(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $before = $manager->password;

        $response = $this->actingAs($manager)
            ->post($this->url((int) $this->companyB->id, (int) $manager->id));

        $this->assertContains($response->getStatusCode(), [403, 404, 302]);

        $this->assertSame(
            $before,
            User::query()->withoutGlobalScopes()->find($manager->id)->password,
            'Firma kullanicisi kendi sifresini panelden sifirlayabildi.'
        );
    }

    public function test_guests_cannot_reset_passwords(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $response = $this->post($this->url((int) $this->companyB->id, (int) $manager->id));

        $this->assertContains($response->getStatusCode(), [401, 403, 404, 302]);
    }

    // ── Panel ───────────────────────────────────────────────────────────────

    public function test_detail_page_lists_staff_but_not_students(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $student = $this->userFor($this->companyB, User::ROLE_STUDENT);

        $response = $this->actingAs($this->owner())
            ->get('/platform/companies/' . $this->companyB->id);

        $response->assertOk();
        $response->assertSee('Panel Hesapları', false);
        $response->assertSee($manager->email, false);
        $response->assertDontSee($student->email, false);
    }
}
