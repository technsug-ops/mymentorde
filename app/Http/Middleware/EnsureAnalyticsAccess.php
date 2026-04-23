<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Analytics dashboardları (User Intelligence, AI Labs Analytics, Lead Actions)
 * için genişletilmiş erişim — admin panel rolleri + marketing/sales rolleri.
 *
 * Manager, system_admin, operations_admin, finance_admin, marketing_admin,
 * sales_admin, marketing_staff, sales_staff, system_staff hepsi erişir.
 */
class EnsureAnalyticsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $allowed = array_unique(array_merge(
            User::ADMIN_PANEL_ROLES,
            User::MARKETING_ACCESS_ROLES
        ));

        if (!in_array((string) $user->role, $allowed, true)) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        return $next($request);
    }
}
