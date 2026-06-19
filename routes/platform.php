<?php

/**
 * Platform Owner Route Group — /platform/*
 *
 * Mentorde SaaS sahibinin (Platform Owner) cross-company yonetim alani.
 * 'platform.owner' middleware tum route'lari korur — Customer Manager'lar bloklanir.
 *
 * Faz 2 (06-2026): Subscription tier yonetimi, modul toggle, company CRUD.
 */

use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\BroadcastController;
use App\Http\Controllers\Platform\CustomerHealthController;
use App\Http\Controllers\Platform\PlatformAnalyticsController;
use App\Http\Controllers\Platform\PlatformBillingController;
use App\Http\Controllers\Platform\PlatformController;
use App\Http\Controllers\Platform\PlatformInfrastructureController;
use App\Http\Controllers\Platform\PlatformMRRController;
use App\Http\Controllers\Platform\PlatformSecurityController;
use App\Http\Controllers\Platform\PlatformSettingsController;
use App\Http\Controllers\Platform\PromoCodeController;
use App\Http\Controllers\Platform\TrialController;
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

    // Impersonate — Customer Manager paneline gecici erisim
    Route::post('/companies/{company}/impersonate', [PlatformController::class, 'impersonate'])
        ->whereNumber('company')
        ->middleware('throttle:30,1')
        ->name('platform.companies.impersonate');

    // Impersonate sonlandir
    Route::post('/stop-impersonating', [PlatformController::class, 'stopImpersonating'])
        ->name('platform.stop-impersonating');

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
    // TRIAL YONETIMI (Faz 8) — Aktif trial'lar, conversion, manuel uzatma, nurture
    // ────────────────────────────────────────────────────────────────────────

    Route::get('/trial', [TrialController::class, 'index'])
        ->name('platform.trial');

    Route::post('/trial/{company}/extend', [TrialController::class, 'extend'])
        ->whereNumber('company')
        ->middleware('throttle:10,1')
        ->name('platform.trial.extend');

    Route::post('/trial/{company}/convert', [TrialController::class, 'convert'])
        ->whereNumber('company')
        ->middleware('throttle:10,1')
        ->name('platform.trial.convert');

    Route::post('/trial/nurture-send', [TrialController::class, 'nurtureSend'])
        ->middleware('throttle:5,1')
        ->name('platform.trial.nurture-send');

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

    // Taslak fatura duzenle (tutar + KDV orani + not; KDV/toplam yeniden hesaplanir)
    Route::get('/billing/{invoice}/edit', [PlatformBillingController::class, 'edit'])
        ->whereNumber('invoice')
        ->name('platform.billing.edit');
    Route::put('/billing/{invoice}', [PlatformBillingController::class, 'update'])
        ->whereNumber('invoice')
        ->middleware('throttle:20,1')
        ->name('platform.billing.update');

    // Yanlis kesilen faturayi iptal et (gonderilmis/gecikmis -> cancelled, audit korunur)
    Route::post('/billing/{invoice}/cancel', [PlatformBillingController::class, 'cancel'])
        ->whereNumber('invoice')
        ->middleware('throttle:10,1')
        ->name('platform.billing.cancel');

    // Taslak/iptal faturayi tamamen sil (promo redemption geri alinir, PDF silinir)
    Route::delete('/billing/{invoice}', [PlatformBillingController::class, 'destroy'])
        ->whereNumber('invoice')
        ->middleware('throttle:10,1')
        ->name('platform.billing.destroy');

    // ────────────────────────────────────────────────────────────────────────
    // PROMO CODES — İndirim kodları (Platform Owner)
    // ────────────────────────────────────────────────────────────────────────

    Route::get('/promo-codes', [PromoCodeController::class, 'index'])
        ->name('platform.promo-codes');

    Route::get('/promo-codes/create', [PromoCodeController::class, 'create'])
        ->name('platform.promo-codes.create');

    Route::post('/promo-codes', [PromoCodeController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('platform.promo-codes.store');

    Route::get('/promo-codes/{id}', [PromoCodeController::class, 'show'])
        ->whereNumber('id')
        ->name('platform.promo-codes.show');

    Route::post('/promo-codes/{id}', [PromoCodeController::class, 'update'])
        ->whereNumber('id')
        ->middleware('throttle:10,1')
        ->name('platform.promo-codes.update');

    Route::delete('/promo-codes/{id}', [PromoCodeController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('throttle:10,1')
        ->name('platform.promo-codes.destroy');

    // ────────────────────────────────────────────────────────────────────────
    // CUSTOMER HEALTH (Faz 8) — Sağlık skorları, churn risk, recompute
    // ────────────────────────────────────────────────────────────────────────

    Route::get('/customer-health', [CustomerHealthController::class, 'index'])
        ->name('platform.customer-health');

    Route::get('/customer-health/{company}', [CustomerHealthController::class, 'show'])
        ->whereNumber('company')
        ->name('platform.customer-health.show');

    Route::post('/customer-health/{company}/recompute', [CustomerHealthController::class, 'recompute'])
        ->whereNumber('company')
        ->middleware('throttle:30,1')
        ->name('platform.customer-health.recompute');

    Route::post('/customer-health/recompute-all', [CustomerHealthController::class, 'recomputeAll'])
        ->middleware('throttle:5,1')
        ->name('platform.customer-health.recompute-all');

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

    // Backup — DB yedek listesi + manuel tetik + indirme
    $backup = \App\Http\Controllers\Platform\PlatformBackupController::class;
    Route::get('/backups',  [$backup, 'index'])
        ->name('platform.backups.index');
    Route::post('/backups', [$backup, 'create'])
        ->middleware('throttle:3,1')
        ->name('platform.backups.create');
    Route::get('/backups/{filename}/download', [$backup, 'download'])
        ->where('filename', 'db_[A-Za-z0-9_-]+\.sql\.gz')
        ->middleware('throttle:10,1')
        ->name('platform.backups.download');
    Route::delete('/backups/{filename}', [$backup, 'destroy'])
        ->where('filename', 'db_[A-Za-z0-9_-]+\.sql\.gz')
        ->middleware('throttle:10,1')
        ->name('platform.backups.destroy');

    // Güvenlik
    Route::get('/security', [PlatformSecurityController::class, 'index'])
        ->name('platform.security');
    Route::post('/security', [PlatformSecurityController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('platform.security.update');

    // ────────────────────────────────────────────────────────────────────────
    // AUDIT LOG — Denetim Kayitlari (cross-tenant event akisi)
    // ────────────────────────────────────────────────────────────────────────

    Route::get('/audit-log', [AuditLogController::class, 'index'])
        ->name('platform.audit-log');

    Route::get('/audit-log/export.csv', [AuditLogController::class, 'export'])
        ->middleware('throttle:5,1')
        ->name('platform.audit-log.export');

    Route::get('/audit-log/{id}', [AuditLogController::class, 'show'])
        ->whereNumber('id')
        ->name('platform.audit-log.show');

    // ────────────────────────────────────────────────────────────────────────
    // BROADCASTS — Cross-tenant duyuru / kampanya sistemi
    // ────────────────────────────────────────────────────────────────────────

    Route::get('/broadcasts', [BroadcastController::class, 'index'])
        ->name('platform.broadcasts');

    Route::get('/broadcasts/create', [BroadcastController::class, 'create'])
        ->name('platform.broadcasts.create');

    Route::post('/broadcasts', [BroadcastController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('platform.broadcasts.store');

    Route::get('/broadcasts/{id}', [BroadcastController::class, 'show'])
        ->whereNumber('id')
        ->name('platform.broadcasts.show');

    Route::post('/broadcasts/{id}/send', [BroadcastController::class, 'send'])
        ->whereNumber('id')
        ->middleware('throttle:5,1')
        ->name('platform.broadcasts.send');

    Route::post('/broadcasts/{id}/cancel', [BroadcastController::class, 'cancel'])
        ->whereNumber('id')
        ->middleware('throttle:10,1')
        ->name('platform.broadcasts.cancel');

    Route::get('/broadcasts/{id}/preview', [BroadcastController::class, 'preview'])
        ->whereNumber('id')
        ->name('platform.broadcasts.preview');
});
