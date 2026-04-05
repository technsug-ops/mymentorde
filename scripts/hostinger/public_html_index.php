<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Hostinger Deploy - Modified index.php
|--------------------------------------------------------------------------
| Bu dosya public_html/ klasörüne yüklenir.
| Laravel uygulama dosyaları public_html bir üst dizindeki
| "mentorde_app" klasöründe bulunur.
|
| Dizin yapısı:
|   /home/kullanici/mentorde_app/   <- Laravel root
|   /home/kullanici/public_html/    <- Bu dosya buraya gider
|
*/

$appRoot = realpath(__DIR__ . '/../mentorde_app');

if ($appRoot === false) {
    die('HATA: mentorde_app klasörü bulunamadı. Lütfen uygulama dosyalarını public_html ile aynı seviyede "mentorde_app" klasörüne yükleyin.');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appRoot . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appRoot . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appRoot . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
