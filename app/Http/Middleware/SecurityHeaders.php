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

        // style-src: 'unsafe-inline' yeterli — CSP3'te nonce+unsafe-inline birlikte kullanılınca
        // Chrome unsafe-inline'ı görmezden gelip nonce gerektiriyor. Tüm <style> blokları kırılıyor.
        // Nonce şimdilik sadece script-src için, style-src'ye EKLENMEZ.
        // V6 sprint: tüm Blade template <style> taglerine nonce="{{ $cspNonce }}" eklendikten sonra
        //            'unsafe-inline' style-src'den kaldırılır, nonce eklenir.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://api.qrserver.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https:",
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://open.spotify.com https://docs.google.com https://www.canva.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
