<?php

namespace App\Services;

use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiWritingService
{
    public const PROVIDERS = ['openai', 'anthropic', 'gemini', 'openrouter'];

    /**
     * DB'den setting oku, yoksa default'a düş. Tablo yoksa sessizce default.
     */
    private function setting(string $key, mixed $default = null): mixed
    {
        try {
            if (Schema::hasTable('marketing_admin_settings')) {
                $dbValue = MarketingAdminSetting::getValue($key, null);
                if ($dbValue !== null && $dbValue !== '') {
                    return $dbValue;
                }
            }
        } catch (\Throwable $e) {
            // sessizce default'a düş
        }
        return $default;
    }

    public function currentProvider(): string
    {
        $p = (string) $this->setting('ai_writer_provider', config('services.ai_writer.provider', 'openai'));
        return in_array($p, self::PROVIDERS, true) ? $p : 'openai';
    }

    /**
     * Her provider için endpoint/auth/default model bilgisi.
     *
     * @return array{base_url:string,default_model:string,format:string}
     */
    public function providerConfig(string $provider): array
    {
        return match ($provider) {
            'anthropic' => [
                'base_url'      => 'https://api.anthropic.com/v1',
                'default_model' => 'claude-haiku-4-5-20251001',
                'format'        => 'anthropic_messages',
            ],
            'gemini' => [
                'base_url'      => 'https://generativelanguage.googleapis.com/v1beta',
                'default_model' => 'gemini-2.5-flash',
                'format'        => 'gemini_generate',
            ],
            'openrouter' => [
                'base_url'      => 'https://openrouter.ai/api/v1',
                'default_model' => 'openai/gpt-4o-mini',
                'format'        => 'openai_chat',
            ],
            default => [
                'base_url'      => (string) config('services.ai_writer.base_url', 'https://api.openai.com/v1'),
                'default_model' => 'gpt-4o-mini',
                'format'        => 'openai_chat',
            ],
        };
    }

    /**
     * Provider-specific DB key: ai_writer_openai_key / ai_writer_anthropic_key / ...
     * Legacy: 'ai_writer_api_key' (eski tek-key kurulumu) openai'ye fallback eder.
     */
    public function apiKeyFor(string $provider): string
    {
        $dbKey = (string) $this->setting("ai_writer_{$provider}_key", '');
        if ($dbKey !== '') {
            return $dbKey;
        }
        if ($provider === 'openai') {
            $legacy = (string) $this->setting('ai_writer_api_key', config('services.ai_writer.api_key', ''));
            if ($legacy !== '') {
                return $legacy;
            }
        }
        return '';
    }

    public function effectiveApiKey(): string
    {
        return $this->apiKeyFor($this->currentProvider());
    }

    public function effectiveModel(): string
    {
        $model = (string) $this->setting('ai_writer_model', '');
        if ($model !== '') {
            return $model;
        }
        return $this->providerConfig($this->currentProvider())['default_model'];
    }

    public function isEnabled(): bool
    {
        $dbFlag = $this->setting('ai_writer_enabled', null);
        if ($dbFlag !== null) {
            return filter_var($dbFlag, FILTER_VALIDATE_BOOLEAN);
        }
        return (bool) config('services.ai_writer.enabled', false);
    }

    public function isConfigured(): bool
    {
        return trim($this->effectiveApiKey()) !== '';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled() && $this->isConfigured();
    }

    /**
     * @param  array<string,mixed>  $context
     * @return array{ok:bool,content:?string,error:?string,provider:string,model:string,tokens_used?:int}
     */
    public function improveGermanDocument(string $documentType, string $draftContent, array $context = []): array
    {
        $systemPrompt = $this->systemPromptFor($documentType);
        $userPrompt   = $this->buildUserPrompt($documentType, $draftContent, $context);

        return $this->chat($systemPrompt, $userPrompt, 2000);
    }

    /**
     * Generic chat: herhangi bir system+user prompt için aktif provider'ı kullanır.
     * Guest AI Asistanı, document builder, vs. hepsi buradan geçer.
     *
     * @return array{ok:bool,content:?string,error:?string,provider:string,model:string,tokens_used?:int}
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 1024): array
    {
        $provider = $this->currentProvider();
        $model    = $this->effectiveModel();

        if (!$this->isEnabled()) {
            return ['ok' => false, 'content' => null, 'error' => 'ai_writer_disabled', 'provider' => $provider, 'model' => $model];
        }
        if (!$this->isConfigured()) {
            return ['ok' => false, 'content' => null, 'error' => 'ai_writer_not_configured', 'provider' => $provider, 'model' => $model];
        }

        $apiKey  = $this->effectiveApiKey();
        $cfg     = $this->providerConfig($provider);
        $timeout = (int) config('services.ai_writer.timeout', 30);

        try {
            $result = match ($cfg['format']) {
                'anthropic_messages' => $this->callAnthropic($cfg['base_url'], $apiKey, $model, $systemPrompt, $userMessage, $timeout, $maxTokens),
                'gemini_generate'    => $this->callGemini($cfg['base_url'], $apiKey, $model, $systemPrompt, $userMessage, $timeout, $maxTokens),
                default              => $this->callOpenAiCompat($cfg['base_url'], $apiKey, $model, $systemPrompt, $userMessage, $timeout, $maxTokens),
            };
        } catch (\Throwable $e) {
            Log::warning('AI writer exception', [
                'provider' => $provider,
                'model'    => $model,
                'message'  => $e->getMessage(),
            ]);
            return ['ok' => false, 'content' => null, 'error' => 'ai_exception', 'provider' => $provider, 'model' => $model];
        }

        if (!$result['ok']) {
            Log::warning('AI writer request failed', [
                'provider' => $provider,
                'model'    => $model,
                'status'   => $result['status'] ?? 0,
                'body'     => $result['body'] ?? '',
            ]);
            return ['ok' => false, 'content' => null, 'error' => $result['error'] ?? 'ai_unknown', 'provider' => $provider, 'model' => $model];
        }

        $content = trim((string) ($result['content'] ?? ''));
        if ($content === '') {
            return ['ok' => false, 'content' => null, 'error' => 'ai_empty_response', 'provider' => $provider, 'model' => $model];
        }

        return [
            'ok'          => true,
            'content'     => $content,
            'error'       => null,
            'provider'    => $provider,
            'model'       => $model,
            'tokens_used' => (int) ($result['tokens_used'] ?? 0),
        ];
    }

    // ─── Provider call implementations ────────────────────────────────────

    /**
     * OpenAI chat/completions format — hem OpenAI hem OpenRouter için aynı.
     * @return array{ok:bool,content?:string,error?:string,status?:int,body?:string,tokens_used?:int}
     */
    private function callOpenAiCompat(string $baseUrl, string $apiKey, string $model, string $system, string $user, int $timeout, int $maxTokens): array
    {
        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                'model'                 => $model,
                'temperature'           => 0.3,
                'max_completion_tokens' => $maxTokens,
                'messages'              => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
            ]);

        if (!$response->successful()) {
            return ['ok' => false, 'error' => 'ai_http_' . $response->status(), 'status' => $response->status(), 'body' => (string) $response->body()];
        }

        $json = $response->json();
        return [
            'ok'          => true,
            'content'     => (string) data_get($json, 'choices.0.message.content', ''),
            'tokens_used' => (int) data_get($json, 'usage.total_tokens', 0),
        ];
    }

    /**
     * Anthropic messages API — farklı auth + body format.
     * Docs: https://docs.anthropic.com/en/api/messages
     */
    private function callAnthropic(string $baseUrl, string $apiKey, string $model, string $system, string $user, int $timeout, int $maxTokens): array
    {
        $response = Http::timeout($timeout)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->acceptJson()
            ->post(rtrim($baseUrl, '/') . '/messages', [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'system'     => $system,
                'messages'   => [
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (!$response->successful()) {
            return ['ok' => false, 'error' => 'ai_http_' . $response->status(), 'status' => $response->status(), 'body' => (string) $response->body()];
        }

        $json = $response->json();
        $inTok  = (int) data_get($json, 'usage.input_tokens', 0);
        $outTok = (int) data_get($json, 'usage.output_tokens', 0);
        return [
            'ok'          => true,
            'content'     => (string) data_get($json, 'content.0.text', ''),
            'tokens_used' => $inTok + $outTok,
        ];
    }

    /**
     * Google Gemini generateContent API.
     * Docs: https://ai.google.dev/api/generate-content
     */
    private function callGemini(string $baseUrl, string $apiKey, string $model, string $system, string $user, int $timeout, int $maxTokens): array
    {
        $url = rtrim($baseUrl, '/') . '/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->post($url, [
                'systemInstruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $user]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.3,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);

        if (!$response->successful()) {
            return ['ok' => false, 'error' => 'ai_http_' . $response->status(), 'status' => $response->status(), 'body' => (string) $response->body()];
        }

        $json = $response->json();
        return [
            'ok'          => true,
            'content'     => (string) data_get($json, 'candidates.0.content.parts.0.text', ''),
            'tokens_used' => (int) data_get($json, 'usageMetadata.totalTokenCount', 0),
        ];
    }

    // ─── Prompt helpers (değişmedi) ───────────────────────────────────────

    private function systemPromptFor(string $documentType): string
    {
        $override = $this->systemPromptOverride($documentType);
        if ($override !== '') {
            return $override;
        }

        if ($documentType === 'motivation') {
            return 'You are a professional German university application writing assistant. Rewrite and improve the given motivation letter draft into polished, natural, formal German. Keep facts unchanged. Do not invent achievements. Output only the final German letter text.';
        }

        if ($documentType === 'reference') {
            return 'You are a professional academic recommendation letter assistant. Convert the provided observations and draft into a formal German Empfehlungsschreiben. Keep facts unchanged, avoid exaggeration, preserve professional tone. Output only the final German recommendation letter text.';
        }

        return 'You are a German document writing assistant. Improve clarity, grammar and structure without changing facts. Output only final German text.';
    }

    private function systemPromptOverride(string $documentType): string
    {
        $key = match ($documentType) {
            'motivation' => 'ai_writer_prompt_motivation',
            'reference'  => 'ai_writer_prompt_reference',
            default      => '',
        };

        if ($key === '') {
            return '';
        }

        try {
            if (!Schema::hasTable('marketing_admin_settings')) {
                return '';
            }

            $row = MarketingAdminSetting::query()
                ->where('setting_key', $key)
                ->first(['setting_value']);

            return trim((string) data_get($row, 'setting_value.value', ''));
        } catch (\Throwable $e) {
            Log::warning('AI writer prompt override read failed', [
                'document_type' => $documentType,
                'message'       => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function buildUserPrompt(string $documentType, string $draftContent, array $context): string
    {
        $ctxLines = [];
        foreach ($context as $k => $v) {
            if (is_scalar($v) && trim((string) $v) !== '') {
                $ctxLines[] = $k . ': ' . trim((string) $v);
            }
        }

        $heading = match ($documentType) {
            'motivation' => 'Improve this German motivation letter draft for a student application.',
            'reference'  => 'Improve this German recommendation letter draft based on teacher observations.',
            default      => 'Improve this German document draft.',
        };

        return $heading . "\n\n"
            . ($ctxLines ? "Context:\n- " . implode("\n- ", $ctxLines) . "\n\n" : '')
            . "Constraints:\n"
            . "- Keep all factual details unchanged\n"
            . "- Do not add fictional claims\n"
            . "- Maintain formal tone\n"
            . "- Return plain text only\n\n"
            . "Draft:\n" . $draftContent;
    }
}
