<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissionCode): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Kimlik dogrulama gerekli.');
        }

        if (!method_exists($user, 'hasPermissionCode') || !$user->hasPermissionCode($permissionCode)) {
            abort(Response::HTTP_FORBIDDEN, "Yetki yok: {$permissionCode}");
        }

        return $next($request);
    }
}
