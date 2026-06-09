<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform Owner Gate
 *
 * Sadece ROLE_PLATFORM_OWNER user'ları geçer. Customer Manager, System Admin
 * vb. dahil tüm diğer roller 403 alır.
 *
 * Kullanım:
 *   Route::middleware(['auth', 'platform.owner'])->prefix('platform')->group(...)
 *
 * Yetkileri:
 *  - Cross-company görüntüleme + yönetim
 *  - Module toggle (companies.enabled_modules)
 *  - Subscription tier upgrade/downgrade
 *  - Yeni şirket provisioning
 *  - Billing + analytics dashboard
 *
 * Bu role Mentorde sahibi gibi platform-level operator için. Customer
 * Manager'lar (her şirketin kendi yöneticisi) bu yetkilere sahip değildir.
 */
class EnsurePlatformOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || (string) $user->role !== User::ROLE_PLATFORM_OWNER) {
            abort(Response::HTTP_FORBIDDEN, 'Bu alan sadece Platform Owner içindir.');
        }
        return $next($request);
    }
}
