<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // all-inkl / paylaşımlı hosting: HTTPS proxy arkasında çalışıyoruz
        $middleware->trustProxies(at: '*');

        // Analytics consent cookie'sini şifreleme dışında bırak (JS'ten set ediliyor)
        $middleware->encryptCookies(except: [
            'analytics_consent',
        ]);

        // Giriş yapmış kullanıcılar guest route'larına erişirse /config'e yönlendir
        $middleware->redirectUsersTo('/auth/redirect');

        // Global güvenlik başlıkları — tüm web yanıtlarına eklenir
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Çok dil desteği — session/kullanıcı tercihine göre locale ayarla
        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // Authenticated kullanıcıların presence durumunu günceller (heartbeat)
        $middleware->append(\App\Http\Middleware\UpdateUserPresence::class);

        // Şifre değiştirme zorunluluğu — manager reset sonrası geçici şifrenin tek-kullanımlık olmasını sağlar
        $middleware->append(\App\Http\Middleware\EnsurePasswordChanged::class);

        $middleware->alias([
            'manager.role' => \App\Http\Middleware\EnsureManagerRole::class,
            'senior.role' => \App\Http\Middleware\EnsureSeniorRole::class,
            'ai_labs.access' => \App\Http\Middleware\AiLabsAccess::class,
            'student.role' => \App\Http\Middleware\EnsureStudentRole::class,
             'guest.role' => \App\Http\Middleware\EnsureGuestRole::class,
             'guest.owns.ticket' => \App\Http\Middleware\EnsureGuestOwnsTicket::class,
             'guest.owns.document' => \App\Http\Middleware\EnsureGuestOwnsDocument::class,
             'student.owns.document' => \App\Http\Middleware\EnsureStudentOwnsDocument::class,
             'dealer.role' => \App\Http\Middleware\EnsureDealerRole::class,
            'dealer.type.permission' => \App\Http\Middleware\CheckDealerTypePermission::class,
            'field.rule.validator' => \App\Http\Middleware\FieldRuleValidator::class,
            'process.outcome.visibility' => \App\Http\Middleware\CheckProcessOutcomeVisibility::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'manager.or.permission' => \App\Http\Middleware\EnsureManagerOrPermission::class,
            'marketing.access' => \App\Http\Middleware\EnsureMarketingAccess::class,
            'marketing.admin' => \App\Http\Middleware\EnsureMarketingAdminOnly::class,
            'marketing.team'  => \App\Http\Middleware\EnsureMarketingTeam::class,
            'analytics.access' => \App\Http\Middleware\EnsureAnalyticsAccess::class,
            'company.context' => \App\Http\Middleware\SetCompanyContext::class,
            'task.access' => \App\Http\Middleware\EnsureTaskAccess::class,
            'require.2fa' => \App\Http\Middleware\Require2FA::class,
            'module' => \App\Http\Middleware\ModuleEnabled::class,
            'page.visible' => \App\Http\Middleware\EnsurePageVisible::class,
            'api.key' => \App\Http\Middleware\VerifyApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // PostHog Error Tracking — backend exception capture (Sentry alternatifi)
        // Sadece 500+ ve unexpected exception'lar (HTTP 4xx, validation, auth gibi
        // beklenen exception'lar gonderilmez — gurultu olmasin diye).
        $exceptions->report(function (\Throwable $e): void {
            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof TokenMismatchException
                || $e instanceof HttpResponseException) {
                return;
            }
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return; // 404/403/422 gibi beklenen hatalari capture etme
            }
            try {
                app(\App\Services\Analytics\AnalyticsService::class)->captureException($e);
            } catch (\Throwable $inner) {
                // PostHog cokerse log'a yaz — ana exception flow'unu bozma
                \Illuminate\Support\Facades\Log::warning('PostHog exception capture failed', [
                    'original' => $e->getMessage(),
                    'inner'    => $inner->getMessage(),
                ]);
            }
        });

        $mapErrorCode = static function (int $status): string {
            return match ($status) {
                400 => 'ERR_BAD_REQUEST',
                401 => 'ERR_UNAUTHORIZED',
                403 => 'ERR_FORBIDDEN',
                404 => 'ERR_NOT_FOUND',
                405 => 'ERR_METHOD_NOT_ALLOWED',
                409 => 'ERR_CONFLICT',
                419 => 'ERR_CSRF_TOKEN',
                422 => 'ERR_VALIDATION',
                429 => 'ERR_RATE_LIMIT',
                default => $status >= 500 ? 'ERR_INTERNAL' : 'ERR_UNKNOWN',
            };
        };

        // 419 Page Expired → AJAX istekleri için JSON, web için login redirect
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('api/*')) {
                return null;
            }
            if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
                return response()->json(['message' => 'Oturum süresi doldu.', 'error_code' => 'ERR_CSRF_TOKEN'], 419);
            }
            return redirect()->route('login')->withErrors(['session' => 'Oturum süresi doldu, lütfen tekrar giriş yapın.']);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($mapErrorCode) {
            if (!$request->is('api/*')) {
                return null;
            }
            $status = 401;
            return response()->json([
                'message' => 'Kimlik dogrulama gerekli.',
                'error_code' => $mapErrorCode($status),
                'status' => $status,
            ], $status);
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($mapErrorCode) {
            if (!$request->is('api/*')) {
                return null;
            }
            $status = 422;
            return response()->json([
                'message' => $e->getMessage() ?: 'Validation hatasi.',
                'error_code' => $mapErrorCode($status),
                'status' => $status,
                'errors' => $e->errors(),
            ], $status);
        });

        // 429 Too Many Requests → Türkçe mesaj (web + API). Laravel default "Too Many
        // Attempts." mesajını override eder. Booking/NPS/login gibi throttle'lı
        // endpoint'lerde kullanıcıya net Türkçe geri bildirim sağlar.
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) use ($mapErrorCode) {
            $retry = (int) ($e->getHeaders()['Retry-After'] ?? 60);
            $msg = $retry > 1
                ? "Çok fazla deneme yapıldı. Lütfen {$retry} saniye sonra tekrar deneyin."
                : 'Çok fazla deneme yapıldı. Lütfen biraz bekleyip tekrar deneyin.';
            if ($request->expectsJson() || $request->hasHeader('X-Requested-With') || $request->is('api/*')) {
                return response()->json([
                    'message' => $msg,
                    'error_code' => $mapErrorCode(429),
                    'status' => 429,
                    'retry_after' => $retry,
                ], 429, $e->getHeaders());
            }
            return response($msg, 429, $e->getHeaders() + ['Content-Type' => 'text/plain; charset=utf-8']);
        });

        $exceptions->render(function (\Throwable $e, Request $request) use ($mapErrorCode) {
            if (!$request->is('api/*')) {
                return null;
            }
            if ($e instanceof HttpResponseException) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = trim((string) $e->getMessage());
            if ($message === '') {
                $message = $status >= 500 ? 'Sunucu hatasi.' : 'Request failed';
            }

            return response()->json([
                'message' => $message,
                'error_code' => $mapErrorCode($status),
                'status' => $status,
            ], $status);
        });
    })->create();
