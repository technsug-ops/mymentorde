<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * PWA icon generator — GD library ile dinamik PNG.
 *
 * Endpoint:
 *   GET /icons/{role}-icon-{size}.png   (role: guest|student|senior, size: 192|512)
 *
 * Mevcut manifest-{role}.json dosyaları bu URL'leri referans veriyor.
 * Brand-renkli daire + accent harf (DE) ile minimal logo.
 * 7 gün HTTP cache + Laravel memo cache.
 */
class PwaIconController extends Controller
{
    private const ROLE_COLORS = [
        'guest'   => ['#7e58bf', '#5a3a8d'], // mor — brandbook
        'student' => ['#1e40af', '#3b5fcc'], // mavi — student portal
        'senior'  => ['#16a34a', '#0d7332'], // yeşil — senior portal
        'manager' => ['#0f172a', '#1e293b'], // koyu — manager portal
        'dealer'  => ['#f59e0b', '#d97706'], // turuncu — dealer portal
    ];

    public function show(string $role, int $size)
    {
        $role = strtolower($role);
        if (!isset(self::ROLE_COLORS[$role])) {
            abort(404);
        }
        if (!in_array($size, [192, 512], true)) {
            abort(404);
        }

        $cacheKey = "pwa_icon_v1_{$role}_{$size}";
        $png = Cache::remember($cacheKey, now()->addDays(30), function () use ($role, $size) {
            return $this->render($role, $size);
        });

        return response($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=2592000, immutable',
        ]);
    }

    private function render(string $role, int $size): string
    {
        [$colorA, $colorB] = self::ROLE_COLORS[$role];
        $accent = (string) config('brand.accent', 'DE');
        $accent = mb_strtoupper(mb_substr($accent, 0, 2));

        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        // Transparent background
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);

        imagealphablending($img, true);

        // Gradient daire (color A → color B)
        [$ar, $ag, $ab] = $this->hexToRgb($colorA);
        [$br, $bg2, $bb] = $this->hexToRgb($colorB);

        // Vertical gradient stripe (yumuşak geçiş)
        for ($y = 0; $y < $size; $y++) {
            $t = $y / max(1, $size - 1);
            $r = (int) round($ar + ($br - $ar) * $t);
            $g = (int) round($ag + ($bg2 - $ag) * $t);
            $b = (int) round($ab + ($bb - $ab) * $t);
            $color = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, 0, $y, $size, $y, $color);
        }

        // Maskable safe area — yuvarlatılmış kare maske
        $radius = (int) ($size * 0.18);
        $this->applyRoundedMask($img, $size, $radius);

        // Accent harf — beyaz, merkez
        $textColor = imagecolorallocate($img, 255, 255, 255);
        $fontSize = (int) ($size * 0.46);

        // Built-in PHP font ile yaklaşık merkez (TTF gerek yok, basit ama net)
        $fontFile = $this->resolveFontPath();
        if ($fontFile && function_exists('imagettftext')) {
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $accent);
            $textW = abs($bbox[2] - $bbox[0]);
            $textH = abs($bbox[7] - $bbox[1]);
            $x = (int) (($size - $textW) / 2 - $bbox[0]);
            $y = (int) (($size - $textH) / 2 + abs($bbox[7]));
            imagettftext($img, $fontSize, 0, $x, $y, $textColor, $fontFile, $accent);
        } else {
            // TTF yoksa fallback — built-in font 5x büyütüldü
            $font = 5;
            $charW = imagefontwidth($font) * strlen($accent);
            $charH = imagefontheight($font);
            $scale = max(2, (int) ($size / 60));
            // Built-in font scale edemediği için 5'i kullan ve repeated draw yerine
            // imagestring center'a koy (kabaca)
            imagestring($img, $font, (int) (($size - $charW * $scale) / 2),
                        (int) (($size - $charH * $scale) / 2), $accent, $textColor);
        }

        ob_start();
        imagepng($img, null, 6);
        $png = ob_get_clean();
        imagedestroy($img);
        return (string) $png;
    }

    private function applyRoundedMask($img, int $size, int $radius): void
    {
        // Köşeleri transparent yap — rounded square mask
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagealphablending($img, false);

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $inCorner = false;
                $dx = 0; $dy = 0;
                if ($x < $radius && $y < $radius) { $dx = $radius - $x; $dy = $radius - $y; $inCorner = true; }
                elseif ($x >= $size - $radius && $y < $radius) { $dx = $x - ($size - $radius); $dy = $radius - $y; $inCorner = true; }
                elseif ($x < $radius && $y >= $size - $radius) { $dx = $radius - $x; $dy = $y - ($size - $radius); $inCorner = true; }
                elseif ($x >= $size - $radius && $y >= $size - $radius) { $dx = $x - ($size - $radius); $dy = $y - ($size - $radius); $inCorner = true; }

                if ($inCorner && ($dx * $dx + $dy * $dy) > $radius * $radius) {
                    imagesetpixel($img, $x, $y, $transparent);
                }
            }
        }
        imagealphablending($img, true);
    }

    private function resolveFontPath(): ?string
    {
        // DOMPdf ile birlikte gelen DejaVuSans-Bold TTF — KAS shared host'ta hazır
        $candidates = [
            public_path('fonts/SpaceGrotesk-Bold.ttf'),
            public_path('fonts/Inter-Bold.ttf'),
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
        ];
        foreach ($candidates as $p) {
            if (is_file($p)) return $p;
        }
        return null;
    }

    /** @return array{0:int,1:int,2:int} */
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
