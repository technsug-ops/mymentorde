<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\GuestPortalTrait;
use App\Models\GuestAiConversation;
use App\Models\GuestFeedback;
use App\Models\GuestTimelineMilestone;
use App\Models\Marketing\CmsContent;
use App\Services\AiGuestAssistantService;
use App\Services\GuestResolverService;
use App\Services\GuestTimelineService;
use App\Services\GuestViewDataService;
use Illuminate\Http\Request;

class GuestEngagementController extends Controller
{
    use GuestPortalTrait;

    public function __construct(
        private readonly GuestResolverService $guestResolver,
        private readonly GuestViewDataService $viewData,
    ) {}

    // ── AI Başvuru Asistanı ──────────────────────────────────────────────────

    public function aiAssistantPage(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->buildViewData($request, $guest);

        return view('guest.ai-assistant', $data);
    }

    public function aiAssistantAsk(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return response()->json(['ok' => false, 'answer' => 'Oturum bulunamadı.'], 401);
        }

        $question = trim((string) $request->input('question', ''));
        if (mb_strlen($question) < 3) {
            return response()->json(['ok' => false, 'answer' => 'Lütfen bir soru yazın.'], 422);
        }

        $data    = $this->buildViewData($request, $guest);
        $context = [
            'first_name'       => trim((string) ($guest->first_name ?? '')),
            'full_name'        => trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')),
            'email'            => (string) ($guest->email ?? ''),
            'application_type' => (string) ($guest->application_type ?? ''),
            'target_city'      => (string) ($guest->target_city ?? ''),
            'package_code'     => (string) ($guest->selected_package_code ?? ''),
            'docs_uploaded'    => (int) ($data['docsChecklistStats']['required_uploaded'] ?? 0),
            'docs_required'    => (int) ($data['docsChecklistStats']['required_total']    ?? count($data['requiredDocumentChecklist'] ?? [])),
            'progress_percent' => $this->viewData->calculateProfileCompletionPercent($guest),
        ];

        // AI Labs modülü açıksa gelişmiş RAG akışına geç (3-seviye + citation)
        $companyId = (int) ($guest->company_id ?? 0);
        if ($companyId > 0 && \App\Support\ModuleAccess::enabled('ai_labs', $companyId)) {
            $result = app(\App\Services\AiLabs\AiLabsAssistantService::class)
                ->ask($companyId, 'guest', 0, (int) $guest->id, $question, $context);
            return response()->json($result);
        }

        // Eski akış
        $result = app(AiGuestAssistantService::class)->ask($guest, $question, $context);

        return response()->json($result);
    }

    public function aiAssistantHistory(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return response()->json(['history' => []]);
        }

        $history = GuestAiConversation::where('guest_application_id', $guest->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'question', 'answer', 'created_at']);

        return response()->json(['history' => $history]);
    }

    public function aiAssistantRemaining(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);

        $companyId = (int) ($guest->company_id ?? 0);
        if ($companyId > 0 && \App\Support\ModuleAccess::enabled('ai_labs', $companyId) && $guest) {
            $labs = app(\App\Services\AiLabs\AiLabsAssistantService::class);
            return response()->json([
                'remaining' => $labs->remainingToday($companyId, 'guest', 0, (int) $guest->id),
                'limit'     => $labs->dailyLimit($companyId, 'guest'),
            ]);
        }

        $service   = app(AiGuestAssistantService::class);
        $remaining = $service->getRemainingToday($guest);
        $limit     = $service->getDailyLimit($guest);

        return response()->json(['remaining' => $remaining, 'limit' => $limit]);
    }

    // ── İnteraktif Başvuru Takvimi ───────────────────────────────────────────

    public function timeline(Request $request)
    {
        $guest = $this->resolveGuest($request);

        if (!$guest) {
            // Guest application yok → dashboard'daki açıklayıcı sayfaya yönlendir
            return redirect()->route('guest.dashboard');
        }

        $data  = $this->buildViewData($request, $guest);

        $timelineService = app(GuestTimelineService::class);
        $count           = GuestTimelineMilestone::where('guest_application_id', $guest->id)->count();
        if ($count < 22) {
            $timelineService->generateMilestones($guest);
        }
        // Retroaktif: daha önce yapılmış aksiyonların milestone'larını otomatik tamamla
        $timelineService->syncCompletions($guest);

        $data['milestones'] = GuestTimelineMilestone::where('guest_application_id', $guest->id)
            ->orderBy('sort_order')
            ->get();
        $data['milestoneProgress'] = $timelineService->computeProgress($guest);

        return view('guest.timeline', $data);
    }

    public function timelineExport(Request $request)
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            abort(404);
        }

        if (GuestTimelineMilestone::where('guest_application_id', $guest->id)->doesntExist()) {
            app(GuestTimelineService::class)->generateMilestones($guest);
        }

        $ics = app(GuestTimelineService::class)->exportIcs($guest);

        return response($ics)
            ->header('Content-Type', 'text/calendar; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename=mentorde-takvim.ics');
    }

    // ── Geri Bildirim ────────────────────────────────────────────────────────

    public function feedback(Request $request)
    {
        $guest    = $this->resolveGuest($request);
        $data     = $this->buildViewData($request, $guest);
        $existing = $guest
            ? GuestFeedback::where('guest_application_id', $guest->id)->latest('created_at')->get()
            : collect();

        $data['stepLabels'] = GuestFeedback::STEP_LABELS;
        $data['existing']   = $existing;

        return view('guest.feedback', $data);
    }

    // ── Banner Click ─────────────────────────────────────────────────────────

    public function bannerClick(int $id): \Illuminate\Http\JsonResponse
    {
        CmsContent::query()
            ->where('id', $id)
            ->where('category', 'guest_banner')
            ->increment('view_count');

        return response()->json(['ok' => true]);
    }
}
