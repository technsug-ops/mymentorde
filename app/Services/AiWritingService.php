<?php

namespace App\Services;

use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiWritingService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.ai_writer.enabled', false);
    }

    public function isConfigured(): bool
    {
        return trim((string) config('services.ai_writer.api_key', '')) !== '';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled() && $this->isConfigured();
    }

    /**
     * @param  array<string,mixed>  $context
     * @return array{ok:bool,content:?string,error:?string,provider:string,model:string}
     */
    public function improveGermanDocument(string $documentType, string $draftContent, array $context = []): array
    {
        $provider = (string) config('services.ai_writer.provider', 'openai_compatible');
        $model = (string) config('services.ai_writer.model', 'gpt-4o-mini');

        if (!$this->isEnabled()) {
            return ['ok' => false, 'content' => null, 'error' => 'ai_writer_disabled', 'provider' => $provider, 'model' => $model];
        }
        if (!$this->isConfigured()) {
            return ['ok' => false, 'content' => null, 'error' => 'ai_writer_not_configured', 'provider' => $provider, 'model' => $model];
        }

        $systemPrompt = $this->systemPromptFor($documentType);
        $userPrompt = $this->buildUserPrompt($documentType, $draftContent, $context);

        try {
            $baseUrl = (string) config('services.ai_writer.base_url', 'https://api.openai.com/v1');
            $timeout = (int) config('services.ai_writer.timeout', 30);
            $apiKey = (string) config('services.ai_writer.api_key', '');

            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->acceptJson()
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.3,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('AI writer request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'provider' => $provider,
                    'model' => $model,
                ]);

                return [
                    'ok' => false,
                    'content' => null,
                    'error' => 'ai_http_' . $response->status(),
                    'provider' => $provider,
                    'model' => $model,
                ];
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            if ($content === '') {
                return ['ok' => false, 'content' => null, 'error' => 'ai_empty_response', 'provider' => $provider, 'model' => $model];
            }

            return ['ok' => true, 'content' => $content, 'error' => null, 'provider' => $provider, 'model' => $model];
        } catch (\Throwable $e) {
            Log::warning('AI writer exception', [
                'message' => $e->getMessage(),
                'provider' => $provider,
                'model' => $model,
            ]);

            return ['ok' => false, 'content' => null, 'error' => 'ai_exception', 'provider' => $provider, 'model' => $model];
        }
    }

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
            'reference' => 'ai_writer_prompt_reference',
            default => '',
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
                'message' => $e->getMessage(),
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
            'reference' => 'Improve this German recommendation letter draft based on teacher observations.',
            default => 'Improve this German document draft.',
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
