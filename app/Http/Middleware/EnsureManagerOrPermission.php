<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagerOrPermission
{
    public function handle(Request $request, Closure $next, string $permissionCode): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Kimlik dogrulama gerekli.');
        }

        if (in_array((string) $user->role, User::ADMIN_PANEL_ROLES, true)) {
            return $next($request);
        }

        if (method_exists($user, 'hasPermissionCode') && $user->hasPermissionCode($permissionCode)) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, "Yetki yok: manager veya {$permissionCode}");
    }
}

