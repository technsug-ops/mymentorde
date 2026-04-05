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
        // Giriş yapmış kullanıcılar guest route'larına erişirse /config'e yönlendir
        $middleware->redirectUsersTo('/auth/redirect');

        // Global güvenlik başlıkları — tüm web yanıtlarına eklenir
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Çok dil desteği — session/kullanıcı tercihine göre locale ayarla
        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // Authenticated kullanıcıların presence durumunu günceller (heartbeat)
        $middleware->append(\App\Http\Middleware\UpdateUserPresence::class);

        $middleware->alias([
            'manager.role' => \App\Http\Middleware\EnsureManagerRole::class,
            'senior.role' => \App\Http\Middleware\EnsureSeniorRole::class,
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
            'company.context' => \App\Http\Middleware\SetCompanyContext::class,
            'task.access' => \App\Http\Middleware\EnsureTaskAccess::class,
            'require.2fa' => \App\Http\Middleware\Require2FA::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
