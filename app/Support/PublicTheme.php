<?php

namespace App\Support;

use App\Models\MarketingAdminSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Public sayfalar (login, /apply, /randevu) için tema paleti.
 *
 * Manager /manager/brand sayfasından preset seçer; tek değişim üç sayfayı
 * birden etkiler. CSS variable'lar blade'lerin :root bloğunda render edilir.
 *
 * Kullanım:
 *   View::composer(['auth.login','apply.create','booking.public.landing'], fn($v) =>
 *       $v->with('publicTheme', PublicTheme::resolve()));
 */
class PublicTheme
{
    public const PRESETS = [
        'mentorde' => 'MentorDE Mor (Varsayılan)',
        'navy'     => 'Navy Blue (Klasik)',
    ];

    /** @return array<string,string> */
    public static function resolve(?int $companyId = null): array
    {
        $cid = $companyId ?? self::currentCompanyId();

        $preset = Cache::remember("public_theme_{$cid}", 300, function () use ($cid): string {
            if (!Schema::hasTable('marketing_admin_settings')) {
                return 'mentorde';
            }
            $v = MarketingAdminSetting::query()
                ->withoutGlobalScopes()
                ->where('company_id', $cid)
                ->where('setting_key', 'public_theme_preset')
                ->value('setting_value');
            $v = is_string($v) ? strtolower(trim($v)) : '';
            return in_array($v, array_keys(self::PRESETS), true) ? $v : 'mentorde';
        });

        return match ($preset) {
            'navy'  => self::navyPalette(),
            default => self::mentordePalette(),
        };
    }

    /** @return array<string,string> */
    private static function mentordePalette(): array
    {
        return [
            'preset'             => 'mentorde',
            'primary'            => '#5b2e91',
            'primary_dark'       => '#4a2377',
            'primary_deep'       => '#3d1c67',
            'primary_soft'       => '#f1e8fb',
            'accent'             => '#e8b931',
            'accent_dark'        => '#c99c26',
            'text'               => '#12233a',
            'muted'              => '#5e7187',
            'line'               => '#e4d9f2',
            'line_strong'        => '#d3c1ea',
            // Body background radial+linear katmanlar
            'body_bg_r1'         => '#ebe0fa',
            'body_bg_r2'         => '#fff3d6',
            'body_bg_lin1'       => '#f7f3ff',
            'body_bg_lin2'       => '#fff8e8',
            // Sol brand panel gradient
            'brand_gradient_1'   => 'rgba(91,46,145,.97)',
            'brand_gradient_2'   => 'rgba(61,28,103,.98)',
            'brand_fallback'     => '#3d1c67',
            // Brand panel iç metin
            'brand_text_soft'    => '#e4d4f8',
            'brand_text_step_t'  => '#f3e8ff',
            'brand_text_step_s'  => '#c8a9e8',
            'brand_step_badge'   => '#e8b931',       // accent
            'brand_step_badge_t' => '#3d1c67',       // primary-deep
            // Focus shadow
            'focus_shadow_rgb'   => '91,46,145',
            // Submit button shadow rgba
            'submit_shadow'      => 'rgba(91,46,145,.24)',
        ];
    }

    /** @return array<string,string> */
    private static function navyPalette(): array
    {
        return [
            'preset'             => 'navy',
            'primary'            => '#1f66d1',
            'primary_dark'       => '#1149a8',
            'primary_deep'       => '#132f59',
            'primary_soft'       => '#dce9ff',
            'accent'             => '#f59e0b',
            'accent_dark'        => '#d97706',
            'text'               => '#11243d',
            'muted'              => '#5f7392',
            'line'               => '#d8e2f0',
            'line_strong'        => '#c6d5ea',
            'body_bg_r1'         => '#dce9ff',
            'body_bg_r2'         => '#e6f2ff',
            'body_bg_lin1'       => '#ecf2fb',
            'body_bg_lin2'       => '#f7faff',
            'brand_gradient_1'   => 'rgba(19,47,89,.98)',
            'brand_gradient_2'   => 'rgba(14,32,64,.97)',
            'brand_fallback'     => '#0e2040',
            'brand_text_soft'    => '#d2e4fa',
            'brand_text_step_t'  => '#e8f2ff',
            'brand_text_step_s'  => '#9db8da',
            'brand_step_badge'   => 'rgba(255,255,255,.12)',
            'brand_step_badge_t' => '#c8dbfb',
            'focus_shadow_rgb'   => '31,102,209',
            'submit_shadow'      => 'rgba(31,102,209,.22)',
        ];
    }

    private static function currentCompanyId(): int
    {
        if (app()->bound('current_company_id')) {
            return (int) app('current_company_id');
        }
        return (int) (auth()->user()?->company_id ?? 0);
    }

    public static function flushCache(int $companyId = 0): void
    {
        Cache::forget("public_theme_{$companyId}");
    }
}
