<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Güvenlik HTTP başlıkları — OWASP önerileri doğrultusunda.
 *
 * X-Frame-Options          → Clickjacking koruması
 * X-Content-Type-Options   → MIME sniffing koruması
 * Referrer-Policy          → URL sızıntısı kısıtlaması
 * Permissions-Policy       → Kamera/mikrofon/konum erişim engeli
 * Content-Security-Policy  → XSS ve injection koruması
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nonce altyapısı: request başına rastgele üretilir, tüm view'lara iletilir.
        // Blade template'lerde: <script nonce="{{ $cspNonce }}"> veya @cspNonce
        // V6 sprint'inde 'unsafe-inline' kaldırılacak — tüm template'ler nonce aldığında.
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp-nonce', $nonce);

        // View render edilmeden önce share ediyoruz ($next içinde view render edilir)
        if (function_exists('view') && app()->bound('view')) {
            view()->share('cspNonce', $nonce);
        }

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // ── Çıkış sonrası "Geri" butonu güvenliği ──
        // Hem auth'lu sayfalar hem de login/password gibi hassas guest sayfaları
        // tarayıcı bfcache'ine alınmasın. Bu sayede:
        // (a) Logout sonrası geri tuşu eski dashboard'ı göstermez
        // (b) Login sonrası geri tuşu login'e gitse de hemen server'a düşüp
        //     guest middleware auth'lu user'ı dashboard'a redirect eder.
        $isAuthPage = $request->routeIs('login')
                      || $request->routeIs('password.*')
                      || $request->is('login', 'logout', 'forgot-password', 'reset-password/*');
        if (auth()->check() || $isAuthPage) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        // style-src: 'unsafe-inline' yeterli — CSP3'te nonce+unsafe-inline birlikte kullanılınca
        // Chrome unsafe-inline'ı görmezden gelip nonce gerektiriyor. Tüm <style> blokları kırılıyor.
        // Nonce şimdilik sadece script-src için, style-src'ye EKLENMEZ.
        // V6 sprint: tüm Blade template <style> taglerine nonce="{{ $cspNonce }}" eklendikten sonra
        //            'unsafe-inline' style-src'den kaldırılır, nonce eklenir.

        // ── Dev ortamında Vite origin'leri CSP whitelist'ine ekle ──────────
        // Vite dev server localhost/127.0.0.1/[::1] üzerinde random bir port'ta
        // çalışır. Production'da bu satırlar devre dışıdır.
        $viteScript = '';
        $viteStyle  = '';
        $viteConnect = '';
        if (app()->environment('local')) {
            // IPv6 formatı ([::1]) CSP parser'ını bozar — localhost + 127.0.0.1 yeterli
            $viteHosts = 'http://localhost:* http://127.0.0.1:*';
            $wsHosts   = 'ws://localhost:* ws://127.0.0.1:*';
            $viteScript  = ' ' . $viteHosts;
            $viteStyle   = ' ' . $viteHosts;
            $viteConnect = ' ' . $viteHosts . ' ' . $wsHosts;
        }

        $csp = implode('; ', [
            "default-src 'self'",
            // Production: nonce kaldırıldı — unsafe-inline aktif. Tüm view'lar nonce'a geçirildiğinde geri eklenecek.
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://api.qrserver.com" . $viteScript,
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net" . $viteStyle,
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https:" . $viteConnect,
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://open.spotify.com https://docs.google.com https://www.canva.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
