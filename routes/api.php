<?php

use App\Http\Controllers\Api\AccountVaultController;
use App\Http\Controllers\Api\ApplyFormSettingController;
use App\Http\Controllers\Api\BatchOperationController;
use App\Http\Controllers\Api\CompanyContextController;
use App\Http\Controllers\Api\DealerTypeController;
use App\Http\Controllers\Api\DealerController;
use App\Http\Controllers\Api\DealerRevenueMilestoneController;
use App\Http\Controllers\Api\DealerStudentRevenueController;
use App\Http\Controllers\Api\DocumentCategoryController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EntityCatalogController;
use App\Http\Controllers\Api\EscalationRuleController;
use App\Http\Controllers\Api\FieldRuleApprovalController;
use App\Http\Controllers\Api\FieldRuleController;
use App\Http\Controllers\Api\IntegrationConfigController;
use App\Http\Controllers\Api\InternalNoteController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\TaskTemplateController;
use App\Http\Controllers\Api\LeadSourceOptionController;
use App\Http\Controllers\Api\GuestApplicationAdminController;
use App\Http\Controllers\Api\GuestRegistrationFieldController;
use App\Http\Controllers\Api\GuestRequiredDocumentController;
use App\Http\Controllers\Api\MarketingAnalyticsController;
use App\Http\Controllers\Api\MarketingCampaignController;
use App\Http\Controllers\Api\NotificationDispatchController;
use App\Http\Controllers\Api\MessageTemplateController;
use App\Http\Controllers\Api\ProcessDefinitionController;
use App\Http\Controllers\Api\ProcessOutcomeController;
use App\Http\Controllers\Api\PortalUserController;
use App\Http\Controllers\Api\RevenueMilestoneController;
use App\Http\Controllers\Api\RoleCatalogController;
use App\Http\Controllers\Api\RbacController;
use App\Http\Controllers\Api\StudentRevenueController;
use App\Http\Controllers\Api\StudentRiskScoreController;
use App\Http\Controllers\Api\SuggestionController;
use App\Http\Controllers\Api\SeniorManagementController;
use App\Http\Controllers\Api\StudentAssignmentController;
use App\Http\Controllers\Api\StudentCardController as StudentCardApiController;
use App\Http\Controllers\Api\StudentTypeController;
use App\Http\Controllers\Api\SystemHealthController;
use App\Http\Controllers\InternalMessagingController;
use App\Http\Controllers\Api\SystemEventLogController;
use App\Http\Controllers\Api\GuestOpsController;
use App\Http\Controllers\Api\ExternalProviderConnectionController;
use App\Http\Controllers\Api\SeniorAvailabilityController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\IpAccessRuleController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\GuestApplicationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/public')->middleware(['web', 'company.context', 'throttle:60,1'])->group(function (): void {
    Route::get('apply-suggestions', [GuestApplicationController::class, 'suggestions']);
    Route::get('lead-source-options', [GuestApplicationController::class, 'leadSourceOptions']);
});

