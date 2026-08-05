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
 *   $request->validate(['cf_turnstile_response' => TurnstileToken::rules()]);
 */
class TurnstileToken implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Public formların kullanacağı kural dizisi.
     *
     * ── NEDEN `required` DOĞRUDAN YAZILMIYOR ────────────────────────────
     *
     * Formlar `['nullable', 'string', new TurnstileToken()]` kullanıyordu.
     * `nullable` yüzünden alan HİÇ GÖNDERİLMEZSE doğrulama tamamen atlanıyor:
     * bot alanı boş bırakıp geçebiliyordu. Koruma fiilen dekoratifti.
     *
     * Ama `required`'ı sabit yazmak da olmaz: Turnstile kapalıyken (yerel
     * geliştirme, test ortamı) widget hiç render edilmez, token üretilmez ve
     * form kilitlenir. Bu yüzden zorunluluk, KORUMANIN AÇIK OLMASINA bağlı.
     *
     * Karar tek yerde: dört public form da buradan besleniyor.
     *
     * @return list<mixed>
     */
    public static function rules(): array
    {
        if (! self::isEnforced()) {
            // Koruma kapalı ya da yapılandırılmamış — alan opsiyonel.
            return ['nullable', 'string'];
        }

        return ['required', 'string', new self()];
    }

    /** Turnstile gerçekten devrede mi? */
    public static function isEnforced(): bool
    {
        if (! (bool) config('services.turnstile.enabled', false)) {
            return false;
        }

        return trim((string) config('services.turnstile.secret_key', '')) !== '';
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isEnforced()) {
            return; // Kapalı ya da yapılandırılmamış — geç
        }

        $secret = (string) config('services.turnstile.secret_key', '');
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
