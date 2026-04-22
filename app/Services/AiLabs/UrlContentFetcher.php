<?php

namespace App\Services\AiLabs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Web URL'den HTML çek → gereksiz etiketleri temizle → okunabilir metin döndür.
 *
 * Kullanım: AI Labs knowledge source'larına URL eklendiğinde içeriği cache'lemek için.
 * Büyük sayfalar ~100K karakter ile sınırlandırılır (Gemini context maliyetini düşük tut).
 */
class UrlContentFetcher
{
    private const MAX_CHARS = 80000;
    private const USER_AGENT = 'MentorDE-AiLabs/1.0 (knowledge base sync)';

    /**
     * @return array{ok:bool, content?:string, title?:string, error?:string, bytes?:int}
     */
    public function fetch(string $url): array
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'invalid_url'];
        }

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'text/html,application/xhtml+xml'])
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            if (!$response->successful()) {
                return ['ok' => false, 'error' => 'http_' . $response->status()];
            }

            $html = $response->body();
            if (strlen($html) === 0) {
                return ['ok' => false, 'error' => 'empty_body'];
            }

            $title = $this->extractTitle($html);
            $text = $this->htmlToText($html);

            if ($text === '') {
                return ['ok' => false, 'error' => 'no_text_content'];
            }

            return [
                'ok'      => true,
                'title'   => $title,
                'content' => $text,
                'bytes'   => strlen($text),
            ];
        } catch (\Throwable $e) {
            Log::warning('AiLabs URL fetch failed', ['url' => $url, 'error' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    private function extractTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $t = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return mb_substr($t, 0, 200, 'UTF-8');
        }
        return '';
    }

    /**
     * HTML → okunabilir düz metin.
     * Script, style, nav, header, footer, iframe gibi gürültüyü kaldırır.
     */
    private function htmlToText(string $html): string
    {
        // Gürültü etiketleri tamamen sil (içerik dahil)
        $noisyTags = ['script', 'style', 'noscript', 'iframe', 'nav', 'header', 'footer', 'aside', 'form', 'svg'];
        foreach ($noisyTags as $tag) {
            $html = preg_replace("#<{$tag}\b[^>]*>.*?</{$tag}>#is", '', $html);
        }

        // Main content area varsa onu kullan (article, main tag'leri öncelikli)
        if (preg_match('/<(article|main)[^>]*>(.*?)<\/\1>/is', $html, $m)) {
            $html = $m[2];
        }

        // Block elementleri newline'la değiştir
        $html = preg_replace('/<(p|div|br|h[1-6]|li|tr|td|th|blockquote|pre)\b[^>]*>/i', "\n", $html);
        $html = preg_replace('/<\/(p|div|h[1-6]|li|tr|td|th|blockquote|pre)>/i', "\n", $html);

        // Kalan tüm tag'leri temizle
        $text = strip_tags($html);

        // HTML entities decode
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Whitespace normalize
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/^[ \t]+|[ \t]+$/m', '', $text);
        $text = trim($text);

        // Token büyümesini sınırla
        if (mb_strlen($text) > self::MAX_CHARS) {
            $text = mb_substr($text, 0, self::MAX_CHARS) . "\n\n[...içerik kısaltıldı...]";
        }

        return $text;
    }
}
