<?php

/**
 * Platform Owner Route Group — /platform/*
 *
 * Mentorde SaaS sahibinin (Platform Owner) cross-company yonetim alani.
 * 'platform.owner' middleware tum route'lari korur — Customer Manager'lar bloklanir.
 *
 * Faz 2 (06-2026): Subscription tier yonetimi, modul toggle, company CRUD.
 */

use App\Http\Controllers\Platform\PlatformController;
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
});
