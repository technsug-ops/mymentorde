<?php

use App\Http\Controllers\HandbookController;
use App\Http\Controllers\Hr\HrAttendanceController;
use App\Http\Controllers\Hr\HrCertificationController;
use App\Http\Controllers\Hr\HrDashboardController;
use App\Http\Controllers\Hr\HrLeaveController;
use App\Http\Controllers\Hr\HrPersonController;
use App\Http\Controllers\Hr\HrRecruitmentController;
use App\Http\Controllers\Hr\HrOnboardingController;
use App\Http\Controllers\Hr\HrSalaryController;
use App\Http\Controllers\Manager\BusinessContractController;
use App\Http\Controllers\Manager\BulletinManagerController;
use App\Http\Controllers\Manager\ContractTemplateController;
use App\Http\Controllers\Manager\ContractWorkflowController;
use App\Http\Controllers\Manager\ContractPrintController;
use App\Http\Controllers\Manager\FinanceController;
use App\Http\Controllers\Manager\ManagerAnalyticsController;
use App\Http\Controllers\Manager\ManagerPaymentController;
use App\Http\Controllers\Manager\ManagerPortalController;
use App\Http\Controllers\Manager\ManagerReportSnapshotController;
use App\Http\Controllers\Manager\ManagerScheduledReportController;
use App\Http\Controllers\Manager\ManagerTargetAlertController;
use App\Http\Controllers\Manager\StaffController;
use App\Http\Controllers\Manager\SystemAdminController;
use App\Http\Controllers\Manager\ThemeController;
use App\Http\Controllers\Manager\WebhookController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\ManagerPortalPreviewController;
use App\Http\Controllers\ManagerRequestController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\StudentCardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['company.context', 'auth', 'verified', 'manager.role', 'require.2fa'])->group(function (): void {
    Route::get('/manager/dashboard', [ManagerDashboardController::class, 'index']);
    Route::post('/manager/dashboard/snapshot', [ManagerReportSnapshotController::class, 'store'])->name('manager.snapshot.store');
    Route::get('/manager/dashboard/snapshot/{managerReport}', [ManagerReportSnapshotController::class, 'show'])->name('manager.snapshot.show');
    Route::post('/manager/dashboard/snapshot/{managerReport}/mark-sent', [ManagerReportSnapshotController::class, 'markSent'])->name('manager.snapshot.mark-sent');
    Route::post('/manager/dashboard/snapshot/{managerReport}/mark-draft', [ManagerReportSnapshotController::class, 'markDraft'])->name('manager.snapshot.mark-draft');
    Route::post('/manager/dashboard/snapshot/mark-sent-bulk', [ManagerReportSnapshotController::class, 'markSentBulk'])->name('manager.snapshot.mark-sent-bulk');
    Route::post('/manager/dashboard/snapshot/mark-draft-bulk', [ManagerReportSnapshotController::class, 'markDraftBulk'])->name('manager.snapshot.mark-draft-bulk');
    Route::get('/manager/dashboard/snapshot/{managerReport}/export-csv', [ManagerReportSnapshotController::class, 'exportCsv'])->name('manager.snapshot.export-csv');
    Route::get('/manager/dashboard/snapshot/{managerReport}/print', [ManagerReportSnapshotController::class, 'print'])->name('manager.snapshot.print');
    Route::delete('/manager/dashboard/snapshot/{managerReport}', [ManagerReportSnapshotController::class, 'destroy'])->name('manager.snapshot.destroy');
    Route::get('/manager/dashboard/export-csv', [ManagerDashboardController::class, 'exportCsv']);
    Route::get('/manager/dashboard/report-print', [ManagerDashboardController::class, 'reportPrint']);
    Route::view('/config', 'config.index');
    Route::get('/config/export-code/safe', [ProjectExportController::class, 'safe'])->middleware('throttle:3,60');
    Route::get('/config/export-code/full', [ProjectExportController::class, 'full']);
    Route::get('/student-card', [StudentCardController::class, 'index']);
    Route::get('/manager/theme', [ThemeController::class, 'show'])->name('manager.theme.show');
    Route::post('/manager/theme', [ThemeController::class, 'update'])->name('manager.theme.update');
    Route::post('/manager/theme/brand', [ThemeController::class, 'updateBrand'])->name('manager.theme.brand');
    Route::get('/manager/preview/student/{studentId}', [ManagerPortalPreviewController::class, 'student'])->name('manager.preview.student');
    Route::get('/manager/preview/dealer/{dealerCode}', [ManagerPortalPreviewController::class, 'dealer'])->name('manager.preview.dealer');
    Route::get('/manager/preview/senior/{email}', [ManagerPortalPreviewController::class, 'senior'])->name('manager.preview.senior');

    // ─── Manager Portal Pages ─────────────────────────────────────────────
    Route::get('/manager/guests',                           [ManagerPortalController::class, 'guests'])->name('manager.guests');
    Route::get('/manager/guests/export-csv',                [ManagerPortalController::class, 'guestsExportCsv'])->name('manager.guests.export-csv');
    Route::get('/manager/guests/{guest}',                   [ManagerPortalController::class, 'guestShow'])->name('manager.guests.show');
    Route::get('/manager/guests/{guest}/documents/{document}/serve', [ManagerPortalController::class, 'guestDocumentServe'])->name('manager.guest.document.serve');
    Route::get('/manager/guests/{guest}/documents/{document}/download', [ManagerPortalController::class, 'guestDocumentDownload'])->name('manager.guest.document.download');
    Route::get('/manager/guests/{guest}/documents/zip', [ManagerPortalController::class, 'guestDocumentsZip'])->name('manager.guest.documents.zip');
    Route::patch('/manager/guests/{guest}/status',          [ManagerPortalController::class, 'guestUpdateStatus'])->name('manager.guests.status');
    Route::patch('/manager/guests/{guest}/assign',          [ManagerPortalController::class, 'guestAssignSenior'])->name('manager.guests.assign');

    // ─── Lead Pipeline Kanban (Marketing Admin'den bağımsız) ─────────────────
    $leadPipeline = \App\Http\Controllers\Manager\LeadPipelineController::class;
    Route::get('/manager/pipeline/kanban',                  [$leadPipeline, 'kanban'])->name('manager.pipeline.kanban');
    Route::get('/manager/pipeline/kanban/poll',             [$leadPipeline, 'kanbanPoll'])->name('manager.pipeline.kanban.poll');
    Route::post('/manager/pipeline/kanban/{guest}/move',    [$leadPipeline, 'kanbanMove'])->name('manager.pipeline.kanban.move');
    Route::get('/manager/students',                         [ManagerPortalController::class, 'students'])->name('manager.students');
    Route::get('/manager/students/export-csv',              [ManagerPortalController::class, 'studentsExportCsv'])->name('manager.students.export-csv');
    Route::get('/manager/students/{studentId}',             [ManagerPortalController::class, 'studentShow'])->name('manager.students.show');
    Route::patch('/manager/students/{studentId}/update',    [ManagerPortalController::class, 'studentUpdateAssignment'])->name('manager.students.update');
    Route::get('/manager/students/{studentId}/institution-documents', [ManagerPortalController::class, 'studentInstitutionDocs'])->name('manager.students.institution-docs');
    Route::get('/manager/students/{studentId}/university-applications', [ManagerPortalController::class, 'studentUniversityApplications'])->name('manager.students.university-applications');

    // ─── Personel (Staff) Yönetimi ───────────────────────────────────────────
    Route::get('/manager/staff',                              [StaffController::class, 'index'])->name('manager.staff.index');
    Route::get('/manager/staff/create',                       [StaffController::class, 'create'])->name('manager.staff.create');
    Route::post('/manager/staff',                             [StaffController::class, 'store'])->name('manager.staff.store');
    Route::get('/manager/staff/leaderboard',                  [StaffController::class, 'leaderboard'])->name('manager.staff.leaderboard');
    Route::get('/manager/staff/performance',                  [StaffController::class, 'performanceDashboard'])->name('manager.staff.performance');
    Route::get('/manager/staff/{user}/edit',                  [StaffController::class, 'edit'])->name('manager.staff.edit');
    Route::get('/manager/staff/{user}',                       [StaffController::class, 'show'])->name('manager.staff.show');
    Route::put('/manager/staff/{user}',                       [StaffController::class, 'update'])->name('manager.staff.update');
    Route::post('/manager/staff/{user}/toggle',               [StaffController::class, 'toggle'])->name('manager.staff.toggle');
    Route::post('/manager/staff/{user}/kpi-targets',          [StaffController::class, 'setKpiTargets'])->name('manager.staff.kpi-targets');
    Route::post('/manager/staff/bulk',                         [StaffController::class, 'bulkAction'])->name('manager.staff.bulk');

    // ─── HR Modülü (Manager) ─────────────────────────────────────────────────
    Route::get('/manager/hr',                                     [HrDashboardController::class, 'index'])->name('manager.hr.dashboard');
    Route::get('/manager/hr/persons/{user}',                      [HrPersonController::class, 'card'])->name('manager.hr.person.card');
    Route::post('/manager/hr/persons/{user}/profile',             [HrPersonController::class, 'updateProfile'])->name('manager.hr.person.profile');
    Route::post('/manager/hr/persons/{user}/toggle',              [HrPersonController::class, 'toggleActive'])->name('manager.hr.person.toggle');
    Route::post('/manager/hr/persons/{user}/reset-password',      [HrPersonController::class, 'resetPassword'])->name('manager.hr.person.reset-password');
    Route::post('/manager/hr/persons/{user}/templates/add',       [HrPersonController::class, 'addTemplate'])->name('manager.hr.person.template.add');
    Route::patch('/manager/hr/persons/{user}/templates/{assignment}/revoke', [HrPersonController::class, 'revokeTemplate'])->name('manager.hr.person.template.revoke');
    Route::get('/manager/hr/senior-transfer',                     [HrPersonController::class, 'seniorTransferForm'])->name('manager.hr.senior-transfer');
    Route::post('/manager/hr/senior-transfer',                    [HrPersonController::class, 'seniorTransferExecute'])->name('manager.hr.senior-transfer.execute');
    Route::get('/manager/hr/leaves',                              [HrLeaveController::class, 'managerIndex'])->name('manager.hr.leaves');
    Route::post('/manager/hr/leaves',                             [HrLeaveController::class, 'store'])->name('manager.hr.leaves.store');
    Route::post('/manager/hr/leaves/own',                         [HrLeaveController::class, 'managerOwnStore'])->name('manager.hr.leaves.own');
    Route::patch('/manager/hr/leaves/{leave}/approve',            [HrLeaveController::class, 'approve'])->name('manager.hr.leaves.approve');
    Route::patch('/manager/hr/leaves/{leave}/reject',             [HrLeaveController::class, 'reject'])->name('manager.hr.leaves.reject');
    Route::get('/manager/hr/certifications',                      [HrCertificationController::class, 'index'])->name('manager.hr.certifications');
    Route::post('/manager/hr/certifications',                     [HrCertificationController::class, 'store'])->name('manager.hr.certifications.store');
    Route::put('/manager/hr/certifications/{hrCertification}',    [HrCertificationController::class, 'update'])->name('manager.hr.certifications.update');
    Route::delete('/manager/hr/certifications/{hrCertification}', [HrCertificationController::class, 'destroy'])->name('manager.hr.certifications.destroy');

    Route::get('/manager/seniors',                          [ManagerPortalController::class, 'seniors'])->name('manager.seniors');
    Route::get('/manager/seniors/{email}',                  [ManagerPortalController::class, 'seniorShow'])->name('manager.seniors.show')->where('email', '[^/]+');
    Route::get('/manager/senior-leaderboard',               [ManagerPortalController::class, 'seniorLeaderboard'])->name('manager.senior-leaderboard');
    Route::post('/manager/seniors/{email}/targets',         [ManagerPortalController::class, 'setSeniorTargets'])->name('manager.seniors.targets')->where('email', '[^/]+');
    Route::get('/manager/dealers',                          [ManagerPortalController::class, 'dealers'])->name('manager.dealers');
    Route::get('/manager/dealers/{code}',                   [ManagerPortalController::class, 'dealerShow'])->name('manager.dealers.show');
    Route::get('/manager/dealer-types',                     [ManagerPortalController::class, 'dealerTypes'])->name('manager.dealer-types');
    Route::post('/manager/dealer-types/{code}',             [ManagerPortalController::class, 'updateDealerType'])->name('manager.dealer-types.update');
    Route::get('/manager/commissions',                      [ManagerPortalController::class, 'commissions'])->name('manager.commissions');
    Route::patch('/manager/commissions/{payout}/approve',   [ManagerPortalController::class, 'approveCommission'])->name('manager.commissions.approve');
    Route::patch('/manager/commissions/{payout}/reject',    [ManagerPortalController::class, 'rejectCommission'])->name('manager.commissions.reject');
    Route::patch('/manager/commissions/{payout}/mark-paid', [ManagerPortalController::class, 'markPaid'])->name('manager.commissions.mark-paid');

    // ─── Süreç Adımı Sub-task Yönetimi ──────────────────────────────────────
    Route::get('/manager/process-step-tasks',               [ManagerPortalController::class, 'processStepTasks'])->name('manager.process-step-tasks');
    Route::post('/manager/process-step-tasks',              [ManagerPortalController::class, 'processStepTaskStore'])->name('manager.process-step-tasks.store');
    Route::put('/manager/process-step-tasks/{task}',        [ManagerPortalController::class, 'processStepTaskUpdate'])->name('manager.process-step-tasks.update');
    Route::delete('/manager/process-step-tasks/{task}',     [ManagerPortalController::class, 'processStepTaskDelete'])->name('manager.process-step-tasks.delete');

    // ─── Üniversite Belge Haritası ───────────────────────────────────────────
    Route::get('/manager/university-requirements',              [ManagerPortalController::class, 'universityRequirements'])->name('manager.university-requirements');
    Route::post('/manager/university-requirements',             [ManagerPortalController::class, 'universityRequirementStore'])->name('manager.university-requirements.store');
    Route::put('/manager/university-requirements/{map}',        [ManagerPortalController::class, 'universityRequirementUpdate'])->name('manager.university-requirements.update');
    Route::delete('/manager/university-requirements/{map}',     [ManagerPortalController::class, 'universityRequirementDelete'])->name('manager.university-requirements.delete');
    Route::get('/manager/university-requirements/lookup',       [ManagerPortalController::class, 'universityRequirementLookup'])->name('manager.university-requirements.lookup');

    // ─── Katman 2 — Dashboard Alt Modüller ──────────────────────────────────
    Route::get('/manager/revenue-analytics', [ManagerAnalyticsController::class, 'revenueAnalytics'])->name('manager.revenue-analytics');
    Route::get('/manager/feedback-analytics',        [ManagerAnalyticsController::class, 'feedbackAnalytics'])->name('manager.feedback-analytics');
    Route::get('/manager/feedback-analytics/export', [ManagerAnalyticsController::class, 'feedbackExport'])->name('manager.feedback-analytics.export');
    Route::get('/manager/conversion-funnel',         [ManagerAnalyticsController::class, 'conversionFunnel'])->name('manager.conversion-funnel');
    Route::get('/manager/senior-performance',        [ManagerAnalyticsController::class, 'seniorPerformance'])->name('manager.senior-performance');
    Route::get('/manager/ticket-analytics',          [ManagerAnalyticsController::class, 'ticketAnalytics'])->name('manager.ticket-analytics');

    // User Intelligence + Lead Actions routes dışarı alındı — alt bloktaki
    // analytics.access middleware'i kullanıyor (marketing/sales roller dahil).
    Route::get('/manager/scheduled-reports',                          [ManagerScheduledReportController::class, 'index'])->name('manager.scheduled-reports');
    Route::post('/manager/scheduled-reports',                         [ManagerScheduledReportController::class, 'store'])->name('manager.scheduled-reports.store');
    Route::put('/manager/scheduled-reports/{scheduledReport}',        [ManagerScheduledReportController::class, 'update'])->name('manager.scheduled-reports.update');
    Route::delete('/manager/scheduled-reports/{scheduledReport}',     [ManagerScheduledReportController::class, 'destroy'])->name('manager.scheduled-reports.destroy');
    Route::post('/manager/scheduled-reports/{scheduledReport}/toggle', [ManagerScheduledReportController::class, 'toggle'])->name('manager.scheduled-reports.toggle');
    Route::get('/manager/targets',        [ManagerTargetAlertController::class, 'targets'])->name('manager.targets');
    Route::post('/manager/targets',       [ManagerTargetAlertController::class, 'targetStore'])->name('manager.targets.store');
    Route::get('/manager/targets/report', [ManagerTargetAlertController::class, 'targetsReport'])->name('manager.targets.report');
    Route::get('/manager/alert-rules',                    [ManagerTargetAlertController::class, 'alertRules'])->name('manager.alert-rules');
    Route::post('/manager/alert-rules',                   [ManagerTargetAlertController::class, 'alertRuleStore'])->name('manager.alert-rules.store');
    Route::put('/manager/alert-rules/{alertRule}',        [ManagerTargetAlertController::class, 'alertRuleUpdate'])->name('manager.alert-rules.update');
    Route::delete('/manager/alert-rules/{alertRule}',     [ManagerTargetAlertController::class, 'alertRuleDestroy'])->name('manager.alert-rules.destroy');

    // ─── Audit Log ──────────────────────────────────────────────────────────
    Route::get('/manager/audit-log', [ManagerPortalController::class, 'auditLog'])->name('manager.audit-log');

    // ─── Sistem Admin Paneli ─────────────────────────────────────────────────
    Route::get('/manager/system',                            [SystemAdminController::class, 'dashboard'])->name('manager.system.dashboard');
    Route::get('/manager/system/ip-rules',                   [SystemAdminController::class, 'ipRules'])->name('manager.system.ip-rules');
    Route::post('/manager/system/ip-rules',                  [SystemAdminController::class, 'storeIpRule'])->name('manager.system.ip-rules.store');
    Route::patch('/manager/system/ip-rules/{rule}/toggle',   [SystemAdminController::class, 'toggleIpRule'])->name('manager.system.ip-rules.toggle');
    Route::delete('/manager/system/ip-rules/{rule}',         [SystemAdminController::class, 'deleteIpRule'])->name('manager.system.ip-rules.delete');
    Route::get('/manager/system/security',                   [SystemAdminController::class, 'securityPanel'])->name('manager.system.security');
    Route::get('/manager/system/roles',                      [SystemAdminController::class, 'rolesIndex'])->name('manager.system.roles');
    Route::post('/manager/system/roles',                     [SystemAdminController::class, 'storeTemplate'])->name('manager.system.roles.store');
    Route::get('/manager/system/roles/users/{user}',         [SystemAdminController::class, 'userRoleProfile'])->name('manager.system.roles.user');
    Route::post('/manager/system/roles/users/{user}/assign', [SystemAdminController::class, 'assignRoleTemplate'])->name('manager.system.roles.assign');
    Route::patch('/manager/system/roles/assignments/{assignment}/revoke', [SystemAdminController::class, 'revokeRoleAssignment'])->name('manager.system.roles.revoke');
    Route::get('/manager/system/roles/{template}',           [SystemAdminController::class, 'roleTemplateDetail'])->name('manager.system.roles.detail');
    Route::post('/manager/system/roles/{template}/permissions', [SystemAdminController::class, 'updateTemplatePermissions'])->name('manager.system.roles.permissions');

    // ─── Webhook Logları ────────────────────────────────────────────────────
    Route::get('/manager/webhooks',                  [WebhookController::class, 'index'])->name('manager.webhooks.index');
    Route::post('/manager/webhooks/{log}/retry',     [WebhookController::class, 'retry'])->name('manager.webhooks.retry');
    Route::delete('/manager/webhooks/{log}',         [WebhookController::class, 'destroy'])->name('manager.webhooks.destroy');

    // ─── HR Devam Raporu & KPI (Manager) ────────────────────────────────────
    Route::get('/manager/hr/attendance', [HrAttendanceController::class, 'managerReport'])->name('manager.hr.attendance');
    Route::get('/manager/hr/kpi',        [HrPersonController::class, 'kpiDashboard'])->name('manager.hr.kpi');

    // ─── HR İşe Alım & Onboarding ────────────────────────────────────────────
    Route::get('/manager/hr/recruitment',                                          [HrRecruitmentController::class, 'postings'])->name('manager.hr.recruitment');
    Route::post('/manager/hr/recruitment/postings',                                [HrRecruitmentController::class, 'storePosting'])->name('manager.hr.recruitment.postings.store');
    Route::put('/manager/hr/recruitment/postings/{posting}',                       [HrRecruitmentController::class, 'updatePosting'])->name('manager.hr.recruitment.postings.update');
    Route::get('/manager/hr/recruitment/candidates',                               [HrRecruitmentController::class, 'candidates'])->name('manager.hr.recruitment.candidates');
    Route::post('/manager/hr/recruitment/candidates',                              [HrRecruitmentController::class, 'storeCandidate'])->name('manager.hr.recruitment.candidates.store');
    Route::get('/manager/hr/recruitment/candidates/{candidate}',                   [HrRecruitmentController::class, 'candidateDetail'])->name('manager.hr.recruitment.candidates.show');
    Route::patch('/manager/hr/recruitment/candidates/{candidate}/status',          [HrRecruitmentController::class, 'updateCandidateStatus'])->name('manager.hr.recruitment.candidates.status');
    Route::post('/manager/hr/recruitment/candidates/{candidate}/interviews',       [HrRecruitmentController::class, 'storeInterview'])->name('manager.hr.recruitment.interviews.store');
    Route::patch('/manager/hr/recruitment/interviews/{interview}',                 [HrRecruitmentController::class, 'updateInterview'])->name('manager.hr.recruitment.interviews.update');
    Route::get('/manager/hr/recruitment/onboarding',                               [HrRecruitmentController::class, 'onboarding'])->name('manager.hr.recruitment.onboarding');
    Route::post('/manager/hr/recruitment/onboarding/{user}/init',                  [HrRecruitmentController::class, 'initOnboarding'])->name('manager.hr.recruitment.onboarding.init');
    Route::patch('/manager/hr/recruitment/onboarding-tasks/{task}/toggle',         [HrRecruitmentController::class, 'toggleOnboardingTask'])->name('manager.hr.recruitment.onboarding-tasks.toggle');

    // ─── HR Bordro Profilleri ─────────────────────────────────────────────────
    Route::get('/manager/hr/salary',              [HrSalaryController::class, 'index'])->name('manager.hr.salary');
    Route::post('/manager/hr/salary/{user}',      [HrSalaryController::class, 'store'])->name('manager.hr.salary.store');

    // ─── Şirket Finans Yönetimi ───────────────────────────────────────────────
    Route::get('/manager/finance',                           [FinanceController::class, 'dashboard'])->name('manager.finance.dashboard');
    Route::get('/manager/finance/reports',                   [FinanceController::class, 'reports'])->name('manager.finance.reports');
    Route::get('/manager/finance/entries',                   [FinanceController::class, 'entries'])->name('manager.finance.entries');
    Route::post('/manager/finance/entries',                  [FinanceController::class, 'store'])->name('manager.finance.store');
    Route::put('/manager/finance/entries/{entry}',           [FinanceController::class, 'update'])->name('manager.finance.update');
    Route::delete('/manager/finance/entries/{entry}',        [FinanceController::class, 'destroy'])->name('manager.finance.destroy');
    Route::post('/manager/finance/import-csv',               [FinanceController::class, 'importCsv'])->name('manager.finance.import-csv');

    // ─── Öğrenci Ödemeleri & Fatura ──────────────────────────────────────────
    Route::get('/manager/payments',                          [ManagerPaymentController::class, 'index'])->name('manager.payments.index');
    Route::post('/manager/payments',                         [ManagerPaymentController::class, 'store'])->name('manager.payments.store');
    Route::patch('/manager/payments/{payment}/mark-paid',    [ManagerPaymentController::class, 'markPaid'])->name('manager.payments.mark-paid');
    Route::patch('/manager/payments/{payment}/cancel',       [ManagerPaymentController::class, 'cancel'])->name('manager.payments.cancel');
    Route::delete('/manager/payments/{payment}',             [ManagerPaymentController::class, 'destroy'])->name('manager.payments.destroy');
    Route::get('/manager/payments/{payment}/preview',        [ManagerPaymentController::class, 'preview'])->name('manager.payments.preview');
    Route::get('/manager/payments/{payment}/invoice',        [ManagerPaymentController::class, 'invoice'])->name('manager.payments.invoice');
    Route::patch('/manager/payments/{payment}/acknowledge',  [ManagerPaymentController::class, 'acknowledgeUpdate'])->name('manager.payments.acknowledge');

    // ─── Marka Ayarları ──────────────────────────────────────────────────────
    Route::get('/manager/brand', [\App\Http\Controllers\Manager\BrandSettingController::class, 'show'])->name('manager.brand.show');
    Route::put('/manager/brand', [\App\Http\Controllers\Manager\BrandSettingController::class, 'update'])->name('manager.brand.update');

    // ─── Duyuru Yönetimi ─────────────────────────────────────────────────────
    Route::get('/manager/bulletins',                  [BulletinManagerController::class, 'index'])->name('manager.bulletins.index');
    Route::get('/manager/bulletins/create',           [BulletinManagerController::class, 'create'])->name('manager.bulletins.create');
    Route::post('/manager/bulletins',                 [BulletinManagerController::class, 'store'])->name('manager.bulletins.store');
    Route::get('/manager/bulletins/{bulletin}/edit',  [BulletinManagerController::class, 'edit'])->name('manager.bulletins.edit');
    Route::put('/manager/bulletins/{bulletin}',       [BulletinManagerController::class, 'update'])->name('manager.bulletins.update');
    Route::delete('/manager/bulletins/{bulletin}',    [BulletinManagerController::class, 'destroy'])->name('manager.bulletins.destroy');
    Route::get('/manager/bulletins/{bulletin}/analytics', [BulletinManagerController::class, 'analytics'])->name('manager.bulletins.analytics');

    // ─── Tanıtım Popup Yönetimi ──────────────────────────────────────────────
    Route::get('/manager/promo-popups',                    [\App\Http\Controllers\Manager\PromoPopupController::class, 'index'])->name('manager.promo-popups.index');
    Route::get('/manager/promo-popups/create',             [\App\Http\Controllers\Manager\PromoPopupController::class, 'create'])->name('manager.promo-popups.create');
    Route::post('/manager/promo-popups',                   [\App\Http\Controllers\Manager\PromoPopupController::class, 'store'])->name('manager.promo-popups.store');
    Route::get('/manager/promo-popups/{popup}/edit',       [\App\Http\Controllers\Manager\PromoPopupController::class, 'edit'])->name('manager.promo-popups.edit');
    Route::put('/manager/promo-popups/{popup}',            [\App\Http\Controllers\Manager\PromoPopupController::class, 'update'])->name('manager.promo-popups.update');
    Route::delete('/manager/promo-popups/{popup}',         [\App\Http\Controllers\Manager\PromoPopupController::class, 'destroy'])->name('manager.promo-popups.destroy');
    Route::post('/manager/promo-popups/{popup}/toggle',    [\App\Http\Controllers\Manager\PromoPopupController::class, 'toggle'])->name('manager.promo-popups.toggle');

    // ─── Toplu Kayıt İçeri Aktarma (Excel/CSV) ───────────────────────────────
    Route::get('/manager/bulk-import/guests',             [\App\Http\Controllers\Manager\BulkImportController::class, 'index'])->name('manager.bulk-import.index');
    Route::get('/manager/bulk-import/guests/template',    [\App\Http\Controllers\Manager\BulkImportController::class, 'template'])->name('manager.bulk-import.template');
    Route::post('/manager/bulk-import/guests/preview',    [\App\Http\Controllers\Manager\BulkImportController::class, 'preview'])->name('manager.bulk-import.preview');
    Route::post('/manager/bulk-import/guests/commit',     [\App\Http\Controllers\Manager\BulkImportController::class, 'commit'])->name('manager.bulk-import.commit');
    Route::post('/manager/bulk-import/guests/reset',      [\App\Http\Controllers\Manager\BulkImportController::class, 'reset'])->name('manager.bulk-import.reset');

    // ─── Document Builder Templates ───────────────────────────────────────────
    Route::get('/manager/doc-templates',                        [\App\Http\Controllers\Manager\DocTemplateController::class, 'index'])->name('manager.doc-templates.index');
    Route::get('/manager/doc-templates/create',                 [\App\Http\Controllers\Manager\DocTemplateController::class, 'create'])->name('manager.doc-templates.create');
    Route::post('/manager/doc-templates',                       [\App\Http\Controllers\Manager\DocTemplateController::class, 'store'])->name('manager.doc-templates.store');
    Route::get('/manager/doc-templates/{tpl}/edit',             [\App\Http\Controllers\Manager\DocTemplateController::class, 'edit'])->name('manager.doc-templates.edit');
    Route::put('/manager/doc-templates/{tpl}',                  [\App\Http\Controllers\Manager\DocTemplateController::class, 'update'])->name('manager.doc-templates.update');
    Route::delete('/manager/doc-templates/{tpl}',               [\App\Http\Controllers\Manager\DocTemplateController::class, 'destroy'])->name('manager.doc-templates.destroy');
    Route::post('/manager/doc-templates/{tpl}/set-default',     [\App\Http\Controllers\Manager\DocTemplateController::class, 'setDefault'])->name('manager.doc-templates.set-default');
    Route::get('/manager/doc-templates/{tpl}/preview',          [\App\Http\Controllers\Manager\DocTemplateController::class, 'preview'])->name('manager.doc-templates.preview');
    Route::get('/manager/doc-templates/{tpl}/download',         [\App\Http\Controllers\Manager\DocTemplateController::class, 'download'])->name('manager.doc-templates.download');

    // ─── Digital Asset Management (DAM) — macro tanımı AppServiceProvider'da ──
    Route::dam('manager/digital-assets', 'manager.dam.');
});

