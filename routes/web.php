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

// ── SEO Sitemap (public_landings.php'den dinamik) ────────────────────────────
Route::get('/sitemap.xml', function () {
    $landings = config('public_landings', []);
    $base = url('/');
    $urls = [];
    foreach ($landings as $l) {
        if (! ($l['is_active'] ?? false)) continue;
        $path = $l['path'] ?? '';
        // Dynamic placeholder ({slug}, {token}) içeren path'leri sitemap'e koyma
        if (str_contains($path, '{')) continue;
        $priority = match($l['tier'] ?? 'nice') {
            'critical' => '1.0',
            'important' => '0.7',
            default => '0.5',
        };
        $urls[] = ['loc' => $base . $path, 'priority' => $priority];
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
              . '<changefreq>weekly</changefreq>'
              . '<priority>' . $u['priority'] . '</priority></url>' . "\n";
    }
    $xml .= '</urlset>';
    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

// ── robots.txt — sitemap referansı + admin path'leri kapat ────────────────
Route::get('/robots.txt', function () {
    $content = "User-agent: *\nAllow: /\nDisallow: /manager/\nDisallow: /senior/\nDisallow: /student/\nDisallow: /dealer/\nDisallow: /mktg-admin/\nDisallow: /admin/\nDisallow: /im/\nDisallow: /api/\nDisallow: /apply/onay\n\nSitemap: " . url('/sitemap.xml') . "\n";
    return response($content, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

// ── UniMatch Wizard — Public, login gerekmez ────────────────────────────────
Route::middleware('company.context')->group(function (): void {
    $um = \App\Http\Controllers\UniMatch\WizardController::class;
    Route::get('/uni-match',                       [$um, 'landing'])->name('uni-match.landing');
    Route::get('/uni-match/start',                 [$um, 'start'])->middleware('throttle:30,1')->name('uni-match.start');
    Route::get('/uni-match/step/{n}',              [$um, 'step'])->whereNumber('n')->name('uni-match.step');
    Route::post('/uni-match/step/{n}',             [$um, 'saveStep'])->whereNumber('n')->middleware('throttle:60,1')->name('uni-match.step.save');
    Route::get('/uni-match/complete',              [$um, 'complete'])->name('uni-match.complete');
    Route::get('/uni-match/result',                [$um, 'result'])->name('uni-match.result');
    Route::post('/uni-match/convert',              [$um, 'convert'])->middleware('throttle:10,1')->name('uni-match.convert');
    // GET fallback: F5/back tuşundan gelen kullanıcılar 405 görmesin, result'a dön
    Route::get('/uni-match/convert', fn() => redirect()->route('uni-match.result'));

    // Mid-funnel lead capture (step 12 sonrası soft gate — atlanabilir)
    Route::get('/uni-match/lead-capture',           [$um, 'leadCaptureForm'])->name('uni-match.lead-capture.form');
    Route::post('/uni-match/lead-capture',          [$um, 'leadCaptureSubmit'])->middleware('throttle:10,1')->name('uni-match.lead-capture.submit');
    Route::get('/uni-match/lead-capture/skip',      [$um, 'leadCaptureSkip'])->name('uni-match.lead-capture.skip');

    // PDF export — sonuç sayfasından, lead magnet (email opsiyonel)
    Route::get('/uni-match/result/pdf',             [$um, 'resultPdf'])->middleware('throttle:5,1')->name('uni-match.result.pdf');

    // Favori program toggle (AJAX, max 3)
    Route::post('/uni-match/favorite/toggle',       [$um, 'toggleFavorite'])->middleware('throttle:60,1')->name('uni-match.favorite.toggle');

    // Canonical Program detay (public, throttle korumalı)
    Route::get('/program/{program}',               [\App\Http\Controllers\ProgramController::class, 'show'])
        ->middleware('throttle:60,1')->name('program.show');

    // EN→TR çeviri (lazy on-demand, Gemini, 10/dk per IP)
    Route::post('/program/{program}/translate',    [\App\Http\Controllers\ProgramTranslationController::class, 'translate'])
        ->middleware('throttle:10,1')->name('program.translate');
});

// ── Yasal Sayfalar (Privacy / Terms) ─────────────────────────────────────────
// SaaS gerekliliği: Google OAuth consent screen + KVKK/GDPR uyumu için public erişim
// Public yasal sayfalar — manager paneli üzerinden DB'den render edilir
// (eski statik view'lar fallback olarak kalsın diye route name'leri korundu)
Route::get('/privacy',     [\App\Http\Controllers\LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/cookies',     [\App\Http\Controllers\LegalController::class, 'cookies'])->name('legal.cookies');
Route::get('/terms',       [\App\Http\Controllers\LegalController::class, 'terms'])->name('legal.terms');
Route::get('/imprint',     [\App\Http\Controllers\LegalController::class, 'imprint'])->name('legal.imprint');
// Almanca alias'lar (Almanya kullanıcıları için doğal URL)
Route::get('/datenschutz', [\App\Http\Controllers\LegalController::class, 'privacy'])->defaults('lang', 'de')->name('legal.datenschutz');
Route::get('/impressum',   [\App\Http\Controllers\LegalController::class, 'imprint'])->defaults('lang', 'de')->name('legal.impressum');
Route::get('/agb',         [\App\Http\Controllers\LegalController::class, 'terms'])->defaults('lang', 'de')->name('legal.agb');
Route::view('/legal/terms',   'legal.terms');

Route::middleware(['company.context', 'auth', 'manager.role'])->group(function (): void {
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
        // uni-assist üye üniversiteleri etiketle (idempotent — yeniden çalıştırılabilir)
        try {
            \Illuminate\Support\Facades\Artisan::call('unimatch:tag-uni-assist-members');
            $output['unimatch_tag_uni_assist'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $output['unimatch_tag_uni_assist_error'] = $e->getMessage();
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

    // Laravel log'un son 200 satırını göster (prod'da SSH yok, intermittent 500'leri teşhis için)
    Route::get('/system/tail-log', function (\Illuminate\Http\Request $request) {
        $lines = max(50, min(1000, (int) $request->query('lines', 200)));
        $level = trim((string) $request->query('level', ''));

        $logPath = storage_path('logs/laravel.log');
        if (!is_file($logPath)) {
            return response('Log dosyası bulunamadı: ' . $logPath, 404)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        // Son X satırı verimli şekilde oku
        $fh = fopen($logPath, 'r');
        if (!$fh) {
            return response('Log dosyası açılamadı.', 500)->header('Content-Type', 'text/plain');
        }
        $pos = -1;
        $buffer = '';
        $bytes = filesize($logPath);
        $chunkSize = 4096;
        $collected = [];
        while ($bytes > 0 && count($collected) <= $lines) {
            $read = min($chunkSize, $bytes);
            $bytes -= $read;
            fseek($fh, $bytes);
            $buffer = fread($fh, $read) . $buffer;
            $collected = explode("\n", $buffer);
            if ($bytes === 0) break;
        }
        fclose($fh);

        $collected = array_slice($collected, -$lines);
        if ($level !== '') {
            $lv = strtolower($level);
            $collected = array_filter($collected, fn ($l) => stripos($l, '.' . $lv . ':') !== false || stripos($l, '['.$lv.']') !== false);
        }

        return response(implode("\n", $collected), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    })->middleware('throttle:30,1')->name('system.tail-log');

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

// İndirim kodu paylaşım kartı — public landing (extra feature)
// Module disabled olursa 404, mevcut akış etkilenmez.
Route::middleware(['company.context', 'module:discount_codes'])->group(function (): void {
    Route::get('/promo/{code}', [\App\Http\Controllers\Public\PromoController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('promo.show');
});

// ── Public Booking Widget + Landing (booking modülü) ────────────────────────
// Auth opsiyonel: senior settings.is_public=true ise herkes erişebilir,
// aksi halde login student/guest gereklidir (controller kontrol eder).
Route::middleware(['company.context', 'module:booking'])->group(function (): void {
    $bc = \App\Http\Controllers\Booking\PublicBookingController::class;
    Route::get('/randevu', [\App\Http\Controllers\Booking\BookingLandingController::class, 'index'])
        ->middleware('throttle:60,1')->name('booking.landing');
    Route::post('/randevu/sirada', [\App\Http\Controllers\Booking\BookingLandingController::class, 'joinWaitlist'])
        ->middleware('throttle:5,1')->name('booking.waitlist.join');
    Route::get('/book/{slug}',                       [$bc, 'show'])->middleware('throttle:60,1')->name('booking.public.show');
    Route::post('/book/{slug}/slots',                [$bc, 'slots'])->middleware('throttle:120,1')->name('booking.public.slots');
    Route::post('/book/{slug}/confirm',              [$bc, 'confirm'])->middleware('throttle:10,1')->name('booking.public.confirm');
    Route::get('/book/cancel/{token}',               [$bc, 'cancelShow'])->middleware('throttle:60,1')->name('booking.public.cancel.show');
    Route::post('/book/cancel/{token}',              [$bc, 'cancel'])->middleware('throttle:10,1')->name('booking.public.cancel');
});

Route::middleware(['company.context'])->group(function () {
    // Public Platform/SaaS tanıtım sayfası — modüller, kabiliyetler, partner + SaaS pricing
    Route::get('/platform', fn () => view('public.platform-landing'))
        ->middleware('throttle:120,1')->name('public.platform-landing');
    Route::get('/saas',     fn () => redirect()->route('public.platform-landing'))->middleware('throttle:120,1');
    Route::get('/urun',     fn () => redirect()->route('public.platform-landing'))->middleware('throttle:120,1');

    // Public Dealer Başvuru Formu — landing CTA'ları buraya yönlendirir
    $dealerApp = \App\Http\Controllers\Dealer\DealerApplicationController::class;
    Route::get('/satis-ortagi/basvuru',         [$dealerApp, 'create'])->middleware('throttle:30,1')->name('public.dealer-application.create');
    Route::post('/satis-ortagi/basvuru',        [$dealerApp, 'store'])->middleware('throttle:5,1')->name('public.dealer-application.store');
    Route::get('/satis-ortagi/basvuru/tamamlandi', [$dealerApp, 'success'])->middleware('throttle:60,1')->name('public.dealer-application.success');

    // Public Satış Ortağı (dealer) landing — MentorDE Satış Ortaklığı Programı tanıtımı
    Route::get('/satis-ortagi', function () {
        $cfg = config('dealer_landing');
        $theme = \App\Support\PortalTheme::resolve();
        $managerAccent = $theme['accent_manager'] ?? '#1e40af';

        // Günlük deterministic artış — bugüne kadar her günün artışını topla
        $growthStart = \Carbon\Carbon::parse($cfg['growth_start_date'])->startOfDay();
        $today = \Carbon\Carbon::today();
        $daysElapsed = max(0, (int) $growthStart->diffInDays($today));

        $dailyGrowth = $cfg['daily_growth'];

        // Tek günün artışını deterministic hesapla (date + key hash bazlı)
        $computeDailyIncrement = function (string $dateStr, string $key, array $dist): int {
            $seed = crc32($dateStr . ':' . $key);
            $roll = $seed % 100; // 0-99
            if ($roll < $dist['skip_pct']) return 0;
            if ($roll < $dist['skip_pct'] + $dist['single_pct']) return 1;
            return 2;
        };

        // Tüm günleri iteratif topla (cache'li olsa daha iyi — şimdilik 30 gün'den az olduğu için OK)
        $growth = ['sellers' => 0, 'applications' => 0, 'students' => 0, 'commissions_eur' => 0];
        $commissionRange = $dailyGrowth['commissions_eur_per_application'] ?? [180, 380];

        for ($i = 0; $i <= $daysElapsed; $i++) {
            $dateStr = $growthStart->copy()->addDays($i)->toDateString();

            $appInc = $computeDailyIncrement($dateStr, 'applications', $dailyGrowth['applications']);
            $growth['applications'] += $appInc;

            $growth['sellers'] += $computeDailyIncrement($dateStr, 'sellers', $dailyGrowth['sellers']);
            $growth['students'] += $computeDailyIncrement($dateStr, 'students', $dailyGrowth['students']);

            // Commissions: her yeni application için random €180-380 (deterministic)
            if ($appInc > 0) {
                for ($j = 0; $j < $appInc; $j++) {
                    $commSeed = crc32($dateStr . ':comm:' . $j);
                    $growth['commissions_eur'] += $commissionRange[0] + ($commSeed % ($commissionRange[1] - $commissionRange[0]));
                }
            }
        }

        $counters = [
            'sellers' => (int) $cfg['historical_sellers']
                + $growth['sellers']
                + \App\Models\User::query()->withoutGlobalScopes()
                    ->whereIn('role', ['dealer'])
                    ->where('is_active', true)
                    ->count(),
            'applications' => (int) $cfg['historical_applications']
                + $growth['applications']
                + \App\Models\GuestApplication::query()->withoutGlobalScopes()->count(),
            'students' => (int) $cfg['historical_students']
                + $growth['students']
                + \App\Models\User::query()->withoutGlobalScopes()
                    ->where('role', 'student')
                    ->count(),
            'commissions_eur' => (int) $cfg['historical_commissions_eur'] + $growth['commissions_eur'],
        ];

        return view('public.dealer-landing', [
            'counters' => $counters,
            'managerAccent' => $managerAccent,
        ]);
    })
        ->middleware('throttle:120,1')->name('public.dealer-landing');
    Route::get('/partner',      fn () => redirect()->route('public.dealer-landing'))
        ->middleware('throttle:120,1'); // alias

    // Public AI Labs FAQ — Manager'ın yayınladığı SSS'lar
    Route::get('/sss', [\App\Http\Controllers\AiLabs\PublicFaqController::class, 'index'])
        ->middleware('throttle:120,1')->name('public.faq');
    Route::get('/faq', [\App\Http\Controllers\AiLabs\PublicFaqController::class, 'index'])
        ->middleware('throttle:120,1'); // /faq de aynı sayfa (İngilizce slug)

    // Topluluk SSS Arşivi — anonim toplulu forumlarından derlenmiş 374 soru, 15 konu
    Route::get('/sss/topluluk', [\App\Http\Controllers\Public\CommunityFaqController::class, 'index'])
        ->middleware('throttle:120,1')->name('public.community-faq');
    Route::get('/sss/topluluk/{topic}', [\App\Http\Controllers\Public\CommunityFaqController::class, 'topic'])
        ->where('topic', '[a-z_]{2,32}')
        ->middleware('throttle:120,1')->name('public.community-faq.topic');

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

// ── Public Belge Yükleme (Premium: doc_request modülü) ────────────────────────
// Manager'dan aday öğrenciye gönderilen tek-kullanımlık link. Login gerekmez;
// token URL parametresi yetki kanıtıdır. /u/{token}.
Route::get('/u/{token}',  [\App\Http\Controllers\PublicDocumentUploadController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{20,64}')
    ->middleware('throttle:30,1')
    ->name('public.document-upload.show');
Route::post('/u/{token}', [\App\Http\Controllers\PublicDocumentUploadController::class, 'store'])
    ->where('token', '[A-Za-z0-9]{20,64}')
    ->middleware('throttle:10,1')
    ->name('public.document-upload.store');

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // Throttle: 1 dakikada 15 deneme (Turnstile + bcrypt + IP-based zaten korur).
    // 5 çok dar — yeni kullanıcı yanlış şifre + 2-3 deneme = 429
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:15,1');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])
        ->name('password.email')
        ->middleware('throttle:10,1');
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
// Yeni Google kullanıcısı için rol seçici (Aday Öğrenci vs Partner Bayi)
Route::get('/auth/google/choose-role', [SocialAuthController::class, 'showRoleChoice'])
    ->middleware('throttle:30,1')
    ->name('auth.google.choose-role');
Route::post('/auth/google/choose-role', [SocialAuthController::class, 'submitRoleChoice'])
    ->middleware('throttle:10,1')
    ->name('auth.google.choose-role.submit');

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

// Public welcome verify — apply sonrası gönderilen maildeki linke tıklayınca
// (auth gerektirmez, signed URL ile imzalanmış)
Route::get('/welcome/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyPublic'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('welcome.verify');

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

// ── Deploy post-hook (KAS shared hosting'de SSH yok, web ile tetikleme) ──────
// Kullanım: curl 'https://panel.mentorde.com/_deploy/run-pending?secret=XXX'
// Çalıştırır: php artisan migrate --force + UniMatchDripSequencesSeeder
// Secret .env'de DEPLOY_MIGRATE_SECRET — boşsa endpoint 404 döner.
Route::get('/_deploy/run-pending', function (\Illuminate\Http\Request $request) {
    $expected = (string) env('DEPLOY_MIGRATE_SECRET', '');
    $given    = (string) $request->query('secret', '');

    if ($expected === '' || ! hash_equals($expected, $given)) {
        abort(404);
    }

    $output = "═══ Deploy post-hook " . now()->toIso8601String() . " ═══\n\n";

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output .= ">>> migrate --force\n" . \Illuminate\Support\Facades\Artisan::output() . "\n";

        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'UniMatchDripSequencesSeeder',
            '--force' => true,
        ]);
        $output .= ">>> db:seed UniMatchDripSequencesSeeder\n" . \Illuminate\Support\Facades\Artisan::output() . "\n";

        // Yasal metinleri her deploy'da senkron tut — seed-content/policy/*.txt
        // dosyalari kaynaktir, DB updateOrCreate ile guncellenir (idempotent).
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'DefaultPolicyTemplatesSeeder',
            '--force' => true,
        ]);
        $output .= ">>> db:seed DefaultPolicyTemplatesSeeder\n" . \Illuminate\Support\Facades\Artisan::output() . "\n";

        // Cache temizle (yeni route/config görsün)
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        // Sigortalı: route cache dosyası varsa elle de sil (artisan başarısız olursa)
        $rc = base_path('bootstrap/cache/routes-v7.php');
        if (file_exists($rc)) @unlink($rc);
        $output .= ">>> view:clear + config:clear + route:clear OK\n";

        // KAS opcache + composer classmap stale fix:
        // FTP upload sonrası opcache eski autoload_classmap.php'yi serve etmeye devam
        // ediyor → "Failed to open stream" / "Class not found" 500'leri çıkıyor.
        // Reset ile tüm cache pencereleri sıfırlanır.
        if (function_exists('opcache_reset')) {
            $reset = @opcache_reset();
            $output .= ">>> opcache_reset: " . ($reset ? 'OK' : 'no-op (disabled)') . "\n";
        } else {
            $output .= ">>> opcache_reset: function not available\n";
        }
        // Realpath cache da invalidate olsun (file_exists/include yolları için)
        if (function_exists('clearstatcache')) {
            clearstatcache(true);
            $output .= ">>> clearstatcache OK\n";
        }

        // Opsiyonel: junk DAM klasor cleanup — ?cleanup=junk-folders
        // Test sirasinda yaratilmis 'tt'/'dd' gibi 1-2 karakterlik
        // is_system=false klasorleri soft-delete eder. Idempotent.
        if ($request->query('cleanup') === 'junk-folders') {
            $junkNames = ['tt', 'dd', 'TT', 'DD', 'aa', 'bb', 'cc', 'test', 'sample'];
            $deleted = \DB::table('digital_asset_folders')
                ->whereIn('name', $junkNames)
                ->where('is_system', false)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
            $output .= ">>> cleanup junk-folders: {$deleted} folder soft-deleted\n";
        }

        // guest_required_documents tablosunda kayitli zorunlu belgeleri listele
        // Kullanim: /_deploy/run-pending?cleanup=audit-required-docs
        if ($request->query('cleanup') === 'audit-required-docs') {
            $rows = DB::table('guest_required_documents')
                ->orderBy('application_type')->orderBy('sort_order')
                ->get(['application_type', 'document_code', 'name', 'is_required', 'is_active', 'company_id']);
            $output .= "\n>>> guest_required_documents (" . $rows->count() . " row):\n";
            $byType = [];
            foreach ($rows as $r) {
                $byType[$r->application_type][] = $r;
            }
            foreach ($byType as $type => $list) {
                $output .= "\n  [" . $type . "] (" . count($list) . " belge):\n";
                foreach ($list as $r) {
                    $req = $r->is_required ? 'REQ' : 'opt';
                    $act = $r->is_active ? 'active' : 'INACTIVE';
                    $output .= sprintf("    - %s | %s | cid=%s | %s | %s\n",
                        $r->document_code, $r->name, $r->company_id ?? 'NULL', $req, $act);
                }
            }
        }

        // Eksik zorunlu belgeleri seeder'dan yeniden insert et (varsa atlar)
        // Kullanim: /_deploy/run-pending?cleanup=reseed-required-docs
        if ($request->query('cleanup') === 'reseed-required-docs') {
            $companyId = Schema::hasTable('companies')
                ? (int) DB::table('companies')->where('is_active', true)->orderBy('id')->value('id')
                : 0;
            $companyId = $companyId > 0 ? $companyId : null;
            $now = now();

            $catalog = [
                'bachelor' => [
                    ['DOC-DIPL', 'Lise Diploması',                    10],
                    ['DOC-TRNS', 'Transkript',                         20],
                    ['DOC-UNWN', 'Üniversite Kazandı Belgesi',         30],
                    ['DOC-YKSP', 'YKS Yerleştirme Belgesi',            40],
                    ['DOC-IDCR', 'Kimlik Ön-Arka Fotoğrafı',           50],
                    ['DOC-PASS', 'Pasaport İlk 2 Sayfa',               60],
                ],
                'master' => [
                    ['DOC-DIPL', 'Üniversite Diploması + yeminli tercüme', 10],
                    ['DOC-TRNS', 'Üniversite Transkript + yeminli tercüme', 20],
                    ['DOC-UNWN', 'Üniversite Kabul/Referans Belgesi',  30],
                    ['DOC-YKSP', 'Ek Akademik Yerleştirme Belgesi',    40],
                    ['DOC-IDCR', 'Kimlik Ön-Arka Fotoğrafı',           50],
                    ['DOC-PASS', 'Pasaport İlk 2 Sayfa',               60],
                ],
                'dil_kursu' => [
                    ['DOC-PASS', 'Pasaport İlk 2 Sayfa',               10],
                    ['DOC-IDCR', 'Kimlik Ön-Arka Fotoğrafı',           20],
                ],
            ];
            $inserted = 0; $existed = 0;
            foreach ($catalog as $type => $docs) {
                foreach ($docs as [$code, $name, $sort]) {
                    $exists = DB::table('guest_required_documents')
                        ->where('application_type', $type)
                        ->where('document_code', $code)
                        ->exists();
                    if ($exists) { $existed++; continue; }
                    DB::table('guest_required_documents')->insert([
                        'company_id' => $companyId,
                        'application_type' => $type,
                        'document_code' => $code,
                        'category_code' => $code,
                        'name' => $name,
                        'description' => $type . ' için zorunlu',
                        'is_required' => 1,
                        'accepted' => 'pdf,jpg,png',
                        'max_mb' => 10,
                        'sort_order' => $sort,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $inserted++;
                }
            }
            $output .= ">>> reseed-required-docs: {$inserted} insert, {$existed} zaten vardi\n";
        }

        // role_page_visibility tablosunda guest icin kayitli false satirlari listele
        // (debug — manager kayit sonrasi ne yazilmis gormek icin)
        // Kullanim: /_deploy/run-pending?cleanup=audit-page-visibility
        if ($request->query('cleanup') === 'audit-page-visibility') {
            $rows = DB::table('role_page_visibility')
                ->orderBy('company_id')->orderBy('role')->orderBy('page_key')
                ->get(['company_id', 'role', 'page_key', 'is_visible', 'updated_at']);
            $output .= "\n>>> role_page_visibility full dump (" . $rows->count() . " row):\n";
            foreach ($rows as $r) {
                $vis = $r->is_visible ? 'visible' : 'HIDDEN';
                $output .= sprintf("  cid=%d role=%-15s page=%-20s %s @ %s\n",
                    $r->company_id, $r->role, $r->page_key, $vis, $r->updated_at);
            }
        }

        // SOS: tum role_page_visibility kayitlarini sil (manager UI'sinden yanlis kayit
        // yazildiginda default-true mantigina geri don). Bu YIKICI — manager'in
        // kasten kapattigi sayfalar da acilir.
        // Kullanim: /_deploy/run-pending?cleanup=reset-page-visibility
        if ($request->query('cleanup') === 'reset-page-visibility') {
            $deleted = DB::table('role_page_visibility')->delete();
            $output .= ">>> reset-page-visibility: {$deleted} satir silindi (default-true mantigina dondu)\n";
            // Cache temizle tum company icin
            if (Schema::hasTable('companies')) {
                DB::table('companies')->select('id')->get()->each(function ($c) {
                    \Illuminate\Support\Facades\Cache::forget("page_visibility:{$c->id}");
                });
            }
        }

        // public/storage → storage/app/public symbolic link.
        // Profile foto, doc preview vb. icin gerekli. Prod'da bir kez calistirildiktan
        // sonra kalici (idempotent — link zaten varsa --force ile yeniden olusturur).
        // Kullanim: /_deploy/run-pending?cleanup=storage-link
        if ($request->query('cleanup') === 'storage-link') {
            try {
                \Illuminate\Support\Facades\Artisan::call('storage:link', ['--force' => true]);
                $output .= ">>> storage:link --force\n" . \Illuminate\Support\Facades\Artisan::output() . "\n";
            } catch (\Throwable $e) {
                $output .= ">>> storage:link FAILED: " . $e->getMessage() . "\n";
            }
        }

        // KAS shared hosting'de bazen view:clear artisan komutu file'lari
        // gercekten silemiyor (permission / opcache lock). Manuel temizlik:
        // Kullanim: /_deploy/run-pending?cleanup=clear-views
        if ($request->query('cleanup') === 'clear-views') {
            $viewsPath = storage_path('framework/views');
            $deletedCount = 0;
            if (is_dir($viewsPath)) {
                foreach (glob($viewsPath . '/*.php') ?: [] as $f) {
                    if (@unlink($f)) $deletedCount++;
                }
            }
            $output .= ">>> cleanup clear-views: {$deletedCount} compiled view dosyasi silindi\n";
        }

        // Safety net: deploy maintenance mode FTP-rm fail ettiyse manuel cikis
        // Kullanim: /_deploy/run-pending?cleanup=clear-maintenance
        if ($request->query('cleanup') === 'clear-maintenance') {
            $maint = storage_path('framework/maintenance.php');
            if (file_exists($maint)) {
                @unlink($maint);
                $output .= ">>> maintenance.php deleted — site live again\n";
            } else {
                $output .= ">>> maintenance.php zaten yok (site normalde)\n";
            }
        }

        // Opsiyonel inline log tail — ?tail=200 ile tetikle (route cache'siz fallback)
        $tail = (int) $request->query('tail', 0);
        if ($tail > 0) {
            $tail = max(10, min(1000, $tail));
            $date = now()->format('Y-m-d');
            $logFile = storage_path("logs/laravel-{$date}.log");
            if (! file_exists($logFile)) $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $size = filesize($logFile);
                $f = fopen($logFile, 'r');
                $chunk = 65536; $buf = ''; $count = 0; $pos = $size;
                while ($pos > 0 && $count <= $tail) {
                    $r = min($chunk, $pos); $pos -= $r;
                    fseek($f, $pos);
                    $buf = fread($f, $r) . $buf;
                    $count = substr_count($buf, "\n");
                }
                fclose($f);
                $rows = array_slice(explode("\n", $buf), -$tail);
                $output .= "\n═══ TAIL {$logFile} (last {$tail}) ═══\n" . implode("\n", $rows) . "\n";
            } else {
                $output .= "\n[tail] log dosyası yok: {$logFile}\n";
            }
        }

        $output .= "\n═══ TAMAM ═══\n";
    } catch (\Throwable $e) {
        $output .= "\n!!! HATA: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
        return response($output, 500, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    return response($output, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
})->middleware('throttle:60,10')->name('deploy.run-pending');

// ── Log tail — prod 500 hatalarını tespit etmek için son N satır laravel.log
// Kullanım: curl '.../_deploy/tail-log?secret=XXX&lines=200&date=2026-05-03'
// date opsiyonel (default bugün), lines max 1000.
Route::get('/_deploy/tail-log', function (\Illuminate\Http\Request $request) {
    $expected = (string) env('DEPLOY_MIGRATE_SECRET', '');
    $given    = (string) $request->query('secret', '');
    if ($expected === '' || ! hash_equals($expected, $given)) {
        abort(404);
    }

    $date = $request->query('date', now()->format('Y-m-d'));
    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) abort(400, 'invalid date');
    $lines = max(10, min(1000, (int) $request->query('lines', 200)));

    $logFile = storage_path("logs/laravel-{$date}.log");
    if (! file_exists($logFile)) {
        // Fallback: tek dosya log
        $alt = storage_path('logs/laravel.log');
        if (file_exists($alt)) $logFile = $alt;
        else return response("Log dosyası yok: {$date}", 404, ['Content-Type' => 'text/plain']);
    }

    // Son N satır (büyük dosyalar için stream)
    $size = filesize($logFile);
    $f = fopen($logFile, 'r');
    if (! $f) abort(500, 'cannot open log');

    $chunk = 65536;
    $tail = '';
    $count = 0;
    $pos = $size;
    while ($pos > 0 && $count <= $lines) {
        $read = min($chunk, $pos);
        $pos -= $read;
        fseek($f, $pos);
        $tail = fread($f, $read) . $tail;
        $count = substr_count($tail, "\n");
    }
    fclose($f);
    $rows = explode("\n", $tail);
    $rows = array_slice($rows, -$lines);

    $out = "═══ {$logFile} (last {$lines} lines) ═══\n\n";
    $out .= implode("\n", $rows);
    return response($out, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
})->middleware('throttle:60,10')->name('deploy.tail-log');
