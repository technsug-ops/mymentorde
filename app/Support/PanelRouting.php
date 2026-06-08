<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

/**
 * Manager + Marketing-Admin panellerinden ortaklaşa erişilen kaynakların
 * (bayiler, partner, dealer-tipi, kademe vb.) URL ve layout seçimi.
 *
 * Aynı controller iki ayrı route grubundan da çağrılabilir; bu helper
 * mevcut request'in panel'ini tespit edip doğru route name + layout'u verir.
 *
 * Route ad şablonu:
 *   - manager:    'manager.{module}.{action}'         (örn: manager.dealers.show)
 *   - mktg-admin: 'mktg-admin.{module}.{action}'      (örn: mktg-admin.dealers.show)
 *
 * Layout:
 *   - manager:    'manager.layouts.app'
 *   - mktg-admin: 'marketing-admin.layouts.app'
 */
class PanelRouting
{
    /** Mevcut request hangi panel altında? */
    public static function panel(): string
    {
        $req = Request::instance();
        return $req && $req->is('mktg-admin/*') ? 'mktg-admin' : 'manager';
    }

    /**
     * Route adını panel'e göre üret.
     * Örn: routeName('dealers', 'show') → 'manager.dealers.show' veya 'mktg-admin.dealers.show'
     */
    public static function routeName(string $module, string $action): string
    {
        return self::panel() . '.' . $module . '.' . $action;
    }

    /** URL üret. route() wrapper. */
    public static function url(string $module, string $action, mixed $parameters = null): string
    {
        return route(self::routeName($module, $action), $parameters);
    }

    /** Panel'e uygun layout view adı. */
    public static function layout(): string
    {
        return self::panel() === 'mktg-admin'
            ? 'marketing-admin.layouts.app'
            : 'manager.layouts.app';
    }
}
