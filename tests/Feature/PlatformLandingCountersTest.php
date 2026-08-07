<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * /platform tanıtım sayfası — sayaç tutarlılığı bekçisi.
 *
 * Sayfa "7 Portal" ve "33+ Modül" diye rakam veriyor ve aynı rakam dört ayrı
 * yerde tekrarlanıyor (hero şeridi, istatistik bandı, bölüm etiketi, footer).
 * Yeni bir modül eklenip rakamların biri güncellenmediğinde sayfa YANLIŞ
 * BİLGİ vermeye başlıyor ve bu hiçbir hata olarak görünmüyor —
 * `config/public_landings.php` notu bir kez zaten kaymıştı (27+ vs 28+).
 *
 * Bu test sayfanın kendi kartlarını sayıp verdiği rakamla karşılaştırır.
 * Rakamı büyütmek isteyen önce kartı eklemek zorunda.
 */
class PlatformLandingCountersTest extends TestCase
{
    private const VIEW = 'resources/views/public/platform-landing.blade.php';

    private function markup(): string
    {
        return (string) file_get_contents(base_path(self::VIEW));
    }

    /**
     * Yalnızca `#moduller` bölümü.
     *
     * `hl-module` kartı sayfanın başka bölümlerinde de kullanılıyor (firma ağı
     * kabiliyetleri gibi). Tüm sayfayı saymak, modül iddiasını gerçekte
     * karşılamayan bir sayıyla doğrulamak olurdu.
     */
    private function modulesSection(): string
    {
        $markup = $this->markup();
        $start  = strpos($markup, 'id="moduller"');

        $this->assertNotFalse($start, '#moduller bölümü bulunamadı.');

        $end = strpos($markup, '</section>', $start);
        $this->assertNotFalse($end, '#moduller bölümünün sonu bulunamadı.');

        return substr($markup, $start, $end - $start);
    }

    /** Sayfanın iddia ettiği modül sayısı gerçekten var mı? */
    public function test_modul_sayisi_karti_sayisiyla_ayni(): void
    {
        $markup  = $this->markup();
        $section = $this->modulesSection();

        // Sınıf adından sonra boşluk (başka attribute var) ya da `>` (yok)
        // gelir; tek desen ikisini de yakalar. `substr_count` ile ayrı ayrı
        // saymak stilli kartları çift sayardı.
        $flagship = preg_match_all('/class="hl-module"(?:\s|>)/', $section);
        $pills    = preg_match_all('/class="m-pill"(?:\s|>)/', $section);
        $actual   = $flagship + $pills;

        // Sayfa "33+" gibi yazıyor — eşik olarak okunur, kart sayısı bunu karşılamalı.
        preg_match_all('/(\d+)\+\s*(?:Hazır\s+)?Modül/u', $markup, $claims);
        $this->assertNotEmpty($claims[1], 'Sayfada modül sayısı iddiası bulunamadı.');

        foreach (array_unique($claims[1]) as $claimed) {
            $this->assertGreaterThanOrEqual(
                (int) $claimed,
                $actual,
                sprintf(
                    'Sayfa "%d+ modül" diyor ama sayfada %d kart var (%d flagship + %d pill). '
                    . 'Ya kart ekleyin ya rakamı düşürün.',
                    $claimed, $actual, $flagship, $pills
                )
            );
        }
    }

    /** "Tamamlayıcı Modüller (N)" etiketi pill sayısıyla birebir olmalı. */
    public function test_tamamlayici_modul_etiketi_pill_sayisiyla_birebir(): void
    {
        $section = $this->modulesSection();

        $this->assertSame(
            1,
            preg_match('/Tamamlayıcı Modüller \((\d+)\)/u', $section, $m),
            'Tamamlayıcı modül etiketi bulunamadı.'
        );

        $this->assertSame(
            preg_match_all('/class="m-pill"(?:\s|>)/', $section),
            (int) $m[1],
            'Etiketteki sayı ile pill sayısı tutmuyor.'
        );
    }

    /** Portal sayısı iddiası, gerçek portal kartı sayısıyla aynı olmalı. */
    public function test_portal_sayisi_kart_sayisiyla_ayni(): void
    {
        $markup = $this->markup();

        $cards = substr_count($markup, 'class="portal"')
            + substr_count($markup, 'class="portal portal-wide"');

        preg_match_all('/(\d+)\s*Portal Mimarisi/u', $markup, $claims);
        $this->assertNotEmpty($claims[1], 'Portal sayısı iddiası bulunamadı.');

        foreach (array_unique($claims[1]) as $claimed) {
            $this->assertSame(
                $cards,
                (int) $claimed,
                sprintf('Sayfa "%d Portal" diyor ama %d portal kartı var.', $claimed, $cards)
            );
        }
    }

    /** Menüdeki ve sayfadaki iç bağlantıların hedefi gerçekten var mı? */
    public function test_sayfa_ici_baglantilarin_hedefi_var(): void
    {
        $markup = $this->markup();

        preg_match_all('/href="#([a-z0-9-]+)"/i', $markup, $links);

        $missing = [];
        foreach (array_unique($links[1]) as $anchor) {
            if (! str_contains($markup, 'id="' . $anchor . '"')) {
                $missing[] = '#' . $anchor;
            }
        }

        $this->assertSame([], $missing, 'Hedefi olmayan bağlantı: ' . implode(', ', $missing));
    }

    /** Sayfa gerçekten açılıyor mu — ikon adı hatası burada patlar. */
    public function test_sayfa_aciliyor(): void
    {
        $response = $this->get('/platform');

        $response->assertOk();
        $response->assertSee('Kurumsal Partner Firma', false);
        $response->assertSee('Firma Ağı Mimarisi', false);
    }
}