Route::middleware(['company.context', 'auth', 'manager.or.permission:student.assignment.manage'])->group(function (): void {
    // ── Contract Template — CRUD & Display ───────────────────────────────────
    Route::get('/manager/contract-template', [ContractTemplateController::class, 'show'])->name('manager.contract-template.show');
    Route::post('/manager/contract-template', [ContractTemplateController::class, 'save'])->name('manager.contract-template.save');
    Route::post('/manager/contract-template/company-settings', [ContractTemplateController::class, 'saveCompanySettings'])->name('manager.contract-template.company-settings');
    Route::get('/manager/contract-template/diff', [ContractTemplateController::class, 'diff'])->name('manager.contract-template.diff');
    Route::get('/manager/contract-analytics', [ContractTemplateController::class, 'analytics'])->name('manager.contract-analytics');

    // ── Contract Workflow (ContractWorkflowController) ────────────────────────
    Route::post('/manager/contract-template/start-contract', [ContractWorkflowController::class, 'startContract'])->name('manager.contract-template.start-contract');
    Route::post('/manager/contract-template/decision', [ContractWorkflowController::class, 'decideContract'])->name('manager.contract-template.decision');
    Route::post('/manager/contract-template/cancel', [ContractWorkflowController::class, 'cancelContract'])->name('manager.contract-template.cancel');
    Route::post('/manager/contract-template/reopen-approve', [ContractWorkflowController::class, 'approveReopen'])->name('manager.contract-template.reopen-approve');
    Route::post('/manager/contract-template/reopen-reject', [ContractWorkflowController::class, 'rejectReopen'])->name('manager.contract-template.reopen-reject');
    Route::post('/manager/contract-template/reset', [ContractWorkflowController::class, 'resetContract'])->name('manager.contract-template.reset');
    Route::post('/manager/contract-template/batch-decision', [ContractWorkflowController::class, 'batchDecision'])->name('manager.contract-template.batch-decision');

    // ── Contract Signed File Serve ──────────────────────────────────────────
    Route::get('/manager/contract-template/signed-file/{guest}', [ContractWorkflowController::class, 'serveSignedFile'])->name('manager.contract-template.signed-file');

    // ── Contract Print & Utilities (ContractPrintController) ──────────────────
    Route::post('/manager/contract-template/student-services', [ContractPrintController::class, 'saveStudentServices'])->name('manager.contract-template.student-services');
    Route::post('/manager/contract-template/refresh-snapshot', [ContractPrintController::class, 'refreshSnapshot'])->name('manager.contract-template.refresh-snapshot');
    Route::get('/manager/contract-template/print/{guestId}', [ContractPrintController::class, 'printContract'])->name('manager.contract-template.print');
    Route::get('/manager/contract-template/pdf/{guestId}', [ContractPrintController::class, 'downloadPdf'])->name('manager.contract-template.pdf');
    Route::get('/manager/notification-stats', [ManagerAnalyticsController::class, 'notificationStats'])->name('manager.notification-stats');
    Route::get('/manager/gdpr-dashboard', [ManagerAnalyticsController::class, 'gdprDashboard'])->name('manager.gdpr-dashboard');

    // Business Contracts (Dealer / Staff)
    Route::get('/manager/business-contracts',                        [BusinessContractController::class, 'index'])->name('manager.business-contracts.index');
    Route::get('/manager/business-contracts/create',                 [BusinessContractController::class, 'create'])->name('manager.business-contracts.create');
    Route::post('/manager/business-contracts',                       [BusinessContractController::class, 'store'])->name('manager.business-contracts.store');
    Route::get('/manager/business-contracts/{businessContract}',     [BusinessContractController::class, 'show'])->name('manager.business-contracts.show');
    Route::patch('/manager/business-contracts/{businessContract}/issue',        [BusinessContractController::class, 'issue'])->name('manager.business-contracts.issue');
    Route::post('/manager/business-contracts/{businessContract}/upload-signed', [BusinessContractController::class, 'uploadSigned'])->name('manager.business-contracts.upload-signed');
    Route::patch('/manager/business-contracts/{businessContract}/approve',      [BusinessContractController::class, 'approve'])->name('manager.business-contracts.approve');
    Route::patch('/manager/business-contracts/{businessContract}/cancel',       [BusinessContractController::class, 'cancel'])->name('manager.business-contracts.cancel');
    Route::get('/manager/business-contracts/{businessContract}/download-signed',[BusinessContractController::class, 'downloadSigned'])->name('manager.business-contracts.download-signed');
    Route::patch('/manager/business-contracts/{businessContract}/update-body',  [BusinessContractController::class, 'updateBody'])->name('manager.business-contracts.update-body');

    // ── El Kitabı ─────────────────────────────────────────────────────────────
    Route::get('/manager/handbook', [HandbookController::class, 'manager'])->name('manager.handbook');
    Route::get('/manager/handbook/download', [HandbookController::class, 'download'])->defaults('role', 'manager')->name('manager.handbook.download');

    // ── Booking modülü: Manager pricing + tax + payment + commission cockpit ──
    Route::middleware('module:booking')->group(function (): void {
        $b = \App\Http\Controllers\Booking\ManagerBookingPricingController::class;
        Route::get('/manager/booking-pricing',                      [$b, 'index'])->name('manager.booking-pricing');
        Route::post('/manager/booking-pricing/pricing',             [$b, 'updatePricing'])->middleware('throttle:20,1')->name('manager.booking-pricing.update');
        Route::post('/manager/booking-pricing/payment',             [$b, 'updatePaymentSettings'])->middleware('throttle:20,1')->name('manager.booking-pricing.payment.update');
        Route::post('/manager/booking-pricing/tax',                 [$b, 'storeTaxRule'])->middleware('throttle:30,1')->name('manager.booking-pricing.tax.store');
        Route::post('/manager/booking-pricing/tax/{rule}/toggle',   [$b, 'toggleTaxRule'])->middleware('throttle:60,1')->name('manager.booking-pricing.tax.toggle');
        Route::delete('/manager/booking-pricing/tax/{rule}',        [$b, 'destroyTaxRule'])->middleware('throttle:30,1')->name('manager.booking-pricing.tax.destroy');
        Route::post('/manager/booking-pricing/commission',          [$b, 'storeCommissionRule'])->middleware('throttle:30,1')->name('manager.booking-pricing.commission.store');
        Route::delete('/manager/booking-pricing/commission/{rule}', [$b, 'destroyCommissionRule'])->middleware('throttle:30,1')->name('manager.booking-pricing.commission.destroy');
    });

    // ── AI Labs modülü: Knowledge base + ayarlar ─────────────────────────────
    // Senior de erişsin — parent middleware'leri ezerek ai_labs.access kullan
    Route::middleware(['module:ai_labs', 'ai_labs.access'])
        ->withoutMiddleware(['manager.role', 'manager.or.permission:student.assignment.manage'])
        ->group(function (): void {
        $src = \App\Http\Controllers\AiLabs\ManagerAiLabsSourcesController::class;
        $set = \App\Http\Controllers\AiLabs\ManagerAiLabsSettingsController::class;

        Route::get('/manager/ai-labs/sources',                    [$src, 'index'])->name('manager.ai-labs.sources');
        Route::post('/manager/ai-labs/sources',                   [$src, 'store'])->middleware('throttle:30,1')->name('manager.ai-labs.sources.store');
        Route::post('/manager/ai-labs/sources/bulk',              [$src, 'bulkUpdate'])->middleware('throttle:20,1')->name('manager.ai-labs.sources.bulk');
        Route::post('/manager/ai-labs/sources/bulk-urls',         [$src, 'storeBulkUrls'])->middleware('throttle:5,1')->name('manager.ai-labs.sources.bulk-urls');
        Route::post('/manager/ai-labs/sources/bulk-files',        [$src, 'storeBulkFiles'])->middleware('throttle:5,1')->name('manager.ai-labs.sources.bulk-files');
        Route::put('/manager/ai-labs/sources/{source}',           [$src, 'update'])->middleware('throttle:30,1')->name('manager.ai-labs.sources.update');
        Route::post('/manager/ai-labs/sources/{source}/toggle',   [$src, 'toggle'])->middleware('throttle:60,1')->name('manager.ai-labs.sources.toggle');
        Route::post('/manager/ai-labs/sources/{source}/refetch',  [$src, 'refetch'])->middleware('throttle:20,1')->name('manager.ai-labs.sources.refetch');
        Route::delete('/manager/ai-labs/sources/{source}',        [$src, 'destroy'])->middleware('throttle:30,1')->name('manager.ai-labs.sources.destroy');

        Route::get('/manager/ai-labs/settings',                   [$set, 'show'])->name('manager.ai-labs.settings');
        Route::put('/manager/ai-labs/settings',                   [$set, 'update'])->middleware('throttle:20,1')->name('manager.ai-labs.settings.update');
        Route::post('/manager/ai-labs/test-connection',           [$set, 'testConnection'])->middleware('throttle:10,1')->name('manager.ai-labs.test-connection');
        Route::post('/manager/ai-labs/sync-now',                  [$set, 'syncNow'])->middleware('throttle:5,1')->name('manager.ai-labs.sync-now');

        // Manager kendi AI asistanı (iç kullanım)
        $int = \App\Http\Controllers\AiLabs\InternalAssistantController::class;
        Route::get('/manager/ai-assistant',             fn (Request $r) => app($int)->page($r, 'manager'))->name('manager.ai-assistant');
        Route::post('/manager/ai-assistant/ask',        fn (Request $r) => app($int)->ask($r, 'manager', app(\App\Services\AiLabs\AiLabsAssistantService::class)))->middleware('throttle:15,1')->name('manager.ai-assistant.ask');
        Route::post('/manager/ai-assistant/ask-stream', fn (Request $r) => app($int)->askStream($r, 'manager'))->middleware('throttle:15,1')->name('manager.ai-assistant.ask-stream');
        Route::get('/manager/ai-assistant/history',     fn (Request $r) => app($int)->history($r, 'manager'))->name('manager.ai-assistant.history');
        Route::get('/manager/ai-assistant/remaining',   fn (Request $r) => app($int)->remaining($r, 'manager', app(\App\Services\AiLabs\AiLabsAssistantService::class)))->name('manager.ai-assistant.remaining');

        // Analytics (Phase 5)
        $analytics = \App\Http\Controllers\AiLabs\ManagerAiLabsAnalyticsController::class;
        Route::get('/manager/ai-labs/analytics',                  [$analytics, 'index'])->name('manager.ai-labs.analytics');
        Route::get('/manager/ai-labs/analytics/faq.csv',          [$analytics, 'faqCsv'])->middleware('throttle:20,1')->name('manager.ai-labs.analytics.faq-csv');
        Route::get('/manager/ai-labs/analytics/lead/{leadId}',    [$analytics, 'lead'])->where('leadId', '[0-9]+')->name('manager.ai-labs.analytics.lead');

        // External Sources (Phase 4.3) — Wikipedia + RSS + Web Search
        $ext = \App\Http\Controllers\AiLabs\ManagerAiLabsExternalController::class;
        Route::get('/manager/ai-labs/external',                 [$ext, 'index'])->name('manager.ai-labs.external');
        Route::post('/manager/ai-labs/external/wikipedia',      [$ext, 'searchWikipedia'])->middleware('throttle:30,1');
        Route::post('/manager/ai-labs/external/rss',            [$ext, 'parseRss'])->middleware('throttle:30,1');
        Route::post('/manager/ai-labs/external/web',            [$ext, 'searchWeb'])->middleware('throttle:20,1');
        Route::post('/manager/ai-labs/external/import',         [$ext, 'import'])->middleware('throttle:30,1')->name('manager.ai-labs.external.import');
        Route::post('/manager/ai-labs/external/import-bulk',    [$ext, 'importBulk'])->middleware('throttle:10,1')->name('manager.ai-labs.external.import-bulk');

        // Content Generator (Phase 4)
        $cnt = \App\Http\Controllers\AiLabs\ManagerAiLabsContentController::class;
        Route::get('/manager/ai-labs/content',                           [$cnt, 'index'])->name('manager.ai-labs.content.index');
        Route::get('/manager/ai-labs/content/new/{template}',            [$cnt, 'newForm'])->name('manager.ai-labs.content.new');
        Route::post('/manager/ai-labs/content/generate/{template}',      [$cnt, 'generate'])->middleware('throttle:20,1')->name('manager.ai-labs.content.generate');
        Route::post('/manager/ai-labs/content/suggest-keywords',         [$cnt, 'suggestKeywords'])->middleware('throttle:20,1')->name('manager.ai-labs.content.suggest-keywords');
        Route::get('/manager/ai-labs/content/{draft}/edit',              [$cnt, 'edit'])->name('manager.ai-labs.content.edit');
        Route::put('/manager/ai-labs/content/{draft}',                   [$cnt, 'update'])->middleware('throttle:60,1')->name('manager.ai-labs.content.update');
        Route::delete('/manager/ai-labs/content/{draft}',                [$cnt, 'destroy'])->middleware('throttle:30,1')->name('manager.ai-labs.content.destroy');
        Route::get('/manager/ai-labs/content/{draft}/export/pdf',        [$cnt, 'exportPdf'])->name('manager.ai-labs.content.export.pdf');
        Route::get('/manager/ai-labs/content/{draft}/export/docx',       [$cnt, 'exportDocx'])->name('manager.ai-labs.content.export.docx');
        Route::get('/manager/ai-labs/content/{draft}/export/md',         [$cnt, 'exportMarkdown'])->name('manager.ai-labs.content.export.md');

        // Admin personel AI asistanı (manager layout altında)
        Route::get('/admin-staff/ai-assistant',             fn (Request $r) => app($int)->page($r, 'admin_staff'))->name('admin-staff.ai-assistant');
        Route::post('/admin-staff/ai-assistant/ask',        fn (Request $r) => app($int)->ask($r, 'admin_staff', app(\App\Services\AiLabs\AiLabsAssistantService::class)))->middleware('throttle:15,1')->name('admin-staff.ai-assistant.ask');
        Route::post('/admin-staff/ai-assistant/ask-stream', fn (Request $r) => app($int)->askStream($r, 'admin_staff'))->middleware('throttle:15,1')->name('admin-staff.ai-assistant.ask-stream');
        Route::get('/admin-staff/ai-assistant/history',     fn (Request $r) => app($int)->history($r, 'admin_staff'))->name('admin-staff.ai-assistant.history');
        Route::get('/admin-staff/ai-assistant/remaining',   fn (Request $r) => app($int)->remaining($r, 'admin_staff', app(\App\Services\AiLabs\AiLabsAssistantService::class)))->name('admin-staff.ai-assistant.remaining');
    });
});

