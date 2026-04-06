<?php
// Laravel session testi — middleware bypass
require __DIR__.'/../vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Normal request oluştur ve middleware pipeline'dan geçir
$request = Illuminate\Http\Request::capture();

try {
    $response = $kernel->handle($request);

    // Response header'larını göster
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== Laravel Session Test ===\n\n";
    echo "Response Status: " . $response->getStatusCode() . "\n\n";

    echo "Response Headers:\n";
    foreach ($response->headers->all() as $key => $values) {
        foreach ($values as $v) {
            echo "  {$key}: {$v}\n";
        }
    }

    echo "\nSet-Cookie headers:\n";
    $cookies = $response->headers->get('set-cookie', null, false);
    if (empty($cookies)) {
        echo "  NONE! <-- THIS IS THE PROBLEM\n";
    } else {
        foreach ((array)$cookies as $c) {
            echo "  {$c}\n";
        }
    }

    // Session info
    echo "\nSession:\n";
    if ($request->hasSession()) {
        $session = $request->session();
        echo "  ID: " . $session->getId() . "\n";
        echo "  Name: " . $session->getName() . "\n";
        echo "  Driver: " . get_class($session->getHandler()) . "\n";
    } else {
        echo "  NO SESSION ON REQUEST\n";
    }

    echo "\nConfig:\n";
    echo "  session.driver: " . config('session.driver') . "\n";
    echo "  session.cookie: " . config('session.cookie') . "\n";
    echo "  session.domain: " . var_export(config('session.domain'), true) . "\n";
    echo "  session.secure: " . var_export(config('session.secure'), true) . "\n";
    echo "  session.same_site: " . config('session.same_site') . "\n";
    echo "  session.http_only: " . var_export(config('session.http_only'), true) . "\n";
    echo "  session.path: " . config('session.path') . "\n";
    echo "  app.url: " . config('app.url') . "\n";

} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
