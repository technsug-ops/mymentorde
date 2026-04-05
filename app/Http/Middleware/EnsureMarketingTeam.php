<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sadece pazarlama ekibine açık route'lar için.
 * Satış rolleri (sales_admin, sales_staff) bu rotaları kullanamaz.
 * Kampanya, CMS, e-posta, sosyal medya, etkinlik, tracking link, A/B test.
 */
class EnsureMarketingTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = (string) optional($request->user())->role;

        $allowed = [
            User::ROLE_MANAGER,
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_MARKETING_ADMIN,
            User::ROLE_MARKETING_STAFF,
            User::ROLE_SALES_ADMIN,
            User::ROLE_SALES_STAFF,
        ];

        if (!in_array($role, $allowed, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Bu bolum pazarlama/satis ekibine aittir.');
        }

        return $next($request);
    }
}