// ─── User Activity Intelligence + Lead Actions + Dealer Applications ────
// Geniş erişim: admin panel rolleri + marketing/sales rolleri
Route::middleware(['company.context', 'auth', 'verified', 'analytics.access'])->group(function (): void {
    $dealerApps = \App\Http\Controllers\Manager\DealerApplicationController::class;
    Route::get('/manager/dealer-applications',                [$dealerApps, 'index'])->name('manager.dealer-applications');
    Route::get('/manager/dealer-applications/{id}',          [$dealerApps, 'show'])->where('id', '[0-9]+')->name('manager.dealer-applications.show');
    Route::post('/manager/dealer-applications/{id}/status',  [$dealerApps, 'updateStatus'])->where('id', '[0-9]+')->middleware('throttle:30,1')->name('manager.dealer-applications.status');

    $userIntel = \App\Http\Controllers\Manager\ManagerUserIntelligenceController::class;
    Route::get('/manager/user-intelligence',                  [$userIntel, 'index'])->name('manager.user-intelligence');
    Route::get('/manager/user-intelligence/guest/{guestId}',  [$userIntel, 'guest'])->where('guestId', '[0-9]+')->name('manager.user-intelligence.guest');
    Route::get('/manager/user-intelligence/student/{userId}', [$userIntel, 'student'])->where('userId', '[0-9]+')->name('manager.user-intelligence.student');

    $leadAct = \App\Http\Controllers\Manager\LeadActionController::class;
    Route::get('/manager/actions/templates',                  [$leadAct, 'templates'])->name('manager.actions.templates');
    Route::get('/manager/actions/templates/{id}/render',      [$leadAct, 'renderTemplate'])->where('id', '[0-9]+')->name('manager.actions.templates.render');
    Route::post('/manager/actions/{type}/{id}/assign-senior', [$leadAct, 'assignSenior'])->where(['type' => 'guest|student', 'id' => '[0-9]+'])->middleware('throttle:30,1')->name('manager.actions.assign-senior');
    Route::post('/manager/actions/{type}/{id}/update-notes',  [$leadAct, 'updateNotes'])->where(['type' => 'guest|student', 'id' => '[0-9]+'])->middleware('throttle:30,1')->name('manager.actions.update-notes');
    Route::post('/manager/actions/{type}/{id}/log',           [$leadAct, 'logMe'])->where(['type' => 'guest|student', 'id' => '[0-9]+'])->middleware('throttle:60,1')->name('manager.actions.log');
});
