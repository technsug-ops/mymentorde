<?php

use App\Http\Controllers\HandbookController;
use App\Http\Controllers\TaskBoardController;
use App\Http\Controllers\MarketingAdmin\ABTestController;
use App\Http\Controllers\MarketingAdmin\AttributionController;
use App\Http\Controllers\MarketingAdmin\BudgetController;
use App\Http\Controllers\MarketingAdmin\CampaignController;
use App\Http\Controllers\MarketingAdmin\CMSCategoryController;
use App\Http\Controllers\MarketingAdmin\CMSContentController;
use App\Http\Controllers\MarketingAdmin\CMSMediaController;
use App\Http\Controllers\MarketingAdmin\DashboardController;
use App\Http\Controllers\MarketingAdmin\DealerRelationsController;
use App\Http\Controllers\MarketingAdmin\EmailCampaignController;
use App\Http\Controllers\MarketingAdmin\EmailSegmentController;
use App\Http\Controllers\MarketingAdmin\EmailTemplateController;
use App\Http\Controllers\MarketingAdmin\EventController;
use App\Http\Controllers\MarketingAdmin\EventRegistrationController;
use App\Http\Controllers\MarketingAdmin\KPIReportController;
use App\Http\Controllers\MarketingAdmin\LeadSourceController;
use App\Http\Controllers\MarketingAdmin\IntegrationController;
use App\Http\Controllers\MarketingAdmin\NotificationController;
use App\Http\Controllers\MarketingAdmin\ProfileController;
use App\Http\Controllers\MarketingAdmin\SalesPipelineController;
use App\Http\Controllers\MarketingAdmin\ScoringController;
use App\Http\Controllers\MarketingAdmin\SettingsController;
use App\Http\Controllers\MarketingAdmin\SocialAccountController;
use App\Http\Controllers\MarketingAdmin\SocialMetricsController;
use App\Http\Controllers\MarketingAdmin\SocialPostController;
use App\Http\Controllers\MarketingAdmin\TeamController;
use App\Http\Controllers\MarketingAdmin\TaskController;
use App\Http\Controllers\MarketingAdmin\TrackingLinkController;
use App\Http\Controllers\MarketingAdmin\WorkflowController;
use App\Http\Controllers\MarketingAdmin\AiAssistantController;
use App\Http\Controllers\MarketingAdmin\AnalyticsController;
use App\Http\Controllers\MarketingAdmin\EmailDripController;
use Illuminate\Support\Facades\Route;

