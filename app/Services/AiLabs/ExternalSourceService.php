<?php

namespace App\Services\AiLabs;

use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Labs — dış kaynak entegrasyonu.
 *
 * Desteklenen sağlayıcılar:
 *   - Wikipedia (ücretsiz, API key yok)
 *   - RSS feed reader (ücretsiz, SimpleXML)
 *   - Web Search (Serper.dev — API key gerektirir, $5-50/ay)
 *
 * Her fonksiyon standart response:
 *   ['ok' => bool, 'results'|'content'|... => ..., 'error' => ?]
 */
class ExternalSourceService
{
    public function __construct(private UrlContentFetcher $urlFetcher) {}

    // ── Wikipedia (ücretsiz) ────────────────────────────────────────

    /**
     * Wikipedia'da başlık araması.
     *
     * @return array{ok:bool, results?:array, error?:string}
     */
    public function searchWikipedia(string $query, string $lang = 'tr', int $limit = 8): array
    {
        if (trim($query) === '') {
            return ['ok' => false, 'error' => 'empty_query'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'MentorDE-AiLabs/1.0 (education consultancy knowledge base; contact@mentorde.com)'])
                ->get("https://{$lang}.wikipedia.org/w/api.php", [
                'action'   => 'query',
                'list'     => 'search',
                'srsearch' => $query,
                'srlimit'  => $limit,
                'format'   => 'json',
                'utf8'     => 1,
            ]);

            if (!$response->successful()) {
                return ['ok' => false, 'error' => 'http_' . $response->status()];
            }

            $items = $response->json('query.search') ?? [];
            $results = array_map(fn ($i) => [
                'title'   => (string) ($i['title'] ?? ''),
                'snippet' => strip_tags((string) ($i['snippet'] ?? '')),
                'pageid'  => (int) ($i['pageid'] ?? 0),
                'size'    => (int) ($i['size'] ?? 0),
                'url'     => "https://{$lang}.wikipedia.org/wiki/" . urlencode(str_replace(' ', '_', $i['title'] ?? '')),
            ], $items);

            return ['ok' => true, 'results' => $results];
        } catch (\Throwable $e) {
            Log::warning('AiLabs Wikipedia search failed', ['e' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    /**
     * Wikipedia makalesinin düz metin içeriğini çek (summary + body).
     *
     * @return array{ok:bool, title?:string, extract?:string, url?:string, bytes?:int, error?:string}
     */
    public function fetchWikipediaArticle(string $title, string $lang = 'tr'): array
    {
        try {
            // Extract action — plain text
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'MentorDE-AiLabs/1.0 (education consultancy knowledge base; contact@mentorde.com)'])
                ->get("https://{$lang}.wikipedia.org/w/api.php", [
                'action'        => 'query',
                'prop'          => 'extracts|info',
                'titles'        => $title,
                'explaintext'   => 1,
                'exsectionformat' => 'plain',
                'inprop'        => 'url',
                'redirects'     => 1,
                'format'        => 'json',
                'utf8'          => 1,
            ]);

            if (!$response->successful()) {
                return ['ok' => false, 'error' => 'http_' . $response->status()];
            }

            $pages = $response->json('query.pages') ?? [];
            $page = reset($pages);
            if (!$page || !empty($page['missing'])) {
                return ['ok' => false, 'error' => 'page_not_found'];
            }

            $extract = (string) ($page['extract'] ?? '');
            if (mb_strlen($extract) < 50) {
                return ['ok' => false, 'error' => 'content_too_short'];
            }

            // Max 80K char
            if (mb_strlen($extract) > 80000) {
                $extract = mb_substr($extract, 0, 80000) . "\n\n[...içerik kısaltıldı...]";
            }

            return [
                'ok'      => true,
                'title'   => (string) ($page['title'] ?? $title),
                'extract' => $extract,
                'url'     => (string) ($page['fullurl'] ?? "https://{$lang}.wikipedia.org/wiki/" . urlencode(str_replace(' ', '_', $title))),
                'bytes'   => strlen($extract),
            ];
        } catch (\Throwable $e) {
            Log::warning('AiLabs Wikipedia fetch failed', ['e' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    // ── RSS Feed Reader (ücretsiz) ──────────────────────────────────

    /**
     * RSS feed URL'inden item listesi çıkar.
     *
     * @return array{ok:bool, feed_title?:string, items?:array, error?:string}
     */
    public function parseRss(string $feedUrl, int $limit = 20): array
    {
        if (!filter_var($feedUrl, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'invalid_url'];
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'MentorDE-AiLabs/1.0', 'Accept' => 'application/rss+xml, application/xml'])
                ->get($feedUrl);

            if (!$response->successful()) {
                return ['ok' => false, 'error' => 'http_' . $response->status()];
            }

            $xml = @simplexml_load_string($response->body());
            if ($xml === false) {
                return ['ok' => false, 'error' => 'invalid_xml'];
            }

            $items = [];
            $feedTitle = '';

            // RSS 2.0
            if (isset($xml->channel)) {
                $feedTitle = (string) ($xml->channel->title ?? '');
                foreach ($xml->channel->item as $item) {
                    if (count($items) >= $limit) break;
                    $items[] = [
                        'title'       => trim((string) $item->title),
                        'link'        => trim((string) $item->link),
                        'description' => strip_tags(trim((string) $item->description)),
                        'published'   => trim((string) ($item->pubDate ?? '')),
                    ];
                }
            }
            // Atom
            elseif (isset($xml->entry)) {
                $feedTitle = (string) ($xml->title ?? '');
                foreach ($xml->entry as $entry) {
                    if (count($items) >= $limit) break;
                    $link = '';
                    foreach ($entry->link as $l) {
                        $attrs = $l->attributes();
                        if ((string) ($attrs['rel'] ?? 'alternate') === 'alternate' || !isset($attrs['rel'])) {
                            $link = (string) $attrs['href'];
                            break;
                        }
                    }
                    $items[] = [
                        'title'       => trim((string) $entry->title),
                        'link'        => $link,
                        'description' => strip_tags(trim((string) ($entry->summary ?? $entry->content ?? ''))),
                        'published'   => trim((string) ($entry->published ?? $entry->updated ?? '')),
                    ];
                }
            }

            if (empty($items)) {
                return ['ok' => false, 'error' => 'no_items'];
            }

            return [
                'ok'         => true,
                'feed_title' => $feedTitle,
                'items'      => $items,
            ];
        } catch (\Throwable $e) {
            Log::warning('AiLabs RSS parse failed', ['url' => $feedUrl, 'e' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    // ── Web Search (Serper.dev — API key gerektirir) ────────────────

    public function isWebSearchConfigured(?int $companyId = null): bool
    {
        return !empty($this->resolveSerperKey($companyId));
    }

    /**
     * Serper.dev üzerinden Google arama sonuçları.
     *
     * @return array{ok:bool, results?:array, error?:string}
     */
    public function searchWeb(string $query, ?int $companyId = null, int $limit = 10, string $lang = 'tr'): array
    {
        $apiKey = $this->resolveSerperKey($companyId);
        if (!$apiKey) {
            return ['ok' => false, 'error' => 'no_api_key'];
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'X-API-KEY'    => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://google.serper.dev/search', [
                    'q'    => $query,
                    'hl'   => $lang,
                    'gl'   => $lang === 'tr' ? 'tr' : 'us',
                    'num'  => min(20, $limit),
                ]);

            if (!$response->successful()) {
                return ['ok' => false, 'error' => 'http_' . $response->status() . ': ' . substr((string) $response->body(), 0, 200)];
            }

            $organic = $response->json('organic') ?? [];
            $results = array_map(fn ($r) => [
                'title'   => (string) ($r['title'] ?? ''),
                'link'    => (string) ($r['link'] ?? ''),
                'snippet' => (string) ($r['snippet'] ?? ''),
                'position'=> (int) ($r['position'] ?? 0),
            ], array_slice($organic, 0, $limit));

            return ['ok' => true, 'results' => $results];
        } catch (\Throwable $e) {
            Log::warning('AiLabs web search failed', ['e' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    private function resolveSerperKey(?int $companyId = null): ?string
    {
        if ($companyId) {
            try {
                $val = MarketingAdminSetting::where('company_id', $companyId)
                    ->where('setting_key', 'ai_labs_serper_key')
                    ->value('setting_value');
                if (!empty($val) && is_string($val)) {
                    return trim($val);
                }
            } catch (\Throwable) {}
        }
        $env = (string) config('services.ai_labs.serper.api_key', '');
        return $env !== '' ? $env : null;
    }

    // ── Import helper: seçilen external kaynağı KnowledgeSource'a çevir ──

    /**
     * Import edilen external kaynak için content_markdown hazırlar.
     * URL verilirse fetch eder, Wikipedia için extract direkt kullanılır.
     *
     * @return array{ok:bool, content?:string, title?:string, url?:string, error?:string}
     */
    public function prepareForImport(string $type, array $data): array
    {
        // $type: 'wikipedia' | 'rss' | 'web'
        if ($type === 'wikipedia') {
            $title = (string) ($data['title'] ?? '');
            $lang  = (string) ($data['lang'] ?? 'tr');
            return $this->fetchWikipediaArticle($title, $lang);
        }

        if ($type === 'web' || $type === 'rss') {
            $url = (string) ($data['url'] ?? '');
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return ['ok' => false, 'error' => 'invalid_url'];
            }
            $result = $this->urlFetcher->fetch($url);
            if (!($result['ok'] ?? false)) {
                return ['ok' => false, 'error' => $result['error'] ?? 'fetch_failed'];
            }
            return [
                'ok'      => true,
                'title'   => (string) ($data['title'] ?? ($result['title'] ?? $url)),
                'extract' => (string) ($result['content'] ?? ''),
                'url'     => $url,
                'bytes'   => $result['bytes'] ?? 0,
            ];
        }

        return ['ok' => false, 'error' => 'unknown_type'];
    }
}
