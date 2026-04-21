<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\HandbookController;
use App\Http\Controllers\Senior\SeniorAppointmentController;
use App\Http\Controllers\Senior\SeniorEngagementController;
use App\Http\Controllers\Senior\SeniorKnowledgeBaseController;
use App\Http\Controllers\Senior\SeniorPerformanceController;
use App\Http\Controllers\Senior\SeniorPipelineController;
use App\Http\Controllers\Senior\SeniorProfileController;
use App\Http\Controllers\Senior\SeniorStudentController;
use App\Http\Controllers\Senior\SeniorTemplateController;
use App\Http\Controllers\Senior\SeniorVaultController;
use App\Http\Controllers\Senior\SeniorVisaHousingController;
use App\Http\Controllers\Senior\SeniorInstitutionController;
use App\Http\Controllers\SeniorDashboardController;
use App\Http\Controllers\SeniorPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['company.context', 'auth', 'senior.role'])->group(function (): void {
    Route::get('/senior/dashboard', [SeniorDashboardController::class, 'index'])->name('senior.dashboard');
    Route::get('/senior/guests/{guest}', [SeniorPortalController::class, 'guestDetail'])->name('senior.guest.detail');
    Route::get('/senior/guests/{guest}/documents/{document}/serve', [SeniorPortalController::class, 'guestDocumentServe'])->name('senior.guest.document.serve');
    Route::get('/senior/guests/{guest}/documents/{document}/download', [SeniorPortalController::class, 'guestDocumentDownload'])->name('senior.guest.document.download');
    Route::get('/senior/guests/{guest}/documents/zip', [SeniorPortalController::class, 'guestDocumentsZip'])->name('senior.guest.documents.zip');
    Route::get('/senior/students', [SeniorStudentController::class, 'students'])->name('senior.students');
    Route::get('/senior/students/export-csv', [SeniorStudentController::class, 'studentsExportCsv'])->name('senior.students.export-csv');
    Route::get('/senior/registration-documents', [SeniorPortalController::class, 'registrationDocuments'])->name('senior.registration-documents');
    Route::get('/senior/process-tracking', [SeniorPortalController::class, 'processTracking'])->name('senior.process-tracking');
    Route::post('/senior/process-tasks/{task}/toggle', [SeniorPortalController::class, 'toggleProcessTask'])->middleware('throttle:60,1')->name('senior.process-tasks.toggle');
    Route::post('/senior/language-courses', [SeniorPortalController::class, 'languageCourseStore'])->middleware('throttle:20,1')->name('senior.language-courses.store');
    Route::put('/senior/language-courses/{course}', [SeniorPortalController::class, 'languageCourseUpdate'])->middleware('throttle:20,1')->name('senior.language-courses.update');
    Route::delete('/senior/language-courses/{course}', [SeniorPortalController::class, 'languageCourseDelete'])->middleware('throttle:20,1')->name('senior.language-courses.delete');
    Route::get('/senior/appointments', [SeniorAppointmentController::class, 'appointments'])->name('senior.appointments');
    Route::post('/senior/appointments/{appointment}/confirm', [SeniorAppointmentController::class, 'appointmentConfirm'])->middleware('throttle:20,1')->name('senior.appointments.confirm');
    Route::post('/senior/appointments/{appointment}/update', [SeniorAppointmentController::class, 'appointmentUpdate'])->middleware('throttle:30,1')->name('senior.appointments.update');
    Route::post('/senior/appointments/{appointment}/cancel', [SeniorAppointmentController::class, 'appointmentCancel'])->middleware('throttle:20,1')->name('senior.appointments.cancel');
    Route::post('/senior/appointments/check-collision', [SeniorAppointmentController::class, 'checkCollision'])->middleware('throttle:120,1')->name('senior.appointments.check-collision');
    Route::get('/senior/tickets', [SeniorPortalController::class, 'tickets'])->name('senior.tickets');
    Route::get('/senior/materials', [SeniorProfileController::class, 'materials'])->name('senior.materials');
    Route::post('/senior/knowledge-base/{article}/toggle-role', [SeniorKnowledgeBaseController::class, 'knowledgeBaseToggleRole'])->middleware('throttle:30,1')->name('senior.kb.toggle-role');
    Route::get('/senior/contracts', [SeniorPortalController::class, 'contracts'])->name('senior.contracts');
    Route::get('/senior/services', [SeniorPortalController::class, 'services'])->name('senior.services');
    Route::get('/senior/vault', [SeniorVaultController::class, 'vault'])->name('senior.vault');
    Route::get('/senior/notes', [SeniorPortalController::class, 'notes'])->name('senior.notes');
    Route::get('/senior/knowledge-base', [SeniorKnowledgeBaseController::class, 'knowledgeBase'])->name('senior.knowledge-base');
    Route::post('/senior/knowledge-base', [SeniorKnowledgeBaseController::class, 'knowledgeBaseStore'])->middleware('throttle:20,1')->name('senior.kb.store');
    Route::post('/senior/knowledge-base/{article}/update', [SeniorKnowledgeBaseController::class, 'knowledgeBaseUpdate'])->middleware('throttle:20,1')->name('senior.kb.update');
    Route::post('/senior/knowledge-base/{article}/delete', [SeniorKnowledgeBaseController::class, 'knowledgeBaseDelete'])->middleware('throttle:10,1')->name('senior.kb.delete');
    Route::post('/senior/knowledge-base/{article}/toggle', [SeniorKnowledgeBaseController::class, 'knowledgeBaseToggle'])->middleware('throttle:30,1')->name('senior.kb.toggle');
    Route::post('/senior/knowledge-base/{article}/helpful', [SeniorKnowledgeBaseController::class, 'knowledgeBaseHelpful'])->middleware('throttle:10,1')->name('senior.kb.helpful');
    Route::get('/senior/knowledge-base/{article}/file', [SeniorKnowledgeBaseController::class, 'knowledgeBaseServeFile'])->name('senior.kb.file');
    Route::get('/senior/performance', [SeniorPerformanceController::class, 'performance'])->name('senior.performance');
    Route::get('/senior/performance/report-print', [SeniorPerformanceController::class, 'performanceReportPrint'])->name('senior.performance.report-print');
    Route::get('/senior/performance/report-csv', [SeniorPerformanceController::class, 'performanceReportCsv'])->name('senior.performance.report-csv');
    Route::get('/senior/search', [\App\Http\Controllers\SeniorDashboardController::class, 'globalSearch'])->middleware('throttle:60,1')->name('senior.search');
    Route::get('/senior/profile', [SeniorProfileController::class, 'profile'])->name('senior.profile');
    Route::get('/senior/settings', [SeniorProfileController::class, 'settings'])->name('senior.settings');
    Route::post('/senior/profile', [SeniorProfileController::class, 'updateProfile'])->middleware('throttle:10,1')->name('senior.profile.update');
    Route::post('/senior/settings', [SeniorProfileController::class, 'updateSettings'])->middleware('throttle:10,1')->name('senior.settings.update');
    Route::post('/senior/settings/password', [SeniorProfileController::class, 'changePassword'])->middleware('throttle:15,1')->name('senior.settings.password');
    Route::get('/senior/messages', [ConversationController::class, 'senior'])->name('senior.messages');
    Route::post('/senior/messages/{thread}/send', [ConversationController::class, 'seniorSend'])->name('senior.messages.send');
    Route::post('/senior/messages/{thread}/typing', [ConversationController::class, 'markAdvisorTyping'])->middleware('throttle:60,1')->name('senior.messages.typing');
    Route::get('/senior/document-builder', [SeniorDashboardController::class, 'documentBuilder'])->name('senior.document-builder');
    Route::post('/senior/document-builder/generate', [SeniorDashboardController::class, 'generateDocumentBuilderFile'])->name('senior.document-builder.generate');
    Route::post('/senior/document-builder/preview', [SeniorDashboardController::class, 'previewDocumentBuilder'])->name('senior.document-builder.preview');
    Route::get('/senior/registration/documents/{document}/download', [SeniorDashboardController::class, 'downloadDocument'])->name('senior.registration.documents.download');

    // Process Outcomes
    Route::post('/senior/process-outcomes', [SeniorPortalController::class, 'storeProcessOutcome'])->middleware('throttle:20,1')->name('senior.process-outcomes.store');
    Route::post('/senior/process-outcomes/{outcome}/make-visible', [SeniorPortalController::class, 'makeOutcomeVisible'])->middleware('throttle:30,1')->name('senior.process-outcomes.make-visible');

    // Vault CRUD
    Route::post('/senior/vault', [SeniorVaultController::class, 'storeVault'])->middleware('throttle:20,1')->name('senior.vault.store');
    Route::delete('/senior/vault/{vault}', [SeniorVaultController::class, 'destroyVault'])->middleware('throttle:10,1')->name('senior.vault.destroy');
    Route::post('/senior/vault/{vault}/toggle-visibility', [SeniorVaultController::class, 'toggleVaultVisibility'])->middleware('throttle:30,1')->name('senior.vault.toggle-visibility');

    // Institution Document Tracking
    Route::post('/senior/institution-documents', [SeniorInstitutionController::class, 'institutionDocumentStore'])->middleware('throttle:20,1')->name('senior.institution-documents.store');
    Route::put('/senior/institution-documents/{institutionDoc}', [SeniorInstitutionController::class, 'institutionDocumentUpdate'])->middleware('throttle:20,1')->name('senior.institution-documents.update');
    Route::delete('/senior/institution-documents/{institutionDoc}', [SeniorInstitutionController::class, 'institutionDocumentDelete'])->middleware('throttle:10,1')->name('senior.institution-documents.delete');
    Route::post('/senior/institution-documents/{institutionDoc}/visibility', [SeniorInstitutionController::class, 'institutionDocumentToggleVisibility'])->middleware('throttle:30,1')->name('senior.institution-documents.visibility');

    // University Application Tracking
    Route::get('/senior/university-applications', [SeniorInstitutionController::class, 'universityApplications'])->name('senior.university-applications');
    Route::post('/senior/university-applications', [SeniorInstitutionController::class, 'universityApplicationStore'])->middleware('throttle:20,1')->name('senior.university-applications.store');
    Route::put('/senior/university-applications/{uniApp}', [SeniorInstitutionController::class, 'universityApplicationUpdate'])->middleware('throttle:20,1')->name('senior.university-applications.update');
    Route::delete('/senior/university-applications/{uniApp}', [SeniorInstitutionController::class, 'universityApplicationDelete'])->middleware('throttle:10,1')->name('senior.university-applications.delete');
    Route::post('/senior/university-applications/{uniApp}/visibility', [SeniorInstitutionController::class, 'universityApplicationToggleVisibility'])->middleware('throttle:30,1')->name('senior.university-applications.visibility');

    // Vize & Konut Takibi
    Route::get('/senior/visa', fn() => redirect('/senior/process-tracking?tab=vize'))->name('senior.visa');
    Route::post('/senior/visa', [SeniorVisaHousingController::class, 'visaStore'])->middleware('throttle:20,1')->name('senior.visa.store');
    Route::put('/senior/visa/{visa}', [SeniorVisaHousingController::class, 'visaUpdate'])->middleware('throttle:20,1')->name('senior.visa.update');
    Route::delete('/senior/visa/{visa}', [SeniorVisaHousingController::class, 'visaDelete'])->middleware('throttle:10,1')->name('senior.visa.delete');
    Route::post('/senior/visa/{visa}/visibility', [SeniorVisaHousingController::class, 'visaToggleVisibility'])->middleware('throttle:30,1')->name('senior.visa.visibility');
    Route::get('/senior/housing', fn() => redirect('/senior/process-tracking?tab=ikamet'))->name('senior.housing');
    Route::post('/senior/housing', [SeniorVisaHousingController::class, 'housingStore'])->middleware('throttle:20,1')->name('senior.housing.store');
    Route::put('/senior/housing/{accommodation}', [SeniorVisaHousingController::class, 'housingUpdate'])->middleware('throttle:20,1')->name('senior.housing.update');
    Route::delete('/senior/housing/{accommodation}', [SeniorVisaHousingController::class, 'housingDelete'])->middleware('throttle:10,1')->name('senior.housing.delete');

    // Banner & Checklist
    Route::post('/senior/banner/{id}/click', [SeniorDashboardController::class, 'bannerClick'])->middleware('throttle:30,1')->name('senior.banner.click');
    Route::post('/senior/students/{studentId}/checklist', [SeniorInstitutionController::class, 'storeChecklist'])->middleware('throttle:30,1')->name('senior.checklist.store');
    Route::delete('/senior/students/{studentId}/checklist/{checklist}', [SeniorInstitutionController::class, 'deleteChecklist'])->middleware('throttle:20,1')->name('senior.checklist.delete');

    // Student 360° & Batch Review
    Route::get('/senior/students/{studentId}', [SeniorStudentController::class, 'studentDetail'])->name('senior.student-detail');
    Route::get('/senior/batch-review', [SeniorStudentController::class, 'batchReview'])->name('senior.batch-review');
    Route::post('/senior/batch-review/{document}/action', [SeniorStudentController::class, 'batchReviewAction'])->middleware('throttle:30,1')->name('senior.batch-review.action');

    // Quick Note & Unified Inbox
    Route::post('/senior/quick-note', [SeniorPortalController::class, 'quickNote'])->middleware('throttle:30,1')->name('senior.quick-note');
    Route::get('/senior/quick-note/recent', [SeniorPortalController::class, 'recentNotes'])->name('senior.quick-note.recent');
    Route::get('/senior/inbox', [SeniorPortalController::class, 'unifiedInbox'])->name('senior.inbox');

    // Pipeline Kanban
    Route::get('/senior/guest-pipeline',                              [SeniorPipelineController::class, 'guestPipeline'])->name('senior.guest-pipeline');
    Route::patch('/senior/guest-pipeline/{guest}/move',               [SeniorPipelineController::class, 'guestPipelineMove'])->middleware('throttle:30,1')->name('senior.guest-pipeline.move');
    Route::get('/senior/guest-pipeline/poll',                         [SeniorPipelineController::class, 'guestPipelinePoll'])->name('senior.guest-pipeline.poll');
    Route::get('/senior/student-pipeline', [SeniorPipelineController::class, 'studentPipeline'])->name('senior.student-pipeline');
    Route::post('/senior/student-pipeline/advance', [SeniorPipelineController::class, 'advanceStudentStep'])->middleware('throttle:20,1')->name('senior.student-pipeline.advance');

    // Canned Response Templates
    Route::get('/senior/response-templates', [SeniorTemplateController::class, 'responseTemplates'])->name('senior.response-templates.index');
    Route::post('/senior/response-templates', [SeniorTemplateController::class, 'responseTemplateStore'])->middleware('throttle:20,1')->name('senior.response-templates.store');
    Route::put('/senior/response-templates/{template}', [SeniorTemplateController::class, 'responseTemplateUpdate'])->middleware('throttle:20,1')->name('senior.response-templates.update');
    Route::delete('/senior/response-templates/{template}', [SeniorTemplateController::class, 'responseTemplateDelete'])->middleware('throttle:10,1')->name('senior.response-templates.delete');
    Route::post('/senior/response-templates/{template}/use', [SeniorTemplateController::class, 'responseTemplateUse'])->middleware('throttle:60,1')->name('senior.response-templates.use');

    // ── AI Danışman Asistanı ──────────────────────────────────────────────────
    Route::get('/senior/ai-assistant',           [SeniorEngagementController::class, 'aiAssistantPage'])->name('senior.ai-assistant');
    Route::post('/senior/ai-assistant/ask',      [SeniorEngagementController::class, 'aiAssistantAsk'])->middleware('throttle:10,1')->name('senior.ai-assistant.ask');
    Route::get('/senior/ai-assistant/history',   [SeniorEngagementController::class, 'aiAssistantHistory'])->name('senior.ai-assistant.history');
    Route::get('/senior/ai-assistant/remaining', [SeniorEngagementController::class, 'aiAssistantRemaining'])->name('senior.ai-assistant.remaining');

    // ── Kılavuz ──────────────────────────────────────────────────────────────
    Route::get('/senior/help', [HandbookController::class, 'senior'])->name('senior.handbook');
    Route::get('/senior/help/download', [HandbookController::class, 'download'])->defaults('role', 'senior')->name('senior.handbook.download');

    // ── Digital Asset Management (DAM) — macro tanımı AppServiceProvider'da ──
    // İzni olmayan senior permission middleware ile engellenir.
    Route::dam('senior/digital-assets', 'senior.dam.');

    // ── Booking / Randevu Modülü (module:booking gate) ───────────────────────
    Route::middleware('module:booking')->group(function (): void {
        $c = \App\Http\Controllers\Booking\SeniorAvailabilityController::class;
        Route::get('/senior/booking-settings',                       [$c, 'index'])->name('senior.booking-settings');
        Route::post('/senior/booking-settings',                      [$c, 'updateSettings'])->middleware('throttle:30,1')->name('senior.booking-settings.update');
        Route::post('/senior/booking-settings/patterns',             [$c, 'storePattern'])->middleware('throttle:60,1')->name('senior.booking-settings.patterns.store');
        Route::delete('/senior/booking-settings/patterns/{pattern}', [$c, 'destroyPattern'])->middleware('throttle:60,1')->name('senior.booking-settings.patterns.destroy');
        Route::post('/senior/booking-settings/exceptions',           [$c, 'storeException'])->middleware('throttle:60,1')->name('senior.booking-settings.exceptions.store');
        Route::delete('/senior/booking-settings/exceptions/{exception}', [$c, 'destroyException'])->middleware('throttle:60,1')->name('senior.booking-settings.exceptions.destroy');

        // Earnings dashboard
        Route::get('/senior/earnings', [\App\Http\Controllers\Booking\SeniorEarningsController::class, 'index'])->name('senior.earnings');
    });
});
