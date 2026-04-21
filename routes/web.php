<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;
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

// ── Yasal Sayfalar (Privacy / Terms) ─────────────────────────────────────────
// SaaS gerekliliği: Google OAuth consent screen + KVKK/GDPR uyumu için public erişim
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms',   'legal.terms')->name('legal.terms');

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
        // Auto-repair adımları — cleanup sonrası drift'leri kapatır
        try {
            \Illuminate\Support\Facades\Artisan::call('system:sync-user-email-relations');
            $output['sync_user_emails'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $output['sync_user_emails_error'] = $e->getMessage();
        }
        try {
            \Illuminate\Support\Facades\Artisan::call('system:repair-registration-fields');
            $output['repair_registration_fields'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $output['repair_registration_fields_error'] = $e->getMessage();
        }
        return response()->json(['ok' => true, 'output' => $output]);
    })->middleware('throttle:5,1')->name('system.post-deploy');

    Route::get('/system/post-deploy', function () {
        return view('system.post-deploy');
    })->name('system.post-deploy.show');

    // Demo student hesabını zengin verilerle doldur (FullyTransitionedStudentSeeder)
    // KAS SSH yok, seeder'ı buradan tetikle. student@my.mentorde.com veya
    // student@mentorde.local hesabı olan her ortamda çalışır.
    Route::post('/system/seed-demo-student', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'FullyTransitionedStudentSeeder',
                '--force' => true,
            ]);
            return response()->json([
                'ok'     => true,
                'output' => trim(\Illuminate\Support\Facades\Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    })->middleware('throttle:3,1')->name('system.seed-demo-student');

    // Prod test temizliği: 11 canonical user dışındakileri siler, emailleri @panel.mentorde.com yapar.
    // Önce GET ile rapor sayfası (dry-run), sonra POST ile gerçek çalıştırma.
    Route::get('/system/cleanup-prod-test', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('system:cleanup-prod-test', ['--dry-run' => true]);
            $output = \Illuminate\Support\Facades\Artisan::output();
        } catch (\Throwable $e) {
            $output = 'HATA: ' . $e->getMessage();
        }
        return view('system.cleanup-prod-test', ['dryRunOutput' => $output]);
    })->name('system.cleanup-prod-test.show');

    Route::post('/system/cleanup-prod-test', function (\Illuminate\Http\Request $request) {
        if ($request->input('confirm') !== 'DELETE_ALL_TEST_DATA') {
            return response()->json(['ok' => false, 'error' => 'Confirmation token gerekli.'], 422);
        }
        try {
            \Illuminate\Support\Facades\Artisan::call('system:cleanup-prod-test');
            return response()->json([
                'ok'     => true,
                'output' => trim(\Illuminate\Support\Facades\Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    })->middleware('throttle:2,1')->name('system.cleanup-prod-test');

    // users.email değişince bağlı tablolardaki email drift'i kapatır
    // (guest_applications.email, senior_email, assigned_senior_email vb.)
    Route::post('/system/sync-user-email-relations', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('system:sync-user-email-relations');
            return response()->json([
                'ok'     => true,
                'output' => trim(\Illuminate\Support\Facades\Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    })->middleware('throttle:5,1')->name('system.sync-user-email-relations');

    // guest_registration_fields'ta eksik section/field'ları default catalog'dan tamamlar
    // (insertOrIgnore mantığı — mevcutlara dokunmaz). Örn. Adım 2 "Adres ve Başvuru"
    // prod'da eksikse onu ekler.
    Route::post('/system/repair-registration-fields', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('system:repair-registration-fields');
            return response()->json([
                'ok'     => true,
                'output' => trim(\Illuminate\Support\Facades\Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    })->middleware('throttle:5,1')->name('system.repair-registration-fields');
});
Route::get('/go/{code}', TrackedLinkRedirectController::class)->name('tracked-link.redirect');

// DAM4 — Public share link access (auth gerekmez, expires + password protected)
Route::get('/share/{token}', [\App\Http\Controllers\Shared\DigitalAssetController::class, 'sharePublic'])
    ->middleware('throttle:60,1')
    ->name('dam.share.public');

// ── Public Booking Widget + Landing (booking modülü) ────────────────────────
// Auth opsiyonel: senior settings.is_public=true ise herkes erişebilir,
// aksi halde login student/guest gereklidir (controller kontrol eder).
Route::middleware(['company.context', 'module:booking'])->group(function (): void {
    $bc = \App\Http\Controllers\Booking\PublicBookingController::class;
    Route::get('/randevu', [\App\Http\Controllers\Booking\BookingLandingController::class, 'index'])
        ->middleware('throttle:60,1')->name('booking.landing');
    Route::get('/book/{slug}',                       [$bc, 'show'])->middleware('throttle:60,1')->name('booking.public.show');
    Route::post('/book/{slug}/slots',                [$bc, 'slots'])->middleware('throttle:120,1')->name('booking.public.slots');
    Route::post('/book/{slug}/confirm',              [$bc, 'confirm'])->middleware('throttle:10,1')->name('booking.public.confirm');
    Route::get('/book/cancel/{token}',               [$bc, 'cancelShow'])->middleware('throttle:60,1')->name('booking.public.cancel.show');
    Route::post('/book/cancel/{token}',              [$bc, 'cancel'])->middleware('throttle:10,1')->name('booking.public.cancel');
});

Route::middleware(['company.context'])->group(function () {
    Route::get('/apply', [GuestApplicationController::class, 'create'])->name('apply.create');
    Route::post('/apply', [GuestApplicationController::class, 'store'])
        ->middleware(['field.rule.validator:student_registration,application_type', 'throttle:30,1'])
        ->name('apply.store');
    Route::get('/apply/success', [GuestApplicationController::class, 'success'])->middleware('throttle:20,1')->name('apply.success');
    // ── Partner/Bayi özel landing: /apply/partner/{dealer_code} ──
    // Formu aynı (apply.create) ama dealer_code prefill edilir ve partner logosu gösterilir.
    Route::get('/apply/partner/{code}', [GuestApplicationController::class, 'createForPartner'])
        ->where('code', '[A-Za-z0-9_-]{3,64}')
        ->middleware('throttle:60,1')
        ->name('apply.partner');
});

// Promo popup: aktif popup'ı JSON döner (tüm portal layout'ları bu endpoint'i çağırır)
Route::get('/api/promo-popup', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    if (!$user) return response()->json(null);
    $role  = strtolower($user->role ?? 'guest');
    $page  = (string) $request->query('page', '');
    $now   = now();
    $popup = \App\Models\PromoPopup::where('is_active', true)
        ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
        ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now))
        ->whereJsonContains('target_roles', $role)
        ->when($page !== '', fn ($q) => $q->whereJsonContains('target_pages', $page))
        ->orderBy('priority')
        ->first(['id', 'title', 'video_url', 'video_type', 'description', 'delay_seconds', 'frequency']);
    return response()->json($popup);
})->middleware(['auth', 'throttle:60,1'])->name('api.promo-popup');

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
});

// ── Password reset: auth'lu kullanıcılar da erişebilsin ──────────────────────
// Reset link'e tıklandığında mevcut session ne olursa olsun form gösterilmeli.
// Aksi takdirde auth'lu user reset link'e bastığında dashboard'a atılır ve
// şifre form'u hiç görünmez — bu bir güvenlik açığıdır (şifre sıfırlanmaz).
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->name('password.update')
    ->middleware('throttle:5,1');

// ── Google OAuth ─────────────────────────────────────────────────────────────
// guest middleware yok — kullanıcı zaten login'se callback'te session regenerate edilir
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle'])
    ->middleware('throttle:10,1')
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])
    ->middleware('throttle:20,1')
    ->name('auth.google.callback');

// ── Google Calendar Integration (ayrı scope, login'den bağımsız) ─────────────
Route::middleware(['auth'])->group(function (): void {
    Route::get('/integrations/google-calendar/connect',
        [\App\Http\Controllers\Integrations\GoogleCalendarController::class, 'connect'])
        ->middleware('throttle:10,1')
        ->name('integrations.google-calendar.connect');
    Route::get('/integrations/google-calendar/callback',
        [\App\Http\Controllers\Integrations\GoogleCalendarController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('integrations.google-calendar.callback');
    Route::post('/integrations/google-calendar/disconnect',
        [\App\Http\Controllers\Integrations\GoogleCalendarController::class, 'disconnect'])
        ->middleware('throttle:10,1')
        ->name('integrations.google-calendar.disconnect');
    Route::post('/integrations/google-calendar/toggle',
        [\App\Http\Controllers\Integrations\GoogleCalendarController::class, 'toggle'])
        ->middleware('throttle:20,1')
        ->name('integrations.google-calendar.toggle');
    Route::post('/integrations/google-calendar/sync-now',
        [\App\Http\Controllers\Integrations\GoogleCalendarController::class, 'manualPull'])
        ->middleware('throttle:10,1')
        ->name('integrations.google-calendar.sync-now');
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
