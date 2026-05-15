<?php

namespace App\Services;

use App\Models\University;
use Smalot\PdfParser\Parser;

/**
 * HRK Rektorenliste PDF'inden uni → domain mapping çıkarıp
 * University.image_path'i Clearbit logo URL'i ile dolduran servis.
 *
 * Hem `php artisan unis:link-logos` hem /_deploy/run-pending endpoint
 * tarafından kullanılır.
 */
class UniLogoSyncService
{
    private const HRK_PDF_URL = 'https://hs-kompass.de/kompass/xml/download/rektorenliste.pdf';

    /**
     * Tam senkron: PDF indir → parse → match → apply.
     *
     * @return array<string, mixed> stats
     */
    public function syncAll(bool $apply = true, float $minScore = 0.5): array
    {
        $logs = [];

        // 1. PDF indir (storage/app/rektorenliste.pdf)
        $pdfPath = storage_path('app/rektorenliste.pdf');
        if (! file_exists($pdfPath) || filesize($pdfPath) < 10_000) {
            $data = @file_get_contents(self::HRK_PDF_URL);
            if ($data === false || strlen($data) < 10_000) {
                throw new \RuntimeException('PDF download başarısız (HRK erişilemez veya boş).');
            }
            file_put_contents($pdfPath, $data);
            $logs[] = 'PDF indirildi: ' . number_format(strlen($data) / 1024, 1) . ' KB';
        } else {
            $logs[] = 'PDF cache hit (storage/app/rektorenliste.pdf)';
        }

        // 2. Parse — uni → url mapping
        $hrk = $this->parsePdf($pdfPath);
        $logs[] = 'Parse: ' . count($hrk) . ' unique uni + domain';

        // 3. DB ile fuzzy match
        $matches = $this->matchUniversities($hrk);
        $logs[] = 'Match: ' . count($matches) . ' eşleşme (>= ' . $minScore . ' score)';

        // 4. Apply
        $updated = 0;
        $skipped = 0;
        foreach ($matches as $m) {
            if (($m['score'] ?? 0) < $minScore) continue;
            $uni = University::find($m['db_id']);
            if (! $uni) continue;

            $clearbitUrl = 'https://logo.clearbit.com/' . $m['domain'];
            // Manuel custom path varsa dokunma; sadece NULL veya zaten clearbit
            if ($uni->image_path !== null && ! str_starts_with($uni->image_path, 'https://logo.clearbit.com/')) {
                $skipped++;
                continue;
            }
            if ($uni->image_path === $clearbitUrl) {
                $skipped++;
                continue;
            }
            if ($apply) {
                $uni->update(['image_path' => $clearbitUrl]);
            }
            $updated++;
        }
        $logs[] = ($apply ? 'APPLIED' : 'DRY-RUN') . ": {$updated} updated, {$skipped} skipped";

        return [
            'logs'        => $logs,
            'pdf_unis'    => count($hrk),
            'matches'     => count($matches),
            'updated'     => $updated,
            'skipped'     => $skipped,
            'applied'     => $apply,
        ];
    }

    /**
     * PDF'i smalot/pdfparser ile parse et, uni → URL listesi döndür.
     *
     * @return list<array{name: string, url: string, domain: string}>
     */
    public function parsePdf(string $pdfPath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();
        $lines = preg_split('/\r\n|\n|\r/', $text);

        $results = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (! preg_match('/Internet:\s*(https?:\/\/[^\s,]+)/i', $line, $m)) continue;

            $url = trim($m[1], '.,;)');

            // Adı geriye doğru ara
            $name = null;
            for ($j = $i - 1; $j >= max(0, $i - 8); $j--) {
                $candidate = trim($lines[$j]);
                if ($candidate === '') continue;
                if (preg_match('/^\d{5}\s/', $candidate)) continue;
                if (preg_match('/^(Tel\.?|Telefax|Postfach|E-Mail|Seite|Stand)/i', $candidate)) continue;
                if (preg_match('/^\d+\s*[\.\-\/]/', $candidate)) continue;
                if (str_contains($candidate, '@')) continue;
                if (preg_match('/\d{5}\s+[A-ZÄÖÜa-zäöü]/', $candidate)) continue;
                if (preg_match('/^(Rektor|Präsident|Kanzler|Prorektor|Vizepräsident)/i', $candidate)) continue;
                if (preg_match('/^\(/', $candidate)) continue;
                if (str_starts_with($candidate, 'Hochschulrektorenkonferenz')) continue;
                if (preg_match('/,\s*\d{5}\s+/', $candidate)) continue;
                if (mb_strlen($candidate) > 120) continue;

                $name = $candidate;
                break;
            }

            if ($name !== null) {
                $host = parse_url($url, PHP_URL_HOST);
                $domain = preg_replace('/^www\./i', '', strtolower((string) $host));
                $results[] = ['name' => $name, 'url' => $url, 'domain' => $domain];
            }
        }

