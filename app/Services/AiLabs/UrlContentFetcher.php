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

            // Charset dönüşümü — HTTP Content-Type veya <meta charset> ile tespit et,
            // UTF-8 değilse mb_convert_encoding ile çevir. Alman siteleri sık sık Windows-1252
            $contentType = (string) $response->header('Content-Type');
            $html = $this->ensureUtf8($html, $contentType);

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

    /**
     * HTML'in encoding'ini tespit edip UTF-8'e çevir.
     * Öncelik: HTTP Content-Type > HTML meta charset > mb_detect > Windows-1252 fallback.
     */
    private function ensureUtf8(string $html, string $contentType = ''): string
    {
        $charset = null;

        // 1. HTTP Content-Type header
        if (preg_match('/charset=["\']?([A-Za-z0-9_\-]+)/i', $contentType, $m)) {
            $charset = strtoupper($m[1]);
        }

        // 2. HTML <meta charset=...> veya <meta http-equiv="Content-Type">
        if (!$charset) {
            if (preg_match('/<meta[^>]+charset=["\']?([A-Za-z0-9_\-]+)/i', $html, $m)) {
                $charset = strtoupper($m[1]);
            }
        }

        // 3. mb_detect_encoding
        if (!$charset) {
            $detected = mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ISO-8859-9'], true);
            $charset = $detected ?: 'UTF-8';
        }

        $charset = strtoupper(trim($charset));

        // UTF-8 değilse dönüştür
        if ($charset !== 'UTF-8' && $charset !== 'UTF8') {
            // Alias'lar
            $charset = match ($charset) {
                'LATIN1', 'LATIN-1' => 'ISO-8859-1',
                'CP1252'            => 'Windows-1252',
                default             => $charset,
            };
            $converted = @mb_convert_encoding($html, 'UTF-8', $charset);
            if ($converted !== false && $converted !== '') {
                $html = $converted;
            }
        }

        // Son güvence: hala geçersiz UTF-8 byte'lar varsa zorla temizle
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8,Windows-1252,ISO-8859-1,ISO-8859-9');
        }

        return $html;
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
        // 1. Aşama: sadece GÜVENLİ gürültü etiketlerini sil (asla içerik barındırmayan)
        // nav/header/footer/aside/form gibi tag'ler bazı sitelerde (DAAD, vb.) ana
        // içeriği host ettiği için bu aşamada SİLİNMEZ — ana alan seçildikten sonra
        // o alan içinde temizlik yapılır.
        $safeNoise = ['script', 'style', 'noscript', 'iframe', 'svg'];
        foreach ($safeNoise as $tag) {
            $html = preg_replace("#<{$tag}\b[^>]*>.*?</{$tag}>#is", '', $html);
        }

        // 2. Aşama: Ana içerik alanı tespit
        $bodyHtml = $html;
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $bm)) {
            $bodyHtml = $bm[1];
        }

        // article/main'in en uzununu candidate olarak seç
        $candidate = null;
        if (preg_match_all('/<(article|main)[^>]*>(.*?)<\/\1>/is', $bodyHtml, $mm)) {
            foreach ($mm[2] as $inner) {
                if ($candidate === null || mb_strlen(strip_tags($inner)) > mb_strlen(strip_tags($candidate))) {
                    $candidate = $inner;
                }
            }
        }

        // Candidate yeterince büyükse (500+ char) kullan, değilse body fallback
        if ($candidate !== null && mb_strlen(strip_tags($candidate)) >= 500) {
            $html = $candidate;
        } else {
            $html = $bodyHtml;
            // Body fallback'te ek olarak navigation noise'ı temizle (ama tüm sayfa değil, sadece body içinden)
            // DAAD gibi içeriği <header> içine koyan siteler için dikkatli: sadece role/class tabanlı
            $html = preg_replace('#<nav\b[^>]*>.*?</nav>#is', '', $html);
            $html = preg_replace('#<footer\b[^>]*>.*?</footer>#is', '', $html);
            $html = preg_replace('#<form\b[^>]*>.*?</form>#is', '', $html);
            // Cookie banner / consent modal — aria-label veya role attribute bazlı
            $html = preg_replace('#<[^>]+role=["\']banner["\'][^>]*>.*?</[^>]+>#is', '', $html);
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

        // Son güvence: DB'ye yazmadan UTF-8 validation (MySQL utf8mb4 reddederse 500)
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8,Windows-1252,ISO-8859-1,ISO-8859-9');
        }

        return $text;
    }
}
