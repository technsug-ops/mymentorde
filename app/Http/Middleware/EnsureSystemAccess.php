<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform-altyapı sayfaları için gate: GDPR, Webhook, ROPA, AVV, vb.
 *
 * Sadece Platform Owner + System Admin. Customer Manager, VIP ve diğer admin
 * rolleri BLOKLANIR — bunlar altyapı/uyumluluk alanlarıdır, iş yetkisi değil.
 *
 * (EnsurePlatformOwner sadece owner'a açar; bu middleware system_admin'e de
 *  izin verir çünkü teknik destek/sysadmin bu alanları görebilmeli.)
 */
class EnsureSystemAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $allowed = [User::ROLE_PLATFORM_OWNER, User::ROLE_SYSTEM_ADMIN];

        if (!$user || !in_array((string) $user->role, $allowed, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alan yalnızca platform yönetimine açıktır.');
        }

        return $next($request);
    }
}
