<?php

/**
 * HRK PDF uni listesi ile DB'deki University tablosunu fuzzy match preview.
 *
 * Eşleştirme stratejisi:
 *  1. Normalize: lowercase, noktalama temizle, kısaltmaları expand et
 *     ("Univ." → "universität", "FH" → "fachhochschule", "TH" → "technische hochschule")
 *  2. Token set: kelimelere böl, stopword'ları at (für, und, der, in, ...)
 *  3. Jaccard similarity (intersection / union) hesapla
 *  4. >= 0.5 threshold → match. En yüksek skorlu eşleşmeyi al.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hrkPath = __DIR__ . '/../storage/app/hrk-universities.json';
if (!file_exists($hrkPath)) {
    fwrite(STDERR, "HRK JSON yok: {$hrkPath}\n");
    exit(1);
}
$hrk = json_decode(file_get_contents($hrkPath), true);
echo "HRK entry: " . count($hrk) . "\n";

$dbUnis = \App\Models\University::query()->where('is_active', true)->get(['id', 'name']);
echo "DB üni: " . $dbUnis->count() . "\n\n";

// ── Normalize fonksiyonu ──────────────────────────────────────
function normalize_uni_name(string $name): array
{
    $n = mb_strtolower($name);
    // TR/DE karakter map
    $n = strtr($n, [
        'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss',
        'ı' => 'i', 'İ' => 'i', 'ç' => 'c', 'ğ' => 'g', 'ş' => 's',
    ]);
    // Kısaltmaları expand
    $n = preg_replace('/\buniv\.?\b/', 'universitat', $n);
    $n = preg_replace('/\bfh\b/', 'fachhochschule', $n);
    $n = preg_replace('/\bth\b/', 'technische hochschule', $n);
    $n = preg_replace('/\btu\b/', 'technische universitat', $n);
    $n = preg_replace('/\bhs\b/', 'hochschule', $n);
    // Çok dilli — bizim DB'de "University of X" şeklinde var, "Universität X" çevir
    $n = str_replace([' university', 'university ', 'university'], 'universitat', $n);
    $n = str_replace(' of applied sciences', '', $n);
    $n = str_replace(' applied sciences', '', $n);
    // Noktalama, *, - temizle
    $n = preg_replace('/[\*\.\-,\(\)\/]/', ' ', $n);
    // Birden fazla boşluk
    $n = preg_replace('/\s+/', ' ', trim($n));

    // Tokenize, stopword filtrele
    $stop = ['fur', 'fuer', 'und', 'der', 'die', 'das', 'des', 'in', 'an',
            'am', 'zu', 'zur', 'of', 'the', 'university', 'universitat',
            'hochschule', 'fachhochschule', 'technische'];
    $tokens = array_filter(explode(' ', $n), fn ($t) => mb_strlen($t) >= 2 && ! in_array($t, $stop, true));

    return ['normalized' => $n, 'tokens' => array_values($tokens)];
}

function jaccard(array $a, array $b): float
{
    if (! $a || ! $b) return 0.0;
    $intersection = count(array_intersect($a, $b));
    $union = count(array_unique(array_merge($a, $b)));
    return $union > 0 ? $intersection / $union : 0.0;
}

// HRK'yı pre-normalize et
$hrkNormalized = [];
foreach ($hrk as $h) {
    $n = normalize_uni_name($h['name']);
    $hrkNormalized[] = ['hrk' => $h, 'tokens' => $n['tokens'], 'norm' => $n['normalized']];
}

// DB iterasyonu, en iyi match'i bul
$matches = [];
$unmatched = [];
foreach ($dbUnis as $dbUni) {
    $dbNorm = normalize_uni_name($dbUni->name);
    $bestScore = 0;
    $bestHrk = null;

    foreach ($hrkNormalized as $hn) {
        // Exact normalized match
        if ($dbNorm['normalized'] === $hn['norm']) {
            $bestScore = 1.0;
            $bestHrk = $hn['hrk'];
            break;
        }
        $score = jaccard($dbNorm['tokens'], $hn['tokens']);
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
    } else {
        $unmatched[] = $dbUni->name;
    }
}

usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

echo "=== ÖZET ===\n";
echo "Match (>=0.5): " . count($matches) . " / " . $dbUnis->count() . "\n";
echo "Unmatched: " . count($unmatched) . "\n\n";

// Skor dağılımı
$buckets = ['1.0' => 0, '0.8-0.99' => 0, '0.6-0.79' => 0, '0.5-0.59' => 0];
foreach ($matches as $m) {
    if ($m['score'] >= 1.0)        $buckets['1.0']++;
    elseif ($m['score'] >= 0.8)    $buckets['0.8-0.99']++;
    elseif ($m['score'] >= 0.6)    $buckets['0.6-0.79']++;
    else                            $buckets['0.5-0.59']++;
}
echo "=== Skor dağılımı ===\n";
foreach ($buckets as $range => $c) echo "  $range : $c\n";

echo "\n=== En iyi 15 match (score=1.0) ===\n";
foreach (array_slice($matches, 0, 15) as $m) {
    echo "  [{$m['score']}] " . str_pad(mb_substr($m['db_name'], 0, 50), 52) . " → " . $m['domain'] . "\n";
}

echo "\n=== Düşük score eşleşmeler (potansiyel hatalı, 0.5-0.7) ===\n";
$low = array_filter($matches, fn ($m) => $m['score'] < 0.7);
foreach (array_slice($low, 0, 10) as $m) {
    echo "  [{$m['score']}] DB: " . mb_substr($m['db_name'], 0, 45) . "\n";
    echo "         HRK: " . mb_substr($m['hrk'], 0, 45) . " → " . $m['domain'] . "\n";
}

echo "\n=== Unmatched (ilk 15) ===\n";
foreach (array_slice($unmatched, 0, 15) as $u) {
    echo "  · " . $u . "\n";
}

// Sonucu kaydet
$outPath = __DIR__ . '/../storage/app/hrk-match-result.json';
file_put_contents($outPath, json_encode([
    'matches'   => $matches,
    'unmatched' => $unmatched,
    'stats'     => [
        'db_total'    => $dbUnis->count(),
        'hrk_total'   => count($hrk),
        'matched'     => count($matches),
        'buckets'     => $buckets,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "\n✓ Detaylı sonuç: {$outPath}\n";
