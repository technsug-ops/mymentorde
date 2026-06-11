<?php

namespace App\Services\ProgramCatalog;

use App\Models\Program;
use App\Services\AiLabs\GeminiProvider;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Canonical Program detay alanlarının EN→TR çevirisi.
 *
 * Lazy on-demand pattern:
 *  - translateProgram($program) → 4 alanı tek API call ile çevirir, DB'ye yazar
 *  - Manager elle düzelttiyse (is_manually_curated=true) override etmez
 *  - Çeviri başarısız olursa orijinal alan kalır, _tr null kalır
 *
 * AI provider: Gemini Flash (mevcut AI Labs altyapısı).
 */
class ProgramTranslationService
{
    /** Hangi alanları çevireceğiz — orijinal_field => tr_field */
    private const TRANSLATABLE_FIELDS = [
        'description'                => 'description_tr',
        'qualification_requirements' => 'qualification_requirements_tr',
        'language_requirements'      => 'language_requirements_tr',
        'required_documents'         => 'required_documents_tr',
    ];

    public function __construct(private readonly GeminiProvider $gemini) {}

    /**
     * Bir programın 4 alanını tek seferde çevir + DB'ye yaz.
     *
     * @param  ?int  $companyId  API key resolve için. Null ise default 1.
     * @return array{translated_fields:int, skipped_fields:int, error:?string}
     */
    public function translateProgram(Program $program, bool $force = false, ?int $companyId = null): array
    {
        // Programs SHARED tablo (company_id yok) ama Gemini API key company-spesifik.
        // Default fallback: company 1 (MentorDE main).
        $apiCompanyId = $companyId ?? 1;

        // Manager manuel düzeltmişse override etme
        if ($program->is_manually_curated && ! $force) {
            return [
                'translated_fields' => 0,
                'skipped_fields'    => 4,
                'error'             => 'Program manager tarafından korunuyor (is_manually_curated)',
            ];
        }

        // Çevrilecek alanları topla — sadece orijinali dolu + TR'si boş olanlar
        $payload = [];
        $skipped = 0;
        foreach (self::TRANSLATABLE_FIELDS as $orig => $trCol) {
            $origValue = $program->{$orig};
            $trValue = $program->{$trCol};

            if (empty($origValue)) { $skipped++; continue; }
            if (! $force && ! empty($trValue)) { $skipped++; continue; }

            $payload[$orig] = (string) $origValue;
        }

        if (empty($payload)) {
            return [
                'translated_fields' => 0,
                'skipped_fields'    => $skipped,
                'error'             => 'Çevrilecek alan yok (hepsi boş veya çevrilmiş).',
            ];
        }

        // Gemini'ye tek istek
        try {
            $translated = $this->callGemini($payload, $apiCompanyId);
        } catch (\Throwable $e) {
            Log::warning('ProgramTranslation.gemini_failed', [
                'program_id' => $program->id,
                'error' => $e->getMessage(),
            ]);
            return [
                'translated_fields' => 0,
                'skipped_fields'    => $skipped,
                'error'             => 'AI çeviri başarısız: ' . $e->getMessage(),
            ];
        }

        // DB'ye yaz
        $updates = ['translated_at' => now()];
        $count = 0;
        foreach (self::TRANSLATABLE_FIELDS as $orig => $trCol) {
            if (! empty($translated[$orig])) {
                $updates[$trCol] = $translated[$orig];
                $count++;
            }
        }

        if ($count > 0) {
            // Raw update — Eloquent cast bypass
            \DB::table('programs')->where('id', $program->id)->update($updates);
            $program->refresh();
        }

        return [
            'translated_fields' => $count,
            'skipped_fields'    => $skipped,
            'error'             => null,
        ];
    }

    /**
     * Gemini ile EN→TR çeviri yapar — JSON yapısı isteği ile.
     *
     * @param  array<string,string>  $payload  field_name => english_text
     * @return array<string,string>            field_name => turkish_text
     */
    private function callGemini(array $payload, int $companyId = 1): array
    {
        $systemPrompt = "Sen bir teknik çevirmensin. Almanya üniversite programı bilgilerini İngilizce'den Türkçe'ye çevirirsin.\n\n"
            . "KURALLAR:\n"
            . "1. Akademik terimleri Türkçeye çevir, ama Almanca özel isimleri KORU (Bachelor, Master, Hochschule, Studienkolleg, Sperrkonto, TestDaF, vb.)\n"
            . "2. Markdown formatını koru (- madde, * vurgu, ** kalın)\n"
            . "3. Çeviri SADE ve AKADEMİK olsun — gereksiz süslemeden kaçın\n"
            . "4. Üniversite/program isimlerini ÇEVİRME (Hochschule Bremen → aynı kalır)\n"
            . "5. JSON yapısını tam koru — sadece value'ları çevir, key'lere dokunma\n\n"
            . "ÇIKTI: Sadece JSON döndür, başka metin ekleme. Format örneği:\n"
            . '{"description": "Türkçe çeviri...", "qualification_requirements": "Türkçe çeviri..."}';

        $userMessage = "Aşağıdaki JSON'daki tüm value'ları Türkçeye çevir:\n\n"
            . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $resp = $this->gemini->chat(
            systemPrompt: $systemPrompt,
            userMessage:  $userMessage,
            fileRefs:     [],
            options:      [
                'temperature' => 0.3, // teknik çeviri — düşük creativity
                // Engineering/CS programlarinda uzun "qualification requirements" + "required
                // documents" listeleri 4096'yi asabiliyor (Gemini truncation -> JSON parse 500).
                // 8192'ye yukseltildi — Gemini 1.5/2.0 modelleri rahat destekliyor.
                'max_tokens'  => 8192,
            ],
            companyId:    $companyId
        );

        // Provider response field: 'content' (asıl text), 'text' (eski isim — yedek)
        $rawText = $resp['content'] ?? $resp['text'] ?? '';
        if (empty($rawText)) {
            throw new RuntimeException('Gemini boş döndü: ' . substr(json_encode($resp), 0, 300));
        }

        // Gemini bazen ```json ... ``` formatı döndürür — temizle
        $text = trim((string) $rawText);
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini cevabı JSON parse edilemedi: ' . substr($text, 0, 200));
        }

        // Validate — orijinal key'lerin hepsi cevapta olmalı
        $result = [];
        foreach (array_keys($payload) as $key) {
            if (isset($decoded[$key]) && is_string($decoded[$key]) && trim($decoded[$key]) !== '') {
                $result[$key] = $decoded[$key];
            }
        }

        return $result;
    }
}
