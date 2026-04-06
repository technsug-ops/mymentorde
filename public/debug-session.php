<?php
// GEÇİCİ DEBUG — sorun çözülünce SİL
header('Content-Type: text/plain; charset=utf-8');

// Test 1: PHP düzeyinde cookie set edilebiliyor mu?
$testOk = setcookie('debug_test', 'works_' . time(), [
    'expires'  => time() + 3600,
    'path'     => '/',
    'secure'   => false,
    'httponly'  => true,
    'samesite'  => 'Lax',
]);
echo "=== MentorDE Cookie Debug ===\n\n";
echo "1. PHP setcookie() result: " . ($testOk ? 'TRUE (header queued)' : 'FALSE (headers already sent?)') . "\n";
echo "   headers_sent: " . (headers_sent($file, $line) ? "YES at {$file}:{$line}" : 'NO') . "\n\n";

// Test 2: Laravel boot + session start
echo "2. Laravel Session Test:\n";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

    // Fake request
    $request = \Illuminate\Http\Request::create('/debug-session.php', 'GET');
    $request->server->set('HTTPS', 'on');

    // Boot app
    $app->instance('request', $request);
    $app->boot();

    echo "   APP_ENV: " . config('app.env') . "\n";
    echo "   SESSION_DRIVER: " . config('session.driver') . "\n";
    echo "   SESSION_DOMAIN: " . var_export(config('session.domain'), true) . "\n";
    echo "   SESSION_SECURE: " . var_export(config('session.secure'), true) . "\n";
    echo "   SESSION_COOKIE: " . config('session.cookie') . "\n";
    echo "   SESSION_PATH: " . config('session.path') . "\n";
    echo "   SESSION_SAMESITE: " . var_export(config('session.same_site'), true) . "\n";
    echo "   SESSION_LIFETIME: " . config('session.lifetime') . "\n";

    // Try to start session manually
    $session = $app->make('session');
    $session->start();
    $session->put('debug_test', 'hello');
    $session->save();

    echo "   Session ID: " . $session->getId() . "\n";
    echo "   Session started: YES\n";
    echo "   Session data: " . json_encode($session->all()) . "\n";

    // Check if DB driver works
    if (config('session.driver') === 'database') {
        try {
            $count = \Illuminate\Support\Facades\DB::table('sessions')->count();
            echo "   DB sessions table rows: {$count}\n";

            // Check table structure
            $columns = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM sessions");
            echo "   DB sessions columns: ";
            foreach ($columns as $col) {
                echo $col->Field . '(' . $col->Type . ') ';
            }
            echo "\n";
        } catch (\Throwable $e) {
            echo "   DB ERROR: " . $e->getMessage() . "\n";
        }
    }

} catch (\Throwable $e) {
    echo "   LARAVEL ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n3. Response Headers (should include Set-Cookie):\n";
foreach (headers_list() as $h) {
    echo "   {$h}\n";
}
