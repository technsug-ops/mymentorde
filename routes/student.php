<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\GdprController;
use App\Http\Controllers\Guest\PortalController as GuestPortalController;
use App\Http\Controllers\HandbookController;
use App\Http\Controllers\Student\PaymentCheckoutController;
use App\Http\Controllers\Student\StudentContentController;
use App\Http\Controllers\Student\StudentContractController;
use App\Http\Controllers\Student\StudentEngagementController;
use App\Http\Controllers\Student\StudentDocumentBuilderController;
use App\Http\Controllers\Student\StudentDocumentController;
use App\Http\Controllers\Student\StudentInteractionController;
use App\Http\Controllers\Student\StudentLearningController;
use App\Http\Controllers\Student\StudentNotificationController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\Student\StudentProgressController;
use App\Http\Controllers\Student\StudentServiceController;
use App\Http\Controllers\Student\StudentTicketController;
use App\Http\Controllers\Student\StudentWorkflowController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentPortalController;
use Illuminate\Support\Facades\Route;

// Sözleşme onayı sonrası guest→student terfi tebrik sayfası (role check yok, sadece auth)
Route::middleware(['company.context', 'auth'])->get('/guest/promoted-to-student', [GuestPortalController::class, 'promotedToStudent'])->name('guest.promoted-to-student');

