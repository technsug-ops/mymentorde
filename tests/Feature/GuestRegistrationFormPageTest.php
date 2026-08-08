<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Ön kayıt formu sayfası — render + eksik alan özeti.
 *
 * Sayfanın hiç render testi yoktu; 1800 satırlık bir blade ve içinde
 * koşullu bloklar var, bir @if'i bozmak sessizce 500 üretir.
 *
 * Ayrıca eksik alan özetinin VARSAYILAN KAPALI olduğunu sabitliyor.
 * Açık hâli formun üstünü bir hata listesine çeviriyordu; kapalı olması
 * kasıtlı bir karar, yanlışlıkla geri alınmasın.
 */
class GuestRegistrationFormPageTest extends TestCase
{
    use RefreshDatabase;

    private function guestUser(): User
    {
        $user = User::query()->create([
            'name'              => 'Aday Ogrenci',
            'email'             => 'aday_form@test.local',
            'password'          => Hash::make('Secret123!'),
            'role'              => User::ROLE_GUEST,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        GuestApplication::query()->create([
            'tracking_token'   => 'TOK-FORM-001',
            'first_name'       => 'Aday',
            'last_name'        => 'Ogrenci',
            'email'            => $user->email,
            'application_type' => 'bachelor',
            'kvkk_consent'     => true,
            'guest_user_id'    => $user->id,
        ]);

        return $user;
    }

    public function test_registration_form_page_renders(): void
    {
        $this->actingAs($this->guestUser())
            ->get('/guest/registration/form')
            ->assertOk();
    }

    /**
     * Eksik alan listesi kapalı gelmeli.
     *
     * `hidden` niteliği markup'ta duruyor — JS çalışmasa bile kapalı kalır.
     * Bu, sunucudan gelen HTML üzerinden ölçülüyor; bir sonraki geliştirici
     * "açık daha görünür olur" diye değiştirirse test uyarır.
     */
    public function test_missing_fields_panel_is_collapsed_by_default(): void
    {
        $html = $this->actingAs($this->guestUser())
            ->get('/guest/registration/form')
            ->assertOk()
            ->getContent();

        // Özet barı hiç yoksa test bir şey ölçmüyor demektir — önce onu doğrula.
        $this->assertStringContainsString('grf-missing-chips', $html, 'Eksik alan ozeti sayfada yok.');

        $this->assertMatchesRegularExpression(
            '/id="grfMissingList"[^>]*\shidden/',
            $html,
            'Eksik alan listesi acik geliyor — varsayilan kapali olmali.'
        );

        $this->assertStringContainsString('aria-expanded="false"', $html);
    }
}
