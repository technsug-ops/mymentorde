<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Tenancy\Concerns\MakesCompanies;
use Tests\TestCase;

/**
 * Giriş e-postası değişikliği — iki yönden.
 *
 * 1. Platform sahibi firma hesabının e-postasını değiştirebilir (hesap yönetimi)
 * 2. Firma kendi e-postasını kendi panelinden değiştirebilir
 *
 * ── SENKRON ─────────────────────────────────────────────────────────────
 * E-posta TEK bir yerde (`users.email`) tutuluyor, kopyası çıkarılmıyor.
 * Hangi taraftan değiştirilirse değiştirilsin diğer taraf anında yeni adresi
 * görür. Bu testler o tekliği de doğruluyor.
 */
class StaffEmailChangeTest extends TestCase
{
    use MakesCompanies;
    use RefreshDatabase;

    private const PASSWORD = 'gizli-sifre-123';

    private function owner(): User
    {
        return $this->userFor($this->companyA, User::ROLE_PLATFORM_OWNER);
    }

    private function managerWithPassword(): User
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $manager->forceFill(['password' => Hash::make(self::PASSWORD)])->save();

        return $manager->fresh();
    }

    private function asStaff(User $user): self
    {
        return $this->actingAs($user)->withSession(['2fa_passed' => true]);
    }

    private function url(int $companyId, int $userId): string
    {
        return "/platform/companies/{$companyId}/staff/{$userId}/email";
    }

    // ── Platform tarafı ─────────────────────────────────────────────────────

    public function test_owner_can_change_a_company_staff_email(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $manager->id), [
                'email' => 'yeni-adres@example.test',
            ])
            ->assertRedirect();

        $this->assertSame(
            'yeni-adres@example.test',
            User::query()->withoutGlobalScopes()->find($manager->id)->email
        );
    }

    /** `users.email` GLOBAL unique — başka şirketteki adres bile alınamaz. */
    public function test_duplicate_email_is_rejected(): void
    {
        $mine = $this->userFor($this->companyB, User::ROLE_MANAGER);
        $other = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $mine->id), [
                'email' => $other->email,
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(
            $mine->email,
            User::query()->withoutGlobalScopes()->find($mine->id)->email
        );
    }

    /** Öğrenci hesabının e-postası platform sahibi tarafından değiştirilemez. */
    public function test_student_email_cannot_be_changed_from_the_console(): void
    {
        $student = $this->userFor($this->companyB, User::ROLE_STUDENT);

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, (int) $student->id), [
                'email' => 'ogrenci-yeni@example.test',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_company_users_cannot_change_emails_from_the_console(): void
    {
        $manager = $this->userFor($this->companyB, User::ROLE_MANAGER);

        $response = $this->actingAs($manager)
            ->post($this->url((int) $this->companyB->id, (int) $manager->id), [
                'email' => 'kendim@example.test',
            ]);

        $this->assertContains($response->getStatusCode(), [403, 404, 302]);
    }

    // ── Firma tarafı ────────────────────────────────────────────────────────

    public function test_manager_can_change_own_email(): void
    {
        $manager = $this->managerWithPassword();

        $this->asStaff($manager)
            ->post('/manager/account', [
                'name' => $manager->name,
                'email' => 'kendi-yeni@example.test',
                'current_password' => self::PASSWORD,
            ])
            ->assertRedirect();

        $this->assertSame(
            'kendi-yeni@example.test',
            User::query()->withoutGlobalScopes()->find($manager->id)->email
        );
    }

    /**
     * MEVCUT ŞİFRE şart: e-posta hem giriş kimliği hem şifre sıfırlamanın
     * gittiği adres. Açık bir oturumu ele geçiren biri adresi değiştirip
     * hesabı kalıcı olarak devralabilirdi.
     */
    public function test_wrong_current_password_blocks_the_change(): void
    {
        $manager = $this->managerWithPassword();

        $this->asStaff($manager)
            ->post('/manager/account', [
                'name' => $manager->name,
                'email' => 'calinan@example.test',
                'current_password' => 'yanlis-sifre',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame(
            $manager->email,
            User::query()->withoutGlobalScopes()->find($manager->id)->email
        );
    }

    public function test_manager_cannot_take_an_email_used_elsewhere(): void
    {
        $manager = $this->managerWithPassword();
        $other = $this->userFor($this->companyA, User::ROLE_MANAGER);

        $this->asStaff($manager)
            ->post('/manager/account', [
                'name' => $manager->name,
                'email' => $other->email,
                'current_password' => self::PASSWORD,
            ])
            ->assertSessionHasErrors('email');
    }

    // ── Senkron ─────────────────────────────────────────────────────────────

    /**
     * Firma kendi tarafından değiştirdiğinde platform konsolu da yeni adresi
     * gösterir — e-posta tek yerde tutuluyor, kopyası yok.
     */
    public function test_change_from_the_firm_side_is_visible_in_the_console(): void
    {
        $manager = $this->managerWithPassword();

        // Eski adresi AYRI değişkende sakla: actingAs() controller'a bu MODEL
        // NESNESİNİ veriyor, controller onu güncelleyince $manager->email de
        // değişiyor ve karşılaştırma kendisiyle yapılmış olurdu.
        $oldEmail = (string) $manager->email;

        $this->asStaff($manager)->post('/manager/account', [
            'name' => $manager->name,
            'email' => 'senkron@example.test',
            'current_password' => self::PASSWORD,
        ]);

        $this->actingAs($this->owner())
            ->get('/platform/companies/' . $this->companyB->id)
            ->assertOk()
            ->assertSee('senkron@example.test', false)
            ->assertDontSee($oldEmail, false);
    }

    /** Ters yön: konsoldan değişince firma yeni adresle giriş yapar. */
    public function test_change_from_the_console_updates_the_login_identity(): void
    {
        $manager = $this->managerWithPassword();

        $managerId = (int) $manager->id;

        $this->actingAs($this->owner())
            ->post($this->url((int) $this->companyB->id, $managerId), [
                'email' => 'konsoldan@example.test',
            ])
            ->assertRedirect();

        // actingAs oturumu açık bırakıyor; gerçek giriş denemesi için kapat.
        $this->post('/logout');

        $this->post('/login', [
            'email' => 'konsoldan@example.test',
            'password' => self::PASSWORD,
        ])->assertRedirect();

        $this->assertAuthenticatedAs(User::query()->withoutGlobalScopes()->find($managerId));
    }
}