// Student Portal — grup throttle: 180 istek/dk (authenticated user başına)
Route::middleware(['company.context', 'auth', 'verified', 'student.role', 'throttle:600,1'])->group(function (): void {
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');

    // ── Kayıt Akışı ──────────────────────────────────────────────────────────
    Route::get('/student/registration', [StudentPortalController::class, 'registration'])->name('student.registration');
    Route::post('/student/registration/form/auto-save', [StudentWorkflowController::class, 'autoSaveRegistration'])->middleware('throttle:60,1')->name('student.registration.autosave');
    Route::post('/student/registration/form/submit',    [StudentWorkflowController::class, 'submitRegistration'])->middleware('throttle:5,1')->name('student.registration.submit');
    Route::get('/student/registration/form/pdf',       [StudentWorkflowController::class, 'registrationFormPdf'])->middleware('throttle:60,1')->name('student.registration.form.pdf');

    // ── Belgeler ─────────────────────────────────────────────────────────────
    Route::get('/student/registration/documents',                          [StudentPortalController::class,  'registrationDocuments'])->name('student.registration.documents');
    Route::post('/student/registration/documents/upload',                  [StudentDocumentController::class, 'upload'])->middleware('throttle:10,1')->name('student.registration.documents.upload');
    Route::get('/student/registration/documents/{document}/download',      [StudentDocumentController::class, 'download'])->middleware('throttle:20,1')->name('student.registration.documents.download');
    Route::delete('/student/registration/documents/{document}',            [StudentDocumentController::class, 'delete'])->middleware('throttle:20,1')->name('student.registration.documents.delete');
    Route::get('/student/documents/{document}/preview',                    [StudentDocumentController::class, 'preview'])->middleware(['student.owns.document', 'throttle:30,1'])->name('student.documents.preview');
    Route::get('/student/registration/documents/{document}/serve',         [StudentDocumentController::class, 'serve'])->middleware(['student.owns.document', 'throttle:10,1'])->name('student.registration.documents.serve');

    // ── Süreç Takibi ─────────────────────────────────────────────────────────
    Route::get('/student/process-tracking', [StudentProgressController::class, 'processTracking'])->name('student.process-tracking');
    Route::post('/student/workflow/request-next-step', [StudentWorkflowController::class, 'requestNextStep'])->middleware('throttle:5,1')->name('student.workflow.request-next-step');

    // ── Doküman Oluşturucu ───────────────────────────────────────────────────
    Route::get('/student/document-builder', [StudentPortalController::class, 'documentBuilder'])->name('student.document-builder');
    Route::post('/student/document-builder/generate', [StudentDocumentBuilderController::class, 'generateDocumentBuilderFile'])->middleware('throttle:5,1')->name('student.document-builder.generate');
    Route::post('/student/document-builder/ai-draft', [StudentDocumentBuilderController::class, 'aiDraftPreview'])->middleware('throttle:5,1')->name('student.document-builder.ai-draft');
    Route::post('/student/document-builder/preview',  [StudentDocumentBuilderController::class, 'previewDocumentBuilder'])->middleware('throttle:20,1')->name('student.document-builder.preview');

    // ── Randevular ───────────────────────────────────────────────────────────
    Route::get('/student/appointments',                          [StudentPortalController::class,  'appointments'])->name('student.appointments');
    Route::post('/student/appointments',                         [StudentInteractionController::class, 'storeAppointment'])->middleware('throttle:10,1')->name('student.appointments.store');
    Route::post('/student/appointments/{appointment}/cancel',    [StudentInteractionController::class, 'cancelAppointment'])->middleware('throttle:10,1')->name('student.appointments.cancel');

    // ── Ticket / Destek ──────────────────────────────────────────────────────
    Route::get('/student/tickets',                           [StudentPortalController::class,  'tickets'])->name('student.tickets');
    Route::post('/student/tickets',                          [StudentTicketController::class, 'store'])->middleware('throttle:10,1')->name('student.tickets.store');
    Route::post('/student/tickets/{ticket}/reply',           [StudentTicketController::class, 'reply'])->middleware('throttle:20,1')->name('student.tickets.reply');
    Route::post('/student/tickets/{ticket}/close',           [StudentTicketController::class, 'close'])->middleware('throttle:10,1')->name('student.tickets.close');
    Route::post('/student/tickets/{ticket}/reopen',          [StudentTicketController::class, 'reopen'])->middleware('throttle:10,1')->name('student.tickets.reopen');

    // ── Materyaller ──────────────────────────────────────────────────────────
    Route::get('/student/materials',                         [StudentContentController::class,  'materials'])->name('student.materials');
    Route::post('/student/materials/{article}/read',         [StudentLearningController::class, 'markMaterialRead'])->middleware('throttle:60,1')->name('student.materials.read');
    Route::get('/student/materials/{article}/file',          [StudentContentController::class,  'materialFile'])->middleware('throttle:20,1')->name('student.materials.file');

    // ── Sözleşme ─────────────────────────────────────────────────────────────
    Route::get('/student/contract',                          [StudentPortalController::class,  'contract'])->name('student.contract');
    Route::get('/student/contract/download-signed',          [StudentContractController::class, 'downloadSignedContract'])->middleware('throttle:10,1')->name('student.contract.download-signed');
    Route::post('/student/contract/request',                 [StudentContractController::class, 'requestContract'])->middleware('throttle:5,1')->name('student.contract.request');
    Route::post('/student/contract/upload-signed',           [StudentContractController::class, 'uploadSignedContract'])->middleware('throttle:5,1')->name('student.contract.upload-signed');
    Route::post('/student/contract/addendum-request',        [StudentContractController::class, 'requestContractAddendum'])->middleware('throttle:5,1')->name('student.contract.addendum-request');

    // ── Servisler & Paket ────────────────────────────────────────────────────
    Route::get('/student/services',                                          [StudentPortalController::class,  'services'])->name('student.services');
    Route::post('/student/services/select-package',                          [StudentServiceController::class, 'selectPackage'])->middleware('throttle:10,1')->name('student.services.select-package');
    Route::post('/student/services/add-extra',                               [StudentServiceController::class, 'addExtra'])->middleware('throttle:20,1')->name('student.services.add-extra');
    Route::delete('/student/services/remove-extra/{extraCode}',              [StudentServiceController::class, 'removeExtra'])->middleware('throttle:20,1')->name('student.services.remove-extra');

    // ── Profil & Ayarlar ─────────────────────────────────────────────────────
    Route::get('/student/profile',                           [StudentPortalController::class,  'profile'])->name('student.profile');
    Route::get('/student/settings',                          [StudentPortalController::class,  'settings'])->name('student.settings');
    Route::post('/student/profile/photo',                    [StudentProfileController::class, 'uploadPhoto'])->middleware('throttle:5,1')->name('student.profile.photo');
    Route::post('/student/profile',                          [StudentProfileController::class, 'update'])->middleware('throttle:20,1')->name('student.profile.update');
    Route::post('/student/settings',                         [StudentProfileController::class, 'updateSettings'])->middleware('throttle:20,1')->name('student.settings.update');
    Route::post('/student/settings/password',                [StudentProfileController::class, 'changePassword'])->middleware('throttle:15,1')->name('student.settings.password');

    // ── Mesajlaşma ───────────────────────────────────────────────────────────
    Route::get('/student/messages',                          [ConversationController::class, 'student'])->name('student.messages');
    Route::post('/student/messages/send',                    [ConversationController::class, 'studentSend'])->middleware('throttle:30,1')->name('student.messages.send');

    // ── Bildirimler ──────────────────────────────────────────────────────────
    Route::get('/student/notifications',                                  [StudentNotificationController::class, 'notifications'])->name('student.notifications');
    Route::post('/student/notifications/read-all',                        [StudentNotificationController::class, 'notificationsReadAll'])->middleware('throttle:20,1')->name('student.notifications.read-all');
    Route::post('/student/notifications/{notification}/read',             [StudentNotificationController::class, 'notificationMarkRead'])->middleware('throttle:60,1')->name('student.notifications.read');

    // ── Ödemeler ─────────────────────────────────────────────────────────────
    Route::get('/student/payments', [PaymentCheckoutController::class, 'index'])->name('student.payments');
    Route::get('/student/payments/{id}/checkout', [PaymentCheckoutController::class, 'checkout'])
        ->where('id', '[0-9]+')
        ->name('student.payment.checkout');

    // ── Vault (Hesap Kasası) ─────────────────────────────────────────────────
    Route::get('/student/vault',                   [StudentPortalController::class, 'vault'])->name('student.vault');
    Route::get('/student/vault/{vault}/reveal',    [StudentPortalController::class, 'revealVault'])->middleware('throttle:20,1')->name('student.vault.reveal');

    // ── GDPR ─────────────────────────────────────────────────────────────────
    Route::get('/student/gdpr/export',   [GdprController::class, 'exportStudentData'])->middleware('throttle:5,60')->name('student.gdpr.export');
    Route::post('/student/gdpr/erasure', [GdprController::class, 'requestStudentErasure'])->middleware('throttle:3,60')->name('student.gdpr.erasure');

    // ── Takip Sayfaları ──────────────────────────────────────────────────────
    Route::get('/student/institution-documents',  [StudentProgressController::class, 'institutionDocuments'])->name('student.institution-documents');
    Route::get('/student/university-applications',[StudentProgressController::class, 'universityApplications'])->name('student.university-applications');
    Route::get('/student/visa',                   [StudentProgressController::class, 'visa'])->name('student.visa');
    Route::get('/student/housing',                [StudentProgressController::class, 'housing'])->name('student.housing');

    // ── Banner ───────────────────────────────────────────────────────────────
    Route::post('/student/banner/{id}/click', [StudentDashboardController::class, 'bannerClick'])->middleware('throttle:30,1')->name('student.banner.click');

    // ── Checklist ────────────────────────────────────────────────────────────
    Route::get('/student/checklist',                      [StudentProgressController::class,  'checklist'])->name('student.checklist');
    Route::post('/student/checklist/{item}/toggle',       [StudentLearningController::class, 'toggleChecklist'])->middleware('throttle:60,1')->name('student.checklist.toggle');

    // ── Global Arama ─────────────────────────────────────────────────────────
    Route::get('/student/search', [StudentInteractionController::class, 'globalSearch'])->middleware('throttle:30,1')->name('student.search');

    // ── Onboarding Wizard ────────────────────────────────────────────────────
    Route::get('/student/onboarding',                          [StudentProgressController::class,  'onboarding'])->name('student.onboarding');
    Route::post('/student/onboarding/{stepCode}/complete',     [StudentLearningController::class, 'completeOnboardingStep'])->middleware('throttle:30,1')->name('student.onboarding.complete');
    Route::post('/student/onboarding/{stepCode}/skip',         [StudentLearningController::class, 'skipOnboardingStep'])->middleware('throttle:30,1')->name('student.onboarding.skip');

    // ── Takvim ───────────────────────────────────────────────────────────────
    Route::get('/student/calendar',        [StudentProgressController::class,  'calendar'])->name('student.calendar');
    Route::get('/student/calendar/events', [StudentInteractionController::class, 'calendarEvents'])->middleware('throttle:60,1')->name('student.calendar.events');

    // ── Geri Bildirim & NPS ──────────────────────────────────────────────────
    Route::get('/student/feedback',  [StudentProgressController::class,  'feedback'])->name('student.feedback');
    Route::post('/student/feedback', [StudentInteractionController::class, 'storeFeedback'])->middleware('throttle:30,60')->name('student.feedback.store');
    Route::post('/student/nps',      [StudentInteractionController::class, 'storeNps'])->middleware('throttle:3,1')->name('student.nps.store');

    // ── Bilgi Sayfaları ──────────────────────────────────────────────────────
    Route::get('/student/info/university-guide', [StudentContentController::class, 'infoUniversityGuide'])->name('student.info.university-guide');
    Route::get('/student/info/success-stories',  [StudentContentController::class, 'infoSuccessStories'])->name('student.info.success-stories');
    Route::get('/student/info/living-guide',     [StudentContentController::class, 'infoLivingGuide'])->name('student.info.living-guide');
    Route::get('/student/info/document-guide',   [StudentContentController::class, 'infoDocumentGuide'])->name('student.info.document-guide');
    Route::get('/student/info/vize-guide',       [StudentContentController::class, 'infoVizeGuide'])->name('student.info.vize-guide');

    // ── Content Hub (Keşfet) ─────────────────────────────────────────────────
    Route::get('/student/discover',                   [StudentContentController::class, 'discoverPage'])->name('student.discover');
    Route::get('/student/discover/more',              [StudentContentController::class, 'discoverMore'])->name('student.discover.more');
    Route::get('/student/content/{slug}',             [StudentContentController::class, 'contentDetail'])->name('student.content-detail')->where('slug', '[^/]+');
    Route::get('/student/saved',                      [StudentContentController::class, 'savedList'])->name('student.saved');
    Route::post('/student/content/{slug}/save',       [StudentContentController::class, 'toggleSave'])->name('student.content.save')->where('slug', '[^/]+');
    Route::post('/student/content/{slug}/react',      [StudentContentController::class, 'toggleReaction'])->name('student.content.react')->where('slug', '[^/]+');

    // ── AI Asistan ───────────────────────────────────────────────────────────
    Route::get('/student/ai-assistant',             [StudentEngagementController::class, 'aiAssistantPage'])->name('student.ai-assistant');
    Route::post('/student/ai-assistant/ask',        [StudentEngagementController::class, 'aiAssistantAsk'])->middleware('throttle:10,1')->name('student.ai-assistant.ask');
    Route::get('/student/ai-assistant/history',     [StudentEngagementController::class, 'aiAssistantHistory'])->name('student.ai-assistant.history');
    Route::get('/student/ai-assistant/remaining',   [StudentEngagementController::class, 'aiAssistantRemaining'])->name('student.ai-assistant.remaining');

    // ── Yardım & Hesaplama ───────────────────────────────────────────────────
    Route::get('/student/help-center',              [StudentContentController::class, 'helpCenter'])->name('student.help-center');
    Route::get('/student/cost-calculator',          [StudentContentController::class, 'costCalculator'])->name('student.cost-calculator');

    // ── Sözleşme (genişletilmiş) ─────────────────────────────────────────────
    Route::post('/student/contract/digital-sign',    [StudentContractController::class, 'digitalSign'])->middleware('throttle:10,1')->name('student.contract.digital-sign');
    Route::post('/student/contract/withdraw',        [StudentContractController::class, 'withdrawContractRequest'])->middleware('throttle:5,1')->name('student.contract.withdraw');
    Route::post('/student/contract/reopen-request', [StudentContractController::class, 'requestReopen'])->middleware('throttle:5,1')->name('student.contract.reopen-request');
    Route::get('/student/contract/signed-thanks',   [StudentContractController::class, 'contractSignedThanks'])->name('student.contract.signed-thanks');

    // ── Offline ──────────────────────────────────────────────────────────────
    Route::get('/student/offline', fn () => view('student.offline'))->name('student.offline');

    // ── Kılavuz ──────────────────────────────────────────────────────────────
    Route::get('/student/help', [HandbookController::class, 'student'])->name('student.handbook');
    Route::get('/student/help/download', [HandbookController::class, 'download'])->defaults('role', 'student')->name('student.handbook.download');
});
