<?php

namespace App\Services\AiLabs;

/**
 * Multi-provider token fiyatlandırma — 2026 tarifeleri (USD/M token).
 *
 * - costEur(): tek konuşma için EUR maliyeti
 * - providerOf(): model adından provider çıkarımı (gemini / openai / anthropic / unknown)
 * - normalizeModel(): "gpt-4o-2024-08-06" → "gpt-4o" gibi tarihsel ekleri kırpar
 *
 * Yeni model eklemek için: PRICES_USD array'ine satır ekle + providerOf() prefix'ini kontrol et.
 * Kur: USD → EUR 0.92 (sabit; FX entegrasyonu olmadığı için ortalama 2026 değeri).
 */
class ProviderPricing
{
    /**
     * Fiyatlar: $/M token (input/output ayrı).
     * Kaynaklar: 2026 Q1-Q2 provider pricing sayfaları.
     */
    private const PRICES_USD = [
        // ── Gemini (Google) ───────────────────────────
        'gemini-1.5-flash'         => ['in' => 0.075,  'out' => 0.30],
        'gemini-1.5-flash-8b'      => ['in' => 0.0375, 'out' => 0.15],
        'gemini-1.5-pro'           => ['in' => 1.25,   'out' => 5.00],
        'gemini-2.0-flash'         => ['in' => 0.10,   'out' => 0.40],
        'gemini-2.0-flash-exp'     => ['in' => 0.0,    'out' => 0.0], // free during exp
        'gemini-2.0-flash-lite'    => ['in' => 0.075,  'out' => 0.30],
        'gemini-2.5-flash'         => ['in' => 0.30,   'out' => 2.50],
        'gemini-2.5-pro'           => ['in' => 1.25,   'out' => 10.00],

        // ── OpenAI ─────────────────────────────────────
        'gpt-4o'                   => ['in' => 2.50,   'out' => 10.00],
        'gpt-4o-mini'              => ['in' => 0.15,   'out' => 0.60],
        'gpt-4-turbo'              => ['in' => 10.00,  'out' => 30.00],
        'gpt-4'                    => ['in' => 30.00,  'out' => 60.00],
        'gpt-3.5-turbo'            => ['in' => 0.50,   'out' => 1.50],
        'o1-preview'               => ['in' => 15.00,  'out' => 60.00],
        'o1-mini'                  => ['in' => 3.00,   'out' => 12.00],

        // ── Anthropic ─────────────────────────────────
        'claude-sonnet-4-5'        => ['in' => 3.00,   'out' => 15.00],
        'claude-opus-4-1'          => ['in' => 15.00,  'out' => 75.00],
        'claude-haiku-3-5'         => ['in' => 0.80,   'out' => 4.00],
        'claude-3-5-sonnet'        => ['in' => 3.00,   'out' => 15.00],
        'claude-3-opus'            => ['in' => 15.00,  'out' => 75.00],
        'claude-3-haiku'           => ['in' => 0.25,   'out' => 1.25],
    ];

    /** Sabit USD → EUR dönüşüm katsayısı. */
    public const USD_TO_EUR = 0.92;

    /**
     * Bir conversation için EUR cinsinden maliyet.
     * Bilinmeyen model → 0.0 (sessizce; KPI'i bozmasın).
     */
    public static function costEur(string $model, int $inputTokens, int $outputTokens): float
    {
        $key = self::normalizeModel($model);
        $rate = self::PRICES_USD[$key] ?? ['in' => 0.0, 'out' => 0.0];
        $usd = ($inputTokens / 1_000_000) * $rate['in']
             + ($outputTokens / 1_000_000) * $rate['out'];

        return round($usd * self::USD_TO_EUR, 6);
    }

    /**
     * Model adından provider çıkarımı.
     *
     * @return 'gemini'|'openai'|'anthropic'|'unknown'
     */
    public static function providerOf(string $model): string
    {
        $m = strtolower(trim($model));
        if ($m === '') {
            return 'unknown';
        }

        if (str_starts_with($m, 'gemini') || str_starts_with($m, 'models/gemini')) {
            return 'gemini';
        }
        if (str_starts_with($m, 'gpt') || str_starts_with($m, 'o1') || str_starts_with($m, 'o3')) {
            return 'openai';
        }
        if (str_starts_with($m, 'claude')) {
            return 'anthropic';
        }

        return 'unknown';
    }

    /**
     * Tarihsel ek / version suffix'i kırp.
     * 'gpt-4o-2024-08-06'    → 'gpt-4o'
     * 'gemini-1.5-flash-001' → 'gemini-1.5-flash'
     * 'claude-3-5-sonnet-20241022' → 'claude-3-5-sonnet'
     * 'models/gemini-2.0-flash' → 'gemini-2.0-flash'
     */
    private static function normalizeModel(string $model): string
    {
        $m = strtolower(trim($model));

        // "models/..." prefix (Gemini API'sinde olur)
        if (str_starts_with($m, 'models/')) {
            $m = substr($m, 7);
        }

        // Tam eşleşme varsa direkt dön
        if (isset(self::PRICES_USD[$m])) {
            return $m;
        }

        // Sondaki tarih (-20240806, -2024-08-06) veya version (-001, -002) sufixini düş
        $patterns = [
            '/-\d{8}$/',          // -20240806
            '/-\d{4}-\d{2}-\d{2}$/', // -2024-08-06
            '/-\d{3,4}$/',         // -001, -1234
            '/-latest$/',
            '/-preview$/',
        ];
        foreach ($patterns as $p) {
            $candidate = preg_replace($p, '', $m);
            if ($candidate !== $m && isset(self::PRICES_USD[$candidate])) {
                return $candidate;
            }
        }

        // Hala bulamadıysak en uzun prefix eşleşmesi dene
        $best = '';
        foreach (array_keys(self::PRICES_USD) as $known) {
            if (str_starts_with($m, $known) && strlen($known) > strlen($best)) {
                $best = $known;
            }
        }

        return $best !== '' ? $best : $m;
    }

    /**
     * UI için provider rozet etiketi (Türkçe başlık + renk önerisi).
     *
     * @return array{label:string, color:string}
     */
    public static function providerLabel(string $provider): array
    {
        return match ($provider) {
            'gemini'    => ['label' => 'Gemini',    'color' => '#4285f4'],
            'openai'    => ['label' => 'OpenAI',    'color' => '#10a37f'],
            'anthropic' => ['label' => 'Anthropic', 'color' => '#d97757'],
            default     => ['label' => 'Bilinmeyen','color' => '#94a3b8'],
        };
    }

    /**
     * Tüm desteklenen provider'lar — UI'da kart sırası için.
     *
     * @return array<int,string>
     */
    public static function allProviders(): array
    {
        return ['gemini', 'openai', 'anthropic'];
    }
}
