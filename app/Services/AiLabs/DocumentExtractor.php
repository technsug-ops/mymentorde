<?php

namespace App\Services\AiLabs;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

/**
 * Yüklenen dosyalardan (DOCX, XLSX, TXT) düz metin çıkartır.
 * PDF bu service'ten geçmez — Gemini File API direkt yükleme kullanır.
 *
 * Çıktı: content_markdown alanına yazılabilir düz metin.
 * Max 100K karakter (Gemini context maliyetini kontrol altında tut).
 */
class DocumentExtractor
{
    private const MAX_CHARS = 100000;

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
