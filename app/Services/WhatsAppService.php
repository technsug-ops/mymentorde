<?php

namespace App\Services;

use App\Services\Analytics\AnalyticsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta WhatsApp Cloud API wrapper.
 *
 * Doc: https://developers.facebook.com/docs/whatsapp/cloud-api
 *
 * Onemli notlar:
 *  - Plain text mesajlar sadece "session" icinde (kullanici son 24h icinde
 *    isletmeye mesaj gonderdiyse) gonderilir. Aksi takdirde Meta tarafindan
 *    onaylanmis bir template gerekir → {@see sendTemplate()}.
 *  - Konfigurasyon bos veya servis disabled ise tum send'ler false doner ve
 *    Log::warning duser; uygulama crash etmez (addon-safe).
 *
 * Config kaynagi: config/whatsapp.php (env'lerden okunur)
 */
class WhatsAppService
{
    private bool $enabled;
    private string $phoneNumberId;
    private string $token;
    private string $apiVersion;
    private string $baseUrl;
    private int $timeout;
    private string $defaultCountryCode;
    private string $defaultLanguage;

    public function __construct()
    {
        $this->enabled            = (bool) config('whatsapp.enabled', false);
        $this->phoneNumberId      = (string) config('whatsapp.phone_number_id', '');
        $this->token              = (string) config('whatsapp.access_token', '');
        $this->apiVersion         = (string) config('whatsapp.api_version', 'v21.0');
        $this->baseUrl            = rtrim((string) config('whatsapp.base_url', 'https://graph.facebook.com'), '/');
        $this->timeout            = (int) config('whatsapp.timeout', 15);
        $this->defaultCountryCode = (string) config('whatsapp.default_country_code', '90');
        $this->defaultLanguage    = (string) config('whatsapp.default_language', 'tr');
    }

    /**
     * Plain text mesaj — sadece 24h session icinde calisir.
     *
     * @return bool true: gonderildi, false: skip/hata (Log::warning duser)
     */
    public function sendText(string $toPhone, string $body): bool
    {
        if (!$this->isConfigured($toPhone, ['mode' => 'text'])) {
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($toPhone),
            'type'              => 'text',
            'text'              => ['body' => $body, 'preview_url' => true],
        ];

        return $this->sendRequest($payload, $toPhone, [
            'mode' => 'text',
            'body_length' => mb_strlen($body),
        ]);
    }

    /**
     * Onayli template mesaji — 24h session disinda kullanilir.
     *
     * @param string $toPhone Recipient phone (TR/E.164 — normalize edilir)
     * @param string $templateName Meta Business Manager'da onayli template adi
     * @param array  $params Body parametreleri ({{1}}, {{2}}, ... yerlerine)
     * @param string $lang Dil kodu (default: tr)
     */
    public function sendTemplate(
        string $toPhone,
        string $templateName,
        array $params = [],
        string $lang = 'tr'
    ): bool {
        if (!$this->isConfigured($toPhone, ['mode' => 'template', 'template' => $templateName])) {
            return false;
        }

        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn ($v) => ['type' => 'text', 'text' => (string) $v],
                    array_values($params)
                ),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($toPhone),
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $lang ?: $this->defaultLanguage],
                'components' => $components,
            ],
        ];

        return $this->sendRequest($payload, $toPhone, [
            'mode' => 'template',
            'template' => $templateName,
            'param_count' => count($params),
        ]);
    }

    /**
     * Belge talep linki ozel reminder helper.
     *
     * Hem ilk gonderim hem hatirlatma icin kullanilir. Text mode'da calisir
     * (manager-onayli template yoksa fallback). Eger template gerekiyorsa
     * (Meta WABA disinda 24h disinda gonderim icin) override edilebilir.
     */
    public function sendDocumentRequestReminder(
        string $toPhone,
        string $tokenUrl,
        string $categoryName,
        int $hoursLeft
    ): bool {
        $body = "Merhaba! {$categoryName} belgesini su linkten yukleyebilirsin: {$tokenUrl}";
        if ($hoursLeft > 0) {
            $body .= "\n\nSon teslim suresi: yaklasik {$hoursLeft} saat icinde dolacak.";
        }
        $body .= "\n\n- MentorDE";

        return $this->sendText($toPhone, $body);
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function isConfigured(string $toPhone, array $context = []): bool
    {
        if (!$this->enabled || $this->token === '' || $this->phoneNumberId === '') {
            Log::warning('WhatsAppService: not configured, skipping send', array_merge([
                'to' => $this->maskPhone($toPhone),
            ], $context));
            return false;
        }
        if (trim($toPhone) === '') {
            Log::warning('WhatsAppService: empty recipient phone', $context);
            return false;
        }
        return true;
    }

    private function sendRequest(array $payload, string $toPhone, array $analyticsCtx = []): bool
    {
        $maskedTo = $this->maskPhone($toPhone);

        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $this->captureAnalytics('whatsapp_message_sent', array_merge([
                    'to_masked'   => $maskedTo,
                    'status_code' => $response->status(),
                ], $analyticsCtx));
                return true;
            }

            Log::warning('WhatsAppService: API error', [
                'to'     => $maskedTo,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            $this->captureAnalytics('whatsapp_send_failed', array_merge([
                'to_masked'   => $maskedTo,
                'status_code' => $response->status(),
                'error'       => 'http_' . $response->status(),
            ], $analyticsCtx));

            return false;
        } catch (\Throwable $e) {
            Log::warning('WhatsAppService: exception', [
                'to'    => $maskedTo,
                'error' => $e->getMessage(),
            ]);

            $this->captureAnalytics('whatsapp_send_failed', array_merge([
                'to_masked' => $maskedTo,
                'error'     => 'exception',
                'exception' => substr($e->getMessage(), 0, 200),
            ], $analyticsCtx));

            return false;
        }
    }

    /**
     * Telefon normalize — Turkiye formatlari icin "+90" prefix ekler.
     *
     * Kabul edilen girdi formatlari:
     *   "0532 123 45 67"  → "905321234567"
     *   "532 123 45 67"   → "905321234567"
     *   "+90 532 ..."     → "905321234567"
     *   "+44 79 ..."      → "447900000000" (UK — country code korunur)
     *   "905321234567"    → "905321234567"
     *
     * Meta API'sine "+" gondermez (sadece rakam — dokumentasyona uygun).
     */
    public function normalizePhone(string $phone): string
    {
        // Sadece rakamlari tut
        $digits = preg_replace('/[^\d]/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        // Ulke kodu zaten girilmis mi? (00 prefix → "+" karsiligi)
        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        // Turkiye yerel format: "0532..." (11 hane) — 0'i at, 90 ekle
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return $this->defaultCountryCode . substr($digits, 1);
        }

        // TR mobile 10 hane (5XX XXX XX XX) — country code ekle
        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return $this->defaultCountryCode . $digits;
        }

        // Diger durumda country code zaten var (90, 49, 44, ...) varsay
        return $digits;
    }

    /**
     * PII korumasi — log'a sadece son 4 rakam ile maskelenmis hali gider.
     */
    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '****';
        }
        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    private function captureAnalytics(string $event, array $properties): void
    {
        try {
            app(AnalyticsService::class)->capture($event, $properties);
        } catch (\Throwable $e) {
            // Analytics never breaks the flow
            Log::debug('WhatsAppService: analytics capture failed', ['error' => $e->getMessage()]);
        }
    }
}
