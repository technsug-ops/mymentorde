<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSeniorRole
{
    /**
     * @param string $permissionCode  İsteğe bağlı. Belirtilirse bu yetki koduna sahip
     *                                kullanıcılar da senior paneline erişebilir.
     *                                Örnek route tanımı: 'senior.role:senior.portal.access'
     */
    public function handle(Request $request, Closure $next, string $permissionCode = ''): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Kimlik dogrulama gerekli.');
        }

        if (in_array((string) $user->role, [User::ROLE_SENIOR, User::ROLE_MENTOR], true)) {
            return $next($request);
        }

        if ($permissionCode !== ''
            && method_exists($user, 'hasPermissionCode')
            && $user->hasPermissionCode($permissionCode)
        ) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'Bu alana erisim izniniz yok.');
    }
}
