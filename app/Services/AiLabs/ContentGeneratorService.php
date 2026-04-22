<?php

namespace App\Services\AiLabs;

use App\Models\AiLabsContentDraft;
use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Log;

/**
 * AI Labs içerik üretici servisi.
 *
 * Her template için:
 *   1. Variables'tan content-specific system + user prompt kur
 *   2. Knowledge base içeriğini (uses_knowledge_base=true ise) optional context olarak ekle
 *   3. Gemini'ye gönder (max_tokens template'e göre)
 *   4. Output format'a göre parse (markdown / faq_json / blog_html)
 *   5. Draft olarak kaydet
 */
class ContentGeneratorService
{
    public function __construct(
        private GeminiProvider $gemini,
        private KnowledgeBaseService $kb,
    ) {}

    /**
     * @param array<string,mixed> $variables Template field'larının değerleri
     *
     * @return array{ok:bool, draft?:AiLabsContentDraft, error?:string}
     */
    public function generate(int $companyId, int $userId, string $templateCode, string $title, array $variables): array
    {
        $template = ContentTemplates::find($templateCode);
        if (!$template) {
            return ['ok' => false, 'error' => 'unknown_template'];
        }

        // System prompt + user prompt
        [$systemPrompt, $userPrompt] = $this->buildPrompts($templateCode, $template, $variables, $companyId);

        // Knowledge base context (optional)
        $fileRefs = [];
        if ($template['uses_knowledge_base'] ?? false) {
            $ctx = $this->kb->prepareContext($companyId, 'manager');
            if (!empty($ctx['system_context'])) {
                $systemPrompt .= "\n\n## Bilgi Havuzu (kaynak olarak kullan, gerekirse):\n" . $ctx['system_context'];
            }
            $fileRefs = $ctx['file_refs'] ?? [];
        }

        $chatOptions = [
            'temperature' => $this->temperatureFor($templateCode),
            'max_output_tokens' => (int) ($template['max_tokens'] ?? 2500),
        ];
        // FAQ template JSON döner → Gemini'ye structured output zorunluluğu
        if (($template['output_format'] ?? '') === 'faq_json') {
            $chatOptions['response_mime_type'] = 'application/json';
        }

        $result = $this->gemini->chat(
            $systemPrompt,
            $userPrompt,
            $fileRefs,
            $chatOptions,
            $companyId
        );

        if (!($result['ok'] ?? false)) {
            Log::warning('AiLabs content generation failed', ['template' => $templateCode, 'error' => $result['error'] ?? null]);
            return ['ok' => false, 'error' => $result['error'] ?? 'unknown'];
        }

        $content = (string) ($result['content'] ?? '');
        $metadata = $this->extractMetadata($templateCode, $template, $content);

        $draft = AiLabsContentDraft::create([
            'company_id'     => $companyId,
            'user_id'        => $userId,
            'template_code'  => $templateCode,
            'title'          => $title,
            'variables'      => $variables,
            'content'        => $content,
            'metadata'       => $metadata,
            'status'         => 'draft',
            'tokens_input'   => (int) ($result['tokens_input'] ?? 0),
            'tokens_output'  => (int) ($result['tokens_output'] ?? 0),
            'provider'       => 'gemini',
            'model'          => $result['model'] ?? null,
        ]);

        return ['ok' => true, 'draft' => $draft];
    }

