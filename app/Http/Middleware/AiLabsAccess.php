<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AI Labs sayfalarına erişim — manager/admin paneli rolleri + senior danışman.
 *
 * Erişen rollerin her biri AI Labs bilgi havuzuna, içerik üreticiye ve dış
 * kaynaklara kullanıcı haklarıyla girebilir. Permission-bazlı ince ayarlama
 * gerekirse controller seviyesinde abort ile yapılır.
 */
class AiLabsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $allowed = array_merge(User::ADMIN_PANEL_ROLES, [User::ROLE_SENIOR]);
        if (!in_array((string) $user->role, $allowed, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alana erişim izniniz yok.');
        }

        return $next($request);
    }
}
