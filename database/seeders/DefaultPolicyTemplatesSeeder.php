<?php

namespace Database\Seeders;

use App\Models\PolicyDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Default policy templates — DSGVO/KVKK uyumlu metinler 3 dilde.
 *
 * Kapsam:
 *  - privacy  → Datenschutzerklärung / Privacy Policy / KVKK Aydınlatma Metni
 *  - cookie   → Cookie-Richtlinie / Cookie Policy / Çerez Politikası
 *
 * Yeni kind eklemek için $kinds dizisine ekle + database/seed-content/policy/
 * altına {kind}_{locale}.txt dosyalarını koy. Seeder otomatik bulur.
 *
 * Idempotent — updateOrCreate ile aynı kayıt 2x yazılmaz.
 */
class DefaultPolicyTemplatesSeeder extends Seeder
{
    /**
     * @var array<string,array<string,string>> kind => locale => human title
     */
    private const TITLES = [
        'privacy' => [
            'de' => 'Datenschutzerklärung',
            'en' => 'Privacy Policy',
            'tr' => 'Aydınlatma Metni ve Açık Rıza Beyanı (KVKK)',
        ],
        'cookie' => [
            'de' => 'Cookie-Richtlinie',
            'en' => 'Cookie Policy',
            'tr' => 'Çerez Politikası',
        ],
        'terms' => [
            'de' => 'Allgemeine Geschäftsbedingungen (AGB)',
            'en' => 'Terms and Conditions',
            'tr' => 'Kullanım Koşulları',
        ],
        'tom' => [
            'de' => 'Technische und Organisatorische Maßnahmen (TOM)',
            'en' => 'Technical and Organizational Measures (TOM)',
            'tr' => 'Teknik ve Organizasyonel Önlemler (TOM)',
        ],
        'incident_plan' => [
            'de' => 'Datenpannen-Notfallplan',
            'en' => 'Data Breach Incident Response Plan',
            'tr' => 'Veri İhlali Acil Eylem Planı',
        ],
    ];

    public function run(): void
    {
        // Hedef company — env COMPANY_ID ile override edilebilir, default = 1 (mentorde)
        $companyId = (int) (env('COMPANY_ID') ?: 1);

        $total = 0;
        foreach (self::TITLES as $kind => $titles) {
            foreach (['de', 'en', 'tr'] as $locale) {
                $path = database_path("seed-content/policy/{$kind}_{$locale}.txt");
                if (!File::exists($path)) {
                    $this->command?->warn("⚠ Atlandı (dosya yok): {$kind}_{$locale}.txt");
                    continue;
                }

                $raw  = File::get($path);
                $body = $this->textToHtml($raw);

                PolicyDocument::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'kind'       => $kind,
                        'locale'     => $locale,
                    ],
                    [
                        'title' => $titles[$locale],
                        'body'  => $body,
                    ]
                );

                $this->command?->info("✅ {$kind} [{$locale}] seeded for company {$companyId} (" . number_format(strlen($body)) . " chars)");
                $total++;
            }
        }
        $this->command?->info("Toplam {$total} doküman yazıldı.");
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

            // h2: "X. ..." (top section)
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
