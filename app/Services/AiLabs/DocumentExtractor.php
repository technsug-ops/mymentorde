<?php

namespace App\Services\AiLabs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

/**
 * Yüklenen dosyalardan (DOCX, XLSX, TXT) düz metin çıkartır.
 * PDF bu service'ten geçmez — Gemini File API direkt yükleme kullanır.
 *
 * Çıktı: content_markdown alanına yazılabilir düz metin.
 * Max 100K karakter (Gemini context maliyetini kontrol altında tut).
 *
 * Ek: extractWithVision() — Gemini Vision üzerinden image/PDF belgelerden
 * structured field çıkarımı (pasaport, diploma, transkript vb. için).
 */
class DocumentExtractor
{
    private const MAX_CHARS = 100000;

    public function __construct(private readonly ?GeminiProvider $gemini = null)
    {
    }

    /**
     * @return array{ok:bool, content?:string, error?:string, format?:string, bytes?:int}
     */
    public function extract(string $absolutePath, string $extension): array
    {
        $ext = strtolower(trim($extension, '.'));

        try {
            $content = match ($ext) {
                'docx'   => $this->extractDocx($absolutePath),
                'doc'    => null, // eski binary format desteklenmiyor
                'xlsx'   => $this->extractXlsx($absolutePath),
                'xls'    => $this->extractXlsx($absolutePath),
                'txt'    => file_get_contents($absolutePath),
                'md'     => file_get_contents($absolutePath),
                default  => null,
            };

            if ($content === null) {
                return ['ok' => false, 'error' => 'unsupported_format: ' . $ext];
            }

            $content = $this->normalize($content);

            if (trim($content) === '') {
                return ['ok' => false, 'error' => 'empty_content'];
            }

            return [
                'ok'      => true,
                'content' => $content,
                'format'  => $ext,
                'bytes'   => strlen($content),
            ];
        } catch (\Throwable $e) {
            Log::warning('AiLabs DocumentExtractor failed', ['path' => $absolutePath, 'ext' => $ext, 'error' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'extraction_failed: ' . $e->getMessage()];
        }
    }

    private function extractDocx(string $path): string
    {
        $phpWord = WordIOFactory::load($path);
        $lines = [];

        foreach ($phpWord->getSections() as $section) {
            $this->walkElements($section->getElements(), $lines);
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int,mixed> $elements
     * @param array<int,string> &$lines
     */
    private function walkElements(array $elements, array &$lines): void
    {
        foreach ($elements as $el) {
            $cls = class_basename($el);

            if ($cls === 'Text' || $cls === 'Link') {
                $lines[] = method_exists($el, 'getText') ? (string) $el->getText() : '';
            } elseif ($cls === 'TextRun' || $cls === 'ListItemRun') {
                $inner = method_exists($el, 'getElements') ? $el->getElements() : [];
                $parts = [];
                foreach ($inner as $child) {
                    if (method_exists($child, 'getText')) {
                        $parts[] = (string) $child->getText();
                    }
                }
                $lines[] = implode('', $parts);
            } elseif ($cls === 'Title') {
                $text = '';
                if (method_exists($el, 'getText')) {
                    $t = $el->getText();
                    $text = is_object($t) && method_exists($t, 'getText') ? (string) $t->getText() : (string) $t;
                }
                $depth = method_exists($el, 'getDepth') ? (int) $el->getDepth() : 1;
                $prefix = str_repeat('#', max(1, min(3, $depth)));
                $lines[] = "\n{$prefix} {$text}\n";
            } elseif ($cls === 'Table') {
                foreach ($el->getRows() as $row) {
                    $cells = [];
                    foreach ($row->getCells() as $cell) {
                        $cellLines = [];
                        $this->walkElements($cell->getElements(), $cellLines);
                        $cells[] = trim(implode(' ', $cellLines));
                    }
                    $lines[] = '| ' . implode(' | ', $cells) . ' |';
                }
            } elseif ($cls === 'PageBreak') {
                $lines[] = "\n---\n";
            } elseif (method_exists($el, 'getElements')) {
                $this->walkElements($el->getElements(), $lines);
            }
        }
    }

    private function extractXlsx(string $path): string
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('phpoffice/phpspreadsheet kurulu değil');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $lines = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetTitle = (string) $sheet->getTitle();
            $lines[] = "\n## Sayfa: {$sheetTitle}\n";

            $rows = $sheet->toArray(null, true, true, true);
            foreach ($rows as $row) {
                // Boş satırları atla
                $values = array_map(fn ($v) => trim((string) $v), array_values($row));
                $nonEmpty = array_filter($values, fn ($v) => $v !== '');
                if (empty($nonEmpty)) continue;

                $lines[] = '| ' . implode(' | ', $values) . ' |';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Image / PDF belgesinden şemaya göre structured field çıkarımı (Gemini Vision).
     *
     * Akış:
     *   1. Dosyayı Gemini File API'ye upload et (resumable protocol)
     *   2. Schema'yı JSON istek prompt'una dönüştür + responseMimeType=application/json
     *   3. Gemini'den JSON döndür, parse et
     *   4. Confidence hesabı: required field'lardan dolu olan oran
     *
     * Storage path: documents.storage_path — Storage 'local' disk relative path.
     *
     * @param array $schema  DocumentOcrSchemas'den dönen schema array
     * @return array{
     *   ok:bool,
     *   data?:array<string,mixed>,
     *   confidence?:float,
     *   model?:string,
     *   tokens_input?:int,
     *   tokens_output?:int,
     *   error?:string
     * }
     */
    public function extractWithVision(string $storagePath, array $schema, ?int $companyId = null): array
    {
        if (!$this->gemini) {
            return ['ok' => false, 'error' => 'gemini_provider_unavailable'];
        }
        if (!$this->gemini->isConfigured($companyId)) {
            return ['ok' => false, 'error' => 'gemini_not_configured'];
        }

        // storage_path → mutlak yol (local disk)
        try {
            $absolutePath = Storage::disk('local')->path($storagePath);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'storage_resolve_failed: ' . $e->getMessage()];
        }

        if (!is_file($absolutePath)) {
            return ['ok' => false, 'error' => 'file_not_found: ' . $storagePath];
        }

        $mime = $this->detectMime($absolutePath);
        $supported = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($mime, $supported, true)) {
            return ['ok' => false, 'error' => 'unsupported_mime: ' . $mime];
        }

        $displayName = basename($absolutePath);
        $upload = $this->gemini->uploadFile($absolutePath, $mime, $displayName, $companyId);
        if (!($upload['ok'] ?? false)) {
            return ['ok' => false, 'error' => 'upload_failed: ' . ($upload['error'] ?? 'unknown')];
        }

        $fileUri  = (string) ($upload['file_uri']  ?? '');
        $fileId   = (string) ($upload['file_id']   ?? '');
        $fileMime = (string) ($upload['mime_type'] ?? $mime);

        try {
            [$systemPrompt, $userMessage] = $this->buildVisionPrompts($schema);

            $result = $this->gemini->chat(
                systemPrompt: $systemPrompt,
                userMessage:  $userMessage,
                fileRefs:     [['file_uri' => $fileUri, 'mime_type' => $fileMime]],
                options: [
                    'temperature'        => 0.1,   // belge OCR'da yaratıcılık istemiyoruz
                    'max_output_tokens'  => 2048,
                    'response_mime_type' => 'application/json',
                    'thinking_budget'    => 0,
                ],
                companyId: $companyId,
            );

            // Cleanup — Gemini File API kotasını şişirme
            try { $this->gemini->deleteFile($fileId, $companyId); } catch (\Throwable) {}

            if (!($result['ok'] ?? false)) {
                return ['ok' => false, 'error' => 'gemini_chat_failed: ' . ($result['error'] ?? 'unknown')];
            }

            $raw = (string) ($result['content'] ?? '');
            $parsed = $this->parseJsonResponse($raw);

            if ($parsed === null) {
                Log::warning('DocumentExtractor: Gemini JSON parse failed', [
                    'raw' => mb_substr($raw, 0, 500),
                ]);
                return ['ok' => false, 'error' => 'invalid_json_response'];
            }

            $confidence = $this->computeConfidence($parsed, $schema);

            return [
                'ok'            => true,
                'data'          => $parsed,
                'confidence'    => $confidence,
                'model'         => (string) ($result['model'] ?? ''),
                'tokens_input'  => (int) ($result['tokens_input']  ?? 0),
                'tokens_output' => (int) ($result['tokens_output'] ?? 0),
            ];
        } catch (\Throwable $e) {
            Log::warning('DocumentExtractor extractWithVision exception', [
                'storage_path' => $storagePath,
                'error'        => $e->getMessage(),
            ]);
            // Hata olsa da upload edilen dosyayı sil
            try { $this->gemini->deleteFile($fileId, $companyId); } catch (\Throwable) {}
            return ['ok' => false, 'error' => 'exception: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{0:string,1:string} [systemPrompt, userMessage]
     */
    private function buildVisionPrompts(array $schema): array
    {
        $catLabel = (string) ($schema['category_label'] ?? 'Belge');
        $typeHint = (string) ($schema['doc_type_hint']  ?? '');
        $fields   = (array)  ($schema['fields']         ?? []);

        $jsonSpec = [];
        $rules    = [];
        foreach ($fields as $f) {
            $key      = (string) ($f['key']      ?? '');
            $label    = (string) ($f['label']    ?? $key);
            $type     = (string) ($f['type']     ?? 'string');
            $required = (bool)   ($f['required'] ?? false);
            $format   = (string) ($f['format']   ?? '');
            if ($key === '') continue;

            $jsonSpec[$key] = $type === 'array' ? [] : null;
            $line = "- \"{$key}\" ({$label}, {$type}" . ($required ? ', REQUIRED' : '') . ')';
            if ($format !== '') $line .= " — format: {$format}";
            $rules[] = $line;
        }

        $system = "Sen bir OCR + belge analiz uzmanısın. Sana verilen belgeyi analiz et ve sadece JSON cevap döndür. " .
                  "Belge türü: {$catLabel}. " . ($typeHint !== '' ? "Açıklama: {$typeHint} " : '') .
                  "Kurallar:\n" .
                  "1. Her field için belgede gördüğün değeri çıkar.\n" .
                  "2. Görmediğin / okunamayan / belgede olmayan field'lar için null kullan.\n" .
                  "3. Tarihleri YYYY-MM-DD formatına çevir (DD.MM.YYYY → YYYY-MM-DD).\n" .
                  "4. Sadece okunan değeri ver, açıklama / yorum ekleme.\n" .
                  "5. Karakterleri belgedeki gibi koru (büyük/küçük harf, Türkçe ı/İ).\n" .
                  "6. Cevap SADECE geçerli JSON olmalı, başka hiçbir metin olmamalı.";

        $userParts = [];
        $userParts[] = "Belge: {$catLabel}";
        $userParts[] = "Aşağıdaki field'ları çıkar ve JSON formatında döndür:";
        foreach ($rules as $r) $userParts[] = $r;
        $userParts[] = '';
        $userParts[] = 'Beklenen JSON şablonu (tüm key\'ler dolu olmalı, eksik bilgi için null):';
        $userParts[] = json_encode($jsonSpec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [$system, implode("\n", $userParts)];
    }

    /**
     * Gemini cevabını JSON'a parse et (markdown code fence cleanup dahil).
     */
    private function parseJsonResponse(string $raw): ?array
    {
        $text = trim($raw);
        // ```json ... ``` fence varsa temizle
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-zA-Z]*\s*/', '', $text);
            $text = preg_replace('/\s*```\s*$/', '', $text);
            $text = trim($text);
        }
        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            // Aramayı dene — text içinde JSON object olabilir
            if (preg_match('/\{.*\}/s', $text, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Required field doluluk oranı — basit ama anlaşılır metrik.
     */
    private function computeConfidence(array $data, array $schema): float
    {
        $required = collect($schema['fields'] ?? [])
            ->filter(fn ($f) => ($f['required'] ?? false) === true)
            ->pluck('key')
            ->all();

        if (empty($required)) {
            // Required yoksa: tüm field'lardan dolu olan oran
            $all = collect($schema['fields'] ?? [])->pluck('key')->all();
            if (empty($all)) return 0.0;
            $filled = collect($all)->filter(fn ($k) => $this->fieldIsFilled($data[$k] ?? null))->count();
            return round($filled / max(1, count($all)), 2);
        }

        $filled = collect($required)->filter(fn ($k) => $this->fieldIsFilled($data[$k] ?? null))->count();
        return round($filled / count($required), 2);
    }

    private function fieldIsFilled(mixed $v): bool
    {
        if ($v === null) return false;
        if (is_string($v)) return trim($v) !== '';
        if (is_array($v))  return !empty($v);
        return true;
    }

    private function detectMime(string $path): string
    {
        if (function_exists('mime_content_type')) {
            $m = @mime_content_type($path);
            if (is_string($m) && $m !== '') return $m;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'pdf'         => 'application/pdf',
            default       => 'application/octet-stream',
        };
    }

    private function normalize(string $text): string
    {
        // Line endings
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        // Whitespace collapse
        $text = preg_replace('/[ \t]+/', ' ', $text);
        // Multiple blank lines → max 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/^[ \t]+|[ \t]+$/m', '', $text);
        $text = trim($text);

        // Max uzunluk
        if (mb_strlen($text) > self::MAX_CHARS) {
            $text = mb_substr($text, 0, self::MAX_CHARS) . "\n\n[...içerik kısaltıldı...]";
        }

        return $text;
    }
}
