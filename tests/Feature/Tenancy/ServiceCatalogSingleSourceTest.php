<?php

namespace Tests\Feature\Tenancy;

use Tests\TestCase;

/**
 * Katalog tek kapıdan okunmalı — bekçi testi.
 *
 * Paket ve fiyatlar 35 ayrı yerden `config('service_packages...')` ile
 * okunuyordu. Firma bazlı kataloğa geçince bunların hepsi
 * App\Support\ServiceCatalog'a bağlandı.
 *
 * ⚠ Bu test niye var: yeni bir ekran doğrudan config okumaya dönerse orada
 * FABRİKA fiyatı görünür — firmanın kendi fiyatı değil. Ekran hatasız
 * çalıştığı için kimse fark etmez; fark edildiğinde de yanlış tutarla
 * sözleşme imzalanmış olur. Bu yüzden derleme zamanı değil, test zamanı
 * yakalıyoruz.
 *
 * Yeni bir okuma noktası eklemek istiyorsan cevap "testi gevşet" değil:
 * ServiceCatalog'a bir metot ekle.
 */
class ServiceCatalogSingleSourceTest extends TestCase
{
    /** Katalog config'ini okumasına izin verilen tek dosya. */
    private const ALLOWED = 'app/Support/ServiceCatalog.php';

    /** Fiyat/paket taşıyan anahtarlar. `service_categories` yapısal, serbest. */
    private const GUARDED_KEYS = ['service_packages.packages', 'service_packages.extra_services'];

    public function test_katalog_yalnizca_service_catalog_uzerinden_okunuyor(): void
    {
        $root = base_path();
        $offenders = [];

        foreach ([base_path('app'), base_path('resources/views')] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

            foreach ($files as $file) {
                if (! $file->isFile() || ! in_array($file->getExtension(), ['php'], true)) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

                if ($relative === self::ALLOWED) {
                    continue;
                }

                $contents = (string) file_get_contents($file->getPathname());

                foreach (self::GUARDED_KEYS as $key) {
                    if (str_contains($contents, "config('{$key}") || str_contains($contents, "config(\"{$key}")) {
                        $offenders[] = $relative . ' → ' . $key;
                    }
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", array_merge(
            ['Katalog config\'i doğrudan okunuyor. App\Support\ServiceCatalog kullanın:'],
            $offenders
        )));
    }
}