Route::prefix('v1/config')->middleware(['web', 'company.context', 'auth', 'manager.role'])->group(function (): void {
    Route::get('companies', [CompanyContextController::class, 'index'])->middleware('permission:config.view');
    Route::post('companies', [CompanyContextController::class, 'store'])->middleware('permission:config.manage');
    Route::put('companies/{company}', [CompanyContextController::class, 'update'])->middleware('permission:config.manage');
    Route::post('companies/switch', [CompanyContextController::class, 'switch'])->middleware('permission:config.manage');

    Route::get('student-types', [StudentTypeController::class, 'index'])->middleware('permission:config.view');
    Route::get('role-catalog', [RoleCatalogController::class, 'index'])->middleware('permission:config.view');
    Route::get('rbac/permissions', [RbacController::class, 'permissions'])->middleware('permission:role.template.manage');
    Route::post('rbac/permissions', [RbacController::class, 'createPermission'])->middleware('permission:role.template.manage');
    Route::get('rbac/templates', [RbacController::class, 'templates'])->middleware('permission:role.template.manage');
    Route::post('rbac/templates', [RbacController::class, 'createTemplate'])->middleware('permission:role.template.manage');
    Route::put('rbac/templates/{roleTemplate}', [RbacController::class, 'updateTemplate'])->middleware('permission:role.template.manage');
    Route::post('rbac/templates/{roleTemplate}/permissions/sync', [RbacController::class, 'syncTemplatePermissions'])->middleware('permission:role.template.manage');
    Route::get('rbac/assignments', [RbacController::class, 'assignments'])->middleware('permission:role.template.manage');
    Route::post('rbac/assignments', [RbacController::class, 'assignTemplate'])->middleware('permission:role.template.manage');
    Route::post('rbac/assignments/{userRoleAssignment}/revoke', [RbacController::class, 'revokeAssignment'])->middleware('permission:role.template.manage');
    Route::get('rbac/users/{user}/effective-permissions', [RbacController::class, 'effectivePermissions'])->middleware('permission:role.template.manage');
    Route::get('rbac/permission-usage', [RbacController::class, 'permissionUsageReport'])->middleware('permission:role.template.manage');
    Route::get('suggestions', [SuggestionController::class, 'index'])->middleware('permission:config.view');
    Route::get('entity-catalog', [EntityCatalogController::class, 'index'])->middleware('permission:config.view');
    Route::post('entity-catalog/suggest', [EntityCatalogController::class, 'suggest'])->middleware('permission:config.view');
    Route::post('student-types', [StudentTypeController::class, 'store'])->middleware('permission:config.manage');
    Route::put('student-types/{studentType}', [StudentTypeController::class, 'update'])->middleware('permission:config.manage');

    Route::get('seniors', [SeniorManagementController::class, 'index'])->middleware('permission:config.manage');
    Route::post('seniors', [SeniorManagementController::class, 'store'])->middleware('permission:config.manage');
    Route::put('seniors/{user}', [SeniorManagementController::class, 'update'])->middleware('permission:config.manage');
    Route::post('seniors/{user}/reset-password', [SeniorManagementController::class, 'resetPassword'])->middleware('permission:config.manage');
    Route::post('seniors/{user}/transfer', [SeniorManagementController::class, 'transfer'])->middleware('permission:student.assignment.manage');
    Route::delete('seniors/{user}', [SeniorManagementController::class, 'destroy'])->middleware('permission:config.manage');
    Route::get('portal-users', [PortalUserController::class, 'index'])->middleware('permission:role.template.manage');
    Route::post('portal-users', [PortalUserController::class, 'store'])->middleware('permission:role.template.manage');
    Route::put('portal-users/{user}', [PortalUserController::class, 'update'])->middleware('permission:role.template.manage');
    Route::post('portal-users/{user}/reset-password', [PortalUserController::class, 'resetPassword'])->middleware('permission:role.template.manage');
    Route::delete('portal-users/{user}', [PortalUserController::class, 'destroy'])->middleware('permission:role.template.manage');

    Route::get('dealer-types', [DealerTypeController::class, 'index'])->middleware('permission:config.view');
    Route::post('dealer-types', [DealerTypeController::class, 'store'])->middleware('permission:config.manage');
    Route::put('dealer-types/{dealerType}', [DealerTypeController::class, 'update'])->middleware('permission:config.manage');
    Route::get('lead-source-options', [LeadSourceOptionController::class, 'index'])->middleware('permission:config.view');
    Route::post('lead-source-options', [LeadSourceOptionController::class, 'store'])->middleware('permission:config.manage');
    Route::put('lead-source-options/{leadSourceOption}', [LeadSourceOptionController::class, 'update'])->middleware('permission:config.manage');
    Route::get('apply-form-settings', [ApplyFormSettingController::class, 'show'])->middleware('permission:config.view');
    Route::post('apply-form-settings', [ApplyFormSettingController::class, 'update'])->middleware('permission:config.manage');
    Route::get('dealers', [DealerController::class, 'index'])->middleware('permission:config.view');
    Route::get('dealers/type-history', [DealerController::class, 'typeHistory'])->middleware('permission:config.view');
    Route::post('dealers', [DealerController::class, 'store'])->middleware('permission:config.manage');
    Route::put('dealers/{dealer}', [DealerController::class, 'update'])->middleware('permission:config.manage');
    Route::post('dealers/{dealer}/archive', [DealerController::class, 'archive'])->middleware('permission:config.manage');
    Route::post('dealers/{dealer}/unarchive', [DealerController::class, 'unarchive'])->middleware('permission:config.manage');
    Route::delete('dealers/{dealer}', [DealerController::class, 'destroy'])->middleware('permission:config.manage');

    Route::get('process-definitions', [ProcessDefinitionController::class, 'index'])->middleware('permission:config.view');
    Route::post('process-definitions', [ProcessDefinitionController::class, 'store'])->middleware('permission:config.manage');
    Route::put('process-definitions/{processDefinition}', [ProcessDefinitionController::class, 'update'])->middleware('permission:config.manage');

    Route::get('integration-configs', [IntegrationConfigController::class, 'index'])->middleware('permission:config.view');
    Route::post('integration-configs/{category}', [IntegrationConfigController::class, 'upsert'])->middleware('permission:config.manage');
    Route::post('integration-configs/{category}/test', [IntegrationConfigController::class, 'testConnection'])->middleware('permission:config.manage');

    Route::get('revenue-milestones', [RevenueMilestoneController::class, 'index'])->middleware('permission:revenue.manage');
    Route::post('revenue-milestones', [RevenueMilestoneController::class, 'store'])->middleware('permission:revenue.manage');
    Route::put('revenue-milestones/{revenueMilestone}', [RevenueMilestoneController::class, 'update'])->middleware('permission:revenue.manage');

    Route::get('dealer-revenue-milestones', [DealerRevenueMilestoneController::class, 'index'])->middleware('permission:revenue.manage');
    Route::post('dealer-revenue-milestones', [DealerRevenueMilestoneController::class, 'store'])->middleware('permission:revenue.manage');
    Route::put('dealer-revenue-milestones/{dealerRevenueMilestone}', [DealerRevenueMilestoneController::class, 'update'])->middleware('permission:revenue.manage');

    Route::get('student-revenues/{studentId}', [StudentRevenueController::class, 'show'])->middleware('permission:revenue.manage');
    Route::post('student-revenues/init', [StudentRevenueController::class, 'initialize'])->middleware('permission:revenue.manage');
    Route::post('student-revenues/trigger', [StudentRevenueController::class, 'trigger'])->middleware('permission:revenue.manage');
    Route::post('student-revenues/confirm', [StudentRevenueController::class, 'confirm'])->middleware('permission:revenue.manage');
    Route::post('student-revenues/pay', [StudentRevenueController::class, 'pay'])->middleware('permission:revenue.manage');
    Route::get('student-risk-scores', [StudentRiskScoreController::class, 'index'])->middleware('permission:config.view');
    Route::post('student-risk-scores/calculate-now', [StudentRiskScoreController::class, 'calculateNow'])->middleware('permission:config.manage');
    Route::get('student-assignments', [StudentAssignmentController::class, 'index'])->middleware('permission:student.assignment.manage');
    Route::get('student-assignments/branches', [StudentAssignmentController::class, 'branches'])->middleware('permission:student.assignment.manage');
    Route::post('student-assignments/generate-id', [StudentAssignmentController::class, 'generateStudentId'])->middleware('permission:student.assignment.manage');
    Route::post('student-assignments', [StudentAssignmentController::class, 'upsert'])->middleware('permission:student.assignment.manage');
    Route::post('student-assignments/bulk-assign', [StudentAssignmentController::class, 'bulkAssign'])->middleware('permission:student.assignment.manage');
    Route::post('student-assignments/auto-assign', [StudentAssignmentController::class, 'autoAssign'])->middleware('permission:student.assignment.manage');
    Route::post('student-assignments/{studentAssignment}/archive', [StudentAssignmentController::class, 'archive'])->middleware('permission:student.assignment.manage');
    Route::post('student-assignments/{studentAssignment}/unarchive', [StudentAssignmentController::class, 'unarchive'])->middleware('permission:student.assignment.manage');
    Route::get('student-card/search', [StudentCardApiController::class, 'search'])->middleware('permission:student.card.view');
    Route::get('student-card/{studentId}', [StudentCardApiController::class, 'show'])->middleware('permission:student.card.view');

    Route::get('dealer-student-revenues/{dealerId}/{studentId}', [DealerStudentRevenueController::class, 'show'])->middleware('permission:revenue.manage');
    Route::post('dealer-student-revenues/init', [DealerStudentRevenueController::class, 'initialize'])->middleware('permission:revenue.manage');

    Route::get('document-categories', [DocumentCategoryController::class, 'index'])->middleware('permission:student.card.view');
    Route::post('document-categories', [DocumentCategoryController::class, 'store'])->middleware('permission:config.manage');
    Route::get('guest-registration-fields', [GuestRegistrationFieldController::class, 'index'])->middleware('permission:config.view');
    Route::post('guest-registration-fields', [GuestRegistrationFieldController::class, 'store'])->middleware('permission:config.manage');
    Route::put('guest-registration-fields/{guestRegistrationField}', [GuestRegistrationFieldController::class, 'update'])->middleware('permission:config.manage');
    Route::post('guest-registration-fields/{guestRegistrationField}/move', [GuestRegistrationFieldController::class, 'move'])->middleware('permission:config.manage');
    Route::post('guest-registration-fields/{guestRegistrationField}/clone', [GuestRegistrationFieldController::class, 'clone'])->middleware('permission:config.manage');
    Route::delete('guest-registration-fields/{guestRegistrationField}', [GuestRegistrationFieldController::class, 'destroy'])->middleware('permission:config.manage');
    Route::get('guest-required-documents', [GuestRequiredDocumentController::class, 'index'])->middleware('permission:config.view');
    Route::post('guest-required-documents', [GuestRequiredDocumentController::class, 'store'])->middleware('permission:config.manage');
    Route::post('guest-required-documents/publish', [GuestRequiredDocumentController::class, 'publish'])->middleware('permission:config.manage');
    Route::put('guest-required-documents/{guestRequiredDocument}', [GuestRequiredDocumentController::class, 'update'])->middleware('permission:config.manage');
    Route::delete('guest-required-documents/{guestRequiredDocument}', [GuestRequiredDocumentController::class, 'destroy'])->middleware('permission:config.manage');

    Route::get('documents', [DocumentController::class, 'index'])->middleware('permission:student.card.view');
    Route::post('documents/preview-name', [DocumentController::class, 'previewName'])->middleware('permission:student.card.view');
    Route::post('documents', [DocumentController::class, 'store'])->middleware('permission:student.card.view');
    Route::post('documents/{document}/approve', [DocumentController::class, 'approve'])->middleware('permission:student.card.view');
    Route::post('documents/{document}/reject', [DocumentController::class, 'reject'])->middleware('permission:student.card.view');

    Route::get('process-outcomes', [ProcessOutcomeController::class, 'index'])->middleware('permission:student.card.view');
    Route::post('process-outcomes', [ProcessOutcomeController::class, 'store'])->middleware('permission:student.card.view');
    Route::post('process-outcomes/{processOutcome}/make-visible', [ProcessOutcomeController::class, 'makeVisible'])->middleware('permission:student.card.view');

    Route::get('internal-notes', [InternalNoteController::class, 'index'])->middleware('permission:student.card.view');
    Route::post('internal-notes', [InternalNoteController::class, 'store'])->middleware('permission:student.card.view');
    Route::post('internal-notes/{internalNote}/pin', [InternalNoteController::class, 'pin'])->middleware('permission:student.card.view');
    Route::post('internal-notes/{internalNote}/unpin', [InternalNoteController::class, 'unpin'])->middleware('permission:student.card.view');
    Route::delete('internal-notes/{internalNote}', [InternalNoteController::class, 'destroy'])->middleware('permission:student.card.view');

    Route::get('account-vault', [AccountVaultController::class, 'index'])->middleware('permission:student.card.view');
    Route::post('account-vault', [AccountVaultController::class, 'store'])->middleware('permission:student.card.view');
    Route::put('account-vault/{accountVault}', [AccountVaultController::class, 'update'])->middleware('permission:student.card.view');
    Route::delete('account-vault/{accountVault}', [AccountVaultController::class, 'destroy'])->middleware('permission:student.card.view');
    Route::get('account-vault/{accountVault}/reveal', [AccountVaultController::class, 'reveal'])->middleware(['permission:student.card.view', 'throttle:30,1']);
    Route::get('account-vault-logs', [AccountVaultController::class, 'logs'])->middleware('permission:student.card.view');

    Route::get('field-rules', [FieldRuleController::class, 'index'])->middleware('permission:approval.manage');
    Route::post('field-rules', [FieldRuleController::class, 'store'])->middleware('permission:approval.manage');
    Route::put('field-rules/{fieldRule}', [FieldRuleController::class, 'update'])->middleware('permission:approval.manage');
    Route::post('field-rules/evaluate', [FieldRuleController::class, 'evaluate'])->middleware('permission:approval.manage');

    Route::get('field-rule-approvals', [FieldRuleApprovalController::class, 'index'])->middleware('permission:approval.manage');
    Route::post('field-rule-approvals/archive-bulk', [FieldRuleApprovalController::class, 'bulkArchive'])->middleware('permission:approval.manage');
    Route::delete('field-rule-approvals/cleanup-bulk', [FieldRuleApprovalController::class, 'bulkCleanup'])->middleware('permission:approval.manage');
    Route::post('field-rule-approvals/{fieldRuleApproval}/approve', [FieldRuleApprovalController::class, 'approve'])->middleware('permission:approval.manage');
    Route::post('field-rule-approvals/{fieldRuleApproval}/reject', [FieldRuleApprovalController::class, 'reject'])->middleware('permission:approval.manage');

    Route::get('message-templates', [MessageTemplateController::class, 'index'])->middleware('permission:notification.manage');
    Route::post('message-templates', [MessageTemplateController::class, 'store'])->middleware('permission:notification.manage');
    Route::put('message-templates/{messageTemplate}', [MessageTemplateController::class, 'update'])->middleware('permission:notification.manage');
    Route::get('knowledge-base', [KnowledgeBaseController::class, 'index'])->middleware('permission:config.view');
    Route::post('knowledge-base', [KnowledgeBaseController::class, 'store'])->middleware('permission:config.manage');
    Route::put('knowledge-base/{knowledgeBaseArticle}', [KnowledgeBaseController::class, 'update'])->middleware('permission:config.manage');

    // v3 — Task Templates
    Route::get('task-templates', [TaskTemplateController::class, 'index']);
    Route::post('task-templates', [TaskTemplateController::class, 'store'])->middleware('permission:config.manage');
    Route::get('task-templates/{taskTemplate}', [TaskTemplateController::class, 'show']);
    Route::put('task-templates/{taskTemplate}', [TaskTemplateController::class, 'update'])->middleware('permission:config.manage');
    Route::delete('task-templates/{taskTemplate}', [TaskTemplateController::class, 'destroy'])->middleware('permission:config.manage');
    Route::post('task-templates/{taskTemplate}/items', [TaskTemplateController::class, 'itemStore'])->middleware('permission:config.manage');
    Route::put('task-templates/{taskTemplate}/items/{item}', [TaskTemplateController::class, 'itemUpdate'])->middleware('permission:config.manage');
    Route::delete('task-templates/{taskTemplate}/items/{item}', [TaskTemplateController::class, 'itemDestroy'])->middleware('permission:config.manage');
    Route::post('task-templates/{templateId}/apply', [TaskTemplateController::class, 'apply']);

    Route::get('guest-applications', [GuestApplicationAdminController::class, 'index'])->middleware('permission:student.assignment.manage');
    Route::post('guest-applications/bulk-assign', [GuestApplicationAdminController::class, 'bulkAssign'])->middleware('permission:student.assignment.manage');
    Route::get('guest-applications/{guestApplication}/conversion-readiness', [GuestApplicationAdminController::class, 'conversionReadiness'])->middleware('permission:student.assignment.manage');
    Route::post('guest-applications/archive-stale', [GuestApplicationAdminController::class, 'archiveStale'])->middleware('permission:student.assignment.manage');
    Route::post('guest-applications/{guestApplication}/approve-contract', [GuestApplicationAdminController::class, 'approveContract'])->middleware('permission:student.assignment.manage');
    Route::post('guest-applications/{guestApplication}/reject-contract', [GuestApplicationAdminController::class, 'rejectContract'])->middleware('permission:student.assignment.manage');
    Route::post('guest-applications/{guestApplication}/convert', [GuestApplicationAdminController::class, 'convert'])->middleware('permission:student.assignment.manage');
    Route::get('guest-ops/tickets', [GuestOpsController::class, 'tickets'])->middleware('permission:student.assignment.manage');
    Route::post('guest-ops/tickets/{guestTicket}/status', [GuestOpsController::class, 'updateTicketStatus'])->middleware('permission:student.assignment.manage');
    Route::post('guest-ops/tickets/{guestTicket}/reply', [GuestOpsController::class, 'replyTicket'])->middleware('permission:student.assignment.manage');
    Route::get('guest-ops/documents', [GuestOpsController::class, 'documents'])->middleware('permission:student.assignment.manage');
    Route::post('guest-ops/documents/{document}/decision', [GuestOpsController::class, 'decideDocument'])->middleware('permission:student.assignment.manage');

    Route::get('notification-dispatches', [NotificationDispatchController::class, 'index'])->middleware('permission:notification.manage');
    Route::post('notification-dispatches/dispatch-now', [NotificationDispatchController::class, 'dispatchNow'])->middleware('permission:notification.manage');
    Route::post('notification-dispatches/retry-failed', [NotificationDispatchController::class, 'retryFailed'])->middleware('permission:notification.manage');
    Route::post('notification-dispatches/mark-all-read', [NotificationDispatchController::class, 'markAllRead']);
    Route::post('notification-dispatches/{notificationDispatch}/mark-read', [NotificationDispatchController::class, 'markRead']);
    Route::post('notification-dispatches/{notificationDispatch}/mark-sent', [NotificationDispatchController::class, 'markSent'])->middleware('permission:notification.manage');
    Route::post('notification-dispatches/{notificationDispatch}/mark-failed', [NotificationDispatchController::class, 'markFailed'])->middleware('permission:notification.manage');
    Route::get('batch-operations', [BatchOperationController::class, 'index'])->middleware('permission:notification.manage');
    Route::post('batch-operations/notification-broadcast', [BatchOperationController::class, 'broadcastNotification'])->middleware('permission:notification.manage');

    Route::get('escalation-rules', [EscalationRuleController::class, 'index'])->middleware('permission:notification.manage');
    Route::post('escalation-rules', [EscalationRuleController::class, 'store'])->middleware('permission:notification.manage');
    Route::put('escalation-rules/{escalationRule}', [EscalationRuleController::class, 'update'])->middleware('permission:notification.manage');
    Route::post('escalation-rules/process-now', [EscalationRuleController::class, 'processNow'])->middleware('permission:notification.manage');

    Route::get('system-health', [SystemHealthController::class, 'index'])->middleware('permission:config.view');
    Route::get('system-health/failed-jobs', [SystemHealthController::class, 'failedJobs'])->middleware('permission:config.view');
    Route::post('system-health/run-critical-check', [SystemHealthController::class, 'runCriticalCheck'])->middleware('permission:config.manage');
    Route::get('system-event-logs', [SystemEventLogController::class, 'index'])->middleware('permission:config.view');
    Route::get('external-provider-connections', [ExternalProviderConnectionController::class, 'index'])->middleware('permission:config.view');
    Route::post('external-provider-connections', [ExternalProviderConnectionController::class, 'store'])->middleware('permission:config.manage');
    Route::put('external-provider-connections/{externalProviderConnection}', [ExternalProviderConnectionController::class, 'update'])->middleware('permission:config.manage');
});

