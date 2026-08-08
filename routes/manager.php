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

    // Elle aday girişi — telefonda konuşulan öğrenciyi kaydetmek için.
    // Kayıt, içinde bulunulan şirket bağlamına yazılır.
    Route::get('/manager/leads/create', [\App\Http\Controllers\Manager\ManagerLeadController::class, 'create'])
        ->name('manager.leads.create');
    Route::post('/manager/leads', [\App\Http\Controllers\Manager\ManagerLeadController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('manager.leads.store');

    // Hesabım — kullanıcı kendi giriş bilgilerini yönetir.
    Route::get('/manager/account', [\App\Http\Controllers\Manager\MyAccountController::class, 'edit'])
        ->name('manager.account.edit');
    Route::post('/manager/account', [\App\Http\Controllers\Manager\MyAccountController::class, 'update'])
        ->middleware('throttle:20,1')
        ->name('manager.account.update');

    // Aday devri — operasyonel karar, süreci yürüten firmada.
    Route::get('/manager/leads/transfer', [\App\Http\Controllers\Manager\ManagerLeadController::class, 'transferForm'])
        ->name('manager.leads.transfer.form');
    Route::post('/manager/leads/transfer', [\App\Http\Controllers\Manager\ManagerLeadController::class, 'transfer'])
        ->middleware('throttle:30,1')
        ->name('manager.leads.transfer');

    // Alt firmaların yetki tavanı — kısıtı koyması gereken taraf ağacın
    // üstündeki firma; partnerle anlaşmayı o yapıyor.
    Route::get('/manager/partners', [\App\Http\Controllers\Manager\PartnerCompanyController::class, 'index'])
        ->name('manager.partners.index');
    Route::get('/manager/partners/{company}', [\App\Http\Controllers\Manager\PartnerCompanyController::class, 'edit'])
        ->whereNumber('company')->name('manager.partners.edit');
    Route::post('/manager/partners/{company}', [\App\Http\Controllers\Manager\PartnerCompanyController::class, 'update'])
        ->whereNumber('company')->name('manager.partners.update');
    Route::get('/config/export-code/full', [ProjectExportController::class, 'full']);
    Route::get('/student-card', [StudentCardController::class, 'index']);
    Route::get('/manager/theme', [ThemeController::class, 'show'])->name('manager.theme.show');
    Route::post('/manager/theme', [ThemeController::class, 'update'])->name('manager.theme.update');
    Route::post('/manager/theme/brand', [ThemeController::class, 'updateBrand'])->name('manager.theme.brand');
    Route::post('/manager/theme/modes', [ThemeController::class, 'updateModes'])->name('manager.theme.modes');
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

    // ── Portal ↔ partner anlaşmaları ────────────────────────────────────────
    //
    // ⚠ Öğrenciyle yapılan sözleşmeyle KARIŞTIRMA. Burası iki firma arasındaki
    // iş anlaşması: çerçeve (genel) + öğrenci bazlı bedel. Dönüşümün kapısı
    // öğrenci bazlı anlaşmadır; partnerin kendi müşteri sözleşmesi değil.
    $partnerAgreement = \App\Http\Controllers\Manager\PartnerAgreementController::class;
    Route::get('/manager/partner-agreements', [$partnerAgreement, 'index'])->name('manager.partner-agreements');
    Route::post('/manager/partner-agreements', [$partnerAgreement, 'store'])
        ->middleware('throttle:20,1')->name('manager.partner-agreements.store');
    Route::post('/manager/partner-agreements/{agreement}/send', [$partnerAgreement, 'send'])
        ->whereNumber('agreement')->middleware('throttle:20,1')->name('manager.partner-agreements.send');
    Route::post('/manager/partner-agreements/{agreement}/sign', [$partnerAgreement, 'sign'])
        ->whereNumber('agreement')->middleware('throttle:20,1')->name('manager.partner-agreements.sign');
    Route::post('/manager/partner-agreements/{agreement}/terminate', [$partnerAgreement, 'terminate'])
        ->whereNumber('agreement')->middleware('throttle:20,1')->name('manager.partner-agreements.terminate');

    // Öğrenci bazlı anlaşma — partnerin portala ödeyeceği bedel.
    $studentAgreement = \App\Http\Controllers\Manager\PartnerStudentAgreementController::class;
    Route::post('/manager/guests/{guest}/partner-agreement/settle', [$studentAgreement, 'settleAtStandardFee'])
        ->whereNumber('guest')->middleware('throttle:20,1')->name('manager.partner-agreement.settle');
    Route::post('/manager/guests/{guest}/partner-agreement/propose', [$studentAgreement, 'propose'])
        ->whereNumber('guest')->middleware('throttle:20,1')->name('manager.partner-agreement.propose');
    Route::post('/manager/partner-agreement/{studentAgreement}/accept', [$studentAgreement, 'accept'])
        ->whereNumber('studentAgreement')->middleware('throttle:20,1')->name('manager.partner-agreement.accept');
    Route::post('/manager/partner-agreement/{studentAgreement}/reject', [$studentAgreement, 'reject'])
        ->whereNumber('studentAgreement')->middleware('throttle:20,1')->name('manager.partner-agreement.reject');

    // Partnerin ÖĞRENCİYLE yaptığı sözleşme — isteğe bağlı kayıt, dönüşümü
    // kilitlemez. Ve manuel dönüşüm.
    $partnerContract = \App\Http\Controllers\Manager\PartnerContractController::class;
    Route::post('/manager/guests/{guest}/partner-contract', [$partnerContract, 'close'])
        ->whereNumber('guest')->middleware('throttle:20,1')->name('manager.partner-contract.close');
    Route::post('/manager/guests/{guest}/partner-convert', [$partnerContract, 'convert'])
        ->whereNumber('guest')->middleware('throttle:10,1')->name('manager.partner-contract.convert');

    // Sozlesme tutari — finansin saydigi TEK rakam. Paket fiyati baslangic
    // degeri; pazarlikla degisir, SABITLENINCE finansa girer.
    $contractAmount = \App\Http\Controllers\Manager\ContractAmountController::class;
    Route::post('/manager/guests/{guest}/contract-amount',        [$contractAmount, 'store'])->whereNumber('guest')->name('manager.guests.contract-amount');
    Route::post('/manager/guests/{guest}/contract-amount/unlock', [$contractAmount, 'unlock'])->whereNumber('guest')->name('manager.guests.contract-amount.unlock');

    // ─── Lead Pipeline Oversight (gözetim — operasyonel iş Senior'da) ────────
    $leadPipeline = \App\Http\Controllers\Manager\LeadPipelineController::class;
    Route::get('/manager/pipeline/oversight',               [$leadPipeline, 'oversight'])->name('manager.pipeline.oversight');
    Route::get('/manager/pipeline/oversight/poll',          [$leadPipeline, 'poll'])->name('manager.pipeline.oversight.poll');
    // Eski /kanban URL'i oversight'a yönlendir (sidebar/bookmark uyumluluğu)
    Route::redirect('/manager/pipeline/kanban', '/manager/pipeline/oversight', 301);
    Route::get('/manager/students',                         [ManagerPortalController::class, 'students'])->name('manager.students');
    Route::get('/manager/students/export-csv',              [ManagerPortalController::class, 'studentsExportCsv'])->name('manager.students.export-csv');
    Route::get('/manager/students/{studentId}',             [ManagerPortalController::class, 'studentShow'])->name('manager.students.show');
    Route::patch('/manager/students/{studentId}/update',    [ManagerPortalController::class, 'studentUpdateAssignment'])->name('manager.students.update');
    Route::get('/manager/students/{studentId}/institution-documents', [ManagerPortalController::class, 'studentInstitutionDocs'])->name('manager.students.institution-docs');
    Route::get('/manager/students/{studentId}/university-applications', [ManagerPortalController::class, 'studentUniversityApplications'])->name('manager.students.university-applications');

    // ─── Firma kullanıcıları (sade) ─────────────────────────────────────────
    // Bir firmada tek kişi olması gerçekçi değil. Tam personel ekranı partnere
    // ağır; burası listeleme + ekleme + şifre sıfırlama + pasife alma.
    $partnerStaff = \App\Http\Controllers\Manager\PartnerStaffController::class;
    Route::get('/manager/users',                       [$partnerStaff, 'index'])->name('manager.partner-staff.index');
    Route::post('/manager/users',                      [$partnerStaff, 'store'])->name('manager.partner-staff.store');
    Route::post('/manager/users/{userId}/reset',       [$partnerStaff, 'resetPassword'])->whereNumber('userId')->name('manager.partner-staff.reset');
    Route::post('/manager/users/{userId}/toggle',      [$partnerStaff, 'toggle'])->whereNumber('userId')->name('manager.partner-staff.toggle');

    // 2FA sıfırlama — telefon değişiminde tek çıkış yolu (bkz. MyAccountController).
    Route::post('/manager/account/2fa/reset', [\App\Http\Controllers\Manager\MyAccountController::class, 'resetTwoFactor'])
        ->name('manager.account.2fa.reset');

    // ─── Hizmet kataloğu (firma kendi paketini ve fiyatını tanımlar) ────────
    // Firma kendi kataloğunu tanımlamadıysa üst firmanınkini kullanır; bkz.
    // App\Support\ServiceCatalog.
    $serviceCatalog = \App\Http\Controllers\Manager\ServiceCatalogController::class;
    Route::get('/manager/services',                    [$serviceCatalog, 'index'])->name('manager.services.index');
    Route::post('/manager/services/fork',              [$serviceCatalog, 'fork'])->name('manager.services.fork');
    Route::post('/manager/services/reset',             [$serviceCatalog, 'reset'])->name('manager.services.reset');
    Route::post('/manager/services/packages',          [$serviceCatalog, 'storePackage'])->name('manager.services.packages.store');
    Route::patch('/manager/services/packages/{id}',    [$serviceCatalog, 'updatePackage'])->whereNumber('id')->name('manager.services.packages.update');
    Route::delete('/manager/services/packages/{id}',   [$serviceCatalog, 'destroyPackage'])->whereNumber('id')->name('manager.services.packages.destroy');
    Route::post('/manager/services/extras',            [$serviceCatalog, 'storeExtra'])->name('manager.services.extras.store');
    Route::patch('/manager/services/extras/{id}',      [$serviceCatalog, 'updateExtra'])->whereNumber('id')->name('manager.services.extras.update');
    Route::delete('/manager/services/extras/{id}',     [$serviceCatalog, 'destroyExtra'])->whereNumber('id')->name('manager.services.extras.destroy');

    // ─── Süreç Bilgisi (partner için salt okunur) ────────────────────────────
    // Operasyonu üst firma yürütüyor; partner burada yalnızca izler. Bilerek
    // sadece GET: bu ekrandan süreci ilerletecek hiçbir yol yok.
    $partnerProcess = \App\Http\Controllers\Manager\PartnerProcessInfoController::class;
    Route::get('/manager/process-info',              [$partnerProcess, 'index'])->name('manager.process-info.index');
    Route::get('/manager/process-info/{studentId}',  [$partnerProcess, 'show'])->name('manager.process-info.show');

    // ─── Belge ekranları (partner) ──────────────────────────────────────────
    // Zorunlu belge TANIMLAMA ekranı ile analiz panosu partnerin işi değil;
    // partnere lazım olan "kimden ne istendi, ne geldi" bilgisi.
    $partnerDocs = \App\Http\Controllers\Manager\PartnerDocumentController::class;
    Route::get('/manager/partner-documents',          [$partnerDocs, 'index'])->name('manager.partner-documents.index');
    Route::get('/manager/partner-documents/requests', [$partnerDocs, 'requests'])->name('manager.partner-documents.requests');

    // ─── Bilgi/belge talep zinciri: Operasyon → Partner → Öğrenci ───────────
    // Eksigi operasyon PARTNERDEN ister, partner de kendi ogrencisinden.
    // /incoming rotasi {id} kaliplarindan ONCE gelmeli, yoksa "incoming"
    // parametre olarak yakalanir.
    $partnerReq = \App\Http\Controllers\Manager\PartnerInfoRequestController::class;
    Route::get('/manager/partner-requests',          [$partnerReq, 'outgoing'])->name('manager.partner-requests.index');
    Route::get('/manager/partner-requests/incoming', [$partnerReq, 'incoming'])->name('manager.partner-requests.incoming');
    Route::get('/manager/partner-requests/create',   [$partnerReq, 'create'])->name('manager.partner-requests.create');
    Route::post('/manager/partner-requests',         [$partnerReq, 'store'])->name('manager.partner-requests.store');
    Route::get('/manager/partner-requests/{id}',     [$partnerReq, 'show'])->whereNumber('id')->name('manager.partner-requests.show');
    Route::post('/manager/partner-requests/{id}/items/{itemId}/respond', [$partnerReq, 'respond'])->whereNumber('id')->whereNumber('itemId')->name('manager.partner-requests.respond');
    Route::post('/manager/partner-requests/{id}/items/{itemId}/forward', [$partnerReq, 'forward'])->whereNumber('id')->whereNumber('itemId')->name('manager.partner-requests.forward');

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
    Route::post('/manager/staff/{user}/toggle-doc-request',   [StaffController::class, 'toggleDocRequest'])->name('manager.staff.toggle-doc-request');
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
    // Uzmanlık etiketleri — otomatik atamada başvuru türüyle eşleşme.
    // Danışmanlar /manager/staff ekraninda YOK (orasi back-office rolleri icin).
    Route::post('/manager/seniors/{email}/specialties',     [ManagerPortalController::class, 'setSeniorSpecialties'])->name('manager.seniors.specialties')->where('email', '[^/]+');
    Route::get('/manager/dealers',                          [ManagerPortalController::class, 'dealers'])->name('manager.dealers.index');
    Route::get('/manager/dealers/{code}',                   [ManagerPortalController::class, 'dealerShow'])->name('manager.dealers.show');
    Route::post('/manager/dealers/{code}/override',         [ManagerPortalController::class, 'updateDealerOverride'])->name('manager.dealers.override');
    Route::post('/manager/dealers/{code}/mini-site',        [ManagerPortalController::class, 'updateDealerMiniSite'])->name('manager.dealers.mini-site');
    Route::post('/manager/dealers/{code}/roles',            [ManagerPortalController::class, 'updateDealerRoles'])->name('manager.dealers.roles');
    Route::get('/manager/dealer-types',                     [ManagerPortalController::class, 'dealerTypes'])->name('manager.dealer-types.index');
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

    // ─── Belge Listesi Yönetimi (CRUD) ──────────────────────────────────────
    // Kayıt formu sonrası öğrenciden istenen belgelerin tanımlandığı katalog.
    // Bir belge birden fazla top kategoride etiketlenebilir (multi-tag).
    Route::get('/manager/required-documents',                    [\App\Http\Controllers\Manager\ManagerRequiredDocumentController::class, 'index'])->name('manager.required-documents.index');
    Route::get('/manager/required-documents/create',             [\App\Http\Controllers\Manager\ManagerRequiredDocumentController::class, 'create'])->name('manager.required-documents.create');
    Route::post('/manager/required-documents',                   [\App\Http\Controllers\Manager\ManagerRequiredDocumentController::class, 'store'])->name('manager.required-documents.store');
    Route::get('/manager/required-documents/{document}/edit',    [\App\Http\Controllers\Manager\ManagerRequiredDocumentController::class, 'edit'])->name('manager.required-documents.edit');
    Route::put('/manager/required-documents/{document}',         [\App\Http\Controllers\Manager\ManagerRequiredDocumentController::class, 'update'])->name('manager.required-documents.update');
    Route::delete('/manager/required-documents/{document}',      [\App\Http\Controllers\Manager\ManagerRequiredDocumentController::class, 'destroy'])->name('manager.required-documents.destroy');

    // ─── Sayfa Görünürlüğü Kontrol Paneli (Premium: page_visibility) ────────
    Route::get('/manager/page-visibility',  [\App\Http\Controllers\Manager\ManagerPageVisibilityController::class, 'index'])->name('manager.page-visibility.index');
    Route::post('/manager/page-visibility', [\App\Http\Controllers\Manager\ManagerPageVisibilityController::class, 'update'])->name('manager.page-visibility.update');
    // AJAX toggle (her checkbox change'inde anlik save) + bulk preset
    Route::post('/manager/page-visibility/toggle',   [\App\Http\Controllers\Manager\ManagerPageVisibilityController::class, 'toggle'])->name('manager.page-visibility.toggle');
    Route::post('/manager/page-visibility/bulk-set', [\App\Http\Controllers\Manager\ManagerPageVisibilityController::class, 'bulkSet'])->name('manager.page-visibility.bulk-set');

    // ─── Belge Talep Linki (Premium: doc_request mod.) ──────────────────────
    // Manager aday öğrenciden belirli bir belgeyi tek-kullanımlık link ile ister.
    // Aday linke tıklar, telefonla fotoğraf çeker / dosya yükler. Login gerekmez.
    Route::get('/manager/guests/{guest}/document-tokens',                [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'index'])->name('manager.guest.document-tokens.index');
    Route::post('/manager/guests/{guest}/document-tokens',               [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'store'])->name('manager.guest.document-tokens.store');
    Route::delete('/manager/guests/{guest}/document-tokens/{token}',     [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'destroy'])->name('manager.guest.document-tokens.destroy');

    // Aynı endpoint mantığı, ama öğrenci (StudentAssignment.student_id) için
    Route::get('/manager/students/{studentId}/document-tokens',          [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'indexForStudent'])->name('manager.student.document-tokens.index');
    Route::post('/manager/students/{studentId}/document-tokens',         [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'storeForStudent'])->name('manager.student.document-tokens.store');
    Route::delete('/manager/students/{studentId}/document-tokens/{token}', [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'destroyForStudent'])->name('manager.student.document-tokens.destroy');

    // HR onboarding — Çalışan/personel (User) için
    Route::get('/manager/hr/persons/{user}/document-tokens',             [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'indexForUser'])->name('manager.user.document-tokens.index');
    Route::post('/manager/hr/persons/{user}/document-tokens',            [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'storeForUser'])->name('manager.user.document-tokens.store');
    Route::delete('/manager/hr/persons/{user}/document-tokens/{token}',  [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'destroyForUser'])->name('manager.user.document-tokens.destroy');

    // Dealer KYC — Bayi için (code ile lookup)
    Route::get('/manager/dealers/{code}/document-tokens',                [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'indexForDealer'])->name('manager.dealer.document-tokens.index');
    Route::post('/manager/dealers/{code}/document-tokens',               [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'storeForDealer'])->name('manager.dealer.document-tokens.store');
    Route::delete('/manager/dealers/{code}/document-tokens/{token}',     [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'destroyForDealer'])->name('manager.dealer.document-tokens.destroy');

    // Contract — İmzalı PDF geri yükleme (login'siz public link)
    Route::get('/manager/business-contracts/{businessContract}/document-tokens',          [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'indexForContract'])->name('manager.contract.document-tokens.index');
    Route::post('/manager/business-contracts/{businessContract}/document-tokens',         [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'storeForContract'])->name('manager.contract.document-tokens.store');
    Route::delete('/manager/business-contracts/{businessContract}/document-tokens/{token}', [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'destroyForContract'])->name('manager.contract.document-tokens.destroy');

    // Ticket detay + D3 doc_request entegrasyonu (TARGET_TICKET)
    Route::get('/manager/tickets/{ticket}',                                  [\App\Http\Controllers\Manager\ManagerTicketController::class, 'show'])->name('manager.tickets.show');
    Route::get('/manager/tickets/{ticket}/document-tokens',                  [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'indexForTicket'])->name('manager.ticket.document-tokens.index');
    Route::post('/manager/tickets/{ticket}/document-tokens',                 [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'storeForTicket'])->name('manager.ticket.document-tokens.store');
    Route::delete('/manager/tickets/{ticket}/document-tokens/{token}',       [\App\Http\Controllers\Manager\ManagerDocumentRequestController::class, 'destroyForTicket'])->name('manager.ticket.document-tokens.destroy');

    // ─── Belge Talep Analytics (doc_request KPI + funnel + hatırlatma etkisi) ────
    // KPI + funnel + kategori bazlı performans + aylık trend + CSV export.
    // Sadece doc_request modülü aktif olanlar erişebilir (controller'da assert).
    Route::middleware('module:doc_request')->group(function (): void {
        $dra = \App\Http\Controllers\Manager\DocumentRequestAnalyticsController::class;
        Route::get('/manager/document-requests/analytics',        [$dra, 'index'])->name('manager.document-requests.analytics');
        Route::get('/manager/document-requests/analytics/export', [$dra, 'export'])->middleware('throttle:10,1')->name('manager.document-requests.analytics.export');

        // ── Toplu Belge Talep (D10) ───────────────────────────────────────────
        // Birden fazla aday/öğrenciye aynı kategoriden tek seferde belge talep
        // linki gönder. Email + WhatsApp ilk bildirimi anında tetiklenir, batch
        // sonucu CSV export'a hazır halde session'da kalır.
        $bdr = \App\Http\Controllers\Manager\BulkDocumentRequestController::class;
        Route::get('/manager/document-requests/bulk',                 [$bdr, 'index'])->name('manager.doc-request.bulk.index');
        Route::post('/manager/document-requests/bulk/preview',        [$bdr, 'preview'])->middleware('throttle:30,1')->name('manager.doc-request.bulk.preview');
        Route::post('/manager/document-requests/bulk/store',          [$bdr, 'store'])->middleware('throttle:10,1')->name('manager.doc-request.bulk.store');
        Route::get('/manager/document-requests/bulk/export/{batch}',  [$bdr, 'export'])->where('batch', '[a-zA-Z0-9-]+')->name('manager.doc-request.bulk.export');

        // ── Belge OCR / Gemini Vision extraction (D8) ─────────────────────────
        // Yüklenen pasaport/diploma/transkript belgelerinden alan çıkarımı.
        // Yükleme sırasında otomatik tetiklenir (PublicDocumentUploadController),
        // bu endpoint'ler inline preview + manuel re-trigger için.
        $docOcr = \App\Http\Controllers\Manager\DocumentOcrController::class;
        Route::get('/manager/documents/{id}/extraction',  [$docOcr, 'show'])
            ->whereNumber('id')->name('manager.documents.extraction.show');
        Route::post('/manager/documents/{id}/re-extract', [$docOcr, 'reExtract'])
            ->whereNumber('id')->middleware('throttle:30,1')->name('manager.documents.re-extract');
    });

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

    // ─── ROPA — Verarbeitungsverzeichnis (DSGVO Art. 30) ────────────────────
    // Platform-altyapı/uyumluluk → sadece Platform Owner + System Admin (VIP/manager BLOKLI).
    $ropa = \App\Http\Controllers\Manager\ManagerProcessingActivityController::class;
    Route::middleware('system.access')->group(function () use ($ropa): void {
        Route::get('/manager/ropa',                       [$ropa, 'index'])->name('manager.ropa.index');
        Route::get('/manager/ropa/export-csv',            [$ropa, 'exportCsv'])->name('manager.ropa.export-csv');
        Route::get('/manager/ropa/create',                [$ropa, 'create'])->name('manager.ropa.create');
        Route::post('/manager/ropa',                      [$ropa, 'store'])->name('manager.ropa.store');
        Route::get('/manager/ropa/{activity}/edit',       [$ropa, 'edit'])->name('manager.ropa.edit');
        Route::put('/manager/ropa/{activity}',            [$ropa, 'update'])->name('manager.ropa.update');
        Route::delete('/manager/ropa/{activity}',         [$ropa, 'destroy'])->name('manager.ropa.destroy');
    });

    // ─── AVV Registry — Auftragsverarbeitungsverträge (DSGVO Art. 28) ───────
    // Platform-altyapı/uyumluluk → sadece Platform Owner + System Admin (VIP/manager BLOKLI).
    $avv = \App\Http\Controllers\Manager\ManagerAvvRegistryController::class;
    Route::middleware('system.access')->group(function () use ($avv): void {
        Route::get('/manager/avv',                        [$avv, 'index'])->name('manager.avv.index');
        Route::get('/manager/avv/create',                 [$avv, 'create'])->name('manager.avv.create');
        Route::post('/manager/avv',                       [$avv, 'store'])->name('manager.avv.store');
        Route::get('/manager/avv/{avv}/edit',             [$avv, 'edit'])->name('manager.avv.edit');
        Route::put('/manager/avv/{avv}',                  [$avv, 'update'])->name('manager.avv.update');
        Route::delete('/manager/avv/{avv}',               [$avv, 'destroy'])->name('manager.avv.destroy');
        Route::get('/manager/avv/{avv}/download',         [$avv, 'downloadPdf'])->name('manager.avv.download');
    });

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

    // ─── Webhook Logları → sadece Platform Owner + System Admin (VIP/manager BLOKLI) ──
    Route::middleware('system.access')->group(function (): void {
        Route::get('/manager/webhooks',                  [WebhookController::class, 'index'])->name('manager.webhooks.index');
        Route::post('/manager/webhooks/{log}/retry',     [WebhookController::class, 'retry'])->name('manager.webhooks.retry');
        Route::delete('/manager/webhooks/{log}',         [WebhookController::class, 'destroy'])->name('manager.webhooks.destroy');
    });

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
    // GDPR Uyumluluk → sadece Platform Owner + System Admin (VIP/manager BLOKLI)
    Route::middleware('system.access')->group(function (): void {
        Route::get('/manager/gdpr-dashboard', [ManagerAnalyticsController::class, 'gdprDashboard'])->name('manager.gdpr-dashboard');
        Route::post('/manager/gdpr-dashboard/policy', [ManagerAnalyticsController::class, 'gdprPolicySave'])->name('manager.gdpr.policy.save');
    });

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

    // ── Guest Payment Reminders (sözleşme onaylı + ödeme bekleyen) ────────────
    // Manager: hatırlatma şimdi gönder / pause-resume / manuel teyit / L5 iptal uyarısı
    // İleride finans admin rolüne taşınacak (bkz. memory/project_finance_admin_role_pending.md)
    {
        $gpr = \App\Http\Controllers\Manager\GuestPaymentReminderController::class;
        Route::get('/manager/payments/reminders',                       [$gpr, 'index'])->name('manager.payments.reminders.index');
        Route::post('/manager/payments/reminders/{guest}/send',         [$gpr, 'sendReminder'])->middleware('throttle:30,1')->name('manager.payments.reminders.send');
        Route::post('/manager/payments/reminders/{guest}/pause',        [$gpr, 'pause'])->middleware('throttle:30,1')->name('manager.payments.reminders.pause');
        Route::post('/manager/payments/reminders/{guest}/resume',       [$gpr, 'resume'])->middleware('throttle:30,1')->name('manager.payments.reminders.resume');
        Route::post('/manager/payments/reminders/{guest}/mark-received',[$gpr, 'markReceived'])->middleware('throttle:30,1')->name('manager.payments.reminders.mark-received');
    }

    // ── Bayi Komisyon Kademeleri (Lead Gen 5 + Freelance 3 tier yönetimi) ────
    {
        $dt = \App\Http\Controllers\Manager\DealerCommissionTierController::class;
        Route::get('/manager/dealer-tiers',                          [$dt, 'index'])->name('manager.dealer-tiers.index');
        Route::get('/manager/dealer-tiers/create',                   [$dt, 'create'])->name('manager.dealer-tiers.create');
        Route::post('/manager/dealer-tiers',                         [$dt, 'store'])->middleware('throttle:30,1')->name('manager.dealer-tiers.store');
        Route::get('/manager/dealer-tiers/{dealerCommissionTier}/edit', [$dt, 'edit'])->name('manager.dealer-tiers.edit');
        Route::put('/manager/dealer-tiers/{dealerCommissionTier}',   [$dt, 'update'])->middleware('throttle:30,1')->name('manager.dealer-tiers.update');
        Route::post('/manager/dealer-tiers/{dealerCommissionTier}/toggle', [$dt, 'toggleActive'])->middleware('throttle:30,1')->name('manager.dealer-tiers.toggle');
    }

    // ── İndirim Kodları (extra feature — module:discount_codes) ──────────────
    Route::middleware('module:discount_codes')->group(function (): void {
        $dc = \App\Http\Controllers\Manager\DiscountCodeController::class;
        Route::get('/manager/discount-codes',                    [$dc, 'index'])->name('manager.discount-codes.index');
        Route::get('/manager/discount-codes/redemptions',        [$dc, 'redemptions'])->name('manager.discount-codes.redemptions');
        Route::post('/manager/discount-codes/ai-suggest',        [$dc, 'aiSuggest'])->middleware('throttle:20,1')->name('manager.discount-codes.ai-suggest');
        Route::get('/manager/discount-codes/preview',            [$dc, 'preview'])->middleware('throttle:120,1')->name('manager.discount-codes.preview');
        Route::get('/manager/discount-codes/create',             [$dc, 'create'])->name('manager.discount-codes.create');
        Route::post('/manager/discount-codes',                   [$dc, 'store'])->middleware('throttle:30,1')->name('manager.discount-codes.store');
        Route::get('/manager/discount-codes/{discountCode}/edit',[$dc, 'edit'])->name('manager.discount-codes.edit');
        Route::put('/manager/discount-codes/{discountCode}',     [$dc, 'update'])->middleware('throttle:30,1')->name('manager.discount-codes.update');
        Route::post('/manager/discount-codes/{discountCode}/toggle', [$dc, 'toggleActive'])->middleware('throttle:30,1')->name('manager.discount-codes.toggle');
    });

    // ── Silence Monitor (extra feature — module:silence_checkin) ─────────────
    Route::middleware('module:silence_checkin')->group(function (): void {
        $sm = \App\Http\Controllers\Manager\SilenceMonitorController::class;
        Route::get('/manager/silence-monitor',                                  [$sm, 'index'])->name('manager.silence-monitor.index');
        Route::post('/manager/silence-monitor/{type}/{id}/trigger',             [$sm, 'trigger'])->whereIn('type', ['guest', 'student'])->middleware('throttle:30,1')->name('manager.silence-monitor.trigger');
        Route::post('/manager/silence-monitor/{type}/{id}/override',            [$sm, 'setOverride'])->whereIn('type', ['guest', 'student'])->middleware('throttle:30,1')->name('manager.silence-monitor.override');
        Route::post('/manager/silence-monitor/{type}/{id}/pause',               [$sm, 'pause'])->whereIn('type', ['guest', 'student'])->middleware('throttle:30,1')->name('manager.silence-monitor.pause');
        Route::post('/manager/silence-monitor/{type}/{id}/resume',              [$sm, 'resume'])->whereIn('type', ['guest', 'student'])->middleware('throttle:30,1')->name('manager.silence-monitor.resume');
        Route::post('/manager/silence-monitor/company-overrides',               [$sm, 'updateCompanyOverrides'])->middleware('throttle:10,1')->name('manager.silence-monitor.company-overrides');
    });

    // ── Program Kataloğu Değişiklik Takibi (canonical layer + change detection) ──
    {
        $pcc = \App\Http\Controllers\Manager\ProgramCatalogChangesController::class;
        Route::get('/manager/program-catalog/changes',                  [$pcc, 'index'])->name('manager.program-catalog.changes');
        Route::post('/manager/program-catalog/changes/{log}/review',    [$pcc, 'review'])->middleware('throttle:60,1')->name('manager.program-catalog.changes.review');
    }

    // ── UniMatch Wizard Funnel Analytics ──
    {
        $umf = \App\Http\Controllers\Manager\UniMatchFunnelController::class;
        Route::get('/manager/unimatch-funnel',          [$umf, 'index'])->name('manager.unimatch-funnel.index');
        Route::get('/manager/unimatch-funnel/leads.csv', [$umf, 'exportLeadsCsv'])->name('manager.unimatch-funnel.leads-csv');
    }

    // ── Hızlı Yönetim: Yeni senior, rol ver, lead sil ──
    {
        $qac = \App\Http\Controllers\Manager\QuickAdminController::class;
        Route::post('/manager/quick-admin/senior',          [$qac, 'storeSenior'])->middleware('throttle:30,1')->name('manager.quick-admin.senior.store');
        Route::get('/manager/quick-admin/user-by-email',    [$qac, 'findUserByEmail'])->middleware('throttle:60,1')->name('manager.quick-admin.user.find');
        Route::post('/manager/quick-admin/assign-role',     [$qac, 'assignRole'])->middleware('throttle:30,1')->name('manager.quick-admin.role.assign');
        Route::delete('/manager/quick-admin/guest/{id}',    [$qac, 'deleteGuest'])->middleware('throttle:30,1')->name('manager.quick-admin.guest.delete');
        Route::delete('/manager/quick-admin/student/{id}',  [$qac, 'deleteStudent'])->middleware('throttle:30,1')->name('manager.quick-admin.student.delete');
        Route::delete('/manager/quick-admin/senior/{id}',   [$qac, 'deleteSenior'])->middleware('throttle:30,1')->name('manager.quick-admin.senior.delete');
        Route::delete('/manager/quick-admin/dealer/{id}',   [$qac, 'deleteDealer'])->middleware('throttle:30,1')->name('manager.quick-admin.dealer.delete');
        Route::post('/manager/quick-admin/reset-password',  [$qac, 'resetUserPassword'])->middleware('throttle:20,1')->name('manager.quick-admin.password.reset');
    }

    // ── Partner API Yönetimi ──
    {
        $apc = \App\Http\Controllers\Manager\ApiPartnerController::class;
        Route::get('/manager/api-partners',                       [$apc, 'index'])->name('manager.api-partners.index');
        Route::get('/manager/api-partners/create',                [$apc, 'create'])->name('manager.api-partners.create');
        Route::post('/manager/api-partners',                      [$apc, 'store'])->middleware('throttle:10,1')->name('manager.api-partners.store');
        Route::get('/manager/api-partners/{apiPartner}',          [$apc, 'show'])->name('manager.api-partners.show');
        Route::put('/manager/api-partners/{apiPartner}',          [$apc, 'update'])->name('manager.api-partners.update');
        Route::post('/manager/api-partners/{apiPartner}/rotate',  [$apc, 'rotate'])->middleware('throttle:10,1')->name('manager.api-partners.rotate');
        Route::post('/manager/api-partners/{apiPartner}/toggle',  [$apc, 'toggle'])->name('manager.api-partners.toggle');
        Route::delete('/manager/api-partners/{apiPartner}',       [$apc, 'destroy'])->name('manager.api-partners.destroy');
    }

    // ── Üniversite görsel + video yönetimi ──
    {
        $uc = \App\Http\Controllers\Manager\UniversityController::class;
        Route::get('/manager/universities',                                [$uc, 'index'])->name('manager.universities.index');
        Route::post('/manager/universities/{university}/image',            [$uc, 'uploadImage'])->middleware('throttle:30,1')->name('manager.universities.image.upload');
        Route::delete('/manager/universities/{university}/image',          [$uc, 'deleteImage'])->middleware('throttle:30,1')->name('manager.universities.image.delete');
        Route::post('/manager/universities/{university}/video',            [$uc, 'updateVideo'])->middleware('throttle:30,1')->name('manager.universities.video.update');
        Route::delete('/manager/universities/{university}/video',          [$uc, 'deleteVideo'])->middleware('throttle:30,1')->name('manager.universities.video.delete');
    }

    // ── SaaS modül toggle (company × module matrix) — PLATFORM OWNER ONLY ──
    // Customer Manager BLOKLANIR. Sebep: modul toggle = premium feature paywall.
    // Customer kendi tier'ini upgrade edememeli; sadece Mentorde Platform sahibi
    // (veya billing yönetimi yapan Stripe webhook'u) modüllerini değiştirebilir.
    {
        $cm = \App\Http\Controllers\Manager\CompanyModulesController::class;
        Route::middleware('platform.owner')->group(function () use ($cm) {
            Route::get('/manager/companies/modules',                          [$cm, 'index'])->name('manager.companies.modules');
            Route::post('/manager/companies/{company}/modules',               [$cm, 'update'])->middleware('throttle:30,1')->name('manager.companies.modules.update');
        });
    }

    // ── Customer Manager için "Planim" (read-only modul listesi + tier upgrade talep) ──
    // Customer Manager kendi tier'ini ve aktif modullerini gorur. Modul toggle YAPAMAZ —
    // sadece daha yuksek bir tier'a yukseltme talebinde bulunabilir. Talep Platform
    // Owner'a in_app notification olarak iletilir.
    {
        $mp = \App\Http\Controllers\Manager\MyPlanController::class;
        Route::get('/manager/my-plan',                  [$mp, 'index'])->name('manager.my-plan');
        Route::post('/manager/my-plan/upgrade-request', [$mp, 'requestUpgrade'])->middleware('throttle:5,1')->name('manager.my-plan.upgrade-request');
    }

    // ── Application Guides — student altında (Uni-Assist + Vize) ──────────────
    // Uni-Assist sözleşme+ödeme sonrası (öğrenci aşamasında) yapılır.
    // Guest URL legacy backward-compat için tutuldu.
    {
        $uag = \App\Http\Controllers\Manager\UniAssistGuideController::class;
        // Primary route — öğrenci sayfası altında
        Route::get('/manager/students/{studentId}/uni-assist-rehber',          [$uag, 'showForStudent'])->name('manager.student.uni-assist-guide.show');
        // Legacy guest URL (eski bookmark'lar)
        Route::get('/manager/guests/{guest}/uni-assist-rehber',                [$uag, 'show'])->name('manager.uni-assist-guide.show');
        // POST endpoint'ler underlying guest_application'a yazar (data tek yerde)
        Route::post('/manager/guests/{guest}/uni-assist-rehber/save-meta',     [$uag, 'saveMeta'])->middleware('throttle:30,1')->name('manager.uni-assist-guide.save-meta');
        Route::post('/manager/guests/{guest}/uni-assist-rehber/request-missing', [$uag, 'requestMissingFields'])->middleware('throttle:10,1')->name('manager.uni-assist-guide.request-missing');
    }

    // ── Vize Rehberi (VIDEX — Auswärtiges Amt Auslandsportal 7 bölüm) ─────────
    {
        $vag = \App\Http\Controllers\Manager\VisaGuideController::class;
        Route::get('/manager/students/{studentId}/vize-rehber',                [$vag, 'showForStudent'])->name('manager.student.visa-guide.show');
        Route::get('/manager/guests/{guest}/vize-rehber',                      [$vag, 'show'])->name('manager.visa-guide.show');
        Route::post('/manager/guests/{guest}/vize-rehber/save-meta',           [$vag, 'saveMeta'])->middleware('throttle:30,1')->name('manager.visa-guide.save-meta');
        Route::post('/manager/guests/{guest}/vize-rehber/request-missing',     [$vag, 'requestMissingFields'])->middleware('throttle:10,1')->name('manager.visa-guide.request-missing');
    }

    // ── Generic Application Guides (APS / Anmeldung / Sperrkonto / VPD) ──────
    // Config-driven: config/application_guides.php → her slug için tek view render.
    {
        $ag = \App\Http\Controllers\Manager\ApplicationGuideController::class;
        Route::get('/manager/guests/{guest}/rehber/{slug}',              [$ag, 'showForGuest'])
            ->where('slug', '[a-z_-]+')->name('manager.application-guide.show');
        Route::get('/manager/students/{studentId}/rehber/{slug}',        [$ag, 'showForStudent'])
            ->where('slug', '[a-z_-]+')->name('manager.student.application-guide.show');
    }

    // ── Public Landing Envanter (sisteme bağlı public sayfa registry'si) ─────
    Route::get('/manager/landing-inventory', [\App\Http\Controllers\Manager\LandingInventoryController::class, 'index'])
        ->name('manager.landing-inventory.index');

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

        // ── Marketplace Phase 6: Komisyon Kuralları (matrix UI) ──
        $cr = \App\Http\Controllers\Manager\CommissionRulesController::class;
        Route::get('/manager/commission-rules',                  [$cr, 'index'])->name('manager.commission-rules.index');
        Route::post('/manager/commission-rules',                 [$cr, 'store'])->middleware('throttle:30,1')->name('manager.commission-rules.store');
        Route::put('/manager/commission-rules/{commissionRule}', [$cr, 'update'])->middleware('throttle:30,1')->name('manager.commission-rules.update');
        Route::delete('/manager/commission-rules/{commissionRule}', [$cr, 'destroy'])->middleware('throttle:30,1')->name('manager.commission-rules.destroy');

        // ── Marketplace Phase 6: Şirket Ödeme Ayarları ──
        $ps = \App\Http\Controllers\Manager\PayoutSettingsController::class;
        Route::get('/manager/payout-settings', [$ps, 'index'])->name('manager.payout-settings.index');
        Route::put('/manager/payout-settings', [$ps, 'update'])->middleware('throttle:20,1')->name('manager.payout-settings.update');

        // ── Marketplace Phase 6: Payouts (liste + detay + retry) ──
        $po = \App\Http\Controllers\Manager\PayoutsController::class;
        Route::get('/manager/payouts',                    [$po, 'index'])->name('manager.payouts.index');
        Route::get('/manager/payouts/{id}',               [$po, 'show'])->where('id', '[0-9]+')->name('manager.payouts.show');
        Route::post('/manager/payouts/{id}/retry',        [$po, 'retry'])->where('id', '[0-9]+')->middleware('throttle:10,1')->name('manager.payouts.retry');

        // Marketplace Phase 7 — Yorum moderasyonu cockpit
        $rm = \App\Http\Controllers\Manager\ReviewModerationController::class;
        Route::get('/manager/reviews',                          [$rm, 'index'])->name('manager.reviews.index');
        Route::post('/manager/reviews/{review}/approve',        [$rm, 'approve'])->middleware('throttle:60,1')->name('manager.reviews.approve');
        Route::post('/manager/reviews/{review}/reject',         [$rm, 'reject'])->middleware('throttle:60,1')->name('manager.reviews.reject');
        Route::post('/manager/reviews/{review}/toggle-public',  [$rm, 'togglePublic'])->middleware('throttle:60,1')->name('manager.reviews.toggle');
        Route::delete('/manager/reviews/{review}',              [$rm, 'destroy'])->middleware('throttle:30,1')->name('manager.reviews.destroy');
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
    Route::get('/manager/dealer-applications',                [$dealerApps, 'index'])->name('manager.dealer-applications.index');
    Route::get('/manager/dealer-applications/{id}',          [$dealerApps, 'show'])->where('id', '[0-9]+')->name('manager.dealer-applications.show');
    Route::post('/manager/dealer-applications/{id}/status',  [$dealerApps, 'updateStatus'])->where('id', '[0-9]+')->middleware('throttle:30,1')->name('manager.dealer-applications.status');
    Route::post('/manager/dealer-applications/{id}/roles',   [$dealerApps, 'updateRoles'])->where('id', '[0-9]+')->middleware('throttle:30,1')->name('manager.dealer-applications.roles');

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

// /program-search — internal kullanıcılar için wizard bypass program arama.
// Manager grubu dışında — senior/mentor/admin_staff/operations_* de erişebilir.
// Auth zorunlu, rol kontrolü controller içinde (ALLOWED_ROLES).
Route::middleware(['company.context', 'auth'])->group(function (): void {
    Route::get('/program-search', [\App\Http\Controllers\UniMatch\ProgramSearchController::class, 'index'])
        ->middleware('throttle:120,1')
        ->name('program-search');
});
