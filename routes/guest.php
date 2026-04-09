<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\GdprController;
use App\Http\Controllers\Guest\GuestContentController;
use App\Http\Controllers\HandbookController;
use App\Http\Controllers\Guest\GuestEngagementController;
use App\Http\Controllers\Guest\GuestInfoController;
use App\Http\Controllers\Guest\PortalController as GuestPortalController;
use App\Http\Controllers\Guest\WorkflowController as GuestWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['company.context', 'auth', 'verified', 'guest.role', 'throttle:600,1'])->prefix('guest')->group(function (): void {
    Route::get('', fn () => redirect()->route('guest.dashboard'));
    Route::get('/dashboard', [GuestPortalController::class, 'dashboard'])->name('guest.dashboard');
    Route::get('/registration/form', [GuestPortalController::class, 'registrationForm'])->name('guest.registration.form');
    Route::post('/registration/form/auto-save', [GuestWorkflowController::class, 'autoSaveRegistration'])->middleware('throttle:120,1')->name('guest.registration.autosave');
    Route::post('/registration/form/ajax-save', [GuestWorkflowController::class, 'ajaxSaveRegistration'])->middleware('throttle:120,1')->name('guest.registration.ajax-save');
    Route::post('/registration/form/submit', [GuestWorkflowController::class, 'submitRegistration'])->middleware('throttle:60,1')->name('guest.registration.submit');
    Route::get('/registration/form/pdf', [GuestWorkflowController::class, 'registrationFormPdf'])->middleware('throttle:20,1')->name('guest.registration.form.pdf');
    Route::get('/registration/documents', [GuestPortalController::class, 'registrationDocuments'])->name('guest.registration.documents');
    Route::post('/registration/documents/upload', [GuestWorkflowController::class, 'uploadDocument'])->middleware('throttle:60,1')->name('guest.registration.documents.upload');
    Route::delete('/registration/documents/{document}', [GuestWorkflowController::class, 'deleteDocument'])->middleware(['guest.owns.document', 'throttle:60,1'])->name('guest.registration.documents.delete');
    Route::get('/services', [GuestPortalController::class, 'services'])->name('guest.services');
    Route::post('/services/select-package', [GuestWorkflowController::class, 'selectPackage'])->middleware('throttle:60,1')->name('guest.services.select-package');
    Route::post('/services/add-extra', [GuestWorkflowController::class, 'addExtraService'])->middleware('throttle:60,1')->name('guest.services.add-extra');
    Route::delete('/services/remove-extra/{extraCode}', [GuestWorkflowController::class, 'removeExtraService'])->middleware('throttle:60,1')->name('guest.services.remove-extra');
    Route::post('/services/confirm', [GuestWorkflowController::class, 'confirmPackage'])->middleware('throttle:10,1')->name('guest.services.confirm');

    // ── Sözleşme ─────────────────────────────────────────────────────────────
    Route::get('/contract', [GuestPortalController::class, 'contract'])->name('guest.contract');
    Route::get('/contract/request', fn () => redirect()->route('guest.contract'))->name('guest.contract.request.get');
    Route::post('/contract/request', [GuestWorkflowController::class, 'requestContract'])->middleware('throttle:60,1')->name('guest.contract.request');
    Route::post('/contract/withdraw', [GuestWorkflowController::class, 'withdrawContractRequest'])->middleware('throttle:60,1')->name('guest.contract.withdraw');
    Route::post('/contract/reopen-request', [GuestWorkflowController::class, 'requestReopen'])->middleware('throttle:30,1')->name('guest.contract.reopen-request');
    Route::post('/contract/update-request', [GuestWorkflowController::class, 'requestContractUpdate'])->middleware('throttle:60,1')->name('guest.contract.update-request');
    Route::post('/contract/upload-signed', [GuestWorkflowController::class, 'uploadSignedContract'])->middleware('throttle:60,1')->name('guest.contract.upload-signed');
    Route::post('/contract/digital-sign', [GuestWorkflowController::class, 'digitalSign'])->middleware('throttle:30,1')->name('guest.contract.digital-sign');
    Route::get('/contract/signed-thanks', [GuestPortalController::class, 'contractSignedThanks'])->name('guest.contract.signed-thanks');

    // ── Ticket / Destek ──────────────────────────────────────────────────────
    Route::get('/tickets', [GuestPortalController::class, 'tickets'])->name('guest.tickets');
    Route::post('/tickets', [GuestWorkflowController::class, 'storeTicket'])->middleware('throttle:30,1')->name('guest.tickets.store');
    Route::post('/tickets/{ticket}/reply', [GuestWorkflowController::class, 'replyTicket'])->middleware(['guest.owns.ticket', 'throttle:60,1'])->name('guest.tickets.reply');
    Route::post('/tickets/{ticket}/close', [GuestWorkflowController::class, 'closeTicket'])->middleware(['guest.owns.ticket', 'throttle:60,1'])->name('guest.tickets.close');
    Route::post('/tickets/{ticket}/reopen', [GuestWorkflowController::class, 'reopenTicket'])->middleware(['guest.owns.ticket', 'throttle:60,1'])->name('guest.tickets.reopen');
    Route::get('/tickets/{ticket}/attachment', [GuestWorkflowController::class, 'downloadTicketAttachment'])->middleware(['guest.owns.ticket', 'throttle:30,1'])->name('guest.tickets.attachment');

    // ── Profil & Ayarlar ─────────────────────────────────────────────────────
    Route::get('/profile', [GuestPortalController::class, 'profile'])->name('guest.profile');
    Route::post('/profile/photo', [GuestWorkflowController::class, 'uploadProfilePhoto'])->middleware('throttle:30,1')->name('guest.profile.photo');
    Route::post('/profile', [GuestWorkflowController::class, 'updateProfile'])->middleware('throttle:60,1')->name('guest.profile.update');
    Route::get('/settings', [GuestPortalController::class, 'settings'])->name('guest.settings');
    Route::post('/settings', [GuestWorkflowController::class, 'updateSettings'])->middleware('throttle:60,1')->name('guest.settings.update');
    Route::post('/settings/password', [GuestWorkflowController::class, 'changePassword'])->middleware('throttle:6,1')->name('guest.settings.password');
    Route::post('/settings/logout-all', [GuestWorkflowController::class, 'logoutAllDevices'])->middleware('throttle:5,1')->name('guest.settings.logout-all');

    // ── Mesajlaşma ───────────────────────────────────────────────────────────
    Route::get('/messages', [ConversationController::class, 'guest'])->name('guest.messages');
    Route::post('/messages/send', [ConversationController::class, 'guestSend'])->middleware('throttle:60,1')->name('guest.messages.send');
    Route::get('/messages/poll', [GuestWorkflowController::class, 'pollMessages'])->middleware('throttle:120,1')->name('guest.messages.poll');
    Route::post('/messages/typing', [GuestWorkflowController::class, 'markTyping'])->middleware('throttle:120,1')->name('guest.messages.typing');

    // ── Banner & GDPR ────────────────────────────────────────────────────────
    Route::post('/banner/{id}/click', [GuestEngagementController::class, 'bannerClick'])->middleware('throttle:60,1')->name('guest.banner.click');
    Route::get('/gdpr/export', [GdprController::class, 'exportGuestData'])->middleware('throttle:5,60')->name('guest.gdpr.export');
    Route::post('/gdpr/erasure', [GdprController::class, 'requestGuestErasure'])->middleware('throttle:3,60')->name('guest.gdpr.erasure');

    // ── Belge Önizleme ───────────────────────────────────────────────────────
    Route::get('/registration/documents/{document}/preview', [GuestWorkflowController::class, 'previewDocument'])->middleware('guest.owns.document')->name('guest.registration.documents.preview');
    Route::get('/registration/documents/{document}/serve', [GuestWorkflowController::class, 'serveDocument'])->middleware(['guest.owns.document', 'throttle:60,1'])->name('guest.registration.documents.serve');

    // ── Ödeme & Referral ─────────────────────────────────────────────────────
    Route::post('/services/payment-request', [GuestWorkflowController::class, 'requestPayment'])->middleware('throttle:3,1')->name('guest.services.payment-request');
    Route::post('/referral/create', [GuestWorkflowController::class, 'createReferralLink'])->middleware('throttle:5,1')->name('guest.referral.create');
    Route::get('/referral/stats', [GuestWorkflowController::class, 'referralStats'])->name('guest.referral.stats');

    // ── Onboarding Wizard ────────────────────────────────────────────────────
    Route::get('/onboarding', [GuestPortalController::class, 'onboarding'])->name('guest.onboarding');
    Route::post('/onboarding/{stepCode}/complete', [GuestWorkflowController::class, 'completeOnboardingStep'])->middleware('throttle:60,1')->name('guest.onboarding.complete');
    Route::post('/onboarding/{stepCode}/skip', [GuestWorkflowController::class, 'skipOnboardingStep'])->middleware('throttle:60,1')->name('guest.onboarding.skip');

    // ── Help Center ──────────────────────────────────────────────────────────
    Route::get('/help-center', [GuestInfoController::class, 'helpCenter'])->name('guest.help-center');

    // ── AI Asistanı ──────────────────────────────────────────────────────────
    Route::get('/ai-assistant', [GuestEngagementController::class, 'aiAssistantPage'])->name('guest.ai-assistant');
    Route::post('/ai-assistant/ask', [GuestEngagementController::class, 'aiAssistantAsk'])->middleware('throttle:30,1')->name('guest.ai-assistant.ask');
    Route::get('/ai-assistant/history', [GuestEngagementController::class, 'aiAssistantHistory'])->name('guest.ai-assistant.history');
    Route::get('/ai-assistant/remaining', [GuestEngagementController::class, 'aiAssistantRemaining'])->name('guest.ai-assistant.remaining');

    // ── Başvuru Takvimi ──────────────────────────────────────────────────────
    Route::get('/timeline', [GuestEngagementController::class, 'timeline'])->name('guest.timeline');
    Route::get('/timeline/export.ics', [GuestEngagementController::class, 'timelineExport'])->name('guest.timeline.export');

    // ── Maliyet Hesaplama ────────────────────────────────────────────────────
    Route::get('/cost-calculator', [GuestInfoController::class, 'costCalculator'])->name('guest.cost-calculator');

    // ── İçerik Sayfaları ─────────────────────────────────────────────────────
    Route::get('/university-guide',  [GuestInfoController::class, 'universityGuidePage'])->name('guest.university-guide');
    Route::get('/success-stories',   [GuestInfoController::class, 'successStoriesPage'])->name('guest.success-stories');
    Route::get('/living-guide',      [GuestInfoController::class, 'livingGuidePage'])->name('guest.living-guide');
    Route::get('/vize-guide',        [GuestInfoController::class, 'vizeGuidePage'])->name('guest.vize-guide');
    Route::get('/city/{slug}',       [GuestInfoController::class, 'cityDetail'])->name('guest.city-detail')->where('slug', '[a-z-]+');
    Route::get('/document-guide',    [GuestInfoController::class, 'documentGuidePage'])->name('guest.document-guide');

    // ── Global Arama ─────────────────────────────────────────────────────────
    Route::get('/search', [GuestContentController::class, 'globalSearch'])->middleware('throttle:60,1')->name('guest.search');

    // ── Content Hub (Keşfet) ─────────────────────────────────────────────────
    Route::get('/discover',                      [GuestContentController::class, 'discoverPage'])->name('guest.discover');
    Route::get('/discover/more',                 [GuestContentController::class, 'discoverMore'])->name('guest.discover.more');
    Route::get('/content/{slug}',                [GuestContentController::class, 'contentDetail'])->name('guest.content-detail')->where('slug', '[^/]+');
    Route::get('/saved',                         [GuestContentController::class, 'savedList'])->name('guest.saved');
    Route::post('/content/{slug}/save',          [GuestContentController::class, 'toggleSave'])->name('guest.content.save')->where('slug', '[^/]+');
    Route::post('/content/{slug}/react',         [GuestContentController::class, 'toggleReaction'])->name('guest.content.react')->where('slug', '[^/]+');

    // ── PWA & Geri Bildirim ───────────────────────────────────────────────────
    Route::get('/offline', fn () => view('guest.offline'))->name('guest.offline');
    Route::get('/feedback',  [GuestEngagementController::class, 'feedback'])->name('guest.feedback');
    Route::post('/feedback', [GuestWorkflowController::class, 'storeFeedback'])->middleware('throttle:5,60')->name('guest.feedback.store');
    Route::post('/nps',      [GuestWorkflowController::class, 'storeNps'])->middleware('throttle:3,1')->name('guest.nps.store');

    // ── Kılavuz ──────────────────────────────────────────────────────────────
    Route::get('/help', [HandbookController::class, 'guest'])->name('guest.handbook');
    Route::get('/help/download', [HandbookController::class, 'download'])->defaults('role', 'guest')->name('guest.handbook.download');
});
