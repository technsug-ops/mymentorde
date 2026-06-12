<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trial süresi dolan müşteri company'lerin paneline erişimini bloklar.
 *
 * Davranış:
 *   - Company trial değilse → geçer
 *   - Trial aktif (süre dolmamış) → geçer
 *   - Trial dolduysa → /trial-expired'e redirect
 *
 * İstisnalar (her zaman erişilebilir):
 *   - /trial-expired (payment wall sayfası)
 *   - /trial-banner/dismiss (banner kapatma)
 *   - /manager/my-plan (plan + upgrade)
 *   - /logout
 *   - Platform Owner impersonation (Platform sahibi debug için girince block yok)
 *
 * Platform Owner kendi panelinde (`/platform/*`) hiç etkilenmez.
 */
class EnsureTrialActive
{
    /** Trial expired olsa bile erişilebilir route prefix'leri. */
    private const ALLOWED_PATHS = [
        'trial-expired',
        'trial-banner',
        'manager/my-plan',
        'logout',
        'login',
        'broadcasting/auth',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Platform Owner impersonation aktifse trial block bypass
        if ($request->session()->get('impersonating_company_id')) {
            return $next($request);
        }

        // Path allowlist kontrolü (en başta)
        $path = ltrim($request->path(), '/');
        foreach (self::ALLOWED_PATHS as $allowed) {
            if ($path === $allowed || str_starts_with($path, $allowed . '/')) {
                return $next($request);
            }
        }

        $user = $request->user();
        if (!$user) {
            return $next($request); // auth middleware ayrı handle eder
        }

        // Sadece Customer Manager'lar için kontrol (Platform Owner exempt)
        if ((string) $user->role === User::ROLE_PLATFORM_OWNER) {
            return $next($request);
        }

        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId <= 0) {
            return $next($request);
        }

        $company = Company::query()->find($companyId);
        if (!$company || !$company->isTrialExpired()) {
            return $next($request);
        }

        // Trial dolmuş → payment wall'a yönlendir
        return redirect()->route('public.trial-expired');
    }
}