    /**
     * @return array{0:string, 1:string}  [system_prompt, user_prompt]
     */
    private function buildPrompts(string $code, array $template, array $vars, int $companyId): array
    {
        $brandName = (string) (MarketingAdminSetting::where('company_id', $companyId)
            ->where('setting_key', 'ai_labs_brand_name')
            ->value('setting_value') ?: 'MentorDE AI Labs');

        $varBlock = $this->formatVars($vars);
        $lang = $this->resolveLang($vars);

        $system = match ($code) {
            'motivation_letter' => <<<SYS
Sen profesyonel bir motivasyon mektubu yazım uzmanısın. {$brandName} bilgi havuzunda Almanya eğitim süreçleri, ApS, vize hakkında kaynaklar var; gerekirse kullan.

ÇIKTI KURALLARI:
- Format: Markdown
- Dil: {$lang}
- 350-450 kelime, formal ama sıcak
- 4-5 paragraf: (1) tanıtım + program motivasyonu, (2) akademik geçmiş, (3) ilgili deneyim, (4) kariyer hedefi + uni neden, (5) kapanış
- Klişe ifadeler yok ("since childhood I dreamed..."), öğrencinin özgül özelliklerine odaklan
- Tarih, adres, başlık/selamlama dahil et
- **HİÇBİR açıklama veya yorum ekleme** — sadece mektubu döndür
SYS,

            'sperrkonto' => <<<SYS
Sen Almanya Sperrkonto (bloke hesap) açılışında öğrencilere yardımcı olan bir danışmansın.

ÇIKTI KURALLARI:
- Format: Markdown (başvuru e-postası/formu tarzı)
- Dil: Türkçe
- Seçilen bankaya özel yönerge + gerekli belgeler listesi + adım adım süreç
- Başvuru için öğrencinin kullanacağı hazır metin (e-posta gövdesi)
- Resmi ama anlaşılır
SYS,

            'visa_call' => <<<SYS
Sen Alman konsolosluğu öğrenci vizesi başvurusunda kullanılmak üzere veli çağrı/destek mektubu yazım uzmanısın.

ÇIKTI KURALLARI:
- Format: Markdown
- Dil: {$lang}
- Resmi mektup formatı: veli adres/tarih, selamlama, metin, imza
- İçerik: velinin öğrencinin tüm masraflarını karşılayacağı taahhüdü, miktar + süre
- Almanca ise noter onaylı örneklerdeki dil ve üslubu kullan
- Konsolosluk standardına uygun
SYS,

            'uni_recommendation' => <<<SYS
Sen Almanya üniversite sistemi uzmanısın. Öğrencinin profiline göre 3-5 uygun üniversite önerip karşılaştırmalı rapor üretirsin.

ÇIKTI KURALLARI:
- Format: Markdown
- Dil: Türkçe
- Her üniversite için: kısa tanıtım, bu alandaki gücü, kabul şartları (GPA/dil), tahmini yıllık maliyet, artı/eksi
- Sonda tablo: Uni | Kabul Zorluğu | Dil Şartı | Tahmini Maliyet | Şehir
- Bitişte "Önerim" bölümü: profile en uygun 1 uni, gerekçeli
SYS,

            'blog_post' => <<<SYS
Sen yurt dışı eğitim danışmanlığı alanında SEO-uyumlu blog yazarısın.

ÇIKTI KURALLARI:
- Format: Markdown + başta metadata bloğu
- Dil: Türkçe
- Yapı:
  1. En üstte YAML-benzeri metadata bloğu (`---` içinde): title, slug, meta_description (150-160 karakter), keywords, word_count
  2. H1 (# Başlık)
  3. Lead paragraph (hook)
  4. 3-5 adet H2 bölüm (## Alt Başlık), her birinin altında 2-3 paragraf
  5. Liste, alıntı, örnek kullan (okunabilirlik)
  6. Son H2: "Sonuç" veya "Özet" — eyleme çağrı (CTA) ile
- Anahtar kelimeleri doğal kullan, keyword stuffing yapma
- Verilen kelime sayısı hedefine uy (±%10)
- {$brandName} bilgi havuzundaki kaynaklardan faktual bilgi çek
SYS,

            'faq' => <<<SYS
Sen bilgi havuzundaki kaynaklardan SSS (sık sorulan sorular) çıkartan bir editörsün.

ÇIKTI KURALLARI:
- Format: SADECE JSON (başka metin yok, ```json``` bloğu da yok)
- JSON şema:
  {
    "faqs": [
      {"question": "...", "answer": "...", "category": "..."},
      ...
    ]
  }
- Her cevap 2-4 cümle, net ve pratik
- Sorular doğal dilde, kullanıcının soracağı gibi
- Kategoriler: Başvuru, Vize, Finans, Konaklama, Dil, Genel
- Bilgi havuzu dışına çıkma — kaynaklarda olmayan soru üretme
- **SADECE JSON döndür**, başında/sonunda açıklama yazma
SYS,

            'custom' => <<<SYS
Sen {$brandName}'in içerik üreticisisin. Kullanıcının talimatına göre çıktı üret.

ÇIKTI KURALLARI:
- Format: Markdown
- Dil: {$lang}
- Talimata sıkı sıkıya uy
- Kaynak havuzundan faydalanabilirsin
- Gereksiz giriş/kapanış ekleme, direkt içeriği döndür
SYS,

            default => "Sen bir içerik üreticisin. Markdown formatında, {$lang} dilinde içerik üret.",
        };

        $user = "Aşağıdaki girdilerle içeriği oluştur:\n\n{$varBlock}";

        // Blog'da kelime hedefi özellikle vurgu
        if ($code === 'blog_post' && !empty($vars['word_count'])) {
            $user .= "\n\n⚠️ Kelime hedefi: ~{$vars['word_count']} kelime.";
        }

        return [$system, $user];
    }

    private function formatVars(array $vars): string
    {
        $lines = [];
        foreach ($vars as $key => $value) {
            if (is_array($value)) $value = implode(', ', $value);
            $value = trim((string) $value);
            if ($value === '') continue;
            $lines[] = "- **{$key}:** {$value}";
        }
        return implode("\n", $lines);
    }

    private function resolveLang(array $vars): string
    {
        $lang = (string) ($vars['language'] ?? 'tr');
        return match ($lang) {
            'en' => 'English',
            'de' => 'Deutsch',
            default => 'Türkçe',
        };
    }

    private function temperatureFor(string $code): float
    {
        return match ($code) {
            'motivation_letter' => 0.7,  // yaratıcı ama tutarlı
            'blog_post'         => 0.75, // daha akıcı
            'custom'            => 0.6,
            'faq'               => 0.3,  // deterministik
            'sperrkonto'        => 0.2,  // resmi, kesin
            'visa_call'         => 0.3,
            'uni_recommendation'=> 0.5,
            default             => 0.4,
        };
    }

    /**
     * Blog için SEO anahtar kelime önerisi üret.
     * Gemini'ye 3 kategori için yapılandırılmış JSON ister.
     *
     * @return array{ok:bool, primary?:array, secondary?:array, long_tail?:array, meta_description?:string, error?:string}
     */
    public function suggestKeywords(int $companyId, string $topic, string $audience = 'prospective_students', string $language = 'tr'): array
    {
        $topic = trim($topic);
        if (mb_strlen($topic) < 3) {
            return ['ok' => false, 'error' => 'topic_too_short'];
        }

        $audienceLabel = match ($audience) {
            'prospective_students' => 'Aday Öğrenciler (Türkiye\'den Almanya\'ya gitmek isteyen liseli/üniversiteli)',
            'parents'              => 'Veliler (çocukları Almanya\'da okuyacak/okuyan)',
            'current_students'     => 'Mevcut Öğrenciler (Almanya\'da okuyan Türk öğrenciler)',
            default                => 'Genel kitle',
        };

        $langLabel = match ($language) {
            'en' => 'İngilizce (Google EN)',
            'de' => 'Almanca (Google DE)',
            default => 'Türkçe (Google TR)',
        };

        $systemPrompt = <<<SYS
Sen yurt dışı eğitim danışmanlığı SEO uzmanısın. Verilen konu için Google anahtar kelime önerileri üret.

ÇIKTI: SADECE geçerli JSON. Başında/sonunda metin olmamalı.

JSON ŞEMASI (tam uyum zorunlu):
{
  "primary": [{"keyword": "kelime", "intent": "informational"}, ...],
  "secondary": [{"keyword": "kelime", "intent": "informational"}, ...],
  "long_tail": [{"keyword": "daha uzun ifade", "intent": "transactional"}, ...],
  "meta_description": "155-160 karakterlik meta desc"
}

KURALLAR:
- primary: 3 adet (yüksek volume, rekabetli)
- secondary: 4 adet (orta volume)
- long_tail: 5 adet (3-5 kelimelik spesifik ifadeler, niche)
- intent değerleri: informational | transactional | navigational
- reason alanı yazma (sadece keyword + intent)
- Hedef arama motoru: {$langLabel}
- Hedef kitle: {$audienceLabel}
- Kullanıcının gerçekten arayacağı terimler, clickbait değil
SYS;

        $userPrompt = "Konu: {$topic}\n\nSEO anahtar kelime seti JSON olarak döndür.";

        $result = $this->gemini->chat(
            $systemPrompt,
            $userPrompt,
            [],
            [
                'temperature' => 0.4,
                'max_output_tokens' => 3000,
                'response_mime_type' => 'application/json',
            ],
            $companyId
        );

        if (!($result['ok'] ?? false)) {
            return ['ok' => false, 'error' => $result['error'] ?? 'gemini_failed'];
        }

        $content = trim((string) ($result['content'] ?? ''));
        $data = $this->parseJsonResponse($content);

        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'invalid_json_response', 'raw' => mb_substr($content, 0, 500)];
        }

        return [
            'ok'               => true,
            'primary'          => array_map([$this, 'normalizeKeyword'], $data['primary'] ?? []),
            'secondary'        => array_map([$this, 'normalizeKeyword'], $data['secondary'] ?? []),
            'long_tail'        => array_map([$this, 'normalizeKeyword'], $data['long_tail'] ?? []),
            'meta_description' => (string) ($data['meta_description'] ?? ''),
            'tokens_used'      => ($result['tokens_input'] ?? 0) + ($result['tokens_output'] ?? 0),
        ];
    }

