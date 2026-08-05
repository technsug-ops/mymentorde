<?php

namespace Tests\Feature;

use App\Models\GuestApplication;
use App\Rules\TurnstileToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Turnstile (CAPTCHA) zorunluluğu.
 *
 * ── BULUNAN AÇIK ────────────────────────────────────────────────────────
 * Public formlar `['nullable', 'string', new TurnstileToken()]` kullanıyordu.
 * `nullable` yüzünden alan HİÇ GÖNDERİLMEZSE doğrulama tamamen atlanıyordu:
 * bot alanı boş bırakıp geçebiliyordu. Koruma fiilen dekoratifti.
 *
 * ── NEDEN SABİT `required` DEĞİL ────────────────────────────────────────
 * Turnstile kapalıyken (yerel geliştirme, test) widget render edilmez ve
 * token üretilmez; sabit `required` formu tamamen kilitlerdi. Zorunluluk
 * korumanın AÇIK olmasına bağlı — karar TurnstileToken::rules() içinde.
 */
class TurnstileEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function enableTurnstile(): void
    {
        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.secret_key' => 'test-secret',
        ]);
    }

    private function disableTurnstile(): void
    {
        config(['services.turnstile.enabled' => false]);
    }

    /** @return array<string,string> */
    private function applyPayload(array $extra = []): array
    {
        return array_merge([
            'first_name' => 'Turnstile',
            'last_name' => 'Testi',
            'email' => 'turnstile-' . uniqid() . '@example.test',
            'phone' => '+49 15123456789',
            'application_type' => 'bachelor',
            'kvkk_consent' => '1',
        ], $extra);
    }

    // ── Kural seçimi ────────────────────────────────────────────────────────

    public function test_rules_are_optional_when_turnstile_is_disabled(): void
    {
        $this->disableTurnstile();

        $this->assertSame(['nullable', 'string'], TurnstileToken::rules());
        $this->assertFalse(TurnstileToken::isEnforced());
    }

    /** Anahtar yoksa zorunlu tutmak formu boşuna kilitler. */
    public function test_rules_are_optional_when_secret_is_missing(): void
    {
        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.secret_key' => '',
        ]);

        $this->assertSame(['nullable', 'string'], TurnstileToken::rules());
    }

    public function test_rules_become_required_when_configured(): void
    {
        $this->enableTurnstile();

        $rules = TurnstileToken::rules();

        $this->assertContains('required', $rules);
        $this->assertTrue(TurnstileToken::isEnforced());
    }

    // ── Asıl açık: token'sız gönderim ───────────────────────────────────────

    /**
     * ASIL REGRESYON: token alanı HİÇ gönderilmeden başvuru geçiyordu.
     */
    public function test_application_without_a_token_is_rejected_when_enforced(): void
    {
        $this->enableTurnstile();

        $payload = $this->applyPayload();

        $this->post('/apply', $payload)->assertSessionHasErrors('cf_turnstile_response');

        $this->assertNull(
            GuestApplication::query()->withoutGlobalScope('company')
                ->where('email', $payload['email'])->first(),
            'Token olmadan basvuru olustu — CAPTCHA hala atlanabiliyor.'
        );
    }

    /**
     * Hata mesajı insan diliyle olmalı.
     *
     * Laravel'in varsayılanı alan adını olduğu gibi yazıyordu:
     * "cf turnstile response alanı zorunludur." Widget yüklenmediğinde
     * kullanıcının gördüğü tek şey bu olur ve hiçbir şey anlatmaz.
     */
    public function test_error_message_is_human_readable(): void
    {
        $this->enableTurnstile();

        $this->post('/apply', $this->applyPayload());

        $message = (string) session('errors')?->first('cf_turnstile_response');

        $this->assertStringNotContainsString('cf turnstile response', mb_strtolower($message));
        $this->assertStringContainsString('Güvenlik doğrulaması', $message);
    }

    public function test_application_with_a_valid_token_passes(): void
    {
        $this->enableTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $payload = $this->applyPayload(['cf_turnstile_response' => 'gecerli-token']);

        $this->post('/apply', $payload)->assertRedirect();

        $this->assertNotNull(
            GuestApplication::query()->withoutGlobalScope('company')
                ->where('email', $payload['email'])->first()
        );
    }

    public function test_application_with_a_rejected_token_is_blocked(): void
    {
        $this->enableTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(
                ['success' => false, 'error-codes' => ['invalid-input-response']],
                200
            ),
        ]);

        $payload = $this->applyPayload(['cf_turnstile_response' => 'sahte-token']);

        $this->post('/apply', $payload)->assertSessionHasErrors('cf_turnstile_response');
    }

    /**
     * Cloudflare erişilemezse KULLANICI ENGELLENMEZ (fail-open).
     * Dış servis arızası gerçek başvuruyu kaybettirmemeli.
     */
    public function test_unreachable_cloudflare_does_not_block_the_user(): void
    {
        $this->enableTurnstile();

        Http::fake([
            'challenges.cloudflare.com/*' => fn () => throw new \RuntimeException('baglanti yok'),
        ]);

        $payload = $this->applyPayload(['cf_turnstile_response' => 'token']);

        $this->post('/apply', $payload)->assertRedirect();

        $this->assertNotNull(
            GuestApplication::query()->withoutGlobalScope('company')
                ->where('email', $payload['email'])->first(),
            'Cloudflare arizasi gercek kullaniciyi engelledi.'
        );
    }

    // ── Koruma kapalıyken akış bozulmamalı ──────────────────────────────────

    /** Yerel/test ortamı: widget yok, token yok, form yine de çalışmalı. */
    public function test_application_works_without_a_token_when_disabled(): void
    {
        $this->disableTurnstile();

        $payload = $this->applyPayload();

        $this->post('/apply', $payload)->assertRedirect();

        $this->assertNotNull(
            GuestApplication::query()->withoutGlobalScope('company')
                ->where('email', $payload['email'])->first()
        );
    }
}
