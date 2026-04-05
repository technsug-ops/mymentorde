<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ── Security Headers ─────────────────────────────────────────────────────

    public function test_response_has_x_frame_options_header(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_response_has_x_content_type_options_header(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_response_has_content_security_policy_header(): void
    {
        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy', '');
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_response_has_referrer_policy_header(): void
    {
        $this->get('/')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // ── Throttle Tests ───────────────────────────────────────────────────────

    public function test_forgot_password_throttles_after_five_requests(): void
    {
        // throttle:5,1 → 5. istek geçer, 6. istek 429 döner
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => 'throttle_test@test.local']);
        }

        $this->post('/forgot-password', ['email' => 'throttle_test@test.local'])
            ->assertStatus(429);
    }

    public function test_throttle_resets_after_travel_in_time(): void
    {
        // 5 istek göndererek throttle'ı doldur
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => 'throttle_reset@test.local']);
        }

        // Zaman ilerlet (1 dakika + 1 saniye)
        $this->travel(61)->seconds();

        // Throttle sıfırlandı → artık 429 yok
        $response = $this->post('/forgot-password', ['email' => 'throttle_reset@test.local']);
        $this->assertNotEquals(429, $response->status());
    }
}
