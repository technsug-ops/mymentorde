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
            'docs_uploaded'    => (int) ($data['docsChecklistStats']['required_uploaded'] ?? 0),
            'docs_required'    => (int) ($data['docsChecklistStats']['required_total']    ?? count($data['requiredDocumentChecklist'] ?? [])),
            'progress_percent' => $this->viewData->calculateProfileCompletionPercent($guest),
        ];

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
        $guest     = $this->resolveGuest($request);
        $service   = app(AiGuestAssistantService::class);
        $remaining = $service->getRemainingToday($guest);
        $limit     = $service->getDailyLimit($guest);

        return response()->json(['remaining' => $remaining, 'limit' => $limit]);
    }

    // ── İnteraktif Başvuru Takvimi ───────────────────────────────────────────

    public function timeline(Request $request)
    {
        $guest = $this->resolveGuest($request);
        $data  = $this->buildViewData($request, $guest);

        if ($guest) {
            $timelineService = app(GuestTimelineService::class);
            $count           = GuestTimelineMilestone::where('guest_application_id', $guest->id)->count();
            if ($count === 0) {
                $timelineService->generateMilestones($guest);
            }
            // Retroaktif: daha önce yapılmış aksiyonların milestone'larını otomatik tamamla
            $timelineService->syncCompletions($guest);

            $data['milestones'] = GuestTimelineMilestone::where('guest_application_id', $guest->id)
                ->orderBy('sort_order')
                ->get();
        } else {
            $data['milestones'] = collect();
        }

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