Route::prefix('v1/marketing-admin')->middleware(['web', 'company.context', 'auth', 'marketing.access'])->group(function (): void {
    Route::get('companies', [CompanyContextController::class, 'index']);
    Route::post('companies/switch', [CompanyContextController::class, 'switch']);

    Route::get('suggestions', [SuggestionController::class, 'index']);
    Route::get('campaigns', [MarketingCampaignController::class, 'index']);
    Route::post('campaigns', [MarketingCampaignController::class, 'store']);
    Route::put('campaigns/{marketingCampaign}', [MarketingCampaignController::class, 'update']);
    Route::put('campaigns/{marketingCampaign}/pause', [MarketingCampaignController::class, 'pause']);
    Route::put('campaigns/{marketingCampaign}/resume', [MarketingCampaignController::class, 'resume']);
    Route::delete('campaigns/{marketingCampaign}', [MarketingCampaignController::class, 'destroy']);

    Route::get('analytics/kpis', [MarketingAnalyticsController::class, 'kpis']);
    Route::get('analytics/source-performance', [MarketingAnalyticsController::class, 'sourcePerformance']);
    Route::get('analytics/external-performance', [MarketingAnalyticsController::class, 'externalPerformance']);
});

Route::prefix('v1/student')->middleware(['web', 'company.context', 'auth', 'student.role'])->group(function (): void {
    Route::get('process-outcomes/{processOutcome}', [ProcessOutcomeController::class, 'showForStudent'])
        ->middleware('process.outcome.visibility');
});

