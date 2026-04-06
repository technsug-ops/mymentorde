<?php
// GEÇİCİ DEBUG — sorun çözülünce SİL
header('Content-Type: text/plain; charset=utf-8');
echo "=== MentorDE Debug v3 ===\n\n";

// 1. Laravel log dosyasını oku
echo "1. LARAVEL LOG (son 40 satır):\n";
echo str_repeat('-', 60) . "\n";
$logFile = dirname(__DIR__) . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last = array_slice($lines, -40);
    echo implode('', $last);
} else {
    echo "   laravel.log BULUNAMADI\n";
    // Glob ile herhangi bir log dosyası ara
    $logs = glob(dirname(__DIR__) . '/storage/logs/*.log');
    echo "   Bulunan log dosyaları: " . (empty($logs) ? 'HİÇ YOK' : implode(', ', $logs)) . "\n";
}
echo "\n" . str_repeat('-', 60) . "\n";

// 2. PHP error log
echo "\n2. PHP ERROR LOG:\n";
$phpLog = ini_get('error_log');
echo "   error_log path: " . ($phpLog ?: 'NOT SET') . "\n";
if ($phpLog && file_exists($phpLog)) {
    $lines = file($phpLog);
    $last = array_slice($lines, -20);
    echo implode('', $last);
}

// 3. Bootstrap cache kontrol
echo "\n3. BOOTSTRAP CACHE:\n";
$cacheDir = dirname(__DIR__) . '/bootstrap/cache/';
foreach (glob($cacheDir . '*.php') as $f) {
    echo "   " . basename($f) . " (" . filesize($f) . " bytes)\n";
}

// 4. Middleware kontrol
echo "\n4. KEY FILES CHECK:\n";
$checks = [
    'vendor/autoload.php',
    'vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php',
    'vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php',
    'vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php',
];
foreach ($checks as $f) {
    $full = dirname(__DIR__) . '/' . $f;
    echo "   " . $f . ": " . (file_exists($full) ? 'OK' : 'MISSING!') . "\n";
}

// 5. Composer autoload kontrol
echo "\n5. VENDOR STATUS:\n";
$vendorDir = dirname(__DIR__) . '/vendor';
echo "   vendor/ exists: " . (is_dir($vendorDir) ? 'YES' : 'NO!') . "\n";
$installed = $vendorDir . '/composer/installed.json';
echo "   installed.json exists: " . (file_exists($installed) ? 'YES' : 'NO!') . "\n";

// 6. .env okunabilirlik
echo "\n6. ENV RAW CHECK:\n";
$env = @file_get_contents(dirname(__DIR__) . '/.env');
if ($env) {
    foreach (['APP_ENV', 'APP_DEBUG', 'APP_URL', 'SESSION_DRIVER', 'SESSION_DOMAIN', 'SESSION_SECURE_COOKIE', 'SESSION_COOKIE', 'DB_CONNECTION'] as $key) {
        preg_match("/^{$key}=(.*)$/m", $env, $m);
        echo "   {$key}=" . trim($m[1] ?? 'NOT FOUND') . "\n";
    }
} else {
    echo "   .env OKUNAMADI!\n";
}
