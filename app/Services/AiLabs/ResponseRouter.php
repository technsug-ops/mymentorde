<?php

namespace App\Services\AiLabs;

use App\Models\AiLabsResponseCache;
use App\Models\AiLabsSettings;
use App\Models\KnowledgeSource;
use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Log;

/**
 * AI Labs ResponseRouter — tek giriş noktası.
 *
 * Akış:
 *   1. KnowledgeBaseService::prepareContext(role) — kaynakları + file refs topla
 *   2. SystemPromptBuilder::build(role, mode) — 3-seviye prompt
 *   3. GeminiProvider::chat(system, user, fileRefs)
 *   4. SystemPromptBuilder::parseResponseMeta — mode + citation extract
 *   5. KnowledgeSource.citation_count++ (kullanılan kaynaklar)
 */
class ResponseRouter
{
    public function __construct(
        private GeminiProvider $gemini,
        private KnowledgeBaseService $kb,
        private SystemPromptBuilder $promptBuilder,
    ) {}

    /**
     * @return array{
     *   ok:bool,
     *   content?:string,
     *   mode?:string,           // source | external | refused
     *   source_ids?:array<int,int>,
     *   sources_meta?:array<int,array{id:int,title:string,type:string,url:?string}>,
     *   tokens_input?:int,
     *   tokens_output?:int,
     *   model?:string,
     *   error?:string
     * }
     */
    /**
     * @param array<string,mixed> $userContext Kullanıcı profili — kişiselleştirme için prompt'a ekleneceek
     * @param int|null $userId Cache key'e dahil — kişiselleştirilmiş cevaplar kullanıcılar arasında paylaşılmasın
     * @param array<int,array{role:string,content:string}> $history Son 5 mesaj (konuşma sürekliliği)
     */
    public function ask(int $companyId, string $role, string $userMessage, array $userContext = [], ?int $userId = null, array $history = []): array
    {
        // Settings (admin_instructions cache fingerprint'ine dahil)
        $settings = AiLabsSettings::forCompany($companyId);
        $adminInstructions = (string) ($settings->admin_instructions ?? '');
        $instructionsHash = $adminInstructions !== '' ? substr(hash('sha256', $adminInstructions), 0, 16) : 'none';

        // Cache: sadece history YOKSA kullan (history dolu = konuşma sürekliliği, cache yanlış yanıt verebilir)
        $useCache = empty($history);
        $profileHash = !empty($userContext) ? substr(hash('sha256', json_encode($userContext)), 0, 16) : 'anon';
        $userTag = $userId ? "u{$userId}" : 'uX';
        $fingerprint = $this->kb->sourcesFingerprint($companyId, $role) . '|' . $instructionsHash . '|' . $userTag . '|' . $profileHash;
        $cacheKey = AiLabsResponseCache::buildKey($companyId, $role, $userMessage, $fingerprint);

        $cached = $useCache
            ? AiLabsResponseCache::where('cache_key', $cacheKey)->where('expires_at', '>', now())->first()
            : null;

        if ($cached) {
            $cached->increment('hit_count');
            $cached->update(['last_hit_at' => now()]);
            $payload = json_decode((string) $cached->response_json, true);
            if (is_array($payload) && ($payload['ok'] ?? false)) {
                $payload['cached'] = true;
                return $payload;
            }
        }

        $mode = $settings->default_mode; // strict | hybrid

        // Brand name
        $brandName = (string) (MarketingAdminSetting::where('company_id', $companyId)
            ->where('setting_key', 'ai_labs_brand_name')
            ->value('setting_value') ?: 'MentorDE AI Labs');

        // Context
        $ctx = $this->kb->prepareContext($companyId, $role);

        // System prompt (AI'a mevcut source ID'leri bildir + manager talimatları + kullanıcı profili)
        $systemPrompt = $this->promptBuilder->build(
            $role,
            $mode,
            $brandName,
            $ctx['system_context'],
            $ctx['source_ids'] ?? [],
            $adminInstructions,
            $userContext
        );

        // Gemini chat (history dahil — konuşma sürekliliği)
        $result = $this->gemini->chat(
            $systemPrompt,
            $userMessage,
            $ctx['file_refs'],
            ['temperature' => 0.3, 'max_output_tokens' => 2048, 'history' => $history],
            $companyId
        );

        if (!($result['ok'] ?? false)) {
            Log::warning('AiLabs ResponseRouter chat failed', ['error' => $result['error'] ?? null]);
            return ['ok' => false, 'error' => $result['error'] ?? 'unknown'];
        }

        // Parse meta + clean citation
        $meta = $this->promptBuilder->parseResponseMeta($result['content'] ?? '');

        // Validation: mode=source ama source_ids boşsa → external'a downgrade
        if ($meta['mode'] === 'source' && empty($meta['source_ids'])) {
            $meta['mode'] = 'external';
        }
        // Validation: bildirilmeyen source ID gönderdiyse filtrele
        if (!empty($meta['source_ids']) && !empty($ctx['source_ids'])) {
            $valid = array_intersect($meta['source_ids'], $ctx['source_ids']);
            $meta['source_ids'] = array_values($valid);
            // Filtre sonucu boş kaldıysa mode=external'a düş
            if ($meta['mode'] === 'source' && empty($meta['source_ids'])) {
                $meta['mode'] = 'external';
            }
        }

        // Citation metadata (title + url)
        $sourcesMeta = [];
        if (!empty($meta['source_ids'])) {
            $sourcesMeta = KnowledgeSource::query()
                ->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereIn('id', $meta['source_ids'])
                ->get(['id', 'title', 'type', 'url'])
                ->map(fn ($s) => [
                    'id'    => (int) $s->id,
                    'title' => (string) $s->title,
                    'type'  => (string) $s->type,
                    'url'   => $s->url,
                ])
                ->all();

            // citation_count artır
            KnowledgeSource::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $meta['source_ids'])
                ->update([
                    'citation_count' => \DB::raw('citation_count + 1'),
                    'last_used_at'   => now(),
                ]);
        }

        $payload = [
            'ok'            => true,
            'content'       => $meta['content'],
            'mode'          => $meta['mode'],
            'source_ids'    => $meta['source_ids'],
            'sources_meta'  => $sourcesMeta,
            'tokens_input'  => $result['tokens_input'] ?? 0,
            'tokens_output' => $result['tokens_output'] ?? 0,
            'model'         => $result['model'] ?? null,
            'cached'        => false,
        ];

        // Cache'e yaz (24 saat) — 'refused' ve history-based cevapları cache'leme
        if ($useCache && $meta['mode'] !== 'refused') {
            try {
                AiLabsResponseCache::updateOrCreate(
                    ['cache_key' => $cacheKey],
                    [
                        'company_id'    => $companyId,
                        'role'          => $role,
                        'question'      => $userMessage,
                        'response_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        'hit_count'     => 1,
                        'last_hit_at'   => now(),
                        'expires_at'    => now()->addHours(24),
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('AiLabs cache write failed', ['e' => $e->getMessage()]);
            }
        }

        return $payload;
    }
}
