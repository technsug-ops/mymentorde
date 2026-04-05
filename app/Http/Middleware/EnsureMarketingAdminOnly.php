<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMarketingAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = (string) optional($request->user())->role;
        if (!in_array($role, [
            User::ROLE_MANAGER,
            User::ROLE_SYSTEM_ADMIN,
            User::ROLE_MARKETING_ADMIN,
            User::ROLE_SALES_ADMIN,
        ], true)) {
            abort(Response::HTTP_FORBIDDEN, 'Bu islem sadece marketing admin icin acik.');
        }

        return $next($request);
    }
}
