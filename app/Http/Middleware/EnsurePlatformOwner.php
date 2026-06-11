<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sadece Mentorde Platform sahibinin erisebilecegi alanlar icin gate.
 *
 * Customer Manager'lar BLOKLANIR — bu sayede:
 * - Modul toggle (/manager/companies/modules) Customer'a kapali, bedava
 *   premium feature aktiflestirilemez.
 * - System Admin alanlari (/manager/system/*) Customer'a kapali.
 * - Cross-company analytics, billing, tier yonetimi sadece Platform Owner'da.
 */
class EnsurePlatformOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || (string) $user->role !== User::ROLE_PLATFORM_OWNER) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alan sadece Mentorde Platform sahibine acik.');
        }

        return $next($request);
    }
}
