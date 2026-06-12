<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only Auditor middleware.
 *
 * ROLE_AUDITOR rolündeki kullanıcılar tüm panellere erişebilir ama herhangi bir
 * yazma işlemi yapamaz (POST/PUT/DELETE/PATCH → 403).
 *
 * Allowlist (her zaman izinli, oturum yönetimi vb. için):
 *   - /logout
 *   - /broadcasting/auth (Pusher real-time auth)
 *   - /trial-banner/dismiss
 *   - /password/* (kendi şifresini değiştirebilir)
 *
 * Kullanım: bootstrap/app.php'de web group'una appendToGroup ile eklenir.
 * Audit log: bloklanan her yazma denemesi 'auditor.blocked' event'i ile kayıt edilir.
 */
class EnsureReadOnly
{
    /**
     * Auditor yazma yapabilir paths (allowlist). Path başlangıcı match edilir.
     */
    private const ALLOWED_WRITE_PATHS = [
        'logout',
        'login', // GET ve POST login form (zaten role check yapılıyor)
        'broadcasting/auth',
        'trial-banner/dismiss',
        'password',
        'profile/password',
        '_deploy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $this->doHandle($request, $next);
        } catch (\Throwable $e) {
            // Fail-open: middleware hatası app'i çökertmesin
            Log::warning('EnsureReadOnly non-fatal error', [
                'error' => $e->getMessage(),
                'path'  => $request->path(),
            ]);
            return $next($request);
        }
    }

    private function doHandle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Auditor değilse middleware atla — diğer roller normal akışta devam eder
        if (!$user || (string) $user->role !== User::ROLE_AUDITOR) {
            return $next($request);
        }

        // GET / HEAD / OPTIONS her zaman serbest
        $method = strtoupper($request->method());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Allowlist path kontrolü
        $path = ltrim($request->path(), '/');
        foreach (self::ALLOWED_WRITE_PATHS as $allowed) {
            if ($path === $allowed || str_starts_with($path, $allowed . '/')) {
                return $next($request);
            }
        }

        // Audit log — kim, ne yapmaya çalıştı
        try {
            \App\Models\PlatformAuditLog::record(
                'auditor.blocked',
                [
                    'user_id'    => $user->id,
                    'user_email' => $user->email,
                    'method'     => $method,
                    'path'       => $path,
                    'ip'         => $request->ip(),
                ],
                \App\Models\PlatformAuditLog::SEVERITY_INFO
            );
        } catch (\Throwable $e) { /* audit fail middleware'i bozmasın */ }

        // JSON request ise JSON response, değilse 403 page
        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'error'   => 'read_only_role',
                'message' => 'Bu hesap yalnızca görüntüleme yetkisine sahip (Auditor). Yazma işlemi engellendi.',
            ], 403);
        }

        abort(403, 'Bu hesap yalnızca görüntüleme yetkisine sahip (Read-only Auditor). Lütfen sistem yöneticisi ile iletişime geçin.');
    }
}
