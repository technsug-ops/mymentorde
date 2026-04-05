<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Senior\Concerns\SeniorPortalTrait;
use App\Models\SeniorAiConversation;
use App\Services\SeniorAiAssistantService;
use Illuminate\Http\Request;

class SeniorEngagementController extends Controller
{
    use SeniorPortalTrait;

    // ── AI Danışman Asistanı ─────────────────────────────────────────────────

    public function aiAssistantPage(Request $request)
    {
        $user      = $request->user();
        $studentId = trim((string) $request->query('student_id', ''));
        $stats     = $this->sidebarStats($request);
        $service   = app(SeniorAiAssistantService::class);

        return view('senior.ai-assistant', [
            'user'          => $user,
            'studentId'     => $studentId,
            'remaining'     => $service->getRemainingToday((int) $user->id),
            'limit'         => $service->getDailyLimit(),
            'sidebarStats'  => $stats,
        ]);
    }

    public function aiAssistantAsk(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['ok' => false, 'answer' => 'Oturum bulunamadı.'], 401);
        }

        $question = trim((string) $request->input('question', ''));
        if (mb_strlen($question) < 3) {
            return response()->json(['ok' => false, 'answer' => 'Lütfen bir soru yazın.'], 422);
        }

        $context = [
            'student_id' => trim((string) $request->input('student_id', '')),
        ];

        $result = app(SeniorAiAssistantService::class)->ask($user, $question, $context);

        return response()->json($result);
    }

    public function aiAssistantHistory(Request $request): \Illuminate\Http\JsonResponse
    {
        $user      = $request->user();
        $studentId = trim((string) $request->query('student_id', ''));

        $query = SeniorAiConversation::where('user_id', (int) $user->id)
            ->orderByDesc('created_at')
            ->limit(20);

        if ($studentId !== '') {
            $query->where('student_id', $studentId);
        }

        $history = $query->get(['id', 'student_id', 'question', 'answer', 'created_at']);

        return response()->json(['history' => $history]);
    }

    public function aiAssistantRemaining(Request $request): \Illuminate\Http\JsonResponse
    {
        $user    = $request->user();
        $service = app(SeniorAiAssistantService::class);

        return response()->json([
            'remaining' => $service->getRemainingToday((int) $user->id),
            'limit'     => $service->getDailyLimit(),
        ]);
    }
}
