<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    // ── Page Visibility ──────────────────────────────────────────────────────

    public function test_forgot_password_page_loads(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('name="email"', false);
    }

    // ── Send Reset Link ──────────────────────────────────────────────────────

    public function test_send_reset_link_with_valid_email_returns_status(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name'      => 'Reset User',
            'email'     => 'reset_valid@test.local',
            'password'  => Hash::make('Secret123!'),
            'role'      => User::ROLE_STUDENT,
            'is_active' => true,
        ]);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        // Şifre sıfırlama bildirimi gönderildi mi?
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_send_reset_link_with_unknown_email_returns_same_generic_status(): void
    {
        // GÜVENLİK: E-posta numaralandırma saldırısını önlemek için bilinmeyen
        // e-posta adreslerinde de aynı genel mesaj döndürülür (hata değil).
        // Saldırgan "bu e-posta kayıtlı mı?" bilgisini elde edemez.
        $this->post('/forgot-password', ['email' => 'no_such_user@test.local'])
            ->assertRedirect()
            ->assertSessionHas('status')
            ->assertSessionMissing('errors');
    }

    // ── Reset Password Form ──────────────────────────────────────────────────

    public function test_reset_password_page_loads_with_token(): void
    {
        $this->get('/reset-password/fake-token-abc123')
            ->assertOk()
            ->assertSee('name="password"', false);
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function test_reset_password_with_weak_password_fails_validation(): void
    {
        $user = User::query()->create([
            'name'      => 'Reset Weak',
            'email'     => 'reset_weak@test.local',
            'password'  => Hash::make('Secret123!'),
            'role'      => User::ROLE_STUDENT,
            'is_active' => true,
        ]);

        // Kısa / zayıf şifre → validation error
        $this->post('/reset-password', [
            'token'                 => 'irrelevant-token',
            'email'                 => $user->email,
            'password'              => 'weak',
            'password_confirmation' => 'weak',
        ])->assertRedirect()
          ->assertSessionHasErrors('password');
    }

    // ── Successful Reset ─────────────────────────────────────────────────────

    public function test_successful_password_reset_redirects_to_login(): void
    {
        $user = User::query()->create([
            'name'      => 'Reset Success',
            'email'     => 'reset_success@test.local',
            'password'  => Hash::make('OldPassword1!'),
            'role'      => User::ROLE_STUDENT,
            'is_active' => true,
        ]);

        // Gerçek token oluştur (password_reset_tokens tablosuna insert eder)
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])->assertRedirect('/login')
          ->assertSessionHas('status');

        // Şifre gerçekten değişti mi?
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecret123!', $user->password));
    }
}
