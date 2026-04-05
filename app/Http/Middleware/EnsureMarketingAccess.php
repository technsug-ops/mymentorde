<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMarketingAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = (string) optional($request->user())->role;
        $allowed = User::MARKETING_ACCESS_ROLES;
        if (!in_array($role, $allowed, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Marketing panel erisimi yok.');
        }

        return $next($request);
    }
}
