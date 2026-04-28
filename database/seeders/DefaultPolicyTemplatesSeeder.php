<?php

namespace Database\Seeders;

use App\Models\PolicyDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Default policy templates — DSGVO/KVKK Datenschutzerklärung in 3 dil.
 *
 * Bir SaaS müşterisi açıldığında bu seeder çalıştırılarak privacy/de, en, tr
 * placeholder şablonları policy_documents tablosuna yazılır. Müşteri sonra
 * GDPR Uyumluluk → GDPR Politikalar sekmesinden kendi şirket bilgilerini
 * doldurur.
 *
 * Idempotent — updateOrCreate ile aynı kayıt 2x yazılmaz.
 */
class DefaultPolicyTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Hedef company — env COMPANY_ID ile override edilebilir, default = 1 (mentorde)
        $companyId = (int) (env('COMPANY_ID') ?: 1);

        $titles = [
            'de' => 'Datenschutzerklärung',
            'en' => 'Privacy Policy',
            'tr' => 'Aydınlatma Metni ve Açık Rıza Beyanı (KVKK)',
        ];

        foreach (['de', 'en', 'tr'] as $locale) {
            $path = database_path("seed-content/policy/privacy_{$locale}.txt");
            if (!File::exists($path)) {
                $this->command?->warn("⚠ Eksik: {$path}");
                continue;
            }

            $raw  = File::get($path);
            $body = $this->textToHtml($raw);

            PolicyDocument::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'kind'       => PolicyDocument::KIND_PRIVACY,
                    'locale'     => $locale,
                ],
                [
                    'title' => $titles[$locale],
                    'body'  => $body,
                ]
            );

            $this->command?->info("✅ Privacy [{$locale}] seeded for company {$companyId} (" . number_format(strlen($body)) . " chars)");
        }
    }

    /**
     * Düz metin → minimal HTML.
     *  - "1. Foo"  → <h2>1. Foo</h2>
     *  - "3.1 Foo" → <h3>3.1 Foo</h3>
     *  - "- bullet" → <ul><li>bullet</li>...</ul>
     *  - boş satır → paragraf bölücü
     *  - diğer    → <p>...</p>
     */
    private function textToHtml(string $raw): string
    {
        $lines = preg_split('/\r?\n/', $raw);
        $out = [];
        $listBuf = [];
        $paraBuf = [];

        $flushPara = function () use (&$out, &$paraBuf): void {
            if ($paraBuf === []) return;
            $text = trim(implode(' ', $paraBuf));
            if ($text !== '') $out[] = '<p>' . htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
            $paraBuf = [];
        };
        $flushList = function () use (&$out, &$listBuf): void {
            if ($listBuf === []) return;
            $items = array_map(
                fn ($i) => '<li>' . htmlspecialchars($i, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</li>',
                $listBuf
            );
            $out[] = '<ul>' . implode('', $items) . '</ul>';
            $listBuf = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Boş satır → flush
            if ($trimmed === '') {
                $flushPara();
                $flushList();
                continue;
            }

            // Bullet item (- ...)
            if (preg_match('/^-\s+(.+)$/u', $trimmed, $m)) {
                $flushPara();
                $listBuf[] = $m[1];
                continue;
            } else {
                $flushList();
            }

            // h3: "X.Y ..." (sub-section)
            if (preg_match('/^(\d+)\.(\d+)\s+(.+)$/u', $trimmed, $m)) {
                $flushPara();
                $out[] = '<h3>' . htmlspecialchars("{$m[1]}.{$m[2]} {$m[3]}", ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</h3>';
                continue;
            }

            // h2: "X. ..." (top section)  — sadece ilk karakteri rakam ve "."  olan
            if (preg_match('/^(\d+)\.\s+(.+)$/u', $trimmed, $m)) {
                $flushPara();
                $out[] = '<h2>' . htmlspecialchars("{$m[1]}. {$m[2]}", ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</h2>';
                continue;
            }

            // Default → paragraf parçası
            $paraBuf[] = $trimmed;
        }
        $flushPara();
        $flushList();

        return implode("\n", $out);
    }
}
