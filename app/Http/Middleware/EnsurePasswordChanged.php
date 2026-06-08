<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth'lu kullanıcı `password_must_change=true` ise tüm istekleri
 * /password/change-required'a yönlendirir. Manager şifre sıfırlama
 * akışında üretilen geçici şifre tek-kullanımlık olur.
 *
 * Whitelist: /password/change-required, /logout, /_deploy/*, static asset'ler
 */
class EnsurePasswordChanged
{
    private const WHITELIST_PATHS = [
        'password/change-required',
        'logout',
        '_deploy/*',
        '_deploy.php',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || empty($user->password_must_change)) {
            return $next($request);
        }

        // Whitelist'te ise geç
        foreach (self::WHITELIST_PATHS as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // AJAX/JSON istekleri için 423 (Locked) — frontend handle eder
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'error'           => 'password_change_required',
                'message'         => 'Şifrenizi yeniden belirlemeniz gerekiyor.',
                'redirect'        => url('/password/change-required'),
            ], 423);
        }

        return redirect()->to('/password/change-required')
            ->with('warning', 'Devam etmeden önce yeni şifrenizi belirlemelisiniz.');
    }
}
