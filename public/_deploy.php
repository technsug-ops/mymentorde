<?php
/**
 * Server-side deploy webhook — ZIP extract + cache rebuild.
 *
 * Akış (German_Uni pattern'inden adapte):
 *  1. GitHub Actions workflow projeyi tek deploy-bundle.zip'e paketler
 *  2. Bundle + .deploy-pulse FTPS ile /storage/app/'a yüklenir
 *  3. Workflow HTTPS GET → bu dosya çağrılır
 *  4. Pulse dosyası varsa ZIP extract edilir + cache temizlenir
 *  5. Pulse yoksa 204 döner (saldırgan tetikleyemez — FTP erişimi gerek)
 *
 * Standalone PHP — Laravel bootstrap'e bağımlı değil. Vendor değişirken
 * autoloader uyumsuzluğu yaşansa bile çalışır.
 *
 * Endpoint: https://panel.mentorde.com/_deploy.php
 * HTTP codes: 200 success, 204 no pulse, 429 rate limited, 500 extract error
 */

declare(strict_types=1);

// ── PHP runtime defansif ayar ────────────────────────────────────────
@ini_set('memory_limit',       '256M');  // ZIP extract için
@ini_set('max_execution_time', '180');   // 3 dk — composer dump + cache rebuild
@ignore_user_abort(true);                // curl tarafı timeout olsa bile devam

// ── Config ───────────────────────────────────────────────────────────
$projectRoot   = dirname(__DIR__);                  // .../mentorde/
$bundlePath    = $projectRoot . '/storage/app/deploy-bundle.zip';
$pulsePath     = $projectRoot . '/storage/app/.deploy-pulse';
$lockPath      = $projectRoot . '/storage/app/.deploy-lock';
$logPath       = $projectRoot . '/storage/logs/deploy-webhook.log';
$maintenance   = $projectRoot . '/storage/framework/maintenance.php';
$pulseMaxAgeS  = 600;   // 10 dk'dan eski pulse'u görmezden gel
$lockTtlS      = 300;   // 5 dk lock TTL (eski lock'u temizle)

// ── Logging helper ───────────────────────────────────────────────────
$logFn = function (string $level, string $msg) use ($logPath): void {
    @mkdir(dirname($logPath), 0775, true);
    @file_put_contents(
        $logPath,
        '[' . date('Y-m-d H:i:s') . "] {$level}: {$msg}\n",
        FILE_APPEND
    );
};

