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
        // NOT: Inline event handler'lar (onclick= vb.) aşağıda script-src-attr ile
        // KALICI izinli — script-src'ye nonce eklemek onları bozmaz. Bu yüzden
        // "V6'da unsafe-inline'ı kaldırınca handler'lar bozulur" riski ARTIK YOK.
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

        // ── Test/staging ortami arama motorlarina KAPALI ──
        // test.mentorde.com canlinin birebir kopyasi; indekslenirse Google'da
        // canli sayfalarla ayni icerik iki adreste cikar (duplicate content) ve
        // musteri yanlislikla test ortamina girer. Sadece APP_ENV=staging'de calisir,
        // production'da bu blok hic tetiklenmez.
        if (app()->environment('staging')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

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

        // PostHog analytics + session replay domain'leri
        $posthogScript  = ' https://eu-assets.i.posthog.com https://eu.i.posthog.com https://eu.posthog.com https://*.posthog.com';
        $posthogConnect = ' https://eu-assets.i.posthog.com https://eu.i.posthog.com https://eu.posthog.com https://*.posthog.com wss://*.posthog.com';

        // Cloudflare Turnstile (CAPTCHA) — script + iframe domain'leri
        $turnstile = ' https://challenges.cloudflare.com';

        // Pusher (real-time bildirim) — pusher-js CDN bundle vite ile yukleniyor,
        // gercek WebSocket trafigi ws-*.pusher.com & sockjs-*.pusher.com uzerinden.
        $pusherScript  = ' https://*.pusher.com https://*.pusherapp.com https://*.pusher-js.com';
        $pusherConnect = ' wss://*.pusher.com wss://*.pusherapp.com https://*.pusher.com https://*.pusherapp.com https://sockjs-eu.pusher.com https://sockjs-us.pusher.com https://sockjs-ap1.pusher.com';

        $csp = implode('; ', [
            "default-src 'self'",
            // script-src: <script> BLOKLARI. Şu an 'unsafe-inline' aktif. İleride
            // <script> sertleştirmek istenirse buraya "'nonce-{$nonce}'" eklenip
            // 'unsafe-inline' kaldırılabilir — bu, aşağıdaki script-src-attr sayesinde
            // inline event handler'ları (onclick= vb.) BOZMAZ.
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://api.qrserver.com" . $posthogScript . $turnstile . $pusherScript . $viteScript,
            // script-src-attr: inline event handler'ları (onclick/onchange/onsubmit/
            // onmouseover...) AYRI kontrol eder. Kalıcı olarak izinli — proje genelinde
            // ~100 inline handler var; script-src'ye nonce eklense bile bunlar çalışır.
            // Bu olmadan nonce eklenince tüm handler'lar bloklanır (tema-toggle bug'ı).
            "script-src-attr 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net" . $viteStyle,
            "font-src 'self' data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https:" . $posthogConnect . $pusherConnect . $viteConnect,
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://open.spotify.com https://docs.google.com https://www.canva.com" . $turnstile,
            "media-src 'self' data:",
            "worker-src 'self' blob: https://cdn.jsdelivr.net",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
