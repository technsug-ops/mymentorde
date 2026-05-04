<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile token doğrulama.
 *
 * Frontend widget bir token üretir, backend bu token'i siteverify endpoint'ine
 * yollayıp gerçek kullanıcı olduğunu onaylar.
 *
 * Bypass mantığı:
 * - services.turnstile.enabled = false → tüm istekler kabul (local/dev)
 * - secret_key boş → bypass
 * - turnstile.cloudflare.com erişilemiyor → fail-open (production'da
 *   CAPTCHA fail nedeniyle gerçek kullanıcı engellenmesin)
 *
 * Kullanım:
 *   $request->validate(['cf_turnstile_response' => ['required', new TurnstileToken()]]);
 */
class TurnstileToken implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (bool) config('services.turnstile.enabled', false)) {
            return; // Disabled — pass
        }

        $secret = (string) config('services.turnstile.secret_key', '');
        if ($secret === '') {
            return; // No secret configured — pass (config eksikse engellemiyoruz)
        }

        $token = trim((string) $value);
        if ($token === '') {
            $fail('Lütfen güvenlik doğrulamasını tamamlayın.');
            return;
        }

        try {
            $resp = Http::timeout(8)->asForm()->post(self::VERIFY_URL, [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => request()?->ip(),
            ]);

            if (! $resp->ok()) {
                Log::warning('Turnstile verify HTTP error', [
                    'status' => $resp->status(),
                    'body'   => substr((string) $resp->body(), 0, 500),
                ]);
                return; // Fail-open: cloudflare ulaşılamıyor → kabul et
            }

            $data = (array) $resp->json();
            if (empty($data['success'])) {
                $errorCodes = (array) ($data['error-codes'] ?? []);
                Log::info('Turnstile verify rejected', ['error_codes' => $errorCodes]);
                $fail('Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.');
            }
        } catch (\Throwable $e) {
            Log::warning('Turnstile verify exception', ['error' => $e->getMessage()]);
            // Fail-open: dış servis hatası kullanıcıyı engellemesin
        }
    }
}
