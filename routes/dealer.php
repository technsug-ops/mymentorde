<?php

use App\Http\Controllers\Dealer\DealerAdvisorController;
use App\Http\Controllers\Dealer\DealerContractController;
use App\Http\Controllers\HandbookController;
use App\Http\Controllers\Dealer\DealerEarningsController;
use App\Http\Controllers\Dealer\DealerLeadController;
use App\Http\Controllers\Dealer\DealerPerformanceController;
use App\Http\Controllers\Dealer\DealerProfileController;
use App\Http\Controllers\DealerPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['company.context', 'auth', 'dealer.role'])->group(function (): void {
    Route::get('/dealer/dashboard', [DealerPortalController::class, 'dashboard'])->name('dealer.dashboard');

    // ── Leads ─────────────────────────────────────────────────────────────────
    Route::get('/dealer/lead-create', [DealerLeadController::class, 'leadForm'])->name('dealer.lead-create');
    Route::post('/dealer/lead-create', [DealerLeadController::class, 'storeLead'])->name('dealer.lead-create.store');
    Route::get('/dealer/leads', [DealerLeadController::class, 'leads'])->name('dealer.leads');
    Route::get('/dealer/leads/{lead}', [DealerLeadController::class, 'leadDetail'])->name('dealer.leads.show');
    Route::post('/dealer/leads/{lead}/qualification', [DealerLeadController::class, 'updateLeadQualification'])->name('dealer.leads.qualification');
    Route::get('/dealer/leads/{lead}/tickets', [DealerLeadController::class, 'leadTickets'])->name('dealer.leads.tickets');
    Route::get('/dealer/leads/{lead}/timeline', [DealerLeadController::class, 'leadTimeline'])->name('dealer.leads.timeline');

    // ── Kazançlar & Ödemeler ──────────────────────────────────────────────────
    Route::get('/dealer/earnings', [DealerEarningsController::class, 'earnings'])->name('dealer.earnings');
    Route::get('/dealer/earnings/export', [DealerEarningsController::class, 'earningsExport'])->name('dealer.earnings.export');
    Route::get('/dealer/payments', [DealerEarningsController::class, 'payments'])->name('dealer.payments');
    Route::post('/dealer/payments/accounts', [DealerEarningsController::class, 'addPayoutAccount'])->name('dealer.payments.accounts.store');
    Route::delete('/dealer/payments/accounts/{id}', [DealerEarningsController::class, 'deletePayoutAccount'])->name('dealer.payments.accounts.delete');
    Route::post('/dealer/payments/request', [DealerEarningsController::class, 'requestPayout'])->name('dealer.payments.request');

    // ── Danışmanlık (Destek Yetkisi Gerekli) ─────────────────────────────────
    Route::middleware('dealer.type.permission:canAccessSupport')->group(function () {
        Route::get('/dealer/advisor', [DealerAdvisorController::class, 'advisor'])->name('dealer.advisor');
        Route::post('/dealer/advisor/ticket', [DealerAdvisorController::class, 'createAdvisorTicket'])->name('dealer.advisor.ticket.store');
        Route::get('/dealer/advisor/tickets/{ticket}', [DealerAdvisorController::class, 'ticketDetail'])->name('dealer.advisor.tickets.show');
        Route::post('/dealer/advisor/tickets/{ticket}/reply', [DealerAdvisorController::class, 'replyTicket'])->name('dealer.advisor.tickets.reply');
    });

    // ── Eğitim & Performans ───────────────────────────────────────────────────
    Route::get('/dealer/training', [DealerPerformanceController::class, 'training'])->name('dealer.training');
    Route::post('/dealer/training/{article}/read', [DealerPerformanceController::class, 'markRead'])->name('dealer.training.read');
    Route::get('/dealer/training/progress', [DealerPerformanceController::class, 'trainingProgress'])->name('dealer.training.progress');
    Route::get('/dealer/performance', [DealerPerformanceController::class, 'performanceReport'])->name('dealer.performance');
    Route::get('/dealer/performance/export', [DealerPerformanceController::class, 'performanceExport'])->name('dealer.performance.export');
    Route::get('/dealer/calculator', [DealerPerformanceController::class, 'calculator'])->name('dealer.calculator');

    // ── Takvim ───────────────────────────────────────────────────────────────
    Route::get('/dealer/calendar', [DealerPerformanceController::class, 'calendar'])->name('dealer.calendar');
    Route::get('/dealer/calendar/events', [DealerPerformanceController::class, 'calendarEvents'])->name('dealer.calendar.events');

    // ── Referral Linkleri ────────────────────────────────────────────────────
    Route::get('/dealer/referral-links', [DealerPerformanceController::class, 'referralLinks'])->name('dealer.referral-links');
    Route::post('/dealer/referral-links/utm', [DealerPerformanceController::class, 'storeUtmLink'])->name('dealer.referral.utm.store');
    Route::delete('/dealer/referral-links/utm/{utmLink}', [DealerPerformanceController::class, 'deleteUtmLink'])->name('dealer.referral.utm.delete');

    // ── Profil & Ayarlar ─────────────────────────────────────────────────────
    Route::get('/dealer/profile', [DealerProfileController::class, 'profile'])->name('dealer.profile');
    Route::post('/dealer/profile', [DealerProfileController::class, 'updateProfile'])->name('dealer.profile.update');
    Route::get('/dealer/settings', [DealerProfileController::class, 'settings'])->name('dealer.settings');
    Route::post('/dealer/settings', [DealerProfileController::class, 'updateSettings'])->name('dealer.settings.update');
    Route::post('/dealer/settings/password', [DealerProfileController::class, 'changePassword'])->name('dealer.settings.password');
    Route::get('/dealer/settings/data-export', [DealerProfileController::class, 'dataExport'])->name('dealer.settings.data-export');
    Route::get('/dealer/notifications', [DealerProfileController::class, 'notifications'])->name('dealer.notifications');

    // ── Sözleşmeler ──────────────────────────────────────────────────────────
    Route::get('/dealer/contracts',                               [DealerContractController::class, 'contracts'])->name('dealer.contracts');
    Route::get('/dealer/contracts/{contract}',                    [DealerContractController::class, 'contractShow'])->name('dealer.contracts.show');
    Route::post('/dealer/contracts/{contract}/upload-signed',     [DealerContractController::class, 'contractUploadSigned'])->name('dealer.contracts.upload-signed');

    // ── Kılavuz ──────────────────────────────────────────────────────────────
    Route::get('/dealer/help', [HandbookController::class, 'dealer'])->name('dealer.handbook');
    Route::get('/dealer/help/download', [HandbookController::class, 'download'])->defaults('role', 'dealer')->name('dealer.handbook.download');

    // ── Digital Asset Management (DAM) — macro tanımı AppServiceProvider'da ──
    // Dealer'da dam.upload/update/delete/folder.manage izinleri olmadığı için
    // o endpoint'ler permission middleware tarafından 403 döner.
    Route::dam('dealer/digital-assets', 'dealer.dam.');
});
