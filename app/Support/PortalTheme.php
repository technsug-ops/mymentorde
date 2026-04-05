<?php

namespace App\Support;

use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Schema;

class PortalTheme
{
    /** Request-scope cache: Schema::hasTable() ve DB sorgusunu tekrar çalıştırmaz */
    private static ?array $resolvedCache = null;

    /** Desteklenen font ailesi seçenekleri */
    private static array $fontFamilies = [
        'system'  => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif',
        'inter'   => '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'roboto'  => 'Roboto, -apple-system, "Helvetica Neue", Arial, sans-serif',
        'opensans' => '"Open Sans", -apple-system, "Helvetica Neue", sans-serif',
    ];

    /** Portal adları — font override için */
    public static array $portals = ['student', 'guest', 'senior', 'dealer', 'manager', 'marketing'];

    public static function defaults(): array
    {
        return [
            'brand_primary'       => '#2563EB',
            'brand_secondary'     => '#162C4A',
            'brand_secondary_end' => '#1E3D6B',
            'sidebar_text'        => '#E2E8F0',
            'bg'                  => '#F0F4FA',
            'surface'             => '#ffffff',
            'text'                => '#1E293B',
            'muted'               => '#64748B',
            'line'                => '#E2E8F0',
            'ok'                  => '#10B981',
            'warn'                => '#F59E0B',
            'info'                => '#2563EB',
            'danger'              => '#EF4444',
            'radius'              => '12',
            'font_size'           => '14',
            'font_family'         => 'inter',
            // Per-portal typography (boyut 12-36; font ailesi globalden gelir)
            'font_size_student'   => '13',
            'font_size_guest'     => '13',
            'font_size_senior'    => '14',
            'font_size_dealer'    => '13',
            'font_size_manager'   => '14',
            'font_size_marketing' => '13',
            // Per-portal renk kombinasyonları
            // Tek kaynak: accent — hero ve sidebar bu renkten otomatik türetilir
            'accent_student'    => '#7c3aed',
            'accent_guest'      => '#2563eb',
            'accent_senior'     => '#7c3aed',
            'accent_dealer'     => '#16a34a',
            'accent_manager'    => '#1e40af',
            'accent_marketing'  => '#7c3aed',
            // Per-portal hero gradient koyuluk yüzdesi (0=düz renk, 100=çok koyu)
            'hero_darkness_student'    => '22',
            'hero_darkness_guest'      => '22',
            'hero_darkness_senior'     => '22',
            'hero_darkness_dealer'     => '22',
            'hero_darkness_manager'    => '22',
            'hero_darkness_marketing'  => '22',
        ];
    }

    public static function resolve(): array
    {
        if (self::$resolvedCache !== null) {
            return self::$resolvedCache;
        }

        $theme = self::defaults();
        if (!Schema::hasTable('marketing_admin_settings')) {
            return self::$resolvedCache = $theme;
        }

        $keys = [
            'theme_brand_primary'       => 'brand_primary',
            'theme_brand_secondary'     => 'brand_secondary',
            'theme_brand_secondary_end' => 'brand_secondary_end',
            'theme_sidebar_text'        => 'sidebar_text',
            'theme_bg'                  => 'bg',
            'theme_surface'             => 'surface',
            'theme_text'                => 'text',
            'theme_muted'               => 'muted',
            'theme_line'                => 'line',
            'theme_ok'                  => 'ok',
            'theme_warn'                => 'warn',
            'theme_info'                => 'info',
            'theme_danger'              => 'danger',
            'theme_radius'              => 'radius',
            'theme_font_size'           => 'font_size',
            'theme_font_family'         => 'font_family',
            // Backward-compatible keys from settings page.
            'brand_primary'   => 'brand_primary',
            'brand_secondary' => 'brand_secondary',
        ];
        // Per-portal font + color + hero darkness keys
        foreach (self::$portals as $p) {
            $keys["theme_font_size_{$p}"]    = "font_size_{$p}";
            $keys["theme_accent_{$p}"]       = "accent_{$p}";
            $keys["theme_hero_darkness_{$p}"] = "hero_darkness_{$p}";
        }

        $rows = MarketingAdminSetting::query()
            ->whereIn('setting_key', array_keys($keys))
            ->get(['setting_key', 'setting_value']);

        foreach ($rows as $row) {
            $target = $keys[$row->setting_key] ?? null;
            if (!$target) {
                continue;
            }
            $raw = data_get($row->setting_value, 'value');
            if ($raw === null || $raw === '') {
                continue;
            }
            $theme[$target] = (string) $raw;
        }

        $globalColors = ['brand_primary','brand_secondary','brand_secondary_end','sidebar_text','bg','surface','text','muted','line','ok','warn','info','danger'];
        foreach ($globalColors as $colorKey) {
            $theme[$colorKey] = self::sanitizeColor($theme[$colorKey], self::defaults()[$colorKey]);
        }
        // Per-portal color sanitization (accent only)
        foreach (self::$portals as $p) {
            $k = "accent_{$p}";
            $theme[$k] = self::sanitizeColor($theme[$k] ?? '', self::defaults()[$k]);
        }

        $radius = (int) ($theme['radius'] ?? 12);
        $theme['radius'] = (string) max(8, min(20, $radius));

        $fontSize = (int) ($theme['font_size'] ?? 14);
        $theme['font_size'] = (string) max(12, min(16, $fontSize));

        if (!array_key_exists($theme['font_family'], self::$fontFamilies)) {
            $theme['font_family'] = 'system';
        }

        // Per-portal font size + hero darkness sanitization
        foreach (self::$portals as $p) {
            $sKey = "font_size_{$p}";
            $theme[$sKey] = (string) max(12, min(36, (int) ($theme[$sKey] ?? 15)));
            $dKey = "hero_darkness_{$p}";
            $theme[$dKey] = (string) max(0, min(80, (int) ($theme[$dKey] ?? 22)));
        }

        return self::$resolvedCache = $theme;
    }

