<?php
// GEÇİCİ DEBUG — sorun çözülünce SİL
header('Content-Type: text/plain; charset=utf-8');

echo "=== MentorDE Session Debug ===\n\n";

echo "SERVER:\n";
echo "  HTTPS: " . ($_SERVER['HTTPS'] ?? 'NOT SET') . "\n";
echo "  X-Forwarded-Proto: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'NOT SET') . "\n";
echo "  SERVER_PORT: " . ($_SERVER['SERVER_PORT'] ?? '?') . "\n";
echo "  REQUEST_SCHEME: " . ($_SERVER['REQUEST_SCHEME'] ?? 'NOT SET') . "\n";
echo "  HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? '?') . "\n";
echo "  SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? '?') . "\n";

echo "\nPHP SESSION:\n";
echo "  session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "  session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "  session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
echo "  session.save_path: " . ini_get('session.save_path') . "\n";

echo "\nCOOKIES RECEIVED:\n";
foreach ($_COOKIE as $k => $v) {
    echo "  {$k}: " . substr($v, 0, 20) . "...\n";
}

echo "\nENV CHECK:\n";
$env = @file_get_contents(dirname(__DIR__) . '/.env');
if ($env) {
    foreach (['APP_ENV', 'APP_URL', 'SESSION_DRIVER', 'SESSION_DOMAIN', 'SESSION_SECURE_COOKIE', 'SESSION_PATH', 'DB_CONNECTION', 'DB_HOST'] as $key) {
        preg_match("/^{$key}=(.*)$/m", $env, $m);
        echo "  {$key}: " . trim($m[1] ?? 'NOT FOUND') . "\n";
    }
} else {
    echo "  .env okunamadi!\n";
}

echo "\nSTORAGE WRITABLE:\n";
$sessPath = dirname(__DIR__) . '/storage/framework/sessions';
echo "  sessions dir exists: " . (is_dir($sessPath) ? 'YES' : 'NO') . "\n";
echo "  sessions dir writable: " . (is_writable($sessPath) ? 'YES' : 'NO') . "\n";
$testFile = $sessPath . '/_test_' . time();
$ok = @file_put_contents($testFile, 'test');
echo "  write test: " . ($ok ? 'OK' : 'FAIL') . "\n";
if ($ok) @unlink($testFile);
