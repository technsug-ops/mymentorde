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

// ── SEO Sitemap (public_landings.php'den dinamik + senior profilleri) ────────
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

    // Marketplace Phase 7 — Aktif & public senior profilleri sitemap'e ekle
    try {
        \App\Models\SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('is_public', true)
            ->where('is_active', true)
            ->whereNotNull('public_slug')
            ->select(['public_slug'])
            ->chunk(500, function ($rows) use (&$urls, $base): void {
                foreach ($rows as $r) {
                    if (empty($r->public_slug)) continue;
                    $urls[] = [
                        'loc'      => $base . '/uzman/' . $r->public_slug,
                        'priority' => '0.7',
                    ];
                }
            });
    } catch (\Throwable $e) {
        // Tablo / index yoksa sessizce geç — sitemap kalan landing'lerle render edilir
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

    // Public program catalog browse — wizard'sız, doğrudan filtre + arama
    Route::get('/uni-match/programs', [\App\Http\Controllers\UniMatch\ProgramSearchController::class, 'publicIndex'])
        ->middleware('throttle:120,1')
        ->name('uni-match.programs');

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

    // /program-search route moved to routes/manager.php (lftp prod transfer reliability)

    // EN→TR çeviri (lazy on-demand, Gemini, 10/dk per IP)
    Route::post('/program/{program}/translate',    [\App\Http\Controllers\ProgramTranslationController::class, 'translate'])
        ->middleware('throttle:10,1')->name('program.translate');
});

// ── Dinamik Open Graph image generator (GD library, sosyal medya paylasimlari icin) ──
Route::get('/og/brand.png',    [\App\Http\Controllers\OgImageController::class, 'brand'])->name('og.brand');
Route::get('/og/promo.png',    [\App\Http\Controllers\OgImageController::class, 'promo'])->name('og.promo');
Route::get('/og/unimatch.png', [\App\Http\Controllers\OgImageController::class, 'unimatch'])->name('og.unimatch');

