<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserTwoFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2FA
{
    // Bu roller için 2FA zorunlu
    private const ENFORCED_ROLES = [
        User::ROLE_MANAGER,
        User::ROLE_SYSTEM_ADMIN,
        User::ROLE_OPERATIONS_ADMIN,
        User::ROLE_FINANCE_ADMIN,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, self::ENFORCED_ROLES, true)) {
            return $next($request);
        }

        // Bu oturumda zaten geçildiyse devam et
        if ($request->session()->get('2fa_passed')) {
            return $next($request);
        }

        // 2FA kurulu mu?
        $twoFactor = UserTwoFactor::where('user_id', $user->id)
            ->whereNotNull('enabled_at')
            ->exists();

        if (!$twoFactor) {
            // 2FA kurulu değilse: setup sayfasına yönlendir
            if (!$request->routeIs('2fa.setup*')) {
                return redirect()->route('2fa.setup')->with(
                    'warning',
                    'Hesabınız için iki faktörlü doğrulama (2FA) zorunludur. Lütfen şimdi kurun.'
                );
            }
            return $next($request);
        }

        // 2FA kurulu ama bu oturumda doğrulanmamış: challenge sayfasına
        return redirect()->route('2fa.challenge');
    }
}