        // Dedupe by domain
        $seen = [];
        $unique = [];
        foreach ($results as $r) {
            if (isset($seen[$r['domain']])) continue;
            $seen[$r['domain']] = true;
            $unique[] = $r;
        }
        return $unique;
    }

    /**
     * DB'deki üniversiteleri HRK listesiyle fuzzy match.
     *
     * @param  list<array{name: string, domain: string}>  $hrk
     * @return list<array{db_id: mixed, db_name: string, hrk: string, domain: string, score: float}>
     */
    public function matchUniversities(array $hrk): array
    {
        $hrkNormalized = [];
        foreach ($hrk as $h) {
            $n = $this->normalizeName($h['name']);
            $hrkNormalized[] = ['hrk' => $h, 'tokens' => $n['tokens'], 'norm' => $n['normalized']];
        }

        $matches = [];
        $dbUnis = University::query()->where('is_active', true)->get(['id', 'name']);

        foreach ($dbUnis as $dbUni) {
            $dbNorm = $this->normalizeName($dbUni->name);
            $bestScore = 0.0;
            $bestHrk = null;

            foreach ($hrkNormalized as $hn) {
                if ($dbNorm['normalized'] === $hn['norm']) {
                    $bestScore = 1.0;
                    $bestHrk = $hn['hrk'];
                    break;
                }
                $score = $this->jaccard($dbNorm['tokens'], $hn['tokens']);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestHrk = $hn['hrk'];
                }
            }

            if ($bestScore >= 0.5 && $bestHrk) {
                $matches[] = [
                    'db_id'   => $dbUni->id,
                    'db_name' => $dbUni->name,
                    'hrk'     => $bestHrk['name'],
                    'domain'  => $bestHrk['domain'],
                    'score'   => round($bestScore, 2),
                ];
            }
        }

        return $matches;
    }

    /**
     * @return array{normalized: string, tokens: list<string>}
     */
    private function normalizeName(string $name): array
    {
        $n = mb_strtolower($name);
        $n = strtr($n, [
            'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss',
            'ı' => 'i', 'İ' => 'i', 'ç' => 'c', 'ğ' => 'g', 'ş' => 's',
        ]);
        $n = preg_replace('/\buniv\.?\b/', 'universitat', $n);
        $n = preg_replace('/\bfh\b/', 'fachhochschule', $n);
        $n = preg_replace('/\bth\b/', 'technische hochschule', $n);
        $n = preg_replace('/\btu\b/', 'technische universitat', $n);
        $n = preg_replace('/\bhs\b/', 'hochschule', $n);
        $n = str_replace([' university', 'university ', 'university'], 'universitat', $n);
        $n = str_replace(' of applied sciences', '', $n);
        $n = str_replace(' applied sciences', '', $n);
        $n = preg_replace('/[\*\.\-,\(\)\/]/', ' ', $n);
        $n = preg_replace('/\s+/', ' ', trim($n));

        $stop = [
            'fur', 'fuer', 'und', 'der', 'die', 'das', 'des', 'in', 'an',
            'am', 'zu', 'zur', 'of', 'the', 'university', 'universitat',
            'hochschule', 'fachhochschule', 'technische',
        ];
        $tokens = array_values(array_filter(
            explode(' ', $n),
            fn ($t) => mb_strlen($t) >= 2 && ! in_array($t, $stop, true)
        ));

        return ['normalized' => $n, 'tokens' => $tokens];
    }

    private function jaccard(array $a, array $b): float
    {
        if (! $a || ! $b) return 0.0;
        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));
        return $union > 0 ? $intersection / $union : 0.0;
    }
}
