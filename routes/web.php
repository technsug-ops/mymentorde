<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorSetupController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuestApplicationController;
use App\Http\Controllers\Manager\WebhookController;
use App\Http\Controllers\TrackedLinkRedirectController;
use Illuminate\Support\Facades\Route;

// ── Genel & Public ────────────────────────────────────────────────────────────
Route::redirect('/', '/login');
Route::view('/landing/mentorde', 'landing.mentorde')->name('landing.mentorde');

Route::middleware(['auth', 'manager.role'])->group(function (): void {
    Route::get('/demo', fn() => view('demo.index'));
    Route::get('/demo/checklist', fn() => view('demo.checklist'));
    Route::get('/demo/guest', fn() => view('demo.guest'));

    Route::post('/system/cache-clear', function () {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        return back()->with('status', 'Cache temizlendi.');
    })->middleware('throttle:5,1')->name('system.cache-clear');

    // Post-deploy: migration + cache clear tek buton.
    // KAS shared hosting'de SSH yok, artisan manuel çalıştırılamıyor.
    // Deploy sonrası manager buradan migrate + cache clear tetikler.
    Route::post('/system/post-deploy', function () {
        $output = [];
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $output['migrate'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $output['migrate_error'] = $e->getMessage();
        }
        try {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            $output['cache_clear'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $output['cache_clear_error'] = $e->getMessage();
        }
        try {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            $output['view_clear'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $output['view_clear_error'] = $e->getMessage();
        }
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            $output['config_clear'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $output['config_clear_error'] = $e->getMessage();
        }
        try {
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            $output['route_clear'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $output['route_clear_error'] = $e->getMessage();
        }
        return response()->json(['ok' => true, 'output' => $output]);
    })->middleware('throttle:5,1')->name('system.post-deploy');

    Route::get('/system/post-deploy', function () {
        return view('system.post-deploy');
    })->name('system.post-deploy.show');
});
Route::get('/go/{code}', TrackedLinkRedirectController::class)->name('tracked-link.redirect');

// DAM4 — Public share link access (auth gerekmez, expires + password protected)
Route::get('/share/{token}', [\App\Http\Controllers\Shared\DigitalAssetController::class, 'sharePublic'])
    ->middleware('throttle:60,1')
    ->name('dam.share.public');

Route::middleware(['company.context'])->group(function () {
    Route::get('/apply', [GuestApplicationController::class, 'create'])->name('apply.create');
    Route::post('/apply', [GuestApplicationController::class, 'store'])
        ->middleware(['field.rule.validator:student_registration,application_type', 'throttle:30,1'])
        ->name('apply.store');
    Route::get('/apply/success', [GuestApplicationController::class, 'success'])->middleware('throttle:20,1')->name('apply.success');
});

// GDPR çerez onayı (auth gerektirmez, anonim ziyaretçiler için)
Route::post('/cookie-consent', function(\Illuminate\Http\Request $r) {
    if (auth()->check()) {
        \App\Models\ConsentRecord::updateOrCreate(
            ['user_id' => auth()->id(), 'consent_type' => 'cookie'],
            ['ip_address' => $r->ip(), 'accepted_at' => now()]
        );
    }
    return response()->json(['ok' => true]);
})->middleware('throttle:20,1');

// Rol bazlı yönlendirme
Route::get('/auth/redirect', [AuthController::class, 'redirectByRole'])->middleware('auth');


// Webhook alım rotası (harici sistemlerden — auth gerektirmez)
Route::post('/webhooks/{source}', [WebhookController::class, 'receive'])->middleware('throttle:60,1')->name('webhook.receive');
// Stripe webhook — CSRF muaf, Stripe imzası ile doğrulanır
Route::post('/webhooks/stripe', [\App\Http\Controllers\Student\PaymentCheckoutController::class, 'handleWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware('throttle:120,1')
    ->name('webhook.stripe');

// Dil değiştirme
Route::post('/language', function(\Illuminate\Http\Request $r) {
    $locale = $r->input('locale', 'tr');
    if (!in_array($locale, ['tr', 'de', 'en'])) {
        $locale = 'tr';
    }
    session(['locale' => $locale]);
    if (auth()->check()) {
        $pref = \App\Models\UserPortalPreference::firstOrNew(
            ['user_id' => auth()->id(), 'portal_key' => 'guest']
        );
        $prefs = $pref->preferences_json ?? [];
        $prefs['locale'] = $locale;
        $pref->preferences_json = $prefs;
        $pref->save();
    }
    return back();
})->middleware('throttle:60,1')->name('language.switch');

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])
        ->name('password.email')
        ->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->name('password.update')
        ->middleware('throttle:5,1');
});

Route::middleware(['company.context', 'auth'])->group(function (): void {
    Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout']);
});

// ── 2FA Web Akışı ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/2fa/challenge', [TwoFactorChallengeController::class, 'show'])->name('2fa.challenge');
    Route::post('/2fa/challenge', [TwoFactorChallengeController::class, 'verify'])->middleware('throttle:10,1')->name('2fa.challenge.verify');
    Route::get('/2fa/setup', [TwoFactorSetupController::class, 'show'])->name('2fa.setup');
    Route::post('/2fa/setup', [TwoFactorSetupController::class, 'confirm'])->middleware('throttle:10,1')->name('2fa.setup.confirm');
});

// ── E-posta Doğrulama ─────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.send');
});

// ── Portal Kök Yönlendirmeleri ────────────────────────────────────────────────
Route::redirect('/marketing-admin', '/mktg-admin/dashboard');
Route::redirect('/manager',         '/manager/dashboard');
Route::redirect('/senior',          '/senior/dashboard');
Route::redirect('/student',         '/student/dashboard');
Route::redirect('/dealer',          '/dealer/dashboard');

// ── Portal Route Dosyaları ────────────────────────────────────────────────────
require __DIR__.'/manager.php';
require __DIR__.'/senior.php';
require __DIR__.'/student.php';
require __DIR__.'/guest.php';
require __DIR__.'/dealer.php';
require __DIR__.'/tasks.php';
require __DIR__.'/common.php';
require __DIR__.'/marketing-admin.php';
