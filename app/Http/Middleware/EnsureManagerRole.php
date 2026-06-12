<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagerRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !in_array((string) $user->role, User::ADMIN_PANEL_ROLES, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alana erisim izniniz yok.');
        }

        // Platform Owner manager paneline DIREKT erisemez — sadece impersonate
        // session aktifse veya /manager/system/* (Faz 1'de zaten Platform Owner only).
        // Aksi takdirde /platform/dashboard'a yonlendirilir.
        if ((string) $user->role === User::ROLE_PLATFORM_OWNER) {
            $impersonating = session('impersonating_company_id');
            $isSystemAdminArea = $request->is('manager/system*')
                || $request->is('manager/companies/modules*')
                || $request->is('manager/audit-log*')
                || $request->is('manager/gdpr-dashboard*')
                || $request->is('manager/ropa*')
                || $request->is('manager/avv*')
                || $request->is('manager/webhooks*')
                || $request->is('manager/landing-inventory*')
                || $request->is('manager/page-visibility*');
            if (!$impersonating && !$isSystemAdminArea) {
                return redirect('/platform/dashboard')
                    ->with('status', 'Customer panel erisimi icin once /platform/companies sayfasindan ilgili sirketi impersonate edin.');
            }
        }

        return $next($request);
    }
}