// ── Sadece GET ───────────────────────────────────────────────────────
header('Content-Type: text/plain; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

// ── 1. Pulse kontrolü — saldırı yüzey azaltma ────────────────────────
if (!is_file($pulsePath)) {
    http_response_code(204);
    echo "No deploy pulse found";
    exit;
}

$pulseAge = time() - (int) filemtime($pulsePath);
if ($pulseAge > $pulseMaxAgeS) {
    http_response_code(204);
    echo "Stale pulse ({$pulseAge}s old) — ignored";
    @unlink($pulsePath);
    exit;
}

// ── 2. ZIP var mı? ───────────────────────────────────────────────────
if (!is_file($bundlePath)) {
    http_response_code(500);
    $logFn('ERROR', "Bundle ZIP not found at {$bundlePath}");
    echo "Bundle not found — FTP upload may have failed";
    exit;
}

// ── 3. Lock — paralel deploy'u önle ──────────────────────────────────
if (is_file($lockPath)) {
    $lockAge = time() - (int) filemtime($lockPath);
    if ($lockAge < $lockTtlS) {
        http_response_code(429);
        echo "Deploy already in progress (lock age {$lockAge}s)";
        exit;
    }
    @unlink($lockPath); // stale lock
}
@touch($lockPath);

// ── 4. Maintenance mode ON (extract sırasında 503 dön) ──────────────
$maintenanceContent = "<?php\nreturn [\n"
    . "    'except' => ['_deploy/*', '_deploy.php'],\n"
    . "    'redirect' => null,\n"
    . "    'retry' => 15,\n"
    . "    'refresh' => 15,\n"
    . "    'secret' => null,\n"
    . "    'status' => 503,\n"
    . "    'template' => null,\n"
    . "];\n";
@mkdir(dirname($maintenance), 0775, true);
@file_put_contents($maintenance, $maintenanceContent);
$logFn('INFO', 'Maintenance mode ON');

// ── 5. Extract ZIP ───────────────────────────────────────────────────
$zip = new ZipArchive();
if ($zip->open($bundlePath) !== true) {
    @unlink($lockPath);
    @unlink($maintenance);
    http_response_code(500);
    $logFn('ERROR', "ZipArchive::open failed for {$bundlePath}");
    echo "Failed to open ZIP";
    exit;
}

$fileCount = $zip->numFiles;
$logFn('INFO', "Extracting {$fileCount} files from bundle");

if (!$zip->extractTo($projectRoot)) {
    $zip->close();
    @unlink($lockPath);
    @unlink($maintenance);
    http_response_code(500);
    $logFn('ERROR', 'extractTo failed');
    echo "Extract failed";
    exit;
}
$zip->close();
$logFn('INFO', "Extracted {$fileCount} files successfully");

// ── 5b. PHP 8.4 CLI binary tespiti ──────────────────────────────────
// KAS'ta web subdomain PHP 8.4 ama exec('php ...') sistem default CLI'ı
// (PHP 7.4.33) çağırıyor → composer/artisan "requires >= 8.4.0" ile patlıyor.
// Versiyonlu binary adlarını sırayla dene; bulamazsan bu script'in kendi
// binary'sini (PHP_BINARY — webhook 8.4 altında çalışıyor) kullan.
$phpBin = 'php';
$phpCands = ['php8.4', 'php-8.4', 'php84', '/usr/bin/php8.4', '/usr/local/bin/php8.4'];
if (defined('PHP_BINARY') && PHP_BINARY) {
    $phpCands[] = PHP_BINARY;
}
foreach ($phpCands as $cand) {
    $vOut = []; $vCode = 1;
    @exec(escapeshellarg($cand) . ' -v 2>&1', $vOut, $vCode);
    if ($vCode === 0 && preg_match('/PHP\s+(\d+)\.(\d+)/', implode("\n", $vOut), $m) && (int) $m[1] >= 8) {
        $phpBin = $cand;
        break;
    }
}
$logFn('INFO', "PHP CLI binary: {$phpBin}");

// ── 6. Composer autoload refresh (yeni class'lar için) ──────────────
$composerOk = false;
$composerOut = [];
if (is_file($projectRoot . '/composer.phar')) {
    @exec(
        'cd ' . escapeshellarg($projectRoot)
        . ' && ' . escapeshellarg($phpBin) . ' composer.phar dump-autoload --optimize --no-dev 2>&1',
        $composerOut,
        $composerCode
    );
    $composerOk = ($composerCode === 0);
} else {
    // composer binary olmadan vendor/composer/autoload_classmap.php zaten
    // ZIP'le geldiği için yeni classlar tanınır. composer dump skip.
    $composerOk = true;
    $composerOut = ['skipped — composer.phar not present (classmap from ZIP)'];
}
$logFn($composerOk ? 'INFO' : 'WARN', 'Composer autoload: ' . implode(' | ', $composerOut));

// ── 7. Laravel cache rebuild ────────────────────────────────────────
$artisanCmds = [
    'view:clear',
    'route:clear',
    'config:clear',
    'cache:clear',
    'event:clear',
    'optimize',  // route+config+view cache rebuild
];

$cacheResults = [];
foreach ($artisanCmds as $cmd) {
    $out = [];
    @exec(
        'cd ' . escapeshellarg($projectRoot)
        . ' && ' . escapeshellarg($phpBin) . ' artisan ' . escapeshellarg($cmd) . ' 2>&1',
        $out,
        $code
    );
    $cacheResults[$cmd] = ($code === 0) ? 'ok' : 'fail';
    if ($code !== 0) {
        $logFn('WARN', "artisan {$cmd}: " . implode(' | ', $out));
    }
}
$logFn('INFO', 'Artisan cache: ' . json_encode($cacheResults));

// ── 8. Maintenance OFF + lock clear + pulse clear ───────────────────
@unlink($maintenance);
@unlink($lockPath);
@unlink($pulsePath);
$logFn('INFO', 'Maintenance OFF + lock+pulse cleared');

// ── 9. Bundle ZIP'i sil (~50 MB) ────────────────────────────────────
@unlink($bundlePath);

// ── 10. Success ─────────────────────────────────────────────────────
http_response_code(200);
echo "OK — extracted {$fileCount} files, autoload " . ($composerOk ? 'ok' : 'warn')
    . ", cache " . json_encode($cacheResults);
$logFn('INFO', 'Deploy completed successfully');