// ── Senior Müsait Slot API ────────────────────────────────────────────
Route::prefix('senior')->middleware(['web', 'company.context', 'auth'])->group(function (): void {
    Route::get('{seniorId}/available-slots', [SeniorAvailabilityController::class, 'availableSlots'])
        ->name('api.senior.available-slots');
});

// ── Dahili Mesajlaşma API (polling + unread badge) ─────────────────────
Route::prefix('im')->middleware(['web', 'company.context', 'auth'])->group(function (): void {
    Route::get('unread-count', [InternalMessagingController::class, 'unreadCount'])->name('api.im.unread-count');
    Route::get('conversations/{convId}/poll', [InternalMessagingController::class, 'poll'])->name('api.im.poll');
});

// ── K2: 2FA ───────────────────────────────────────────────────────────
Route::prefix('v1/2fa')->middleware(['web', 'company.context', 'auth'])->group(function (): void {
    Route::post('enable',    [TwoFactorController::class, 'enable']);
    Route::post('verify',    [TwoFactorController::class, 'verify']);
    Route::post('disable',   [TwoFactorController::class, 'disable']);
    Route::post('challenge', [TwoFactorController::class, 'challenge']);
});

// ── K2: IP Erişim Kuralları (Manager) ─────────────────────────────────
Route::prefix('v1/config')->middleware(['web', 'company.context', 'auth', 'manager.role'])->group(function (): void {
    Route::get('ip-rules',       [IpAccessRuleController::class, 'index'])->middleware('permission:config.manage');
    Route::post('ip-rules',      [IpAccessRuleController::class, 'store'])->middleware('permission:config.manage');
    Route::delete('ip-rules/{id}',[IpAccessRuleController::class, 'destroy'])->middleware('permission:config.manage');
});