    public static function toCssVars(array $theme): string
    {
        $fontStack = self::$fontFamilies[$theme['font_family'] ?? 'system'] ?? self::$fontFamilies['system'];

        // Badge tint renklerini PHP'de hesapla (color-mix() tarayıcı tutarsızlığını önler,
        // yeşilin yüksek luminansını dengelemek için ok badge'inde özel oran kullanılır).
        $badgeOkBg     = self::hexMix($theme['ok'],     '#ffffff', 0.12); // ok için daha açık tint
        $badgeOkFg     = self::hexMix($theme['ok'],     '#000000', 0.62); // ok için daha az yoğun metin
        $badgeWarnBg   = self::hexMix($theme['warn'],   '#ffffff', 0.14);
        $badgeWarnFg   = self::hexMix($theme['warn'],   '#000000', 0.72);
        $badgeDangerBg = self::hexMix($theme['danger'], '#ffffff', 0.14);
        $badgeDangerFg = self::hexMix($theme['danger'], '#000000', 0.72);
        $badgeInfoBg   = self::hexMix($theme['info'],   '#ffffff', 0.14);
        $badgeInfoFg   = self::hexMix($theme['info'],   '#000000', 0.72);

        $raw = implode('', [
            '--theme-brand-primary: '.$theme['brand_primary'].';',
            '--theme-brand-secondary: '.$theme['brand_secondary'].';',
            '--theme-brand-secondary-end: '.$theme['brand_secondary_end'].';',
            '--theme-sidebar-text: '.$theme['sidebar_text'].';',
            '--theme-bg: '.$theme['bg'].';',
            '--theme-surface: '.$theme['surface'].';',
            '--theme-text: '.$theme['text'].';',
            '--theme-muted: '.$theme['muted'].';',
            '--theme-line: '.$theme['line'].';',
            '--theme-ok: '.$theme['ok'].';',
            '--theme-warn: '.$theme['warn'].';',
            '--theme-info: '.$theme['info'].';',
            '--theme-danger: '.$theme['danger'].';',
            '--theme-radius: '.$theme['radius'].'px;',
            '--theme-font-size-px: '.$theme['font_size'].'px;',
            '--theme-font-family-stack: '.$fontStack.';',
            '--badge-ok-bg: '.$badgeOkBg.';',
            '--badge-ok-fg: '.$badgeOkFg.';',
            '--badge-warn-bg: '.$badgeWarnBg.';',
            '--badge-warn-fg: '.$badgeWarnFg.';',
            '--badge-danger-bg: '.$badgeDangerBg.';',
            '--badge-danger-fg: '.$badgeDangerFg.';',
            '--badge-info-bg: '.$badgeInfoBg.';',
            '--badge-info-fg: '.$badgeInfoFg.';',
        ]);

        // Per-portal CSS vars — tek kaynak: accent
        // hero_from  = accent'in %22'si (koyu versiyon)
        // hero_to    = accent
        // sidebar_from = accent'in %30'u (daha koyu)
        // sidebar_to   = accent'in %45'i (en koyu)
        foreach (self::$portals as $p) {
            $size        = max(12, min(36, (int) ($theme["font_size_{$p}"] ?? 15)));
            $accent      = $theme["accent_{$p}"];
            $darkness    = max(0, min(80, (int) ($theme["hero_darkness_{$p}"] ?? 22))) / 100;
            $heroFrom    = $darkness > 0 ? self::hexMix($accent, '#000000', $darkness) : $accent;
            $heroTo      = $accent;
            $sidebarFrom = self::hexMix($accent, '#000000', 0.30);
            $sidebarTo   = self::hexMix($accent, '#000000', 0.45);
            $raw .= "--theme-font-size-{$p}:{$size}px;";
            $raw .= "--theme-font-family-{$p}:{$fontStack};";
            $raw .= "--theme-accent-{$p}:{$accent};";
            $raw .= "--theme-hero-from-{$p}:{$heroFrom};";
            $raw .= "--theme-hero-to-{$p}:{$heroTo};";
            $raw .= "--theme-sidebar-from-{$p}:{$sidebarFrom};";
            $raw .= "--theme-sidebar-to-{$p}:{$sidebarTo};";
        }

        // </style><script> injection'ını önle: açı parantezleri CSS değerinde yer almaz.
        return str_replace(['<', '>'], '', $raw);
    }