    /**
     * Gemini cevabından JSON parse et — markdown fence'leri, önek/sonek açıklamaları
     * ve trailing text'i tolere eder. İlk { ile son } arasındaki bloku alır.
     */
    private function parseJsonResponse(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') return null;

        // Markdown code fence'leri temizle
        $content = preg_replace('/```(?:json)?\s*|\s*```/m', '', $content);

        // Direkt parse dene
        $data = json_decode($content, true);
        if (is_array($data)) return $data;

        // İlk { ile eşleşen son } arasını çıkar
        $firstBrace = strpos($content, '{');
        $lastBrace = strrpos($content, '}');
        if ($firstBrace === false || $lastBrace === false || $lastBrace <= $firstBrace) {
            return null;
        }

        $jsonCandidate = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
        $data = json_decode($jsonCandidate, true);
        return is_array($data) ? $data : null;
    }

    private function normalizeKeyword(array $k): array
    {
        return [
            'keyword' => trim((string) ($k['keyword'] ?? '')),
            'intent'  => (string) ($k['intent'] ?? 'informational'),
            'reason'  => (string) ($k['reason'] ?? ''),
        ];
    }

    private function extractMetadata(string $code, array $template, string $content): array
    {
        $meta = [];

        if ($code === 'blog_post') {
            // YAML-benzeri metadata bloğu extract
            if (preg_match('/^---\s*(.+?)\s*---/s', $content, $m)) {
                $block = $m[1];
                foreach (explode("\n", $block) as $line) {
                    if (preg_match('/^(\w+):\s*(.+)$/', trim($line), $mm)) {
                        $meta[$mm[1]] = trim($mm[2], '" ');
                    }
                }
            }
            $meta['word_count'] = str_word_count(strip_tags($content));
        }

        if ($code === 'faq') {
            $data = $this->parseJsonResponse($content);
            if (is_array($data) && isset($data['faqs'])) {
                $meta['faqs'] = $data['faqs'];
                $meta['count'] = count($data['faqs']);
            }
        }

        return $meta;
    }
}
