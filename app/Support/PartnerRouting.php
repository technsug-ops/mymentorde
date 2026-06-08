<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

/**
 * Partner yönetimi iki panelden erişilebilir:
 *   - Manager  → /manager/api-partners/*       (route name: manager.api-partners.*)
 *   - Mktg-Admin → /mktg-admin/partners/*      (route name: mktg-admin.partners.*)
 *
 * Aynı controller (ApiPartnerController) iki route grubundan da çağrılır.
 * View/redirect aşamasında URL üretimi mevcut panel'e göre seçilir.
 */
class PartnerRouting
{
    /**
     * Mevcut request hangi panel altında? Default: 'manager'.
     */
    public static function panel(): string
    {
        $req = Request::instance();
        return $req && $req->is('mktg-admin/*') ? 'mktg-admin' : 'manager';
    }

    /**
     * Panel'e göre route adı.
     *
     *   action: index|create|store|show|update|rotate|toggle|destroy
     */
    public static function routeName(string $action): string
    {
        return self::panel() === 'mktg-admin'
            ? "mktg-admin.partners.{$action}"
            : "manager.api-partners.{$action}";
    }

    /**
     * URL üret. route() wrapper.
     */
    public static function url(string $action, mixed $parameters = null): string
    {
        return route(self::routeName($action), $parameters);
    }

    /**
     * Panel'e uygun layout view adı.
     */
    public static function layout(): string
    {
        return self::panel() === 'mktg-admin'
            ? 'marketing-admin.layouts.app'
            : 'manager.layouts.app';
    }
}