    /**
     * İki hex rengi belirtilen oran ile karıştırır.
     * mix($hex, $base, $ratio): ($ratio * $hex) + ((1-$ratio) * $base)
     */
    public static function hexMix(string $hex, string $base, float $ratio): string
    {
        [$hr, $hg, $hb] = self::hexToRgb($hex);
        [$br, $bg, $bb] = self::hexToRgb($base);

        $r = (int) round($hr * $ratio + $br * (1.0 - $ratio));
        $g = (int) round($hg * $ratio + $bg * (1.0 - $ratio));
        $b = (int) round($hb * $ratio + $bb * (1.0 - $ratio));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /** Hex rengi [R, G, B] dizisine dönüştürür. Geçersiz input için siyah döner. */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) !== 6) {
            return [0, 0, 0];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function persist(array $input, ?int $userId): void
    {
        self::$resolvedCache = null; // temayı yeniden çözmek için önbelleği sıfırla
        $defaults = self::defaults();
        $map = [
            'brand_primary'       => 'theme_brand_primary',
            'brand_secondary'     => 'theme_brand_secondary',
            'brand_secondary_end' => 'theme_brand_secondary_end',
            'sidebar_text'        => 'theme_sidebar_text',
            'bg'                  => 'theme_bg',
            'surface'             => 'theme_surface',
            'text'                => 'theme_text',
            'muted'               => 'theme_muted',
            'line'                => 'theme_line',
            'ok'                  => 'theme_ok',
            'warn'                => 'theme_warn',
            'info'                => 'theme_info',
            'danger'              => 'theme_danger',
            'radius'              => 'theme_radius',
            'font_size'           => 'theme_font_size',
            'font_family'         => 'theme_font_family',
        ];

        foreach ($map as $inputKey => $settingKey) {
            $value = $input[$inputKey] ?? $defaults[$inputKey];

            if ($inputKey === 'radius') {
                $value = (string) max(8, min(20, (int) $value));
            } elseif ($inputKey === 'font_size') {
                $value = (string) max(12, min(16, (int) $value));
            } elseif ($inputKey === 'font_family') {
                $value = array_key_exists($value, self::$fontFamilies) ? $value : 'system';
            } else {
                $value = self::sanitizeColor((string) $value, $defaults[$inputKey]);
            }

            MarketingAdminSetting::query()->updateOrCreate(
                ['setting_key' => $settingKey],
                [
                    'setting_value'       => ['value' => (string) $value],
                    'updated_by_user_id'  => $userId,
                ]
            );
        }

        // Per-portal: font size + accent + hero darkness (sidebar/hero auto-derived, font_family global)
        foreach (self::$portals as $p) {
            $sKey = "font_size_{$p}";
            $sz = (string) max(12, min(36, (int) ($input[$sKey] ?? $defaults[$sKey])));
            MarketingAdminSetting::query()->updateOrCreate(
                ['setting_key' => "theme_{$sKey}"],
                ['setting_value' => ['value' => $sz], 'updated_by_user_id' => $userId]
            );
            $accentKey = "accent_{$p}";
            $accentVal = self::sanitizeColor((string) ($input[$accentKey] ?? $defaults[$accentKey]), $defaults[$accentKey]);
            MarketingAdminSetting::query()->updateOrCreate(
                ['setting_key' => "theme_{$accentKey}"],
                ['setting_value' => ['value' => $accentVal], 'updated_by_user_id' => $userId]
            );
            $dKey = "hero_darkness_{$p}";
            $dVal = (string) max(0, min(80, (int) ($input[$dKey] ?? $defaults[$dKey])));
            MarketingAdminSetting::query()->updateOrCreate(
                ['setting_key' => "theme_{$dKey}"],
                ['setting_value' => ['value' => $dVal], 'updated_by_user_id' => $userId]
            );
        }

        // Backward compatibility with existing marketing settings page.
        $compatPrimary   = self::sanitizeColor((string) ($input['brand_primary']   ?? $defaults['brand_primary']),   $defaults['brand_primary']);
        $compatSecondary = self::sanitizeColor((string) ($input['brand_secondary'] ?? $defaults['brand_secondary']), $defaults['brand_secondary']);
        MarketingAdminSetting::query()->updateOrCreate(
            ['setting_key' => 'brand_primary'],
            ['setting_value' => ['value' => $compatPrimary], 'updated_by_user_id' => $userId]
        );
        MarketingAdminSetting::query()->updateOrCreate(
            ['setting_key' => 'brand_secondary'],
            ['setting_value' => ['value' => $compatSecondary], 'updated_by_user_id' => $userId]
        );
    }

    /** Desteklenen font ailesi anahtarlarını döndürür */
    public static function fontFamilyOptions(): array
    {
        return [
            'system'   => 'System UI (Varsayılan)',
            'inter'    => 'Inter',
            'roboto'   => 'Roboto',
            'opensans' => 'Open Sans',
        ];
    }

    private static function sanitizeColor(string $color, string $fallback): string
    {
        $color = trim($color);
        return preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color) ? $color : $fallback;
    }
}
