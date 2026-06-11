<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Dinamik Open Graph image generator — GD library ile pure PHP render.
 *
 * Endpoint'ler:
 *   GET /og/brand.png      → Generic MentorDE branded OG image (1200x630)
 *   GET /og/promo.png      → İndirim kodu için "Özel İndirim" branded image
 *   GET /og/unimatch.png   → UniMatch wizard için "Uni programı bul" image
 *
 * Browsershot/Chrome gerekmez — sadece native GD library yeterli.
 * Sonuc 7 gun cache (HTTP cache header), Laravel cache ile in-memory deduplikasyon.
 */
class OgImageController extends Controller
{
    private const WIDTH  = 1200;
    private const HEIGHT = 630;

    public function brand()
    {
        return $this->render('brand', 'MentorDE', 'Almanya\'da egitim danismanligi', '#7e58bf', '#1d4ed8');
    }

    public function promo()
    {
        return $this->render('promo', 'Ozel Indirim', 'MentorDE ile basla, indirim kazan', '#dc2626', '#7c3aed');
    }

    public function unimatch()
    {
        return $this->render('unimatch', 'UniMatch', 'Sana uygun Almanya programini bul', '#0891b2', '#16a34a');
    }

    private function render(string $key, string $title, string $subtitle, string $colorA, string $colorB): Response
    {
        $cacheKey = "og_image_v2_{$key}";
        $png = Cache::remember($cacheKey, now()->addDays(7), function () use ($title, $subtitle, $colorA, $colorB) {
            return $this->renderPng($title, $subtitle, $colorA, $colorB);
        });

        return response($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=604800, immutable', // 7 gun
        ]);
    }

    private function renderPng(string $title, string $subtitle, string $colorA, string $colorB): string
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        // Gradient arka plan (vertikal interpolation A -> B)
        [$ar, $ag, $ab] = $this->hexToRgb($colorA);
        [$br, $bg, $bb] = $this->hexToRgb($colorB);

        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t  = $y / self::HEIGHT;
            $r  = (int) ($ar + ($br - $ar) * $t);
            $g  = (int) ($ag + ($bg - $ag) * $t);
            $b  = (int) ($ab + ($bb - $ab) * $t);
            $col = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::WIDTH, $y, $col);
        }

        // Sol alta dekoratif daire (transparan beyaz)
        $accent = imagecolorallocatealpha($img, 255, 255, 255, 110);
        imagefilledellipse($img, 100, self::HEIGHT - 80, 360, 360, $accent);

        // Sag uste dekoratif kucuk dort daire
        for ($i = 0; $i < 4; $i++) {
            $alpha = 100 + ($i * 8);
            $c     = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
            imagefilledellipse($img, self::WIDTH - 120 - ($i * 40), 100 + ($i * 30), 80 - ($i * 10), 80 - ($i * 10), $c);
        }

        $white     = imagecolorallocate($img, 255, 255, 255);
        $whiteSoft = imagecolorallocatealpha($img, 255, 255, 255, 50);

        // Brand badge (sol ust)
        $badgeColor = imagecolorallocatealpha($img, 255, 255, 255, 100);
        imagefilledrectangle($img, 60, 60, 280, 100, $badgeColor);
        imagestring($img, 5, 80, 72, 'MENTORDE.COM', $white);

        // Title
        $this->drawCenteredText($img, $title, self::WIDTH / 2, self::HEIGHT / 2 - 30, $white, 'large');

        // Subtitle
        $this->drawCenteredText($img, $subtitle, self::WIDTH / 2, self::HEIGHT / 2 + 50, $whiteSoft, 'medium');

        // Bottom bar
        $bottomBar = imagecolorallocatealpha($img, 0, 0, 0, 100);
        imagefilledrectangle($img, 0, self::HEIGHT - 60, self::WIDTH, self::HEIGHT, $bottomBar);
        imagestring($img, 4, 60, self::HEIGHT - 40, 'panel.mentorde.com', $white);

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return $data ?: '';
    }

    private function drawCenteredText($img, string $text, int $x, int $y, int $color, string $size = 'medium'): void
    {
        // GD built-in fontlar — boyut 5 max, custom font olmadan
        $fontSize = $size === 'large' ? 5 : 4;
        $charWidth  = imagefontwidth($fontSize);
        $charHeight = imagefontheight($fontSize);
        $textWidth  = strlen($text) * $charWidth;
        $textHeight = $charHeight;

        // Buyuk title icin scale up
        if ($size === 'large') {
            // Karakter karakter buyuk goster
            $scale = 4;
            $temp = imagecreatetruecolor($textWidth + 20, $textHeight + 10);
            $transparent = imagecolorallocatealpha($temp, 0, 0, 0, 127);
            imagecolortransparent($temp, $transparent);
            imagefill($temp, 0, 0, $transparent);
            imagestring($temp, $fontSize, 10, 5, $text, $color);
            $resized = imagescale($temp, ($textWidth + 20) * $scale, ($textHeight + 10) * $scale);
            $rw = imagesx($resized);
            $rh = imagesy($resized);
            imagecopy($img, $resized, $x - $rw / 2, $y - $rh / 2, 0, 0, $rw, $rh);
            imagedestroy($temp);
            imagedestroy($resized);
        } else {
            $scale = 2;
            $temp = imagecreatetruecolor($textWidth + 20, $textHeight + 10);
            $transparent = imagecolorallocatealpha($temp, 0, 0, 0, 127);
            imagecolortransparent($temp, $transparent);
            imagefill($temp, 0, 0, $transparent);
            imagestring($temp, $fontSize, 10, 5, $text, $color);
            $resized = imagescale($temp, ($textWidth + 20) * $scale, ($textHeight + 10) * $scale);
            $rw = imagesx($resized);
            $rh = imagesy($resized);
            imagecopy($img, $resized, $x - $rw / 2, $y - $rh / 2, 0, 0, $rw, $rh);
            imagedestroy($temp);
            imagedestroy($resized);
        }
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
