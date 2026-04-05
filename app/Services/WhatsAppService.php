<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $phoneNumberId;
    private string $token;
    private string $apiVersion;
    private string $baseUrl;

    public function __construct()
    {
        $this->phoneNumberId = (string) config('services.whatsapp.phone_number_id', '');
        $this->token         = (string) config('services.whatsapp.token', '');
        $this->apiVersion    = (string) config('services.whatsapp.api_version', 'v19.0');
        $this->baseUrl       = "https://graph.facebook.com/{$this->apiVersion}";
    }

    /**
     * Send a WhatsApp template message.
     *
     * @param  string  $to          Recipient phone in E.164 format (e.g. +905551234567)
     * @param  string  $templateName Template name registered in Meta Business Manager
     * @param  array   $bodyParams  Text parameters for {{1}}, {{2}}... in template body
     * @param  string  $languageCode Language code (default: tr)
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array  $bodyParams   = [],
        string $languageCode = 'tr'
    ): bool {
        if ($this->token === '' || $this->phoneNumberId === '') {
            Log::debug('WhatsAppService: not configured, skipping send', ['to' => $to, 'template' => $templateName]);
            return false;
        }

        $components = [];
        if (!empty($bodyParams)) {
            $parameters = array_map(
                fn ($value) => ['type' => 'text', 'text' => (string) $value],
                array_values($bodyParams)
            );
            $components[] = ['type' => 'body', 'parameters' => $parameters];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($to),
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->sendRequest($payload, $to);
    }

    /**
     * Send a plain text message (only for verified/test numbers or after 24h window).
     */
    public function sendText(string $to, string $body): bool
    {
        if ($this->token === '' || $this->phoneNumberId === '') {
            Log::debug('WhatsAppService: not configured, skipping send', ['to' => $to]);
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($to),
            'type'              => 'text',
            'text'              => ['body' => $body, 'preview_url' => false],
        ];

        return $this->sendRequest($payload, $to);
    }

    private function sendRequest(array $payload, string $to): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsAppService: API error', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsAppService: exception', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        // Ensure E.164 format (strip spaces, dashes, parentheses)
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }
        return $cleaned;
    }
}