// ── Yasal Sayfalar (Privacy / Terms) ─────────────────────────────────────────
// SaaS gerekliliği: Google OAuth consent screen + KVKK/GDPR uyumu için public erişim
// Public yasal sayfalar — manager paneli üzerinden DB'den render edilir
// (eski statik view'lar fallback olarak kalsın diye route name'leri korundu)
Route::get('/privacy',     [\App\Http\Controllers\LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/cookies',     [\App\Http\Controllers\LegalController::class, 'cookies'])->name('legal.cookies');
Route::get('/terms',       [\App\Http\Controllers\LegalController::class, 'terms'])->name('legal.terms');
Route::get('/imprint',     [\App\Http\Controllers\LegalController::class, 'imprint'])->name('legal.imprint');
// /legal/* alias'lar — dealer-landing, platform-landing vb. view'larda eski URL kullanılıyor
Route::get('/legal/privacy', [\App\Http\Controllers\LegalController::class, 'privacy']);
Route::get('/legal/cookies', [\App\Http\Controllers\LegalController::class, 'cookies']);
Route::get('/legal/terms',   [\App\Http\Controllers\LegalController::class, 'terms']);
Route::get('/legal/imprint', [\App\Http\Controllers\LegalController::class, 'imprint']);
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
        // KAS'ta SSH yok → OPcache eski derlenmiş PHP'yi sunabilir (deploy sonrası
        // kod degisikligi etkisiz kalir). optimize:clear bunu temizlemez; opcache_reset eder.
        try {
            $output['opcache_reset'] = function_exists('opcache_reset')
                ? (opcache_reset() ? 'sifirlandi' : 'devre disi/sifirlanamadi')
                : 'opcache yok';
        } catch (\Throwable $e) {
            $output['opcache_reset_error'] = $e->getMessage();
        }
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

    // VIP hesabı oluştur/yükselt — KAS SSH yok, buradan tek-tıkla tetiklenir.
    // Hedef e-posta HARDCODED (keyfi hesap yükseltilemez = güvenli). Idempotent.
    Route::get('/system/make-vip', function () {
        $email = 'admin@panel.mentorde.com';
        $u     = \App\Models\User::query()->where('email', $email)->first();

        if ($u) {
            if ((string) $u->role === \App\Models\User::ROLE_VIP) {
                $msg = "Zaten VIP: {$email} — çıkış yapıp tekrar girin.";
            } else {
                $old = (string) $u->role;
                $u->forceFill(['role' => \App\Models\User::ROLE_VIP])->save();
                $msg = "✓ Yükseltildi: {$email} ({$old} → vip). Çıkış yapıp tekrar girin.";
            }
        } else {
            // Şifre kodda saklanmaz: runtime'da rastgele üretilir, bir kez gösterilir.
            $tempPass  = \Illuminate\Support\Str::random(16);
            $companyId = (int) (\App\Models\Company::query()->where('is_active', true)->orderBy('id')->value('id') ?? 1);
            $u = new \App\Models\User();
            $u->forceFill([
                'name'              => 'VIP Ortak',
                'email'             => $email,
                'role'              => \App\Models\User::ROLE_VIP,
                'company_id'        => $companyId,
                'is_active'         => true,
                'email_verified_at' => now(),
                'password'          => \Illuminate\Support\Facades\Hash::make($tempPass),
            ])->save();

            // Şifre belirleme daveti de gönder (e-posta çalışıyorsa kendi şifresini kurar).
            try { \Illuminate\Support\Facades\Password::sendResetLink(['email' => $email]); } catch (\Throwable) {}

            $msg = "✓ Oluşturuldu: {$email}\nTek seferlik geçici şifre: {$tempPass}\n"
                 . "(Bu şifre hiçbir yerde saklanmaz — şimdi not alın. E-posta ile şifre belirleme bağlantısı da gönderildi.)";
        }

        return response($msg . "\n", 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:5,1')->name('system.make-vip');

    // Onaylı ama bayi hesabı oluşmamış başvuruları provision et (backfill).
    // fe16350 öncesi onaylanan başvurular approved_dealer_id=null kaldı → Bayi
    // Yönetimi'nde görünmüyor. Idempotent: zaten provision edilmişleri atlar.
    Route::get('/system/backfill-dealers', function () {
        $svc      = app(\App\Services\DealerProvisioningService::class);
        $approved = \App\Models\DealerApplication::withoutGlobalScopes()
            ->where('status', 'approved')->orderBy('id')->get();

        $lines = [];
        $new = 0; $skip = 0; $fail = 0; $cids = [];
        foreach ($approved as $app) {
            $cids[(int) ($app->company_id ?? 0)] = true;
            $r = $svc->fromApplication($app);
            if (!$r['ok'])           { $fail++; $lines[] = "FAIL #{$app->id} {$app->email} — {$r['message']}"; }
            elseif ($r['skipped'])   { $skip++; }
            else                     { $new++;  $lines[] = "NEW  #{$app->id} {$app->email} → {$r['dealer_code']}"; }
        }

        // Etkilenen şirketlerin Bayi Yönetimi cache'ini temizle ki hemen görünsün.
        foreach (array_keys($cids) as $cid) {
            \Illuminate\Support\Facades\Cache::forget("mgr_dealers_{$cid}");
        }

        $dealerByCompany = \App\Models\Dealer::withoutGlobalScopes()
            ->selectRaw('company_id, count(*) c')->groupBy('company_id')->get();

        $report  = "ONAYLI BAŞVURU: {$approved->count()}\n";
        $report .= "Yeni provision: {$new} | Zaten vardı: {$skip} | Hata: {$fail}\n\n";
        $report .= ($lines ? implode("\n", $lines) : '(yeni provision edilen yok)') . "\n\n";
        $report .= "Dealer company dağılımı (toplam " . \App\Models\Dealer::withoutGlobalScopes()->count() . "):\n";
        foreach ($dealerByCompany as $row) {
            $report .= "  company {$row->company_id}: {$row->c} bayi\n";
        }
        $report .= "\nNot: Görünmüyorsa company_id eşleşmesini kontrol et (bayinin company_id'si = giriş yaptığın yöneticinin şirketi olmalı).\n";

        return response($report, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:5,1')->name('system.backfill-dealers');

    // Köprü backfill: docs_pending + senior atanmış + henüz köprülenmemiş aday
    // öğrencileri süreç takibine bağlar (StudentAssignment + başvuru hazırlık + kickoff task).
    // Idempotent. Filiz gibi mevcut aday öğrenciler için tek seferlik tetikleyici.
    Route::get('/system/bridge-docs-pending', function (\Illuminate\Http\Request $request) {
        $svc = app(\App\Services\StudentBridgeService::class);

        $emailFilter = trim((string) $request->query('senior', '')); // opsiyonel: ?senior=drozkanf@gmail.com
        $guests = \App\Models\GuestApplication::query()->withoutGlobalScopes()
            ->where('lead_status', 'docs_pending')
            ->whereNotNull('assigned_senior_email')
            ->where('assigned_senior_email', '!=', '')
            ->where(function ($q) {
                $q->whereNull('converted_student_id')->orWhere('converted_student_id', '');
            })
            ->when($emailFilter !== '', fn ($q) => $q->whereRaw('lower(assigned_senior_email) = ?', [strtolower($emailFilter)]))
            ->orderBy('id')
            ->get();

        $lines = []; $bridged = 0; $skip = 0; $fail = 0;
        foreach ($guests as $g) {
            try {
                $a = $svc->bridgeFromGuest($g);
                if ($a) { $bridged++; $lines[] = "OK   guest#{$g->id} {$g->assigned_senior_email} → {$a->student_id}"; }
                else    { $skip++;    $lines[] = "SKIP guest#{$g->id} (senior yok?)"; }
            } catch (\Throwable $e) {
                $fail++; $lines[] = "FAIL guest#{$g->id} — " . $e->getMessage();
            }
        }

        $report  = "docs_pending + senior atanmış + köprülenmemiş: {$guests->count()}\n";
        $report .= "Köprülendi: {$bridged} | Atlandı: {$skip} | Hata: {$fail}\n\n";
        $report .= ($lines ? implode("\n", $lines) : '(köprülenecek aday yok)') . "\n";

        // ── TEŞHİS: senior'a atanmış TÜM aday öğrenciler hangi statüde? ──
        $diagQ = \App\Models\GuestApplication::query()->withoutGlobalScopes()
            ->whereNotNull('assigned_senior_email')->where('assigned_senior_email', '!=', '')
            ->when($emailFilter !== '', fn ($q) => $q->whereRaw('lower(assigned_senior_email) = ?', [strtolower($emailFilter)]));
        $byStatus = (clone $diagQ)->selectRaw('lead_status, count(*) c')->groupBy('lead_status')->pluck('c', 'lead_status');
        $report .= "\n── TEŞHİS" . ($emailFilter !== '' ? " ({$emailFilter})" : ' (tüm seniorlar)') . " — atanmış aday öğrenciler statü dağılımı:\n";
        if ($byStatus->isEmpty()) {
            $report .= "  (bu senior'a atanmış aday öğrenci YOK — e-posta eşleşmesini kontrol et)\n";
        } else {
            foreach ($byStatus as $st => $c) {
                $report .= "  " . ($st ?: '(boş)') . ": {$c}\n";
            }
        }
        $report .= "\nNot: Köprü sadece 'docs_pending' (Evrak Bekliyor) için kurulur.\n";
        $report .= "Tüm atanmışları statü farketmeksizin köprülemek istersen: &all=1 ekle.\n";

        // Opsiyonel: ?all=1 → statü farketmeksizin tüm atanmış + köprülenmemiş aday'ı köprüle
        if ($request->boolean('all')) {
            $allGuests = (clone $diagQ)->where(function ($q) {
                $q->whereNull('converted_student_id')->orWhere('converted_student_id', '');
            })->orderBy('id')->get();
            $b2 = 0; $f2 = 0;
            foreach ($allGuests as $g) {
                try { if ($svc->bridgeFromGuest($g)) { $b2++; } } catch (\Throwable $e) { $f2++; }
            }
            $report .= "\n[ALL] Statü farketmeksizin köprülenen: {$b2} | hata: {$f2}\n";
        }

        return response($report, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:5,1')->name('system.bridge-docs-pending');

    // Köprü GERİ AL: bridge ile oluşmuş (converted_to_student=false) takip kayıtlarını +
    // kickoff task'ları kaldırır, guest bağını temizler. Aday öğrenciler tekrar sadece
    // aday olur (Aktif Öğrenciler'den çıkar). Tam dönüşmüş öğrenciye dokunmaz.
    Route::get('/system/bridge-rollback', function (\Illuminate\Http\Request $request) {
        $svc = app(\App\Services\StudentBridgeService::class);
        $emailFilter = trim((string) $request->query('senior', ''));

        $guests = \App\Models\GuestApplication::query()->withoutGlobalScopes()
            ->whereNotNull('converted_student_id')->where('converted_student_id', '!=', '')
            ->where('converted_to_student', false)
            ->when($emailFilter !== '', fn ($q) => $q->whereRaw('lower(assigned_senior_email) = ?', [strtolower($emailFilter)]))
            ->orderBy('id')->get();

        $lines = []; $done = 0; $fail = 0;
        foreach ($guests as $g) {
            try {
                $sid = (string) $g->converted_student_id;
                if ($svc->rollbackBridge($g)) { $done++; $lines[] = "GERİ ALINDI guest#{$g->id} → {$sid}"; }
            } catch (\Throwable $e) {
                $fail++; $lines[] = "FAIL guest#{$g->id} — " . $e->getMessage();
            }
        }

        $report  = "Köprü kaydı (converted_to_student=false): {$guests->count()}\n";
        $report .= "Geri alındı: {$done} | Hata: {$fail}\n\n";
        $report .= ($lines ? implode("\n", $lines) : '(geri alınacak köprü kaydı yok)') . "\n";
        $report .= "\nNot: ?senior=email ile sınırlayabilirsin. Geri alma soft-delete (kurtarılabilir).\n";

        return response($report, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:5,1')->name('system.bridge-rollback');

    // Dönüşmüş öğrencinin danışmanını adayın atanmış danışmanına eşitle (#13).
    // convert() eskiden assigned_senior_email'i kullanmıyordu -> öğrenci başka/boş
    // senior'a düşüp aday'ın danışmanının "Öğrencilerim"inde görünmüyordu.
    // ?student=Survey veya ?senior=email ile sınırlanabilir. Idempotent.
    // ?create=1 → converted_student_id var ama StudentAssignment YOK/soft-deleted
    //   ise eksik kaydı (aynı student_id ile) yeniden oluşturur/geri alır.
    Route::get('/system/fix-converted-senior', function (\Illuminate\Http\Request $request) {
        $studentFilter = trim((string) $request->query('student', ''));
        $seniorFilter  = trim((string) $request->query('senior', ''));
        $doCreate      = $request->boolean('create');

        $guests = \App\Models\GuestApplication::query()->withoutGlobalScopes()
            ->where('converted_to_student', true)
            ->whereNotNull('converted_student_id')->where('converted_student_id', '!=', '')
            ->whereNotNull('assigned_senior_email')->where('assigned_senior_email', '!=', '')
            ->when($seniorFilter !== '', fn ($q) => $q->whereRaw('lower(assigned_senior_email)=?', [strtolower($seniorFilter)]))
            ->get();

        $lines = []; $fixed = 0; $ok = 0; $missing = 0; $created = 0; $restored = 0;
        foreach ($guests as $g) {
            $sid = (string) $g->converted_student_id;
            $name = trim(($g->first_name ?? '') . ' ' . ($g->last_name ?? ''));
            if ($studentFilter !== '' && stripos($name, $studentFilter) === false && stripos($sid, $studentFilter) === false) {
                continue;
            }
            $want = strtolower(trim((string) $g->assigned_senior_email));

            // withTrashed: soft-deleted köprü kaydını da yakala.
            $a = \App\Models\StudentAssignment::withTrashed()->withoutGlobalScopes()
                ->where('student_id', $sid)->first();

            if (!$a) {
                if (!$doCreate) { $missing++; $lines[] = "YOK  {$name} ({$sid}) — StudentAssignment bulunamadı (?create=1 ile oluştur)"; continue; }
                $seq = ((int) \App\Models\StudentAssignment::withTrashed()->withoutGlobalScopes()->max('internal_sequence')) + 1;
                \App\Models\StudentAssignment::query()->create([
                    'company_id'        => (int) ($g->company_id ?? 0) > 0 ? (int) $g->company_id : null,
                    'student_id'        => $sid,
                    'internal_sequence' => $seq,
                    'senior_email'      => $g->assigned_senior_email,
                    'branch'            => trim((string) ($g->branch ?? '')) ?: null,
                    'risk_level'        => 'normal',
                    'payment_status'    => 'ok',
                    'dealer_id'         => trim((string) ($g->dealer_code ?? '')) ?: null,
                    'is_archived'       => false,
                ]);
                $created++; $lines[] = "OLUŞTURULDU {$name} ({$sid}) → {$want}";
                continue;
            }

            if (method_exists($a, 'trashed') && $a->trashed()) {
                if (!$doCreate) { $missing++; $lines[] = "SİLİNMİŞ {$name} ({$sid}) — soft-deleted (?create=1 ile geri al)"; continue; }
                $a->restore();
                $a->forceFill(['senior_email' => $g->assigned_senior_email, 'is_archived' => false])->save();
                $restored++; $lines[] = "GERİ ALINDI {$name} ({$sid}) → {$want}";
                continue;
            }

            $have = strtolower(trim((string) $a->senior_email));
            if ($have === $want) { $ok++; continue; }
            $a->forceFill(['senior_email' => $g->assigned_senior_email, 'is_archived' => false])->save();
            $fixed++; $lines[] = "DÜZELTİLDİ {$name} ({$sid}): '{$have}' → '{$want}'";
        }

        $report = "Dönüşmüş + atanmış danışmanlı: {$guests->count()}\n"
            . "Düzeltildi: {$fixed} | Oluşturuldu: {$created} | Geri alındı: {$restored} | Zaten doğru: {$ok} | Eksik: {$missing}\n\n"
            . ($lines ? implode("\n", $lines) : '(düzeltilecek yok)') . "\n";
        return response($report, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:5,1')->name('system.fix-converted-senior');

    // Bekleyen bildirim/mail kuyruğunu işle (#27). KAS'ta queue worker/cron yoksa
    // job'lar 'jobs' tablosunda takılı kalır, mail gitmez. Bu endpoint elle veya
    // KAS URL-cron ile (her dakika) tetiklenebilir.
    Route::get('/system/run-queue', function () {
        $out = [];
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:work', [
                '--queue' => 'notifications,default', '--stop-when-empty' => true,
                '--max-time' => 50, '--tries' => 3,
            ]);
            $out['queue_work'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $out['queue_work_error'] = $e->getMessage();
        }
        try {
            \Illuminate\Support\Facades\Artisan::call('notifications:dispatch', ['--limit' => 100]);
            $out['notifications_dispatch'] = trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $out['notifications_dispatch_error'] = $e->getMessage();
        }
        $out['pending_jobs'] = \Illuminate\Support\Facades\DB::table('jobs')->count();
        return response()->json(['ok' => true, 'output' => $out]);
    })->middleware('throttle:10,1')->name('system.run-queue');

    // KAS cron'a yapıştırılacak TAM URL'i göster (token APP_KEY'den türer).
    Route::get('/system/cron-url', function () {
        $url = url('/system/cron/run-queue') . '?token=' . mentordeCronToken();
        $txt = "KAS URL-CRON için tam adres (her dakika çağır):\n\n{$url}\n\n"
            . "Kurulum: KAS Control Center → Tools → Cronjobs → Neuer Cronjob →\n"
            . "  · Typ: URL (wget/curl)\n  · Adres: yukarıdaki URL\n  · Intervall: her 1 dakika\n\n"
            . "Token gizlidir; bu adresi kimseyle paylaşma. APP_KEY değişirse token da değişir.\n";
        return response($txt, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:10,1')->name('system.cron-url');

    // Mükerrer üniversite kayıtları (DE/EN aynı kurum 2 kez) audit + birleştirme.
    // Program arama university_name_cached exact-match olduğu için DE/EN varyantlar
    // ayrı görünüp programları bölüyor (örn. Hochschule Bremen 18 + Bremen Univ.
    // of Applied Sciences 13 = aynı okul). Heuristik: aynı şehir (programların
    // modal location'ı) + aynı tür (isimden) + tam 1 DE-batch + 1 EN-batch.
    //   varsayılan        → AUDIT (sadece listeler, değiştirmez)
    //   ?merge=1          → EN varyantın programlarını DE canonical'a taşır,
    //                       cached ismi günceller, EN kaydı pasif eder (geri
    //                       alınabilir: dup.metadata'da taşınan program id'leri)
    //   &skip=ab12cd,...  → bu dup'ları (son 6 hane) atla (yanlış eşleşmeler)
    Route::get('/system/uni-dedup-audit', function (\Illuminate\Http\Request $request) {
        $classify = function (string $name): string {
            $n = mb_strtolower($name);
            if (preg_match('/musik|kunst|künste|\barts\b|theolog|kirchlich|pädagog|verwaltung|polizei|\bfilm\b|medien|sport|management school|business school|graduate school/u', $n)) return 'special';
            if (preg_match('/institut(e)?|max planck|fraunhofer|leibniz|helmholtz/u', $n)) return 'research';
            if (preg_match('/technische universit|technical university/u', $n)) return 'tu';
            if (preg_match('/hochschule|applied sciences|fachhochschule/u', $n)) return 'applied';
            return 'uni';
        };

        $unis = \Illuminate\Support\Facades\DB::table('universities')->where('is_active', 1)->get(['id', 'name', 'metadata']);
        $cityOf = [];
        foreach (\App\Models\Program::query()->active()->selectRaw('university_id, location, count(*) c')
                     ->whereNotNull('location')->groupBy('university_id', 'location')->orderByDesc('c')->get() as $r) {
            if (!isset($cityOf[$r->university_id])) $cityOf[$r->university_id] = $r->location;
        }
        $pc = [];
        foreach (\App\Models\Program::query()->active()->selectRaw('university_id, count(*) c')->groupBy('university_id')->get() as $r) {
            $pc[$r->university_id] = (int) $r->c;
        }

        $groups = [];
        foreach ($unis as $u) {
            $key = ($cityOf[$u->id] ?? '?') . '|' . $classify($u->name);
            $groups[$key][] = ['name' => $u->name, 'batch' => str_starts_with($u->id, '019de9f1') ? 'EN' : 'DE', 'id' => $u->id, 'pc' => $pc[$u->id] ?? 0];
        }
        $pairs = [];
        foreach ($groups as $list) {
            $de = array_values(array_filter($list, fn ($x) => $x['batch'] === 'DE'));
            $en = array_values(array_filter($list, fn ($x) => $x['batch'] === 'EN'));
            if (count($de) === 1 && count($en) === 1) {
                $pairs[] = ['canonical' => $de[0], 'dup' => $en[0]];
            }
        }

        $skip = array_filter(array_map('trim', explode(',', (string) $request->query('skip', ''))));
        $doMerge = $request->boolean('merge');

        $out = "MÜKERRER ÜNİVERSİTE AUDIT" . ($doMerge ? " — BİRLEŞTİRME" : " — sadece liste") . "\n"
             . "Aday çift (1 DE + 1 EN, aynı şehir+tür): " . count($pairs) . "\n"
             . "Atla (skip): " . ($skip ? implode(',', $skip) : '(yok)') . "\n"
             . str_repeat('─', 70) . "\n";

        $merged = 0; $movedTotal = 0; $skipped = 0;
        foreach ($pairs as $p) {
            $tok = substr($p['dup']['id'], -6);
            $canId = $p['canonical']['id']; $canName = $p['canonical']['name'];
            $dupId = $p['dup']['id']; $dupName = $p['dup']['name'];

            if (in_array($tok, $skip, true)) {
                $skipped++;
                $out .= "  [ATLA {$tok}] {$canName} <= {$dupName}\n";
                continue;
            }
            $out .= sprintf("  [%s] DE %d  %s  <=  EN %d  %s\n", $tok, $p['canonical']['pc'], mb_strimwidth($canName, 0, 40), $p['dup']['pc'], $dupName);

            if ($doMerge) {
                $progIds = \App\Models\Program::query()->where('university_id', $dupId)->pluck('id')->all();
                \App\Models\Program::query()->where('university_id', $dupId)
                    ->update(['university_id' => $canId, 'university_name_cached' => $canName]);
                \Illuminate\Support\Facades\DB::table('universities')->where('id', $dupId)->update([
                    'is_active' => 0,
                    'metadata'  => json_encode([
                        'merged_into'  => $canId,
                        'old_name'     => $dupName,
                        'program_ids'  => $progIds,
                        'merged_count' => count($progIds),
                    ], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
                $merged++; $movedTotal += count($progIds);
            }
        }

        $out .= str_repeat('─', 70) . "\n";
        if ($doMerge) {
            $out .= "BİRLEŞTİRİLDİ: {$merged} çift | Taşınan program: {$movedTotal} | Atlandı: {$skipped}\n"
                  . "Geri alma: ilgili EN üniversitesi metadata.program_ids ile (is_active=0).\n";
        } else {
            $out .= "Toplam aday: " . count($pairs) . " | Atlanacak: {$skipped}\n"
                  . "Yanlış eşleşmeleri not et, sonra: ?merge=1&skip=tok1,tok2,...\n";
        }
        return response($out, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:10,1')->name('system.uni-dedup-audit');

    // Mükerrer üniversite — 2. TUR: isim-bazlı eşleştirme (round-1 sonrası kalanlar).
    // Round-1 program-location ile eşleşmeyenleri (Bremen gibi) isimdeki ortak
    // kelimeler (şehir/özel ad) + Jaccard benzerliği ile yakalar. is_active=1 EN
    // kayıtlar (round-1'de merge edilenler zaten pasif → otomatik dışlanır).
    //   varsayılan → AUDIT (skor + aday), ?merge=1[&skip=tok,...] → birleştir
    //   &min=0.6   → minimum benzerlik eşiği (varsayılan 0.5)
    Route::get('/system/uni-dedup-audit2', function (\Illuminate\Http\Request $request) {
        $stop = array_flip(['university', 'universitat', 'hochschule', 'fachhochschule', 'of', 'the',
            'fur', 'and', 'applied', 'sciences', 'technische', 'technical', 'institute', 'institut',
            'der', 'des', 'die', 'das', 'zu', 'am', 'fh', 'th', 'for', 'science', 'college', 'de', 'uas']);
        $norm = fn (string $s) => strtr(mb_strtolower($s), ['ä'=>'a','ö'=>'o','ü'=>'u','ß'=>'ss','é'=>'e','è'=>'e','á'=>'a']);
        $toks = function (string $name) use ($norm, $stop): array {
            $n = preg_replace('/[^a-z0-9\s]/', ' ', $norm($name));
            $t = [];
            foreach (preg_split('/\s+/', $n) as $w) {
                if (mb_strlen($w) > 2 && !isset($stop[$w])) $t[$w] = 1;
            }
            return array_keys($t);
        };
        $classify = function (string $name): string {
            $n = mb_strtolower($name);
            if (preg_match('/musik|kunst|künste|\barts\b|theolog|kirchlich|pädagog|verwaltung|polizei|\bfilm\b|medien|sport|business school|management school|school of management/u', $n)) return 'special';
            if (preg_match('/max planck|fraunhofer|leibniz institut|helmholtz/u', $n)) return 'research';
            if (preg_match('/hochschule|applied sciences|fachhochschule/u', $n)) return 'applied';
            return 'uni';
        };
        $compat = fn (string $a, string $b) => $a === $b || ($a === 'uni' && $b === 'uni');

        $pc = [];
        foreach (\App\Models\Program::query()->active()->selectRaw('university_id, count(*) c')->groupBy('university_id')->get() as $r) {
            $pc[$r->university_id] = (int) $r->c;
        }
        $active = \Illuminate\Support\Facades\DB::table('universities')->where('is_active', 1)->get(['id', 'name']);
        $en = []; $de = [];
        foreach ($active as $u) {
            $rec = ['id' => $u->id, 'name' => $u->name, 't' => $toks($u->name), 'cls' => $classify($u->name), 'pc' => $pc[$u->id] ?? 0];
            if (str_starts_with($u->id, '019de9f1')) $en[] = $rec; else $de[] = $rec;
        }

        $min = max(0.3, min(1.0, (float) $request->query('min', 0.5)));
        $cands = [];
        foreach ($en as $e) {
            if (!$e['t']) continue;
            $best = null; $bs = 0.0;
            foreach ($de as $d) {
                if (!$compat($e['cls'], $d['cls'])) continue;
                $i = count(array_intersect($e['t'], $d['t']));
                if (!$i) continue;
                $u = count(array_unique(array_merge($e['t'], $d['t'])));
                $j = $u ? $i / $u : 0;
                if ($j > $bs) { $bs = $j; $best = $d; }
            }
            if ($best && $bs >= $min) {
                $cands[] = ['canonical' => $best, 'dup' => $e, 'score' => $bs];
            }
        }
        usort($cands, fn ($a, $b) => $b['score'] <=> $a['score']);

        $skip = array_filter(array_map('trim', explode(',', (string) $request->query('skip', ''))));
        $doMerge = $request->boolean('merge');
        $out = "MÜKERRER ÜNİVERSİTE — 2. TUR (isim eşleştirme)" . ($doMerge ? " — BİRLEŞTİRME" : " — liste") . "\n"
             . "Aktif EN: " . count($en) . " | DE: " . count($de) . " | min skor: {$min} | aday: " . count($cands) . "\n"
             . "Skip: " . ($skip ? implode(',', $skip) : '(yok)') . "\n" . str_repeat('─', 72) . "\n";

        $merged = 0; $moved = 0; $skipped = 0;
        foreach ($cands as $p) {
            $tok = substr($p['dup']['id'], -6);
            if (in_array($tok, $skip, true)) { $skipped++; $out .= "  [ATLA {$tok}] {$p['canonical']['name']} <= {$p['dup']['name']}\n"; continue; }
            $out .= sprintf("  [%s] %.2f  DE %3d %-34s <= EN %3d %s\n", $tok, $p['score'], $p['canonical']['pc'], mb_strimwidth($p['canonical']['name'], 0, 34), $p['dup']['pc'], $p['dup']['name']);
            if ($doMerge) {
                $canId = $p['canonical']['id']; $canName = $p['canonical']['name']; $dupId = $p['dup']['id']; $dupName = $p['dup']['name'];
                $progIds = \App\Models\Program::query()->where('university_id', $dupId)->pluck('id')->all();
                \App\Models\Program::query()->where('university_id', $dupId)->update(['university_id' => $canId, 'university_name_cached' => $canName]);
                \Illuminate\Support\Facades\DB::table('universities')->where('id', $dupId)->update([
                    'is_active' => 0,
                    'metadata'  => json_encode(['merged_into' => $canId, 'old_name' => $dupName, 'program_ids' => $progIds, 'merged_count' => count($progIds), 'round' => 2], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
                $merged++; $moved += count($progIds);
            }
        }
        $out .= str_repeat('─', 72) . "\n";
        $out .= $doMerge
            ? "BİRLEŞTİRİLDİ: {$merged} | Taşınan program: {$moved} | Atlandı: {$skipped}\n"
            : "Aday: " . count($cands) . " | Düşük skorları (0.5-0.7) dikkatle incele.\nMerge: ?merge=1&skip=tok1,tok2,...\n";
        return response($out, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:10,1')->name('system.uni-dedup-audit2');

    // #23 — Doküman kategorilerinde geçersiz top_category_code ('kök', 'kok' vb.)
    // normalize. Audit: dağılımı göster + kanonik olmayanları işaretle.
    // ?fix=1 → geçersizleri DocumentCategory::normalizeTopCategoryCode ile düzeltir.
    Route::get('/system/doc-categories-audit', function (\Illuminate\Http\Request $request) {
        $canonical = array_keys(\App\Models\DocumentCategory::topCategoryOptions());
        $tables    = ['document_categories', 'guest_required_documents'];
        $doFix     = $request->boolean('fix');
        $out       = "DOKÜMAN top_category_code AUDIT" . ($doFix ? " (FIX MODU)" : "") . "\n"
                   . "Kanonik: " . implode(', ', $canonical) . "\n" . str_repeat('─', 60) . "\n";

        foreach ($tables as $tbl) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'top_category_code')) {
                $out .= "\n[{$tbl}] top_category_code kolonu YOK — atlandı\n";
                continue;
            }
            $dist = \Illuminate\Support\Facades\DB::table($tbl)
                ->select('top_category_code', \Illuminate\Support\Facades\DB::raw('count(*) as c'))
                ->groupBy('top_category_code')->orderByDesc('c')->get();

            $out .= "\n[{$tbl}] dağılım:\n";
            $bad = [];
            foreach ($dist as $row) {
                $code = (string) ($row->top_category_code ?? '');
                $isCanon = in_array($code, $canonical, true) || $code === '';
                $flag = $isCanon ? '  ' : '⚠ ';
                $out .= "  {$flag}" . str_pad($code === '' ? '(boş)' : $code, 28) . " : {$row->c}\n";
                if (!$isCanon) {
                    $bad[$code] = \App\Models\DocumentCategory::normalizeTopCategoryCode($code);
                }
            }

            if (!$bad) {
                $out .= "  → Geçersiz kod yok ✓\n";
                continue;
            }
            $out .= "  Geçersiz → normalize hedefi:\n";
            foreach ($bad as $from => $to) {
                $out .= "    '{$from}' → '{$to}'\n";
            }
            if ($doFix) {
                foreach ($bad as $from => $to) {
                    $n = \Illuminate\Support\Facades\DB::table($tbl)
                        ->where('top_category_code', $from)->update(['top_category_code' => $to]);
                    $out .= "    ✓ DÜZELTİLDİ '{$from}'→'{$to}': {$n} satır\n";
                }
            } else {
                $out .= "  (Düzeltmek için ?fix=1 ekle)\n";
            }
        }
        return response($out . "\n", 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:10,1')->name('system.doc-categories-audit');

    // DAM: mevcut 'kok_' prefix'li dosya adlarını temizle (yeni yüklemeler zaten temiz).
    // name + original_filename salt görünen alanlar; fiziksel dosya yolu ayrı, dokunulmaz.
    Route::get('/system/dam-fix-kok', function (\Illuminate\Http\Request $request) {
        $rows = \App\Models\DigitalAsset::query()->withoutGlobalScopes()
            ->where(function ($w) {
                $w->where('name', 'like', 'kok\_%')->orWhere('original_filename', 'like', 'kok\_%');
            })->get(['id', 'name', 'original_filename']);

        $fixed = 0; $lines = [];
        foreach ($rows as $a) {
            $upd = [];
            if (str_starts_with((string) $a->name, 'kok_')) {
                $upd['name'] = preg_replace('/^kok_/', '', (string) $a->name);
            }
            if (str_starts_with((string) $a->original_filename, 'kok_')) {
                $upd['original_filename'] = preg_replace('/^kok_/', '', (string) $a->original_filename);
            }
            if ($upd) {
                $a->forceFill($upd)->save();
                $fixed++;
                $lines[] = "#{$a->id}: " . ($upd['name'] ?? $a->name);
            }
        }
        $txt = "DAM 'kok_' prefix temizligi\n" . str_repeat('─', 50) . "\n"
            . "Bulunan: {$rows->count()} | Düzeltilen: {$fixed}\n\n"
            . ($lines ? implode("\n", $lines) : '(temizlenecek dosya yok)') . "\n";
        return response($txt, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:10,1')->name('system.dam-fix-kok');

    // Teşhis: prod'da ETKİN mail yapılandırması (gizli anahtarlar maskeli).
    // "sendNow başarılı ama mail gelmiyor" → çoğu zaman MAIL_MAILER=log demek.
    Route::get('/system/mail-config', function () {
        $default = (string) config('mail.default');
        $apiKey  = (string) config('services.resend.key', env('RESEND_API_KEY', ''));
        $mask = fn (string $s) => $s === '' ? '(BOŞ!)' : (strlen($s) <= 8 ? '***' : substr($s, 0, 4) . '…' . substr($s, -4) . ' (' . strlen($s) . ' karakter)');

        $info = [
            'MAIL_MAILER (etkin)'  => $default,
            'mail.from.address'    => (string) config('mail.from.address'),
            'mail.from.name'       => (string) config('mail.from.name'),
            'resend.api_key'       => $mask($apiKey),
            'smtp.host'            => (string) config('mail.mailers.smtp.host'),
            'smtp.port'            => (string) config('mail.mailers.smtp.port'),
            'APP_ENV'              => (string) config('app.env'),
        ];

        $verdict = match (true) {
            $default === 'log'   => '⚠ MAIL_MAILER=log → mail GÖNDERİLMİYOR, sadece laravel.log\'a yazılıyor. .env\'de MAIL_MAILER=resend yap.',
            $default === 'array' => '⚠ MAIL_MAILER=array → mail hiçbir yere gitmez (test sürücüsü).',
            $default === 'resend' && $apiKey === '' => '⚠ resend seçili ama RESEND_API_KEY BOŞ → gönderim başarısız olur.',
            $default === 'resend' => '✓ resend + API key var. Gelmiyorsa: from-address domain Resend\'de doğrulanmış mı + spam + Resend dashboard delivery log.',
            default => 'ℹ Mailer=' . $default . '. Gelmiyorsa SMTP/credential + from-address kontrol.',
        };

        $txt = "ETKİN MAIL YAPILANDIRMASI (prod)\n" . str_repeat('─', 50) . "\n";
        foreach ($info as $k => $v) {
            $txt .= str_pad($k, 22) . ': ' . $v . "\n";
        }
        $txt .= "\nSONUÇ: " . $verdict . "\n";
        return response($txt, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:10,1')->name('system.mail-config');

    // Teşhis: Resend API'sine DİREKT vur, ham yanıtı göster (kesin teslimat cevabı).
    // {"id":"..."} → Resend kabul etti (sorun DNS/spam/bounce, dashboard'a bak).
    // {"statusCode":403/422,...} → domain doğrulanmamış vb., mesaj net söyler.
    Route::get('/system/mail-probe', function (\Illuminate\Http\Request $request) {
        $to  = trim((string) $request->query('to', ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return response("?to=adres@x ver\n", 200)->header('Content-Type', 'text/plain; charset=utf-8');
        }
        $key  = (string) config('services.resend.key', env('RESEND_API_KEY', ''));
        $from = trim((string) config('mail.from.name')) . ' <' . trim((string) config('mail.from.address')) . '>';
        if ($key === '') {
            return response("RESEND_API_KEY boş\n", 200)->header('Content-Type', 'text/plain; charset=utf-8');
        }

        $resp = \Illuminate\Support\Facades\Http::withToken($key)
            ->acceptJson()
            ->post('https://api.resend.com/emails', [
                'from'    => $from,
                'to'      => [$to],
                'subject' => 'MentorDE Resend Probe ' . substr(md5($to . $key), 0, 6),
                'html'    => '<p>Bu bir teslimat testidir. Bu maili aldıysan Resend teslimatı çalışıyor.</p>',
            ]);

        $txt = "RESEND API PROBE\n" . str_repeat('─', 50) . "\n"
            . "From  : {$from}\n"
            . "To    : {$to}\n"
            . "HTTP  : " . $resp->status() . "\n"
            . "Body  : " . $resp->body() . "\n\n"
            . ($resp->successful()
                ? "✓ Resend KABUL etti (id yukarıda). Mail gelmiyorsa: spam + Resend dashboard → Emails → bu id'nin delivery durumu (delivered/bounced)."
                : "⚠ Resend REDDETTI. Yukarıdaki 'message' alanı sebebi söyler (genelde: domain doğrulanmamış / from-address bu domain'e ait değil).")
            . "\n";
        return response($txt, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:10,1')->name('system.mail-probe');

    // Mail render testi (markdown hint-path fix doğrulama, c2a6139). x-mail bileşeni
    // kullanan 7 şablonu dummy veriyle RENDER eder → "No hint path defined for [mail]"
    // hatası gider mi? ?send=adres@x ile gerçekten de gönderir (contract-completed).
    Route::get('/system/test-mail', function (\Illuminate\Http\Request $request) {
        $bank = ['account_holder' => 'MentorDE GmbH', 'bank_name' => 'Test Bank', 'iban' => 'DE00 0000 0000 0000 0000 00', 'bic' => 'TESTDEFF'];
        // Not: document-upload-reminder bir DocumentUploadToken modeli ister
        // (DB), render testine dahil edilmedi; aynı kök neden fix'i (markdown:)
        // onu da kapsar. welcome-new-company ise şirket modeli ister, atlandı.
        $mailables = [
            'contract-completed'        => fn () => new \App\Mail\ContractCompletedMail('Test Kullanıcı', 'Test Sözleşme', 'TEST-001', [], ['Ek madde 1'], url('/'), '2.750 EUR', 'TEST #STU-1', $bank, 14),
            'payment-received'          => fn () => new \App\Mail\PaymentReceivedMail('Test Kullanıcı', 'Test Sözleşme', 'STU-1', '2.750 EUR', '29.04.2026', url('/'), 'Test Danışman'),
            'payment-reminder'          => fn () => new \App\Mail\PaymentReminderMail('Test Kullanıcı', 'Test Sözleşme', 'TEST-001', '2.750 EUR', 'TEST #STU-1', $bank, url('/'), 1, 5, 15),
            'uni-assist-missing-fields' => fn () => new \App\Mail\UniAssistMissingFieldsMail('Test Kullanıcı', ['VPD numarası'], 'Not', url('/')),
            'visa-missing-fields'       => fn () => new \App\Mail\VisaMissingFieldsMail('Test Kullanıcı', ['Sperrkonto'], 'Not', url('/')),
        ];

        $results = [];
        foreach ($mailables as $key => $factory) {
            try {
                $html = $factory()->render();
                $results[$key] = 'RENDER OK (' . strlen($html) . ' byte)';
            } catch (\Throwable $e) {
                $results[$key] = 'FAIL: ' . $e->getMessage();
            }
        }

        $sendTo = trim((string) $request->query('send', ''));
        if ($sendTo !== '' && filter_var($sendTo, FILTER_VALIDATE_EMAIL)) {
            try {
                \Illuminate\Support\Facades\Mail::to($sendTo)->sendNow($mailables['contract-completed']());
                $results['_sent_contract_completed_to'] = $sendTo . ' (sendNow — kuyruğa girmeden)';
            } catch (\Throwable $e) {
                $results['_send_error'] = $e->getMessage();
            }
        }

        $allOk = !collect($results)->contains(fn ($v) => str_starts_with($v, 'FAIL'));
        return response()->json(['ok' => $allOk, 'results' => $results], $allOk ? 200 : 500);
    })->middleware('throttle:10,1')->name('system.test-mail');

    // Teşhis: en yeni log dosyasındaki son hata bloğu + son satırlar (#21 vb.).
    // KAS'ta storage/logs'a erişmek zor; bu endpoint manager-guarded tail verir.
    // ?lines=200 (varsayılan 120), ?full=1 → sadece ham son satırlar.
    Route::get('/system/last-error', function (\Illuminate\Http\Request $request) {
        $dir = storage_path('logs');
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.log') ?: [];
        if (!$files) {
            return response("Log dosyası yok ({$dir})\n", 200)->header('Content-Type', 'text/plain; charset=utf-8');
        }
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        // Varsayılan: UYGULAMA log'u (laravel-*.log) — deploy-webhook.log değil.
        // ?file=deploy → deploy webhook log'u, ?file=<isim parçası> → eşleşen ilk.
        $fileFilter = trim((string) $request->query('file', ''));
        $needle = $fileFilter !== '' ? $fileFilter : 'laravel';
        $pick = null;
        foreach ($files as $f) {
            if (stripos(basename($f), $needle) !== false) { $pick = $f; break; }
        }
        // Eşleşme yoksa: kullanıcıya mevcut log dosyalarını göster (ne var, hangi boyut).
        $matchNote = '';
        if (!$pick) {
            $list = array_map(fn ($f) => '  · ' . basename($f) . ' (' . round(filesize($f) / 1024) . ' KB, ' . date('Y-m-d H:i', filemtime($f)) . ')', $files);
            $matchNote = "'{$needle}' içeren log YOK. Mevcut log dosyaları:\n" . implode("\n", $list)
                . "\n\n→ En yeni dosya gösteriliyor. Belirli dosya için ?file=<isim parçası>\n" . str_repeat('─', 60) . "\n";
        }
        $file = $pick ?: $files[0];

        $want = max(20, min(1000, (int) $request->query('lines', 120)));
        // Dosyanın son ~512KB'ını oku (büyük log'larda bellek dostu).
        $size = filesize($file);
        $chunk = 512 * 1024;
        $fh = fopen($file, 'rb');
        if ($size > $chunk) {
            fseek($fh, -$chunk, SEEK_END);
            fgets($fh); // ilk yarım satırı at
        }
        $all = [];
        while (($ln = fgets($fh)) !== false) {
            $all[] = rtrim($ln, "\r\n");
        }
        fclose($fh);

        $header = $matchNote
            . "DOSYA: " . basename($file) . "  (" . round($size / 1024) . " KB, "
            . date('Y-m-d H:i:s', filemtime($file)) . ")\n"
            . str_repeat('─', 60) . "\n";

        if ($request->boolean('full')) {
            return response($header . implode("\n", array_slice($all, -$want)) . "\n", 200)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        // TÜM buffer'da son Laravel hata BAŞLIK satırını bul:
        // "[2026-..] local.ERROR: <mesaj> {"exception":"...at /path:line)" — mesaj+sınıf+dosya
        // ilk satırdadır. Dev stack trace'ler 120 satırı aştığı için tail içinde aramak yetmez.
        $errIdx = null;
        foreach ($all as $i => $ln) {
            if (preg_match('/^\[[\d\-: ]+\]\s+[\w-]+\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', $ln)) {
                $errIdx = $i;
            }
        }

        if ($errIdx === null) {
            return response($header . "(Belirgin ERROR başlık satırı bulunamadı — ham son {$want} satır)\n\n"
                . implode("\n", array_slice($all, -$want)) . "\n", 200)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        // Hata başlığından itibaren $want satır (mesaj + sınıf + ilk app frame'leri).
        $block = implode("\n", array_slice($all, $errIdx, $want));
        return response($header . "SON HATA (satır " . ($errIdx + 1) . "):\n\n" . $block . "\n", 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:20,1')->name('system.last-error');

    // Teşhis: bir öğrencinin/adayın guest + StudentAssignment durumunu döker
    // (senkron sorunu için). ?q=Survey
    Route::get('/system/student-state', function (\Illuminate\Http\Request $request) {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response("?q=isim/email/student_id ver\n", 200)->header('Content-Type', 'text/plain; charset=utf-8');
        }
        $guests = \App\Models\GuestApplication::query()->withoutGlobalScopes()
            ->where(function ($w) use ($q) {
                $w->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")->orWhere('converted_student_id', 'like', "%{$q}%");
            })->limit(20)->get();

        $r = "=== GUEST ===\n";
        foreach ($guests as $g) {
            $r .= "guest#{$g->id} {$g->first_name} {$g->last_name} <{$g->email}>\n"
                . "  assigned_senior_email: " . ($g->assigned_senior_email ?: '(yok)') . "\n"
                . "  converted_to_student:  " . var_export((bool) $g->converted_to_student, true) . "\n"
                . "  converted_student_id:  " . ($g->converted_student_id ?: '(yok)') . "\n"
                . "  lead_status:           " . ($g->lead_status ?: '-') . "\n";
            $sid = (string) ($g->converted_student_id ?? '');
            if ($sid !== '') {
                $as = \App\Models\StudentAssignment::withTrashed()->withoutGlobalScopes()->where('student_id', $sid)->get();
                if ($as->isEmpty()) {
                    $r .= "  >> StudentAssignment YOK ({$sid})\n";
                }
                foreach ($as as $a) {
                    $r .= "  >> StudentAssignment {$a->student_id}: senior=" . ($a->senior_email ?: '(yok)')
                        . " is_archived=" . var_export((bool) $a->is_archived, true)
                        . " deleted_at=" . ($a->deleted_at ? (string) $a->deleted_at : 'null') . "\n";
                }
            }
            $r .= "\n";
        }
        return response($r ?: "(bulunamadı)\n", 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->middleware('throttle:10,1')->name('system.student-state');

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

// ── KAS URL-CRON: mail kuyruğunu işle (auth GEREKMEZ, token korumalı) ──────
// /system/run-queue manager-auth ardında olduğu için cron (oturumsuz) onu
// çalıştıramaz. Bu endpoint APP_KEY'den türeyen sabit gizli token ile korunur.
// Doğru URL'i öğrenmek için manager olarak /system/cron-url aç.
if (!function_exists('mentordeCronToken')) {
    function mentordeCronToken(): string
    {
        return substr(hash_hmac('sha256', 'system-run-queue', (string) config('app.key')), 0, 32);
    }
}
Route::get('/system/cron/run-queue', function (\Illuminate\Http\Request $request) {
    if (!hash_equals(mentordeCronToken(), (string) $request->query('token', ''))) {
        return response('forbidden', 403)->header('Content-Type', 'text/plain');
    }
    $out = [];
    try {
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--queue' => 'notifications,default', '--stop-when-empty' => true,
            '--max-time' => 50, '--tries' => 3,
        ]);
        $out['queue_work'] = trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Throwable $e) {
        $out['queue_work_error'] = $e->getMessage();
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('notifications:dispatch', ['--limit' => 100]);
        $out['notifications_dispatch'] = trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Throwable $e) {
        $out['notifications_dispatch_error'] = $e->getMessage();
    }
    $out['pending_jobs'] = \Illuminate\Support\Facades\DB::table('jobs')->count();
    return response()->json(['ok' => true, 'output' => $out]);
})->middleware('throttle:120,1')->name('system.cron.run-queue');

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
    // Marketplace Phase 4 — modern senior directory (yeni controller)
    Route::get('/randevu', [\App\Http\Controllers\Booking\PublicBookingDirectoryController::class, 'index'])
        ->middleware('throttle:60,1')->name('booking.public.directory');
    // Eski track-bazlı landing — yedek olarak ayrı URL'de erişilebilir (waitlist akışı kullanır)
    Route::get('/randevu/kategori', [\App\Http\Controllers\Booking\BookingLandingController::class, 'index'])
        ->middleware('throttle:60,1')->name('booking.landing');
    Route::post('/randevu/sirada', [\App\Http\Controllers\Booking\BookingLandingController::class, 'joinWaitlist'])
        ->middleware('throttle:5,1')->name('booking.waitlist.join');
    Route::get('/book/{slug}',                       [$bc, 'show'])->middleware('throttle:60,1')->name('booking.public.show');
    Route::post('/book/{slug}/slots',                [$bc, 'slots'])->middleware('throttle:120,1')->name('booking.public.slots');
    Route::post('/book/{slug}/confirm',              [$bc, 'confirm'])->middleware('throttle:30,1')->name('booking.public.confirm');
    Route::get('/book/cancel/{token}',               [$bc, 'cancelShow'])->middleware('throttle:60,1')->name('booking.public.cancel.show');
    Route::post('/book/cancel/{token}',              [$bc, 'cancel'])->middleware('throttle:10,1')->name('booking.public.cancel');
    // Phase 5 — Stripe Checkout success URL ("ödeme alındı, webhook randevuyu onaylıyor")
    Route::get('/book/success/{token}',              [$bc, 'success'])->middleware('throttle:60,1')->name('booking.public.success');

    // Marketplace Phase 7 — Senior public profile sayfasi + AJAX review list
    $sp = \App\Http\Controllers\Booking\SeniorProfileController::class;
    Route::get('/uzman/{slug}',         [$sp, 'show'])->middleware('throttle:60,1')->name('booking.public.profile');
    Route::get('/uzman/{slug}/reviews', [$sp, 'reviews'])->middleware('throttle:60,1')->name('booking.public.profile.reviews');

    // Marketplace Phase 7 — Review submission (booking_token ile)
    $rv = \App\Http\Controllers\Booking\ReviewSubmissionController::class;
    Route::get('/review/{token}',  [$rv, 'showForm'])->middleware('throttle:60,1')->name('booking.review.show');
    Route::post('/review/{token}', [$rv, 'submit'])->middleware('throttle:5,1')->name('booking.review.submit');
});

Route::middleware(['company.context'])->group(function () {
    // Public Platform/SaaS tanıtım sayfası — modüller, kabiliyetler, partner + SaaS pricing
    Route::get('/platform', fn () => view('public.platform-landing'))
        ->middleware('throttle:120,1')->name('public.platform-landing');
    Route::get('/saas',     fn () => redirect()->route('public.platform-landing'))->middleware('throttle:120,1');
    Route::get('/urun',     fn () => redirect()->route('public.platform-landing'))->middleware('throttle:120,1');

    // Trial expired payment wall (Customer Manager middleware EnsureTrialActive yönlendirir)
    $trialExp = \App\Http\Controllers\Public\TrialExpiredController::class;
    Route::get('/trial-expired',
        [$trialExp, 'show']
    )->middleware(['auth', 'throttle:60,1'])->name('public.trial-expired');
    Route::post('/trial-banner/dismiss',
        [$trialExp, 'dismissBanner']
    )->middleware(['auth', 'throttle:60,1'])->name('public.trial-banner.dismiss');

    // DGmarkt brand kit showcase — logo varyantları + palet + tipografi
    Route::get('/brand/dgmarkt', fn () => view('public.brand.dgmarkt'))
        ->middleware('throttle:60,1')->name('public.brand.dgmarkt');

    // Public Status Page — sistem servislerinin anlık durumu
    $status = \App\Http\Controllers\Public\StatusController::class;
    Route::get('/durum',  [$status, 'show'])->middleware('throttle:60,1')->name('public.status');
    Route::get('/status', fn () => redirect()->route('public.status'))->middleware('throttle:60,1');
    Route::get('/health', fn () => response()->json(['ok' => true, 'ts' => now()->toIso8601String()]))->middleware('throttle:600,1')->name('public.health');

    // PWA icon — dinamik PNG (GD library), manifest-{role}.json'lardaki referans
    Route::get('/icons/{role}-icon-{size}.png',
        [\App\Http\Controllers\PwaIconController::class, 'show']
    )->where('role', 'guest|student|senior|manager|dealer')
     ->where('size', '192|512')
     ->middleware('throttle:120,1')
     ->name('public.pwa-icon');

    // Public Pricing / Fiyatlandırma — 4 tier yan yana + 14 gün trial CTA
    Route::get('/fiyatlar', function () {
        return view('public.pricing');
    })->middleware('throttle:120,1')->name('public.pricing');
    Route::get('/pricing',  fn () => redirect()->route('public.pricing'))->middleware('throttle:120,1');

    // Public Self-service Signup Wizard — Trial provisioning + auto-login
    $signup = \App\Http\Controllers\Public\SignupController::class;
    Route::get('/kayit',  [$signup, 'show'])->middleware('throttle:60,1')->name('public.signup.show');
    Route::post('/kayit', [$signup, 'store'])->middleware('throttle:5,1')->name('public.signup.store');
    Route::get('/signup', fn () => redirect()->route('public.signup.show'))->middleware('throttle:60,1');

    // Public Dealer Başvuru Formu — landing CTA'ları buraya yönlendirir
    $dealerApp = \App\Http\Controllers\Dealer\DealerApplicationController::class;
    Route::get('/satis-ortagi/basvuru',         [$dealerApp, 'create'])->middleware('throttle:30,1')->name('public.dealer-application.create');
    Route::post('/satis-ortagi/basvuru',        [$dealerApp, 'store'])->middleware('throttle:5,1')->name('public.dealer-application.store');
    Route::get('/satis-ortagi/basvuru/tamamlandi', [$dealerApp, 'success'])->middleware('throttle:60,1')->name('public.dealer-application.success');

    // Public Satış Ortağı (dealer) landing — MentorDE Satış Ortaklığı Programı tanıtımı
    Route::get('/satis-ortagi', function () {
        $theme = \App\Support\PortalTheme::resolve();
        return view('public.dealer-landing', [
            'counters' => \App\Support\DealerLandingData::counters(),
            'managerAccent' => $theme['accent_manager'] ?? '#1e40af',
        ]);
    })
        ->middleware('throttle:120,1')->name('public.dealer-landing');
    Route::get('/partner',      fn () => redirect()->route('public.dealer-landing'))
        ->middleware('throttle:120,1'); // alias

    // Bayi mini-sitesinin herkese acik ORNEGI — aday bayiye link olarak verilir.
    // Veritabaninda kaydi yok (bkz. DealerSiteDemoController): sahte bir bayi
    // eklemek raporlara ve komisyon hesaplarina karisirdi.
    Route::get('/demo/bayi-sitesi/{template?}', [\App\Http\Controllers\Public\DealerSiteDemoController::class, 'show'])
        ->where('template', '[a-z0-9-]{2,32}')
        ->middleware('throttle:120,1')
        ->name('public.dealer-site-demo');

    // Bayi white-label mini-site — /p/{slug}
    Route::get('/p/{slug}', [\App\Http\Controllers\Public\DealerMiniSiteController::class, 'show'])
        ->where('slug', '[a-z0-9-]{3,64}')
        ->middleware('throttle:120,1')
        ->name('public.dealer-minisite');

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

    // ── B2B partner FİRMA özel landing: /apply/{firma-slug} ──
    // Firma bu linki kendi öğrencisine verir; gelen başvuru O FİRMAYA yazılır.
    // Bayi (dealer) landing'inden farkı: orada kayıt yine MentorDE'nin, burada
    // kayıt firmanın kendi tenant'ına düşer ve firma yöneticisi görebilir.
    //
    // EN SONA kayıtlı: /apply/success ve /apply/partner/... önce eşleşsin diye.
    // Ayrıca rezerve kelimeler regex ile dışlanıyor — ileride /apply/xyz eklenirse
    // bu rota onu sessizce yutmasın.
    Route::get('/apply/{companySlug}', [GuestApplicationController::class, 'createForCompany'])
        ->where('companySlug', '(?!success$|partner$|onay$|suggestions$|lead-sources$)[a-z0-9][a-z0-9_-]{1,58}')
        ->middleware('throttle:60,1')
        ->name('apply.company');
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

// Manager şifre sıfırlama sonrası zorunlu değişim ekranı
Route::middleware('auth')->group(function () {
    $pcc = \App\Http\Controllers\Auth\PasswordChangeRequiredController::class;
    Route::get('/password/change-required',  [$pcc, 'show'])->name('password.change-required.show');
    Route::post('/password/change-required', [$pcc, 'update'])
        ->middleware('throttle:10,1')
        ->name('password.change-required.update');
});


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
    ->middleware('throttle:20,1'); // şifre belirleme/sıfırlama: kullanıcı birkaç kez deneyebilir (mismatch vb.)

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

    // Şirket bağlamı değiştirici. manager.role ile KAPATILMAZ: danışmanın da
    // partner bağlamına geçmesi gerekiyor. Yetkiyi görünür küme belirler.
    Route::post('/company-context/switch', [\App\Http\Controllers\CompanyContextSwitchController::class, 'switch'])
        ->middleware('throttle:30,1')
        ->name('company-context.switch');
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
require __DIR__.'/platform.php';

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

        // ── Auto-sync modules: Yeni eklenen modüller her company'ye otomatik dağıt ──
        // Yeni modül DEFAULT_MODULES'a girince eski companies otomatik almıyordu;
        // her deploy'da senkronize ediyoruz (idempotent: zaten varsa skip).
        try {
            $allDefault = \App\Support\ModuleAccess::allModules();
            $companies = \App\Models\Company::query()->get(['id', 'name', 'enabled_modules']);
            $output .= ">>> auto-sync modules: {$companies->count()} company, " . count($allDefault) . " default modül\n";
            $totalAdded = 0;
            foreach ($companies as $c) {
                $existing = is_array($c->enabled_modules) ? $c->enabled_modules : [];
                $merged = empty($existing) ? $allDefault : array_values(array_unique(array_merge($existing, $allDefault)));
                $added = array_diff($merged, $existing);
                if (empty($added) && !empty($existing)) {
                    continue; // zaten tam
                }
                \App\Models\Company::query()->where('id', $c->id)
                    ->update(['enabled_modules' => $merged]);
                \App\Support\ModuleAccess::flushCache($c->id);
                $totalAdded += count($added);
                $output .= "    cid={$c->id} '{$c->name}': +" . count($added) . " modül [" . implode(',', $added) . "]\n";
            }
            if ($totalAdded === 0) {
                $output .= "    Hepsi zaten senkron — eklenmesi gereken modül yok.\n";
            }
        } catch (\Throwable $e) {
            $output .= ">>> auto-sync modules WARN: " . $e->getMessage() . "\n";
        }

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

        // optimize:clear — config/route/view/event/compiled cache'leri temizler.
        // Plus bootstrap/cache/*.php manuel sil (artisan komutu KAS'ta bazen
        // sessizce skip ediyor).
        // ── Tüm company'lerin enabled_modules'una eksik modülleri ekle ──────
        // Yeni modüller (application_guides, manager_password_reset vb.) DEFAULT_MODULES'a
        // eklendikten sonra mevcut company kayıtlarına otomatik gelmez (NULL ise default
        // kullanılır ama explicit set edilmişse eski liste kalır).
        // Bu cleanup tüm DEFAULT_MODULES'u her company'nin enabled_modules'una merge eder.
        // Kullanim: /_deploy/run-pending?cleanup=sync-modules
        if ($request->query('cleanup') === 'sync-modules') {
            try {
                $allDefault = \App\Support\ModuleAccess::allModules();
                $companies = \App\Models\Company::query()->get(['id', 'name', 'enabled_modules']);
                $output .= ">>> sync-modules: {$companies->count()} company, " . count($allDefault) . " default modül\n";
                foreach ($companies as $c) {
                    $existing = is_array($c->enabled_modules) ? $c->enabled_modules : [];
                    if (empty($existing)) {
                        // NULL → tüm modülleri açık say
                        $merged = $allDefault;
                    } else {
                        $merged = array_values(array_unique(array_merge($existing, $allDefault)));
                    }
                    $added = array_diff($merged, $existing);
                    if (empty($added) && !empty($existing)) {
                        $output .= "    cid={$c->id} '{$c->name}': zaten tam (skip)\n";
                        continue;
                    }
                    \App\Models\Company::query()->where('id', $c->id)
                        ->update(['enabled_modules' => $merged]);
                    \App\Support\ModuleAccess::flushCache($c->id);
                    $addedList = empty($added) ? 'tümü-eklendi-fresh' : implode(',', $added);
                    $output .= "    cid={$c->id} '{$c->name}': eklendi [{$addedList}]\n";
                }
            } catch (\Throwable $e) {
                $output .= ">>> sync-modules FAILED: " . $e->getMessage() . "\n";
            }
        }

        // Kullanim: /_deploy/run-pending?cleanup=optimize-clear
        if ($request->query('cleanup') === 'optimize-clear') {
            try {
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                $output .= ">>> optimize:clear\n" . \Illuminate\Support\Facades\Artisan::output() . "\n";
            } catch (\Throwable $e) {
                $output .= ">>> optimize:clear FAILED: " . $e->getMessage() . "\n";
            }
            // Manuel bootstrap/cache temizligi (services.php, packages.php, config.php, routes-v7.php)
            $bootCache = base_path('bootstrap/cache');
            $deleted = 0;
            if (is_dir($bootCache)) {
                foreach (glob($bootCache . '/*.php') ?: [] as $f) {
                    if (basename($f) === '.gitignore') continue;
                    if (@unlink($f)) $deleted++;
                }
            }
            $output .= ">>> bootstrap/cache temizlendi: {$deleted} dosya silindi\n";
            // realpath/stat cache (file_exists icin)
            if (function_exists('clearstatcache')) {
                clearstatcache(true);
                $output .= ">>> clearstatcache OK\n";
            }
        }

        // Prod DB'deki Gemini API key'in son 6 karakterini ve uzunlugunu raporla.
        // Yapıştırma sirasinda gizli karakter (NBSP, ZWNJ) eklenmiş veya kayit
        // kismi yazilmis olabilir; direkt karşilaştirma icin.
        // Kullanim: /_deploy/run-pending?cleanup=show-gemini-key
        if ($request->query('cleanup') === 'show-gemini-key') {
            $row = DB::table('marketing_admin_settings')
                ->where('setting_key', 'ai_labs_gemini_key')
                ->orderBy('updated_at', 'desc')
                ->first();
            if (!$row) {
                $output .= ">>> show-gemini-key: marketing_admin_settings'te ai_labs_gemini_key satiri yok\n";
            } else {
                $val = (string) $row->setting_value;
                $len = strlen($val);
                $tail = $len > 0 ? substr($val, max(0, $len - 6)) : '';
                $head = $len > 0 ? substr($val, 0, 6) : '';
                $hasInvisible = preg_match('/[\x00-\x1F\x7F\xA0]|[\xE2\x80\x80-\xE2\x80\x8F]/u', $val) ? 'EVET' : 'hayir';
                $output .= ">>> show-gemini-key:\n";
                $output .= "    company_id: " . ($row->company_id ?? 'NULL') . "\n";
                $output .= "    length: {$len} (beklenen: 39)\n";
                $output .= "    head: '{$head}...' (beklenen: 'AIzaSy')\n";
                $output .= "    tail: '...{$tail}' (yapistirilan key sonu ile karsilastir)\n";
                $output .= "    invisible_char: {$hasInvisible}\n";
                $output .= "    updated_at: " . ($row->updated_at ?? 'NULL') . "\n";
                $output .= "    updated_by: " . ($row->updated_by_user_id ?? 'NULL') . "\n";
            }
        }

        // Gemini API key'i URL uzerinden direkt DB'ye yazar (UI bypass — debug icin).
        // setting_value JSON kolonu, json_encode ile dogru formatlama yapilir.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=set-gemini-key&key=AQ.Ab8...
        if ($request->query('cleanup') === 'set-gemini-key') {
            $newKey = urldecode((string) $request->query('key', ''));
            if (strlen($newKey) < 20) {
                $output .= ">>> set-gemini-key: key parametresi cok kisa veya eksik. Beklenen: 30+ char, gelen: " . strlen($newKey) . " char\n";
            } else {
                $companyId = (int) $request->query('company', 1);
                $jsonValue = json_encode($newKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $existing  = DB::table('marketing_admin_settings')
                    ->where('company_id', $companyId)
                    ->where('setting_key', 'ai_labs_gemini_key')
                    ->first();
                if ($existing) {
                    DB::statement(
                        "UPDATE marketing_admin_settings SET setting_value = ?, updated_at = NOW() WHERE id = ?",
                        [$jsonValue, $existing->id]
                    );
                    $output .= ">>> set-gemini-key: id={$existing->id} guncellendi\n";
                } else {
                    DB::table('marketing_admin_settings')->insert([
                        'company_id'    => $companyId,
                        'setting_key'   => 'ai_labs_gemini_key',
                        'setting_value' => DB::raw("CAST('" . addslashes($jsonValue) . "' AS JSON)"),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                    $output .= ">>> set-gemini-key: yeni satir insert edildi (company_id={$companyId})\n";
                }
                $output .= "    key length: " . strlen($newKey) . " char\n";
                $output .= "    key head: '" . substr($newKey, 0, 8) . "...'\n";
                $output .= "    key tail: '..." . substr($newKey, -6) . "'\n";

                // Cache flush
                try { \Illuminate\Support\Facades\Cache::flush(); } catch (\Throwable $e) {}
                $output .= "    cache flushed\n";
            }
        }

        // Gemini key JSON format kurtarma — kritik bug fix.
        // marketing_admin_settings.setting_value JSON kolonu, dogru format: '"AIzaSy..."'
        // (tirnak DAHIL JSON string). Onceki fix-gemini-key-quotes endpoint tirnaklari
        // sildi -> JSON parse fail -> CHECK constraint violation (MySQL 4025).
        // Bu endpoint: mevcut value'yu plaintext olarak alir, json_encode ile tirnaklar.
        // Kullanim: /_deploy/run-pending?cleanup=restore-gemini-json-format
        if ($request->query('cleanup') === 'restore-gemini-json-format') {
            $rows = DB::table('marketing_admin_settings')
                ->where('setting_key', 'ai_labs_gemini_key')
                ->get();
            $fixed = 0;
            foreach ($rows as $row) {
                $raw = (string) $row->setting_value;
                // Eger zaten valid JSON ise dokunma
                $decoded = json_decode($raw, true);
                if (is_string($decoded) && $decoded !== '') {
                    $output .= ">>> restore-gemini-json-format: id={$row->id} zaten valid JSON, atlandi\n";
                    continue;
                }
                // Plaintext value -> JSON-encode (tirnaklarla sar)
                $clean = trim($raw, "\"' \t\n\r\0\x0B");
                $jsonValue = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                DB::statement(
                    "UPDATE marketing_admin_settings SET setting_value = ?, updated_at = NOW() WHERE id = ?",
                    [$jsonValue, $row->id]
                );
                $fixed++;
                $output .= ">>> restore-gemini-json-format: id={$row->id} JSON formatina restore edildi (" . strlen($clean) . " char)\n";
            }
            $output .= ">>> restore-gemini-json-format: {$fixed} satir duzeltildi\n";
        }

        // [DEPRECATED — KULLANMA] Gemini API key tirnak temizleme.
        // setting_value JSON kolonu oldugu icin tirnak silmek JSON formatini bozar.
        // CHECK constraint violation (4025) verir. Kullanmak yerine: restore-gemini-json-format
        // veya UI'dan yeniden paste etme.
        if ($request->query('cleanup') === 'fix-gemini-key-quotes') {
            $output .= ">>> fix-gemini-key-quotes: DEPRECATED. JSON kolonu boznur. Kullan: ?cleanup=restore-gemini-json-format\n";
        }

        // [DISABLED — eski kullanim ornegi]
        if (false && $request->query('cleanup') === 'fix-gemini-key-quotes') {
            $rows = DB::table('marketing_admin_settings')
                ->where('setting_key', 'ai_labs_gemini_key')
                ->get();
            $fixed = 0;
            $skipped = 0;
            foreach ($rows as $row) {
                $original = (string) $row->setting_value;
                $cleaned = trim($original);
                $cleaned = trim($cleaned, "\"'`");
                $cleaned = preg_replace('/[\s\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', '', $cleaned);
                if ($cleaned === $original) {
                    $skipped++;
                    continue;
                }
                DB::table('marketing_admin_settings')
                    ->where('id', $row->id)
                    ->update([
                        'setting_value' => $cleaned,
                        'updated_at'    => now(),
                    ]);
                $fixed++;
                $output .= ">>> fix-gemini-key-quotes: company {$row->company_id}: "
                    . strlen($original) . " char → " . strlen($cleaned) . " char\n";
            }
            $output .= ">>> fix-gemini-key-quotes: {$fixed} satir temizlendi, {$skipped} satir zaten temizdi\n";
        }

        // Gemini API key'in gercekten calisip calismadigini Google API'sine ping
        // atarak dogrula. Tek bir hafif models.list cagrisi yapar (quota'ya etki yok).
        // Kullanim: /_deploy/run-pending?cleanup=test-gemini
        if ($request->query('cleanup') === 'test-gemini') {
            $row = DB::table('marketing_admin_settings')
                ->where('setting_key', 'ai_labs_gemini_key')
                ->orderBy('updated_at', 'desc')
                ->first();
            if (!$row || empty($row->setting_value)) {
                $output .= ">>> test-gemini: ai_labs_gemini_key kaydedilmemis\n";
            } else {
                // setting_value JSON kolonu — decode et, '"AIzaSy..."' -> 'AIzaSy...'
                $rawValue = (string) $row->setting_value;
                $decoded  = json_decode($rawValue, true);
                $key      = is_string($decoded) ? $decoded : $rawValue;
                $output .= ">>> test-gemini:\n";
                $output .= "    raw length: " . strlen($rawValue) . " (JSON ile)\n";
                $output .= "    actual key length: " . strlen($key) . "\n";
                $output .= "    key head: '" . substr($key, 0, 6) . "...'\n";
                $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($key);
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                ]);
                $resp = curl_exec($ch);
                $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err  = curl_error($ch);
                curl_close($ch);
                $output .= "    http_status: {$http}\n";
                if ($err) {
                    $output .= "    curl_error: {$err}\n";
                }
                if ($http === 200) {
                    $data = json_decode((string) $resp, true);
                    $count = is_array($data['models'] ?? null) ? count($data['models']) : 0;
                    $output .= "    SONUC: KEY CALISIYOR ✓ ({$count} model erisilebilir)\n";
                    if ($count > 0) {
                        $names = array_slice(array_map(fn($m) => $m['name'] ?? '?', $data['models']), 0, 5);
                        $output .= "    ilk modeller: " . implode(', ', $names) . "\n";
                    }
                } else {
                    $errBody = is_string($resp) ? substr($resp, 0, 400) : '';
                    $output .= "    SONUC: KEY CALISMIYOR ✗\n";
                    $output .= "    response: {$errBody}\n";
                }
            }
        }

        // Gemini File API uri'lerini sifirla → bir sonraki "Kaynakları Senkronize Et"
        // butonu re-upload tetikler. Yeni Gemini API key'i farkli projede ise eski
        // file_uri'ler 403 (PERMISSION_DENIED) doner cunku File API proje-bazli.
        // Kullanim: /_deploy/run-pending?cleanup=reset-gemini-files
        if ($request->query('cleanup') === 'reset-gemini-files') {
            if (Schema::hasTable('knowledge_sources')) {
                $count = DB::table('knowledge_sources')
                    ->whereNotNull('gemini_file_uri')
                    ->orWhereNotNull('gemini_file_id')
                    ->update(['gemini_file_uri' => null, 'gemini_file_id' => null]);
                $output .= ">>> reset-gemini-files: {$count} knowledge_sources file_uri/id sifirlandi\n";
                $output .= "    Manager → AI Labs → Ayarlar → 'Kaynakları Şimdi Senkronize Et' butonuna basarak re-upload tetikleyin.\n";
            }
        }

        // Pusher backend credentials'i .env'e yaz + config cache invalidate.
        // .env'e elle yazmaya gerek yok — bu endpoint .env'i patch eder.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=set-pusher
        //          &app_id=XXX&key=XXX&pusher_secret=XXX&cluster=eu
        if ($request->query('cleanup') === 'set-pusher') {
            $appId    = (string) $request->query('app_id', '');
            $key      = (string) $request->query('key', '');
            $secretP  = (string) $request->query('pusher_secret', '');
            $cluster  = (string) $request->query('cluster', 'eu');

            if ($appId === '' || $key === '' || $secretP === '') {
                $output .= ">>> set-pusher: app_id + key + pusher_secret zorunlu\n";
            } else {
                $envPath = base_path('.env');
                $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
                $updates = [
                    'BROADCAST_DRIVER'        => 'pusher',
                    'BROADCAST_CONNECTION'    => 'pusher',
                    'PUSHER_APP_ID'           => $appId,
                    'PUSHER_APP_KEY'          => $key,
                    'PUSHER_APP_SECRET'       => $secretP,
                    'PUSHER_APP_CLUSTER'      => $cluster,
                    'PUSHER_PORT'             => '443',
                    'PUSHER_SCHEME'           => 'https',
                    'VITE_PUSHER_APP_KEY'     => $key,
                    'VITE_PUSHER_APP_CLUSTER' => $cluster,
                ];
                $patched = 0;
                foreach ($updates as $envKey => $envValue) {
                    if (preg_match("/^{$envKey}=.*$/m", $envContent)) {
                        $envContent = preg_replace("/^{$envKey}=.*$/m", "{$envKey}={$envValue}", $envContent);
                    } else {
                        $envContent .= "\n{$envKey}={$envValue}";
                    }
                    $patched++;
                }
                @file_put_contents($envPath, $envContent);
                $output .= ">>> set-pusher: .env'e {$patched} satir yazildi/guncellendi\n";

                try {
                    \Illuminate\Support\Facades\Artisan::call('config:clear');
                    \Illuminate\Support\Facades\Artisan::call('view:clear');
                    $output .= "    config:clear + view:clear OK\n";
                } catch (\Throwable $e) {
                    $output .= "    cache clear hata: " . $e->getMessage() . "\n";
                }
                $output .= "    Pusher: app_id={$appId}, cluster={$cluster}, key={$key}\n";
                $output .= "    Frontend echo.js fallback hardcoded — bir sonraki request'te real-time aktif.\n";
            }
        }

        // .env dosyasindan PUSHER/BROADCAST satirlarini gosterir + opcache/config:cache state.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=show-env-pusher
        if ($request->query('cleanup') === 'show-env-pusher') {
            $envPath = base_path('.env');
            if (!file_exists($envPath)) {
                $output .= ">>> show-env-pusher: .env yok\n";
            } else {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES);
                $output .= ">>> show-env-pusher: .env'deki broadcast/pusher satirlari:\n";
                foreach ($lines as $i => $line) {
                    if (preg_match('/^(BROADCAST|PUSHER|VITE_PUSHER)/', $line)) {
                        // secret'i maskele
                        $shown = preg_replace('/(SECRET=)([^\s]+)/', '$1***(' . strlen('$2') . 'c)', $line);
                        $shown = preg_replace('/(SECRET=)(.{4})(.+)/', '$1$2***', $line);
                        $output .= "    L" . ($i+1) . ": " . $shown . "\n";
                    }
                }

                $cachedConfig = base_path('bootstrap/cache/config.php');
                $output .= "    --- runtime state ---\n";
                $output .= "    bootstrap/cache/config.php: " . (file_exists($cachedConfig) ? 'EXISTS (config:cache aktif!)' : 'YOK (env her requestte okunuyor)') . "\n";
                $output .= "    config('broadcasting.default'):   " . config('broadcasting.default') . "\n";
                $output .= "    env('BROADCAST_DRIVER'):          " . (env('BROADCAST_DRIVER') ?: 'NULL') . "\n";
                $output .= "    env('BROADCAST_CONNECTION'):      " . (env('BROADCAST_CONNECTION') ?: 'NULL') . "\n";
                $output .= "    \$_ENV['BROADCAST_DRIVER']:        " . ($_ENV['BROADCAST_DRIVER'] ?? 'NULL') . "\n";
                $output .= "    getenv('BROADCAST_DRIVER'):       " . (getenv('BROADCAST_DRIVER') ?: 'NULL') . "\n";
            }
        }

        // Pusher broadcaster auth'i sunucu tarafindan simulate eder ve
        // dolu/bos response + exception trace'i gosterir. Frontend
        // /broadcasting/auth 200 + bos body donduren sorunu tesis icin.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=test-pusher-auth&email=USER_EMAIL&channel=private-manager.1
        if ($request->query('cleanup') === 'test-pusher-auth') {
            $email = strtolower(trim((string) $request->query('email', 'manager@panel.mentorde.com')));
            $channel = (string) $request->query('channel', 'private-manager.1');
            try {
                $user = \App\Models\User::query()->whereRaw('lower(email) = ?', [$email])->first();
                if (!$user) {
                    $output .= ">>> test-pusher-auth: user '{$email}' yok\n";
                } else {
                    $output .= ">>> test-pusher-auth: user id={$user->id} role={$user->role} company_id={$user->company_id}\n";
                    $output .= "    channel: {$channel}\n";

                    // ENV degerleri kontrolu
                    $output .= "    --- env values ---\n";
                    $output .= "    BROADCAST_DRIVER:    " . (config('broadcasting.default') ?: 'NULL') . "\n";
                    $output .= "    PUSHER_APP_KEY:      " . (config('broadcasting.connections.pusher.key') ? substr(config('broadcasting.connections.pusher.key'), 0, 8) . '...' : 'NULL') . "\n";
                    $output .= "    PUSHER_APP_SECRET:   " . (config('broadcasting.connections.pusher.secret') ? 'SET (' . strlen(config('broadcasting.connections.pusher.secret')) . ' chars)' : 'NULL') . "\n";
                    $output .= "    PUSHER_APP_ID:       " . (config('broadcasting.connections.pusher.app_id') ?: 'NULL') . "\n";
                    $output .= "    PUSHER_APP_CLUSTER:  " . (config('broadcasting.connections.pusher.options.cluster') ?: 'NULL') . "\n";

                    // Mock request olustur
                    $req = \Illuminate\Http\Request::create('/broadcasting/auth', 'POST', [
                        'channel_name' => $channel,
                        'socket_id'    => '12345.67890',
                    ]);
                    $req->setUserResolver(fn() => $user);

                    $broadcaster = app(\Illuminate\Contracts\Broadcasting\Broadcaster::class);
                    $output .= "    broadcaster class: " . get_class($broadcaster) . "\n";

                    $response = $broadcaster->auth($req);
                    $output .= "    --- auth response ---\n";
                    $output .= "    type: " . gettype($response) . "\n";
                    if (is_object($response)) {
                        $output .= "    class: " . get_class($response) . "\n";
                        if (method_exists($response, 'getContent')) {
                            $output .= "    content: " . substr((string) $response->getContent(), 0, 500) . "\n";
                        }
                    } else {
                        $output .= "    value: " . substr(json_encode($response), 0, 500) . "\n";
                    }
                }
            } catch (\Throwable $e) {
                $output .= ">>> test-pusher-auth EXCEPTION:\n";
                $output .= "    class: " . get_class($e) . "\n";
                $output .= "    msg:   " . $e->getMessage() . "\n";
                $output .= "    file:  " . $e->getFile() . ':' . $e->getLine() . "\n";
                $output .= "    trace:\n" . substr($e->getTraceAsString(), 0, 1500) . "\n";
            }
        }

        // Laravel log son N satirini gosterir.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=tail-log&lines=80
        if ($request->query('cleanup') === 'tail-log') {
            $lines = max(10, min(500, (int) $request->query('lines', 80)));
            $logPath = storage_path('logs/laravel.log');
            if (!file_exists($logPath)) {
                $output .= ">>> tail-log: {$logPath} bulunamadi\n";
            } else {
                $fp = fopen($logPath, 'r');
                if ($fp) {
                    fseek($fp, -32768, SEEK_END);
                    $tail = fread($fp, 32768);
                    fclose($fp);
                    $arr = explode("\n", $tail);
                    $arr = array_slice($arr, -$lines);
                    $output .= ">>> tail-log (son {$lines} satir):\n";
                    $output .= implode("\n", $arr) . "\n";
                }
            }
        }

        // Read-only Auditor kullanici olustur (test/manuel) — POST/PUT/DELETE bloklanir.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=create-auditor
        //          &email=auditor@example.com&name=Audit&password=Min8Chars&company=1
        if ($request->query('cleanup') === 'create-auditor') {
            $email = strtolower(trim((string) $request->query('email', '')));
            $name  = trim((string) $request->query('name', 'Read-only Auditor'));
            $pwd   = (string) $request->query('password', '');
            $cid   = (int) $request->query('company', 1);
            if ($email === '' || strlen($pwd) < 8) {
                $output .= ">>> create-auditor: email + en az 8 karakter password gerekli\n";
                $output .= "    ornek: &email=auditor@panel.mentorde.com&name=Auditor&password=Audit2026!\n";
            } else {
                $exists = \App\Models\User::query()->whereRaw('lower(email) = ?', [$email])->first();
                if ($exists) {
                    $output .= ">>> create-auditor: {$email} zaten var (id={$exists->id}, role={$exists->role})\n";
                    $output .= "    Mevcut hesabi auditor yapmak icin: ?cleanup=promote-auditor&email={$email}\n";
                } else {
                    $user = \App\Models\User::create([
                        'name'              => $name,
                        'email'             => $email,
                        'password'          => bcrypt($pwd),
                        'role'              => \App\Models\User::ROLE_AUDITOR,
                        'company_id'        => $cid,
                        'is_active'         => true,
                        'email_verified_at' => now(),
                    ]);
                    $output .= ">>> create-auditor: olusturuldu\n";
                    $output .= "    id={$user->id} email={$user->email} role=auditor company_id={$cid}\n";
                    $output .= "    Login: {$email} / {$pwd}\n";
                    $output .= "    Login sonrasi tum manager sayfalarini gorebilir ama POST/PUT/DELETE bloklanir.\n";
                }
            }
        }

        // Mevcut bir hesabi Read-only Auditor'a yukselt/dusur.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=promote-auditor&email=USER_EMAIL
        if ($request->query('cleanup') === 'promote-auditor') {
            $email = strtolower(trim((string) $request->query('email', '')));
            if ($email === '') {
                $output .= ">>> promote-auditor: email parametresi gerekli\n";
            } else {
                $user = \App\Models\User::query()->whereRaw('lower(email) = ?', [$email])->first();
                if (!$user) {
                    $output .= ">>> promote-auditor: {$email} bulunamadi\n";
                } else {
                    $oldRole = (string) $user->role;
                    $user->role = \App\Models\User::ROLE_AUDITOR;
                    $user->save();
                    $output .= ">>> promote-auditor: {$email}\n";
                    $output .= "    {$oldRole} -> auditor (read-only, yazma yok)\n";
                }
            }
        }

        // Platform Owner role'unu Manager'a geri dusur (sahip ayrimi icin).
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=demote-to-manager&email=USER_EMAIL
        if ($request->query('cleanup') === 'demote-to-manager') {
            $email = strtolower(trim((string) $request->query('email', '')));
            if ($email === '') {
                $output .= ">>> demote-to-manager: email parametresi gerekli\n";
            } else {
                $user = \App\Models\User::query()
                    ->whereRaw('lower(email) = ?', [$email])
                    ->first();
                if (!$user) {
                    $output .= ">>> demote-to-manager: {$email} bulunamadi\n";
                } else {
                    $oldRole = (string) $user->role;
                    $user->role = \App\Models\User::ROLE_MANAGER;
                    $user->save();
                    $output .= ">>> demote-to-manager: {$email}\n";
                    $output .= "    {$oldRole} -> manager\n";
                }
            }
        }

        // Yeni Platform Owner kullanici olustur (mevcut bir hesabi yukseltmek yerine).
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=create-platform-owner
        //          &email=NEW_EMAIL&name=NAME&password=PWD&company=1
        if ($request->query('cleanup') === 'create-platform-owner') {
            $email = strtolower(trim((string) $request->query('email', '')));
            $name  = trim((string) $request->query('name', 'Platform Owner'));
            $pwd   = (string) $request->query('password', '');
            $cid   = (int) $request->query('company', 1);
            if ($email === '' || strlen($pwd) < 8) {
                $output .= ">>> create-platform-owner: email + en az 8 karakter password gerekli\n";
                $output .= "    ornek: &email=owner@mentorde.com&name=Owner&password=GucluParola2026\n";
            } else {
                $exists = \App\Models\User::query()->whereRaw('lower(email) = ?', [$email])->first();
                if ($exists) {
                    $output .= ">>> create-platform-owner: {$email} zaten var (id={$exists->id}, role={$exists->role})\n";
                    $output .= "    Mevcut hesabi yukseltmek icin: ?cleanup=promote-platform-owner&email={$email}\n";
                } else {
                    $user = \App\Models\User::create([
                        'name'              => $name,
                        'email'             => $email,
                        'password'          => bcrypt($pwd),
                        'role'              => \App\Models\User::ROLE_PLATFORM_OWNER,
                        'company_id'        => $cid,
                        'is_active'         => true,
                        'email_verified_at' => now(),
                    ]);
                    $output .= ">>> create-platform-owner: yeni kullanici olusturuldu\n";
                    $output .= "    id={$user->id} | role=platform_owner | email={$email} | company_id={$cid}\n";
                    $output .= "    Login: panel.mentorde.com/login -> {$email}\n";
                }
            }
        }

        // Bir kullanicinin sifresini sifirla (email + en az 8 karakter yeni sifre).
        // Mailbox'i olmayan teknik hesaplar (owner@mentorde.com gibi) icin
        // "sifremi unuttum" calismaz; bu endpoint guvenli sifre sifirlama saglar.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=set-user-password&email=USER_EMAIL&password=YeniParola2026
        if ($request->query('cleanup') === 'set-user-password') {
            $email = strtolower(trim((string) $request->query('email', '')));
            $pwd   = (string) $request->query('password', '');
            if ($email === '' || strlen($pwd) < 8) {
                $output .= ">>> set-user-password: email + en az 8 karakter password gerekli\n";
                $output .= "    ornek: &email=owner@mentorde.com&password=YeniGucluParola\n";
            } else {
                $user = \App\Models\User::query()
                    ->whereRaw('lower(email) = ?', [$email])
                    ->first();
                if (!$user) {
                    $output .= ">>> set-user-password: {$email} bulunamadi\n";
                } else {
                    $user->password = bcrypt($pwd);
                    $user->save();
                    $output .= ">>> set-user-password: {$email}\n";
                    $output .= "    id={$user->id} | role={$user->role} | sifre guncellendi\n";
                    $output .= "    Login: panel.mentorde.com/login -> {$email}\n";
                }
            }
        }

        // Platform Owner rolune yukselt — Mentorde sahibinin kendi hesabini
        // 'platform_owner' rolune cevirir. SaaS yetki ayrimi Faz 1 deploy sonrasi
        // bir kerelik calistirilir.
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=promote-platform-owner&email=USER_EMAIL
        if ($request->query('cleanup') === 'promote-platform-owner') {
            $email = strtolower(trim((string) $request->query('email', '')));
            if ($email === '') {
                $output .= ">>> promote-platform-owner: email parametresi gerekli\n";
                $output .= "    ornek: ?cleanup=promote-platform-owner&email=owner@mentorde.com\n";
            } else {
                $user = \App\Models\User::query()
                    ->whereRaw('lower(email) = ?', [$email])
                    ->first();
                if (!$user) {
                    $output .= ">>> promote-platform-owner: {$email} kullanicisi bulunamadi\n";
                } else {
                    $oldRole = (string) $user->role;
                    $user->role = \App\Models\User::ROLE_PLATFORM_OWNER;
                    $user->save();
                    $output .= ">>> promote-platform-owner: {$email}\n";
                    $output .= "    eski rol: {$oldRole}\n";
                    $output .= "    yeni rol: platform_owner\n";
                    $output .= "    artik /manager/companies/modules ve /manager/system/* sayfalarina erisebilir.\n";
                    $output .= "    Customer Manager'lar bu sayfalardan BLOKLI.\n";
                }
            }
        }

        // Sadece 'discover' page key'i icin tum rollere ait visibility kayitlarini sil.
        // 404 donen /guest/discover, /student/discover icin hizli kurtarma.
        // Default TRUE mantigi geri gelir (page_visibility module kapali ise zaten TRUE).
        // Kullanim: /_deploy/run-pending?secret=XXX&cleanup=fix-discover-visibility
        if ($request->query('cleanup') === 'fix-discover-visibility') {
            if (Schema::hasTable('role_page_visibility')) {
                $beforeRows = DB::table('role_page_visibility')
                    ->where('page_key', 'discover')
                    ->get(['company_id', 'role', 'page_key', 'is_visible']);
                $output .= ">>> fix-discover-visibility:\n";
                foreach ($beforeRows as $r) {
                    $output .= "    once: company {$r->company_id} | {$r->role} | discover = " . ($r->is_visible ? 'TRUE' : 'FALSE') . "\n";
                }
                $deleted = DB::table('role_page_visibility')->where('page_key', 'discover')->delete();
                $output .= "    {$deleted} satir silindi (default TRUE'ya dondu)\n";
                if (Schema::hasTable('companies')) {
                    DB::table('companies')->select('id')->get()->each(function ($c) {
                        \Illuminate\Support\Facades\Cache::forget("page_visibility:{$c->id}");
                    });
                    $output .= "    page_visibility cache flush edildi\n";
                }
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

        // DEBUG: prod'da bir route mevcut mu?
        // Kullanım: /_deploy/run-pending?secret=...&cleanup=route-exists&route=program-search
        if ($request->query('cleanup') === 'route-exists') {
            $needle = (string) $request->query('route', '');
            $found = [];
            foreach (\Illuminate\Support\Facades\Route::getRoutes() as $r) {
                $uri = $r->uri();
                if ($needle === '' || str_contains($uri, $needle)) {
                    $found[] = strtoupper(implode('|', $r->methods())) . ' ' . $uri . ' → ' . ($r->getActionName() ?: 'closure');
                    if (count($found) >= 20) break;
                }
            }
            $output .= ">>> route-exists '{$needle}': " . count($found) . " match\n";
            $output .= implode("\n", $found) . "\n";
        }

        // DEBUG: prod'da belirli bir dosyanın son N satırını dump et.
        // Kullanım: /_deploy/run-pending?secret=...&cleanup=dump-file&path=routes/manager.php&n=15
        if ($request->query('cleanup') === 'dump-file') {
            $path = (string) $request->query('path', 'routes/web.php');
            // Güvenlik: sadece app içindeki .php dosyaları
            $full = base_path($path);
            if (!is_file($full) || !str_starts_with(realpath($full), realpath(base_path()))) {
                $output .= ">>> dump-file: dosya bulunamadi veya kapsam disi: {$path}\n";
            } else {
                $n = max(5, min(100, (int) $request->query('n', 20)));
                $contents = file_get_contents($full);
                $lines = explode("\n", $contents);
                $tail = array_slice($lines, -$n);
                $output .= ">>> dump-file {$path} (last {$n} lines, total " . count($lines) . " lines, " . filesize($full) . " bytes):\n";
                $output .= implode("\n", $tail) . "\n";
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

// ─────────────────────────────────────────────────────────────────────────────
// Platform Broadcast tracking — signed URL, no auth
// Email open pixel ve CTA click redirect endpoint'leri.
// ─────────────────────────────────────────────────────────────────────────────

Route::get('/b/open/{recipient_id}', function (int $recipient_id) {
    if (! request()->hasValidSignature()) {
        // Pikseli yine de don, ama tracking'i atla
        return response(base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='), 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store',
        ]);
    }
    try {
        app(\App\Services\Platform\BroadcastService::class)->trackOpen($recipient_id);
    } catch (\Throwable $e) {
        // sessizce devam et — pikselin donmesi her zaman onemli
    }
    return response(base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='), 200, [
        'Content-Type'  => 'image/gif',
        'Cache-Control' => 'no-store',
    ]);
})->where('recipient_id', '[0-9]+')
  ->middleware('throttle:600,1')
  ->name('broadcast.track.open');

Route::get('/b/click/{recipient_id}', function (int $recipient_id) {
    $url = (string) request()->query('url', '');
    if (! request()->hasValidSignature() || $url === '' || ! preg_match('#^https?://#i', $url)) {
        return redirect('/');
    }
    try {
        app(\App\Services\Platform\BroadcastService::class)->trackClick($recipient_id);
    } catch (\Throwable $e) {
        // redirect her zaman gerceklesmeli
    }
    return redirect()->away($url);
})->where('recipient_id', '[0-9]+')
  ->middleware('throttle:300,1')
  ->name('broadcast.track.click');
