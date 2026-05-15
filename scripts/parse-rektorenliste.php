<?php

/**
 * HRK Rektorenliste PDF parser.
 *
 * Çıktı: storage/app/hrk-universities.json
 *   [
 *     { "name": "...", "url": "...", "domain": "..." },
 *     ...
 *   ]
 *
 * PDF yapısı (her üniversite bloğu):
 *   University Name
 *   Adress, ZIP City
 *   Postfach ..., ZIP City
 *   Tel.: ..., Telefax: ..., Internet: http://...
 *   E-Mail: ...
 *   Rektor / Präsident ...
 *   ...
 */

require __DIR__ . '/../vendor/autoload.php';

$pdfPath = __DIR__ . '/../storage/app/rektorenliste.pdf';
$outPath = __DIR__ . '/../storage/app/hrk-universities.json';

if (!file_exists($pdfPath)) {
    fwrite(STDERR, "PDF bulunamadı: {$pdfPath}\n");
    exit(1);
}

echo "PDF parse ediliyor…\n";
$parser = new Smalot\PdfParser\Parser();
$pdf = $parser->parseFile($pdfPath);
$text = $pdf->getText();
echo "Text length: " . number_format(strlen($text)) . " char\n";

// Her uni bloğu: name satırı + ... Internet: URL satırı pattern'i
// PDF'te uni adı bağımsız satırda, sonra adres + "Internet: URL" gelir
$lines = preg_split('/\r\n|\n|\r/', $text);
echo "Toplam satır: " . count($lines) . "\n";

$results = [];
$pendingName = null;
$lastInternetLineUni = null;

for ($i = 0; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    if ($line === '') continue;

    // "Internet: http://..." satırı yakaladığımızda → önceki uni adı
    if (preg_match('/Internet:\s*(https?:\/\/[^\s,]+)/i', $line, $m)) {
        $url = trim($m[1], '.,;)');

        // Adı geriye doğru ara: ilk "Name" formatlı satır (rakam değil, e-mail değil,
        // adres değil — sadece kurum adı formatında)
        $name = null;
        for ($j = $i - 1; $j >= max(0, $i - 8); $j--) {
            $candidate = trim($lines[$j]);
            if ($candidate === '') continue;
            // Atla: adres (ZIP veya sokak), telefon, mail, "Postfach", "Seite", "Stand"
            if (preg_match('/^\d{5}\s/', $candidate)) continue;            // ZIP code
            if (preg_match('/^(Tel\.?|Telefax|Postfach|E-Mail|Seite|Stand)/i', $candidate)) continue;
            if (preg_match('/^\d+\s*[\.\-\/]/', $candidate)) continue;     // tarih/numara
            if (str_contains($candidate, '@')) continue;
            if (preg_match('/\d{5}\s+[A-ZÄÖÜa-zäöü]/', $candidate)) continue; // adres + ZIP city
            // Sayfa başlığı vs.
            if (preg_match('/^(Rektor|Präsident|Kanzler|Prorektor|Vizepräsident)/i', $candidate)) continue;
            if (preg_match('/^\(/', $candidate)) continue;
            if (str_starts_with($candidate, 'Hochschulrektorenkonferenz')) continue;

            // Adres satırı: "Templergraben 55, 52062 Aachen"
            if (preg_match('/,\s*\d{5}\s+/', $candidate)) continue;

            // Uzun cümleler — uni adları genelde <80 char, çok uzun değil
            if (mb_strlen($candidate) > 120) continue;

            // Bir uni adı bulduk
            $name = $candidate;
            break;
        }

        if ($name !== null) {
            // Domain extract
            $host = parse_url($url, PHP_URL_HOST);
            $domain = preg_replace('/^www\./i', '', strtolower((string) $host));

            $results[] = [
                'name'   => $name,
                'url'    => $url,
                'domain' => $domain,
            ];
        }
    }
}

// Tekil hale getir (aynı domain bir kez)
$seen = [];
$unique = [];
foreach ($results as $r) {
    if (isset($seen[$r['domain']])) continue;
    $seen[$r['domain']] = true;
    $unique[] = $r;
}

echo "Bulunan eşleşme (raw): " . count($results) . "\n";
echo "Unique domain: " . count($unique) . "\n";

file_put_contents($outPath, json_encode($unique, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "✓ Yazıldı: {$outPath}\n";
echo "Boyut: " . round(filesize($outPath) / 1024, 1) . " KB\n";

echo "\n=== İlk 10 örnek ===\n";
foreach (array_slice($unique, 0, 10) as $r) {
    echo "  " . str_pad($r['name'], 60) . " → " . $r['domain'] . "\n";
}
