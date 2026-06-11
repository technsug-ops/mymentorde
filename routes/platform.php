<?php

/**
 * Platform Owner Route Group — /platform/*
 *
 * Mentorde SaaS sahibinin (Platform Owner) cross-company yonetim alani.
 * 'platform.owner' middleware tum route'lari korur — Customer Manager'lar bloklanir.
 *
 * Faz 2 (06-2026): Subscription tier yonetimi, modul toggle, company CRUD.
 */

use App\Http\Controllers\Platform\PlatformAnalyticsController;
use App\Http\Controllers\Platform\PlatformBillingController;
use App\Http\Controllers\Platform\PlatformController;
use App\Http\Controllers\Platform\PlatformInfrastructureController;
use App\Http\Controllers\Platform\PlatformMRRController;
use App\Http\Controllers\Platform\PlatformSecurityController;
use App\Http\Controllers\Platform\PlatformSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'platform.owner'])->prefix('platform')->group(function (): void {
    // Dashboard — KPI'lar, tier dagilimi, modul kullanim, trial uyarilari
    Route::get('/dashboard', [PlatformController::class, 'dashboard'])
        ->name('platform.dashboard');

    // Companies — cross-company liste
    Route::get('/companies', [PlatformController::class, 'companies'])
        ->name('platform.companies');

    // Yeni company olustur (Self-service provisioning)
    Route::get('/companies/create', [PlatformController::class, 'createCompany'])
        ->name('platform.companies.create');
    Route::post('/companies', [PlatformController::class, 'storeCompany'])
        ->middleware('throttle:10,1')
        ->name('platform.companies.store');

    // Company detay
    Route::get('/companies/{company}', [PlatformController::class, 'showCompany'])
        ->whereNumber('company')
        ->name('platform.companies.show');

    // Tier degistir (modulleri auto-sync)
    Route::post('/companies/{company}/tier', [PlatformController::class, 'updateTier'])
        ->whereNumber('company')
        ->middleware('throttle:30,1')
        ->name('platform.companies.tier');

    // Modul toggle (tier override — manuel)
    Route::post('/companies/{company}/modules', [PlatformController::class, 'updateModules'])
        ->whereNumber('company')
        ->middleware('throttle:30,1')
        ->name('platform.companies.modules');

    // ── Analytics — cross-company KPI'lar, tier dağılım, modül heatmap, top companies, booking funnel
    Route::get('/analytics', [PlatformAnalyticsController::class, 'index'])
        ->name('platform.analytics');
    Route::get('/analytics/export.csv', [PlatformAnalyticsController::class, 'exportCsv'])
        ->middleware('throttle:10,1')
        ->name('platform.analytics.export');

    // ── MRR Trendi — aylık MRR, ARR, ARPU, LTV, churn rate, tier breakdown
    Route::get('/mrr-trend', [PlatformMRRController::class, 'index'])
        ->name('platform.mrr-trend');
    Route::get('/mrr-trend/export.csv', [PlatformMRRController::class, 'exportCsv'])
        ->middleware('throttle:10,1')
        ->name('platform.mrr-trend.export');

    // ────────────────────────────────────────────────────────────────────────
    // BILLING (Faz 7) — Platform'un musteri company'lere kestigi faturalar
    // ────────────────────────────────────────────────────────────────────────

    Route::get('/billing', [PlatformBillingController::class, 'index'])
        ->name('platform.billing');

    Route::get('/billing/{invoice}', [PlatformBillingController::class, 'show'])
        ->whereNumber('invoice')
        ->name('platform.billing.show');

    Route::get('/billing/{invoice}/pdf', [PlatformBillingController::class, 'downloadPdf'])
        ->whereNumber('invoice')
        ->name('platform.billing.pdf');

    Route::post('/billing/generate', [PlatformBillingController::class, 'generate'])
        ->middleware('throttle:10,1')
        ->name('platform.billing.generate');

    Route::post('/billing/{invoice}/send', [PlatformBillingController::class, 'send'])
        ->whereNumber('invoice')
        ->middleware('throttle:10,1')
        ->name('platform.billing.send');

    Route::post('/billing/{invoice}/mark-paid', [PlatformBillingController::class, 'markPaid'])
        ->whereNumber('invoice')
        ->middleware('throttle:10,1')
        ->name('platform.billing.mark-paid');

    // ────────────────────────────────────────────────────────────────────────
    // SYSTEM (Faz 2 sistem sayfaları) — Settings + Infrastructure + Security
    // ────────────────────────────────────────────────────────────────────────

    // Platform Ayarları
    Route::get('/settings', [PlatformSettingsController::class, 'index'])
        ->name('platform.settings');
    Route::post('/settings', [PlatformSettingsController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('platform.settings.update');
    Route::post('/settings/test-email', [PlatformSettingsController::class, 'testEmail'])
        ->middleware('throttle:5,1')
        ->name('platform.settings.test-email');

    // Altyapı
    Route::get('/infrastructure', [PlatformInfrastructureController::class, 'index'])
        ->name('platform.infrastructure');
    Route::post('/infrastructure/flush-cache', [PlatformInfrastructureController::class, 'flushCache'])
        ->middleware('throttle:5,1')
        ->name('platform.infrastructure.flush-cache');
    Route::post('/infrastructure/migrate', [PlatformInfrastructureController::class, 'runMigrations'])
        ->middleware('throttle:5,1')
        ->name('platform.infrastructure.migrate');
    Route::post('/infrastructure/dump-autoload', [PlatformInfrastructureController::class, 'dumpAutoload'])
        ->middleware('throttle:5,1')
        ->name('platform.infrastructure.dump-autoload');

    // Güvenlik
    Route::get('/security', [PlatformSecurityController::class, 'index'])
        ->name('platform.security');
    Route::post('/security', [PlatformSecurityController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('platform.security.update');
});