// ── K2: Session Yönetimi ───────────────────────────────────────────────
Route::prefix('v1/sessions')->middleware(['web', 'company.context', 'auth'])->group(function (): void {
    Route::get('/',        [SessionController::class, 'activeSessions']);
    Route::delete('{id}',  [SessionController::class, 'revokeSession']);
});

// ── K3: Güvenlik (Anomali + Compliance) ───────────────────────────────
Route::prefix('v1/security')->middleware(['web', 'company.context', 'auth', 'manager.role'])->group(function (): void {
    Route::get('anomalies',          [SecurityController::class, 'anomalies'])->middleware('permission:config.manage');
    Route::get('compliance-report',  [SecurityController::class, 'complianceReport'])->middleware('permission:config.manage');
});

// ── Bildirim Tercihleri (Guest/Student) ───────────────────────────────
Route::prefix('v1/profile')->middleware(['web', 'company.context', 'auth', 'guest.role'])->group(function (): void {
    Route::get('notification-preferences',  [NotificationPreferenceController::class, 'show']);
    Route::put('notification-preferences',  [NotificationPreferenceController::class, 'update']);
});

// ── K3: Zamanlanmış Bildirimler (Manager) ─────────────────────────────
Route::prefix('scheduled-notifications')->middleware(['web', 'company.context', 'auth', 'manager.role'])->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Api\ScheduledNotificationController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\ScheduledNotificationController::class, 'store']);
    Route::put('{id}', [\App\Http\Controllers\Api\ScheduledNotificationController::class, 'update']);
    Route::delete('{id}', [\App\Http\Controllers\Api\ScheduledNotificationController::class, 'destroy']);
});

