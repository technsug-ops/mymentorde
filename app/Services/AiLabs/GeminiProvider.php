<?php

namespace App\Services\AiLabs;

use App\Models\MarketingAdminSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini provider — File API + generateContent.
 *
 * RAG için iki tür kaynak desteklenir:
 *   1. PDF → File API upload (file_data reference)
 *   2. URL/text → inline system prompt context
 *
 * API key öncelik sırası:
 *   1. marketing_admin_settings.ai_labs_gemini_key (DB override)
 *   2. config('services.ai_labs.gemini.api_key') (.env)
 */
class GeminiProvider
{
    public function isConfigured(?int $companyId = null): bool
    {
        return !empty($this->resolveApiKey($companyId));
    }

    /**
     * API anahtarının çalıştığını doğrula — models list endpoint'i hızlıdır.
     *
     * @param string|null $overrideKey Save edilmeden test için form'dan gelen key
     * @return array{ok:bool, models?:array<int,string>, error?:string, status?:int}
     */
    public function testConnection(?int $companyId = null, ?string $overrideKey = null): array
    {
        $apiKey = $overrideKey ? trim($overrideKey) : $this->resolveApiKey($companyId);
        if (!$apiKey) {
            return ['ok' => false, 'error' => 'no_api_key'];
        }

        try {
            $base = $this->apiBase();
            $response = $this->http()->get("{$base}/models", ['key' => $apiKey]);

            if (!$response->successful()) {
                return [
                    'ok'     => false,
                    'status' => $response->status(),
                    'error'  => 'http_' . $response->status() . ': ' . substr((string) $response->body(), 0, 200),
                ];
            }

            $models = collect($response->json('models') ?? [])
                ->pluck('name')
                ->filter()
                ->values()
                ->take(20)
                ->all();

            return ['ok' => true, 'models' => $models];
        } catch (\Throwable $e) {
            Log::warning('AiLabs Gemini test failed', ['e' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    /**
     * Dosyayı Gemini File API'ye yükler (resumable upload protocol).
     *
     * @return array{ok:bool, file_id?:string, file_uri?:string, mime_type?:string, error?:string}
     */
    public function uploadFile(string $absolutePath, string $mimeType, string $displayName, ?int $companyId = null): array
    {
        $apiKey = $this->resolveApiKey($companyId);
        if (!$apiKey) {
            return ['ok' => false, 'error' => 'no_api_key'];
        }
        if (!is_file($absolutePath)) {
            return ['ok' => false, 'error' => 'file_not_found: ' . $absolutePath];
        }

        $fileSize = filesize($absolutePath);
        $uploadBase = $this->uploadBase();

        try {
            // Step 1 — start resumable upload
            $start = $this->http()
                ->withHeaders([
                    'X-Goog-Upload-Protocol'       => 'resumable',
                    'X-Goog-Upload-Command'        => 'start',
                    'X-Goog-Upload-Header-Content-Length' => (string) $fileSize,
                    'X-Goog-Upload-Header-Content-Type'   => $mimeType,
                    'Content-Type'                 => 'application/json',
                ])
                ->post("{$uploadBase}/files?key={$apiKey}", [
                    'file' => ['display_name' => $displayName],
                ]);

            if (!$start->successful()) {
                return ['ok' => false, 'error' => 'start_failed_' . $start->status() . ': ' . substr((string) $start->body(), 0, 200)];
            }

            $uploadUrl = $start->header('X-Goog-Upload-URL') ?: $start->header('x-goog-upload-url');
            if (!$uploadUrl) {
                return ['ok' => false, 'error' => 'no_upload_url'];
            }

            // Step 2 — upload file bytes
            $bytes = file_get_contents($absolutePath);
            $finalize = $this->http()
                ->withHeaders([
                    'Content-Length'         => (string) $fileSize,
                    'X-Goog-Upload-Offset'   => '0',
                    'X-Goog-Upload-Command'  => 'upload, finalize',
                ])
                ->withBody($bytes, $mimeType)
                ->post($uploadUrl);

            if (!$finalize->successful()) {
                return ['ok' => false, 'error' => 'upload_failed_' . $finalize->status() . ': ' . substr((string) $finalize->body(), 0, 200)];
            }

            $file = $finalize->json('file') ?? [];
            $name = $file['name'] ?? null; // "files/abc123"
            $uri  = $file['uri']  ?? null;

            if (!$name || !$uri) {
                return ['ok' => false, 'error' => 'invalid_response: ' . substr((string) $finalize->body(), 0, 200)];
            }

            return [
                'ok'        => true,
                'file_id'   => $name,       // "files/abc123"
                'file_uri'  => $uri,        // full URI
                'mime_type' => $file['mimeType'] ?? $mimeType,
            ];
        } catch (\Throwable $e) {
            Log::warning('AiLabs Gemini upload exception', ['e' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    /**
     * Gemini File API'den dosyayı siler.
     *
     * @param string $fileId  "files/abc123" formatında
     */
    public function deleteFile(string $fileId, ?int $companyId = null): array
    {
        $apiKey = $this->resolveApiKey($companyId);
        if (!$apiKey) {
            return ['ok' => false, 'error' => 'no_api_key'];
        }

        try {
            $base = $this->apiBase();
            $response = $this->http()->delete("{$base}/{$fileId}", ['key' => $apiKey]);

            return ['ok' => $response->successful(), 'status' => $response->status()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Gemini generateContent — system prompt + user message + optional file refs + history.
     *
     * @param array<int,array{file_uri:string, mime_type:string}> $fileRefs
     * @param array<string,mixed> $options  max_output_tokens, temperature, history (array of {role, content})
     *
     * @return array{ok:bool, content?:string, tokens_input?:int, tokens_output?:int, error?:string, model?:string}
     */
    public function chat(string $systemPrompt, string $userMessage, array $fileRefs = [], array $options = [], ?int $companyId = null): array
    {
        $apiKey = $this->resolveApiKey($companyId);
        if (!$apiKey) {
            return ['ok' => false, 'error' => 'no_api_key'];
        }

        $model = $this->resolveModel($companyId);
        $base = $this->apiBase();

        // Contents: önce history (user/assistant çiftleri), sonra current user message
        $contents = [];

        // History — Gemini 'model' role bekliyor (assistant yerine)
        foreach (($options['history'] ?? []) as $msg) {
            $role = ($msg['role'] ?? '') === 'user' ? 'user' : 'model';
            $content = trim((string) ($msg['content'] ?? ''));
            if ($content === '') continue;
            $contents[] = [
                'role'  => $role,
                'parts' => [['text' => $content]],
            ];
        }

        // Current user message — file refs + text
        $userParts = [];
        foreach ($fileRefs as $ref) {
            if (empty($ref['file_uri']) || empty($ref['mime_type'])) {
                continue;
            }
            $userParts[] = [
                'file_data' => [
                    'mime_type' => $ref['mime_type'],
                    'file_uri'  => $ref['file_uri'],
                ],
            ];
        }
        $userParts[] = ['text' => $userMessage];
        $contents[] = ['role' => 'user', 'parts' => $userParts];

        $genConfig = [
            'temperature'     => (float) ($options['temperature'] ?? 0.3),
            'maxOutputTokens' => (int) ($options['max_output_tokens'] ?? 2048),
            // Gemini 2.5 thinking mode'u kapat — thinking tokens tüm output budget'ını yiyor
            // Açık bırakılırsa maxOutputTokens=2048 → ~1800 thinking + ~200 candidates kalır
            // ve cevap boş/kesik döner.
            'thinkingConfig' => ['thinkingBudget' => (int) ($options['thinking_budget'] ?? 0)],
        ];
        // Gemini 2.5 structured output — JSON zorunluluğu
        if (!empty($options['response_mime_type'])) {
            $genConfig['responseMimeType'] = (string) $options['response_mime_type'];
        }

        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            'generationConfig' => $genConfig,
        ];

        try {
            // Retry: 503 (UNAVAILABLE) + 429 (rate limit) için 2x otomatik dene, 1sn ve 3sn backoff
            $url = "{$base}/models/{$model}:generateContent?key={$apiKey}";
            $response = null;
            $lastStatus = 0;
            $retryDelays = [0, 1, 3]; // 3 deneme: hemen, 1sn, 3sn

            foreach ($retryDelays as $delay) {
                if ($delay > 0) {
                    sleep($delay);
                }
                $response = $this->http()->post($url, $payload);
                $lastStatus = $response->status();
                if ($response->successful()) {
                    break;
                }
                // Sadece gerçekten geçici olan hatalarda tekrar dene (429 = quota tükenmiş, retry anlamsız)
                if (!in_array($lastStatus, [500, 502, 503, 504], true)) {
                    break;
                }
            }

            if (!$response || !$response->successful()) {
                Log::warning('AiLabs Gemini chat HTTP error (after retries)', [
                    'status' => $lastStatus,
                    'body'   => $response ? substr((string) $response->body(), 0, 500) : 'no_response',
                ]);
                return [
                    'ok'    => false,
                    'error' => 'http_' . $lastStatus . ': ' . ($response ? substr((string) $response->body(), 0, 300) : 'no_response'),
                ];
            }

            $json = $response->json();
            $candidates = $json['candidates'] ?? [];
            $text = '';
            foreach (($candidates[0]['content']['parts'] ?? []) as $part) {
                if (isset($part['text'])) {
                    $text .= $part['text'];
                }
            }

            $usage = $json['usageMetadata'] ?? [];

            return [
                'ok'            => true,
                'content'       => $text,
                'model'         => $model,
                'tokens_input'  => (int) ($usage['promptTokenCount'] ?? 0),
                'tokens_output' => (int) ($usage['candidatesTokenCount'] ?? 0),
                'finish_reason' => $candidates[0]['finishReason'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('AiLabs Gemini chat exception', ['e' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    /**
     * Gemini streamGenerateContent — her yeni chunk için metin parçası yield eder.
     *
     * @return \Generator<int, array{text?:string, done?:bool, usage?:array, finish_reason?:?string, error?:string}>
     */
    public function streamChat(string $systemPrompt, string $userMessage, array $fileRefs = [], array $options = [], ?int $companyId = null): \Generator
    {
        $apiKey = $this->resolveApiKey($companyId);
        if (!$apiKey) {
            yield ['error' => 'no_api_key'];
            return;
        }

        $model = $this->resolveModel($companyId);
        $base = $this->apiBase();

        // Contents — aynı chat() gibi
        $contents = [];
        foreach (($options['history'] ?? []) as $msg) {
            $role = ($msg['role'] ?? '') === 'user' ? 'user' : 'model';
            $content = trim((string) ($msg['content'] ?? ''));
            if ($content === '') continue;
            $contents[] = ['role' => $role, 'parts' => [['text' => $content]]];
        }

        $userParts = [];
        foreach ($fileRefs as $ref) {
            if (empty($ref['file_uri']) || empty($ref['mime_type'])) continue;
            $userParts[] = ['file_data' => ['mime_type' => $ref['mime_type'], 'file_uri' => $ref['file_uri']]];
        }
        $userParts[] = ['text' => $userMessage];
        $contents[] = ['role' => 'user', 'parts' => $userParts];

        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            'generationConfig' => [
                'temperature'     => (float) ($options['temperature'] ?? 0.3),
                'maxOutputTokens' => (int) ($options['max_output_tokens'] ?? 2048),
                // Thinking mode kapat — output budget'ını candidates'a ayır
                'thinkingConfig'  => ['thinkingBudget' => (int) ($options['thinking_budget'] ?? 0)],
            ],
        ];

        $url = "{$base}/models/{$model}:streamGenerateContent?alt=sse&key={$apiKey}";

        // Guzzle raw stream için manuel curl
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: text/event-stream'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_WRITEFUNCTION  => null, // aşağıda callback ekleyeceğiz
        ]);

        $buffer = '';
        $totalUsage = ['promptTokenCount' => 0, 'candidatesTokenCount' => 0];
        $finishReason = null;
        $queue = [];

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$buffer, &$queue, &$totalUsage, &$finishReason) {
            $buffer .= $data;
            // SSE: event ayracı \n\n
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                // Sadece "data: {...}" satırlarını al
                foreach (explode("\n", $event) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) continue;
                    $json = substr($line, 6);
                    if ($json === '[DONE]') { $queue[] = ['done' => true]; continue; }

                    $parsed = json_decode($json, true);
                    if (!is_array($parsed)) continue;

                    $text = '';
                    foreach (($parsed['candidates'][0]['content']['parts'] ?? []) as $part) {
                        if (isset($part['text'])) $text .= $part['text'];
                    }
                    if ($text !== '') $queue[] = ['text' => $text];

                    if (isset($parsed['candidates'][0]['finishReason'])) {
                        $finishReason = $parsed['candidates'][0]['finishReason'];
                    }
                    if (isset($parsed['usageMetadata'])) {
                        $totalUsage = $parsed['usageMetadata'];
                    }
                }
            }
            return strlen($data);
        });

        curl_exec($ch);
        $err = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Kalan buffer'ı da işle — Gemini bazen tek chunk'ta cevap döner
        // ve response \n\n ile bitmez. Bu durumda buffer dolu kalır ve text kaybolur.
        if (!empty($buffer)) {
            foreach (explode("\n", $buffer) as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data: ')) continue;
                $json = substr($line, 6);
                if ($json === '[DONE]') { $queue[] = ['done' => true]; continue; }

                $parsed = json_decode($json, true);
                if (!is_array($parsed)) continue;

                $text = '';
                foreach (($parsed['candidates'][0]['content']['parts'] ?? []) as $part) {
                    if (isset($part['text'])) $text .= $part['text'];
                }
                if ($text !== '') $queue[] = ['text' => $text];

                if (isset($parsed['candidates'][0]['finishReason'])) {
                    $finishReason = $parsed['candidates'][0]['finishReason'];
                }
                if (isset($parsed['usageMetadata'])) {
                    $totalUsage = $parsed['usageMetadata'];
                }
            }
        }

        foreach ($queue as $q) yield $q;

        if ($err) {
            yield ['error' => 'curl: ' . $err];
            return;
        }
        if ($status >= 400) {
            yield ['error' => 'http_' . $status];
            return;
        }

        yield [
            'done'          => true,
            'usage'         => [
                'input'  => (int) ($totalUsage['promptTokenCount'] ?? 0),
                'output' => (int) ($totalUsage['candidatesTokenCount'] ?? 0),
            ],
            'finish_reason' => $finishReason,
            'model'         => $model,
        ];
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function http(): PendingRequest
    {
        $timeout = (int) config('services.ai_labs.gemini.timeout', 60);
        return Http::timeout($timeout)->connectTimeout(10);
    }

    private function apiBase(): string
    {
        return (string) config('services.ai_labs.gemini.api_base', 'https://generativelanguage.googleapis.com/v1beta');
    }

    private function uploadBase(): string
    {
        return (string) config('services.ai_labs.gemini.upload_base', 'https://generativelanguage.googleapis.com/upload/v1beta');
    }

    private function resolveApiKey(?int $companyId = null): ?string
    {
        // DB override önceliği (her company kendi key'ini koyabilir)
        if ($companyId) {
            try {
                $fromDb = MarketingAdminSetting::where('company_id', $companyId)
                    ->where('setting_key', 'ai_labs_gemini_key')
                    ->value('setting_value');
                $fromDb = is_array($fromDb) ? ($fromDb['value'] ?? null) : $fromDb;
                if (!empty($fromDb) && is_string($fromDb)) {
                    return trim($fromDb);
                }
            } catch (\Throwable) {
                // .env fallback
            }
        }
        $envKey = (string) config('services.ai_labs.gemini.api_key', '');
        return $envKey !== '' ? $envKey : null;
    }

    private function resolveModel(?int $companyId = null): string
    {
        if ($companyId) {
            try {
                $fromDb = MarketingAdminSetting::where('company_id', $companyId)
                    ->where('setting_key', 'ai_labs_gemini_model')
                    ->value('setting_value');
                $fromDb = is_array($fromDb) ? ($fromDb['value'] ?? null) : $fromDb;
                if (!empty($fromDb) && is_string($fromDb)) {
                    return trim($fromDb);
                }
            } catch (\Throwable) {
                // fall through
            }
        }
        return (string) config('services.ai_labs.gemini.model', 'gemini-1.5-flash');
    }
}
