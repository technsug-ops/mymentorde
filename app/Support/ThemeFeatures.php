<?php

namespace App\Support;

use App\Models\MarketingAdminSetting;

/**
 * Tema modu izinleri — manager kontrolünde feature flag'leri.
 *
 * Default: ikisi de ON (geriye dönük uyumlu — eski kurulumlarda kayıt yoksa açık)
 * Manager'dan KAPATILIRSA o portal'in tüm kullanıcılarında ilgili toggle gizlenir,
 * mevcut localStorage tercihi de override edilip light/standart mode'a zorlanır.
 */
class ThemeFeatures
{
    public static function darkAllowed(): bool
    {
        try {
            return MarketingAdminSetting::getValue('theme_dark_mode_allowed', '1') === '1';
        } catch (\Throwable) {
            return true; // tablosu yoksa fail-open
        }
    }

    public static function minimalistAllowed(): bool
    {
        try {
            return MarketingAdminSetting::getValue('theme_minimalist_allowed', '1') === '1';
        } catch (\Throwable) {
            return true;
        }
    }
}
