<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActionTemplate;
use App\Models\GuestApplication;
use App\Models\LeadActionLog;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Manager/senior dashboard'ından aksiyon alma endpoint'leri.
 *
 * Route prefix: /manager/actions
 * - Senior atama
 * - Not güncelleme
 * - Aksiyon log kaydı (ara, WhatsApp, email gönderildi tıklandığında)
 * - Template listesi (JSON — modal dropdown için)
 * - Template render (substitute variables)
 */
class LeadActionController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    /**
     * Senior ata — guest veya student'a.
     * POST /manager/actions/{type}/{id}/assign-senior
     */
    public function assignSenior(Request $request, string $type, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'senior_email' => ['required', 'email', 'exists:users,email'],
        ]);

        $target = $this->resolveTarget($type, $id);

        if ($type === 'guest') {
            $oldSenior = $target->assigned_senior_email;
            $target->update([
                'assigned_senior_email' => $data['senior_email'],
                'assigned_at' => now(),
                'assigned_by' => $request->user()->id,
            ]);

            $this->logAction($request, 'guest', $id, 'assign_senior', null, [
                'old_senior' => $oldSenior,
                'new_senior' => $data['senior_email'],
            ]);
        } else {
            // Student için: ayrı bir assignment tablosu var (StudentAssignment) — basit not olarak kaydedelim
            $this->logAction($request, 'student', $id, 'assign_senior', "Senior atandı: {$data['senior_email']}", [
                'new_senior' => $data['senior_email'],
            ]);
        }

        return response()->json([
            'ok' => true,
            'senior_email' => $data['senior_email'],
            'message' => 'Senior atandı.',
        ]);
    }

    /**
     * Hızlı not güncelle (guest'te 'notes' alanı).
     * POST /manager/actions/guest/{id}/update-notes
     */
    public function updateNotes(Request $request, string $type, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        if ($type !== 'guest') {
            return response()->json(['ok' => false, 'error' => 'only_guest_supported'], 400);
        }

        $target = $this->resolveTarget($type, $id);
        $target->update(['notes' => $data['notes']]);

        $this->logAction($request, 'guest', $id, 'note', $data['notes']);

        return response()->json(['ok' => true, 'message' => 'Not kaydedildi.']);
    }

    /**
     * Manual aksiyon log — kullanıcı WhatsApp/telefon/email açtığında
     * frontend bu endpoint'i çağırır, aksiyon loglanır + PostHog event'i.
     * POST /manager/actions/{type}/{id}/log
     */
    public function logMe(Request $request, string $type, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'action_type'   => ['required', 'in:call,whatsapp,email,note,payment_reminder,book_appointment,custom'],
            'template_id'   => ['nullable', 'integer', 'exists:action_templates,id'],
            'channel'       => ['nullable', 'string', 'max:32'],
            'notes'         => ['nullable', 'string', 'max:5000'],
            'follow_up_days'=> ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $this->logAction(
            $request,
            $type,
            $id,
            $data['action_type'],
            $data['notes'] ?? null,
            ['channel' => $data['channel'] ?? null],
            $data['template_id'] ?? null,
            $data['follow_up_days'] ?? null
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Template listesi (JSON) — modal dropdown için.
     * GET /manager/actions/templates?channel=whatsapp&target_type=guest
     */
    public function templates(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $channel = $request->input('channel');
        $targetType = $request->input('target_type');

        $query = ActionTemplate::withoutGlobalScopes()
            ->where('is_active', true)
            ->where(function ($q) {
                $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
                $q->whereNull('company_id')->orWhere('company_id', $cid);
            });

        if ($channel) $query->where('channel', $channel);
        if ($targetType) {
            $query->where(function ($q) use ($targetType) {
                $q->where('target_type', $targetType)->orWhere('target_type', 'both');
            });
        }

        $templates = $query->orderBy('name')->get(['id', 'name', 'channel', 'target_type', 'subject', 'body']);

        return response()->json(['templates' => $templates]);
    }

    /**
     * Template'i variable'larla render et.
     * GET /manager/actions/templates/{id}/render?target_type=guest&target_id=42
     */
    public function renderTemplate(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);

        $template = ActionTemplate::findOrFail($id);
        $type = $request->input('target_type');
        $targetId = (int) $request->input('target_id');

        $target = $this->resolveTarget($type, $targetId);
        $vars = ActionTemplate::extractVariables($target);
        $rendered = $template->render($vars);

        return response()->json([
            'template_id' => $template->id,
            'channel'     => $template->channel,
            'subject'     => $rendered['subject'],
            'body'        => $rendered['body'],
            'variables'   => $vars,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * @return GuestApplication|User
     */
    private function resolveTarget(string $type, int $id)
    {
        $cid = $this->companyId();

        if ($type === 'guest') {
            return GuestApplication::where('id', $id)->where('company_id', $cid)->firstOrFail();
        }

        if ($type === 'student') {
            return User::where('id', $id)->where('company_id', $cid)->where('role', 'student')->firstOrFail();
        }

        abort(400, 'Invalid target type');
    }

    private function logAction(
        Request $request,
        string $type,
        int $id,
        string $actionType,
        ?string $notes = null,
        array $meta = [],
        ?int $templateId = null,
        ?int $followUpDays = null
    ): LeadActionLog {
        $log = LeadActionLog::create([
            'company_id'    => $this->companyId(),
            'actor_user_id' => $request->user()->id,
            'target_type'   => $type,
            'target_id'     => $id,
            'action_type'   => $actionType,
            'template_id'   => $templateId,
            'channel'       => $meta['channel'] ?? null,
            'notes'         => $notes,
            'meta'          => $meta,
            'follow_up_at'  => $followUpDays ? now()->addDays($followUpDays) : null,
        ]);

        // PostHog event
        try {
            $this->analytics->capture('lead_action_taken', [
                'log_id'         => $log->id,
                'target_type'    => $type,
                'target_id'      => $id,
                'action_type'    => $actionType,
                'channel'        => $meta['channel'] ?? null,
                'template_id'    => $templateId,
                'follow_up_days' => $followUpDays,
                'has_notes'      => !empty($notes),
                'company_id'     => $this->companyId(),
            ], (string) $request->user()->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PostHog lead_action_taken failed', ['error' => $e->getMessage()]);
        }

        return $log;
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array((string) $user->role, \App\Models\User::ADMIN_PANEL_ROLES, true)) {
            abort(403);
        }
    }

    private function companyId(): int
    {
        return app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
    }
}