Route::middleware(['company.context', 'auth', 'marketing.access', 'module:marketing_admin'])
    ->prefix('mktg-admin')
    ->name('mktg-admin.')
    ->group(function (): void {
        Route::get('/', fn () => redirect('/mktg-admin/dashboard'));

        // Panel mode switcher (Marketing ↔ Sales) — tüm roller serbestçe geçebilir
        Route::get('/switch-mode/{mode}', function (string $mode) {
            session(['mktg_panel_mode' => in_array($mode, ['marketing', 'sales'], true) ? $mode : 'marketing']);
            return redirect('/mktg-admin/dashboard');
        })->where('mode', 'marketing|sales')->name('switch-mode');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('marketing-admin.dashboard');
        Route::get('/search', [DashboardController::class, 'globalSearch'])->middleware('throttle:60,1')->name('marketing-admin.search');
        Route::get('/suggestions/audience', [DashboardController::class, 'audienceSuggestions']);

        // ─── Pazarlama Ekibi Route'ları (sales rolleri giremez) ─────────────
        Route::middleware('marketing.team')->group(function (): void {
            Route::resource('/campaigns', CampaignController::class)->names('campaigns');
            Route::put('/campaigns/{id}/pause', [CampaignController::class, 'pause']);
            Route::put('/campaigns/{id}/resume', [CampaignController::class, 'resume']);
            Route::get('/campaigns/{id}/report', [CampaignController::class, 'report']);
            Route::get('/campaigns/{id}/daily-metrics', [CampaignController::class, 'dailyMetrics']);
            Route::get('/campaigns/roi', [CampaignController::class, 'roiDashboard']);
            Route::get('/campaigns/{id}/channel-plan', [CampaignController::class, 'channelPlan']);
            Route::post('/campaigns/{id}/channel-plan', [CampaignController::class, 'channelPlanStore']);
            Route::put('/campaigns/{id}/channel-plan/{planId}', [CampaignController::class, 'channelPlanUpdate']);
            Route::delete('/campaigns/{id}/channel-plan/{planId}', [CampaignController::class, 'channelPlanDelete']);

            Route::resource('/content', CMSContentController::class);
            Route::put('/content/{id}/publish', [CMSContentController::class, 'publish']);
            Route::put('/content/{id}/unpublish', [CMSContentController::class, 'unpublish']);
            Route::put('/content/{id}/schedule', [CMSContentController::class, 'schedule']);
            Route::put('/content/{id}/feature', [CMSContentController::class, 'toggleFeatured']);
            Route::get('/content/{id}/stats', [CMSContentController::class, 'stats']);
            Route::get('/content/{id}/revisions', [CMSContentController::class, 'revisions']);
            Route::resource('/categories', CMSCategoryController::class);
            Route::get('/media', [CMSMediaController::class, 'index']);
            Route::post('/media/upload', [CMSMediaController::class, 'upload']);
            Route::delete('/media/{id}', [CMSMediaController::class, 'destroy']);

            Route::resource('/email/templates', EmailTemplateController::class);
            Route::post('/email/templates/{id}/test-send', [EmailTemplateController::class, 'testSend']);
            Route::resource('/email/segments', EmailSegmentController::class);
            Route::get('/email/segments/{id}/preview', [EmailSegmentController::class, 'previewMembers']);
            Route::resource('/email/campaigns', EmailCampaignController::class)->names('email-campaigns');
            Route::post('/email/campaigns/{id}/send', [EmailCampaignController::class, 'send']);
            Route::post('/email/campaigns/{id}/schedule', [EmailCampaignController::class, 'schedule']);
            Route::get('/email/campaigns/{id}/stats', [EmailCampaignController::class, 'stats']);
            Route::get('/email/log', [EmailCampaignController::class, 'sendLog']);
            Route::get('/email/drip-sequences', [EmailDripController::class, 'index']);
            Route::post('/email/drip-sequences', [EmailDripController::class, 'store']);
            Route::get('/email/drip-sequences/{id}', [EmailDripController::class, 'show']);
            Route::put('/email/drip-sequences/{id}', [EmailDripController::class, 'update']);
            Route::delete('/email/drip-sequences/{id}', [EmailDripController::class, 'destroy']);
            Route::post('/email/drip-sequences/{id}/steps', [EmailDripController::class, 'stepStore']);
            Route::put('/email/drip-sequences/{id}/steps/{stepId}', [EmailDripController::class, 'stepUpdate']);
            Route::delete('/email/drip-sequences/{id}/steps/{stepId}', [EmailDripController::class, 'stepDelete']);
            Route::get('/email/drip-sequences/{id}/enrollments', [EmailDripController::class, 'enrollments']);

            Route::resource('/social/accounts', SocialAccountController::class);
            Route::resource('/social/posts', SocialPostController::class);
            Route::put('/social/posts/{id}/publish', [SocialPostController::class, 'markPublished']);
            Route::put('/social/posts/{id}/metrics', [SocialPostController::class, 'updateMetrics']);
            Route::get('/social/metrics', [SocialMetricsController::class, 'index']);
            Route::get('/social/metrics/monthly/{period}', [SocialMetricsController::class, 'monthly']);
            Route::get('/social/calendar', [SocialPostController::class, 'calendar']);
            Route::post('/social/posts/schedule-batch', [SocialPostController::class, 'schedulePosts']);

            Route::resource('/events', EventController::class);
            Route::put('/events/{id}/publish', [EventController::class, 'publish']);
            Route::put('/events/{id}/cancel', [EventController::class, 'cancel']);
            Route::get('/events/{id}/registrations', [EventRegistrationController::class, 'index']);
            Route::put('/events/{id}/registrations/{regId}/status', [EventRegistrationController::class, 'updateStatus']);
            Route::get('/events/{id}/report', [EventController::class, 'report']);
            Route::post('/events/{id}/send-reminder', [EventController::class, 'sendReminder']);
            Route::get('/events/{id}/survey-results', [EventController::class, 'surveyResults']);

            Route::get('/tracking-links', [TrackingLinkController::class, 'index']);
            Route::post('/tracking-links', [TrackingLinkController::class, 'store']);
            Route::put('/tracking-links/{id}', [TrackingLinkController::class, 'update']);
            Route::delete('/tracking-links/{id}', [TrackingLinkController::class, 'destroy']);
            Route::get('/tracking-links/{id}/stats', [TrackingLinkController::class, 'stats']);

            Route::get('/abtests', [ABTestController::class, 'index']);
            Route::post('/abtests', [ABTestController::class, 'store']);
            Route::get('/abtests/{abtest}', [ABTestController::class, 'show']);
            Route::put('/abtests/{abtest}/activate', [ABTestController::class, 'activate']);
            Route::post('/abtests/{abtest}/apply-winner', [ABTestController::class, 'applyWinner']);
        });

        // ─── Satış + Pazarlama Ortak Route'ları (tüm marketing.access rolleri) ──
        Route::get('/lead-sources', [LeadSourceController::class, 'index']);
        Route::get('/lead-sources/funnel', [LeadSourceController::class, 'funnel']);
        Route::get('/lead-sources/utm', [LeadSourceController::class, 'utmPerformance']);
        Route::get('/lead-sources/tracking-codes', [LeadSourceController::class, 'trackingCodes']);
        Route::get('/lead-sources/tracking-codes/csv', [LeadSourceController::class, 'trackingCodesCsv']);
        Route::get('/lead-sources/dropoff', [LeadSourceController::class, 'dropoffAnalysis']);
        Route::get('/lead-sources/source-verify', [LeadSourceController::class, 'sourceVerification']);

        Route::get('/pipeline', [SalesPipelineController::class, 'index']);
        Route::get('/pipeline/kanban', [SalesPipelineController::class, 'kanban']);
        Route::get('/pipeline/kanban/poll', [SalesPipelineController::class, 'kanbanPoll']);
        Route::patch('/pipeline/kanban/{guest}/move', [SalesPipelineController::class, 'kanbanMove']);
        Route::get('/pipeline/kanban/dbgtest', function() {
            $g = \App\Models\GuestApplication::withoutGlobalScope('company')->whereNotIn('lead_status',['converted','lost'])->whereNull('deleted_at')->first();
            if (!$g) return response()->json(['error'=>'no guest found']);
            \Illuminate\Support\Facades\DB::table('guest_applications')->where('id',$g->id)->update(['lead_status'=>'contacted','updated_at'=>now()]);
            $after = \Illuminate\Support\Facades\DB::table('guest_applications')->where('id',$g->id)->value('lead_status');
            return response()->json(['ok'=>true,'guest_id'=>$g->id,'name'=>$g->first_name,'new_status'=>$after]);
        });
        Route::get('/pipeline/value', [SalesPipelineController::class, 'pipelineValue']);
        Route::get('/pipeline/loss-analysis', [SalesPipelineController::class, 'lossAnalysis']);
        Route::get('/pipeline/conversion-time', [SalesPipelineController::class, 'conversionTime']);
        Route::get('/pipeline/re-engagement', [SalesPipelineController::class, 'reEngagement']);
        Route::get('/pipeline/score-analysis', [SalesPipelineController::class, 'scoreAnalysis']);

        // Guest detail — mktg/sales rolleri pipeline'dan erişir
        Route::get('/guests/{guest}', [\App\Http\Controllers\Manager\ManagerPortalController::class, 'guestShow'])->name('mktg.guest-detail');
        Route::patch('/guests/{guest}/status', [\App\Http\Controllers\Manager\ManagerPortalController::class, 'guestUpdateStatus'])->name('mktg.guest-status');
        Route::patch('/guests/{guest}/assign', [\App\Http\Controllers\Manager\ManagerPortalController::class, 'guestAssignSenior'])->name('mktg.guest-assign');

        // Lead Scoring — okuma tüm marketing.access, yazma sadece admin
        Route::get('/scoring', [ScoringController::class, 'index']);
        Route::get('/scoring/leaderboard', [ScoringController::class, 'leaderboard']);
        Route::get('/scoring/config', [ScoringController::class, 'config']);
        Route::get('/scoring/{guestId}/history', [ScoringController::class, 'scoreHistory']);

        // Automation Workflows — sadece marketing_admin (staff erişemez)
        Route::middleware('marketing.admin')->group(function (): void {
            Route::get('/workflows', [WorkflowController::class, 'index']);
            Route::post('/workflows', [WorkflowController::class, 'store']);
            Route::get('/workflows/{workflow}/builder', [WorkflowController::class, 'builder']);
            Route::put('/workflows/{workflow}/activate', [WorkflowController::class, 'activate']);
            Route::put('/workflows/{workflow}/pause', [WorkflowController::class, 'pause']);
            Route::get('/workflows/{workflow}/enrollments', [WorkflowController::class, 'enrollments']);
            Route::get('/workflows/{workflow}/analytics', [WorkflowController::class, 'analytics']);
            Route::delete('/workflows/{workflow}', [WorkflowController::class, 'destroy']);
        });

        // Multi-Touch Attribution
        Route::get('/attribution', [AttributionController::class, 'index']);
        Route::get('/attribution/compare', [AttributionController::class, 'compare']);

        Route::get('/dealers', [DealerRelationsController::class, 'index']);
        Route::get('/dealers/{id}/performance', [DealerRelationsController::class, 'performance']);

        Route::get('/budget', [BudgetController::class, 'index']);
        Route::get('/budget/{period}', [BudgetController::class, 'show']);

        Route::get('/kpi', [KPIReportController::class, 'index']);
        Route::get('/reports', [KPIReportController::class, 'list']);
        Route::get('/reports/scheduled', [KPIReportController::class, 'scheduled']);
        Route::get('/reports/{id}/download/{format}', [KPIReportController::class, 'download']);

        Route::get('/tasks', [TaskController::class, 'index']);
        Route::get('/tasks/kanban', [TaskController::class, 'kanban']);
        Route::get('/tasks/gantt', [TaskController::class, 'gantt']);
        Route::get('/tasks/users', [TaskController::class, 'allUsers']);
        Route::post('/tasks/bulk-update', [TaskController::class, 'bulkUpdate']);
        Route::put('/tasks/{id}/kanban', [TaskController::class, 'kanbanUpdate']);
        Route::patch('/tasks/{id}/kanban', [TaskController::class, 'kanbanUpdate']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::put('/tasks/{id}', [TaskController::class, 'update']);
        Route::post('/tasks/{id}/done', [TaskController::class, 'markDone']);
        Route::post('/tasks/{id}/reopen', [TaskController::class, 'reopen']);
        Route::post('/tasks/{id}/request-review', [TaskController::class, 'requestReview']);
        Route::post('/tasks/{id}/approve', [TaskController::class, 'approve']);
        Route::post('/tasks/{id}/request-revision', [TaskController::class, 'requestRevision']);
        Route::post('/tasks/{id}/hold', [TaskController::class, 'hold']);
        Route::post('/tasks/{id}/resume', [TaskController::class, 'resume']);
        Route::post('/tasks/{id}/cancel', [TaskController::class, 'cancel']);
        Route::post('/tasks/{id}/watch', [TaskController::class, 'watch']);
        Route::get('/tasks/{id}/detail', [TaskController::class, 'detail']);
        Route::post('/tasks/{id}/mention', [TaskController::class, 'mention']);
        Route::post('/tasks/{id}/subtasks', [TaskController::class, 'subtaskStore']);
        Route::post('/tasks/{id}/subtasks/{subId}/toggle', [TaskController::class, 'subtaskToggle']);
        Route::delete('/tasks/{id}/subtasks/{subId}', [TaskController::class, 'subtaskDelete']);
        // v3 — Checklist (TaskBoardController paylaşımlı — reorder önce)
        Route::patch('/tasks/{id}/checklist/reorder', [TaskBoardController::class, 'checklistReorder']);
        Route::post('/tasks/{id}/checklist', [TaskBoardController::class, 'checklistStore']);
        Route::patch('/tasks/{id}/checklist/{itemId}/toggle', [TaskBoardController::class, 'checklistToggle']);
        Route::delete('/tasks/{id}/checklist/{itemId}', [TaskBoardController::class, 'checklistDestroy']);
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
        Route::get('/tasks/{id}/attachments', [TaskController::class, 'attachmentIndex']);
        Route::post('/tasks/{id}/attachments', [TaskController::class, 'attachmentStore']);
        Route::delete('/tasks/attachments/{attachId}', [TaskController::class, 'attachmentDelete']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        // Read-only routes accessible to all marketing users (admin + staff)
        Route::get('/settings', [SettingsController::class, 'show']);
        Route::get('/integrations', [IntegrationController::class, 'index']);
        // ─── Katman 2.5 — Integration Health ────────────────────────────────
        Route::get('/integrations/health', [IntegrationController::class, 'health']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markRead']);

        // Admin-only routes: team, settings write, integrations write, budget write,
        // dealer broadcast/materials, KPI generate, notification dispatch
        Route::middleware('marketing.admin')->group(function (): void {
            // Workflow node CRUD (admin only — triggers can break live enrollments)
            Route::post('/workflows/{workflow}/nodes', function (\Illuminate\Http\Request $req, \App\Models\AutomationWorkflow $workflow) {
                $data = $req->validate([
                    'node_type'   => 'required|string|max:64',
                    'node_config' => 'nullable|array',
                    'sort_order'  => 'nullable|integer',
                ]);
                $workflow->nodes()->create([
                    'node_type'  => $data['node_type'],
                    'node_config' => $data['node_config'] ?? [],
                    'sort_order' => $data['sort_order'] ?? $workflow->nodes()->count(),
                ]);
                return back()->with('success', 'Node eklendi.');
            });
            Route::put('/workflows/{workflow}/nodes/{node}', function (\Illuminate\Http\Request $req, \App\Models\AutomationWorkflow $workflow, \App\Models\AutomationWorkflowNode $node) {
                $data = $req->validate([
                    'node_config' => 'nullable|array',
                    'sort_order'  => 'nullable|integer',
                ]);
                $node->update([
                    'node_config' => $data['node_config'] ?? $node->node_config,
                    'sort_order'  => $data['sort_order'] ?? $node->sort_order,
                ]);
                return back()->with('success', 'Node güncellendi.');
            });
            Route::delete('/workflows/{workflow}/nodes/{node}', function (\App\Models\AutomationWorkflow $workflow, \App\Models\AutomationWorkflowNode $node) {
                $node->delete();
                return back()->with('success', 'Node silindi.');
            });
            Route::get('/team', [TeamController::class, 'index']);
            Route::post('/team/invite', [TeamController::class, 'invite']);
            Route::put('/team/{userId}/permissions', [TeamController::class, 'updatePermissions']);
            Route::delete('/team/{userId}', [TeamController::class, 'remove']);

            Route::put('/settings', [SettingsController::class, 'update']);

            Route::put('/integrations', [IntegrationController::class, 'update']);
            Route::post('/integrations/ai-writer', [IntegrationController::class, 'updateAiWriter'])->name('mktg-admin.integrations.ai-writer.update');
            Route::post('/integrations/test/{provider}', [IntegrationController::class, 'test']);
            Route::post('/integrations/refresh/{provider}', [IntegrationController::class, 'refreshToken']);
            Route::get('/integrations/oauth/{provider}/start', [IntegrationController::class, 'oauthStart']);
            Route::get('/integrations/oauth/{provider}/callback', [IntegrationController::class, 'oauthCallback']);

            Route::post('/budget', [BudgetController::class, 'store']);
            Route::put('/budget/{period}', [BudgetController::class, 'update']);

            Route::post('/dealers/broadcast', [DealerRelationsController::class, 'broadcast']);
            Route::post('/dealers/materials', [DealerRelationsController::class, 'shareMaterial']);
            Route::post('/dealers/{code}/broadcast', [DealerRelationsController::class, 'broadcastOne']);

            Route::post('/reports/generate', [KPIReportController::class, 'generate']);

            Route::post('/notifications/dispatch-now', [NotificationController::class, 'dispatchNow']);
            Route::post('/notifications/retry-failed', [NotificationController::class, 'retryFailed']);
            Route::post('/notifications/{id}/mark-sent', [NotificationController::class, 'markSent']);
            Route::post('/notifications/{id}/mark-failed', [NotificationController::class, 'markFailed']);

            // Scoring config write — sadece admin
            Route::put('/scoring/config/{rule}', [ScoringController::class, 'updateRule']);
        });

        // ─── Katman 3.1 — AI Pazarlama Asistanı ────────────────────────────────
        Route::post('/ai/generate-draft', [AiAssistantController::class, 'generateDraft']);
        Route::post('/ai/subject-variants/{id}', [AiAssistantController::class, 'subjectVariants']);
        Route::post('/ai/ask', [AiAssistantController::class, 'ask']);
        Route::get('/ai/history', [AiAssistantController::class, 'history']);

        // ─── Katman 3.2 — Visual Workflow Builder (admin-only) ──────────────
        Route::middleware('marketing.admin')->group(function (): void {
            Route::get('/workflows/{workflow}/builder-data', [WorkflowController::class, 'builderData']);
            Route::put('/workflows/{workflow}/builder-data', [WorkflowController::class, 'builderDataSave']);
            Route::post('/workflows/{workflow}/simulate', [WorkflowController::class, 'simulate']);
        });

        // ─── Katman 3.3 — Predictive Analytics ─────────────────────────────
        Route::get('/analytics/conversion-probability/{guestId}', [AnalyticsController::class, 'conversionProbability']);
        Route::get('/analytics/revenue-projection', [AnalyticsController::class, 'revenueProjection']);
        Route::get('/analytics/churn-risk', [AnalyticsController::class, 'churnRisk']);
    });

Route::middleware(['company.context', 'auth', 'manager.role'])->prefix('mktg-admin')->group(function (): void {
    Route::get('/manager-view/dashboard', [DashboardController::class, 'managerView']);
    Route::get('/manager-view/kpi', [KPIReportController::class, 'managerView']);
    Route::get('/manager-view/campaigns', [CampaignController::class, 'managerView']);
    Route::get('/manager-view/pipeline', [SalesPipelineController::class, 'managerView']);
});

Route::middleware(['company.context', 'auth', 'marketing.access', 'module:marketing_admin'])->prefix('mktg-admin')->group(function (): void {
    Route::get('/help', [HandbookController::class, 'marketing'])->name('marketing.handbook');
    Route::get('/help/download', [HandbookController::class, 'download'])->defaults('role', 'marketing')->name('marketing.handbook.download');

    // ── Digital Asset Management (DAM) — macro tanımı AppServiceProvider'da ──
    // Dış grup zaten prefix('mktg-admin') ekliyor, macro'ya sadece alt yol.
    Route::dam('digital-assets', 'marketing-admin.dam.');
});
