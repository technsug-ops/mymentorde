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

        $firstName = explode(' ', trim((string) ($user->name ?? '')))[0] ?? '';
        $context = [
            'first_name'    => $firstName,
            'full_name'     => (string) ($user->name ?? ''),
            'email'         => (string) ($user->email ?? ''),
            'student_id'    => trim((string) $request->input('student_id', '')),
        ];

        // AI Labs modülü açıksa gelişmiş RAG akışına geç
        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId > 0 && \App\Support\ModuleAccess::enabled('ai_labs', $companyId)) {
            $result = app(\App\Services\AiLabs\AiLabsAssistantService::class)
                ->ask($companyId, 'senior', (int) $user->id, null, $question, $context);
            return response()->json($result);
        }

        // Eski akış
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
        $user = $request->user();

        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId > 0 && \App\Support\ModuleAccess::enabled('ai_labs', $companyId)) {
            $labs = app(\App\Services\AiLabs\AiLabsAssistantService::class);
            return response()->json([
                'remaining' => $labs->remainingToday($companyId, 'senior', (int) $user->id, null),
                'limit'     => $labs->dailyLimit($companyId, 'senior'),
            ]);
        }

        $service = app(SeniorAiAssistantService::class);
        return response()->json([
            'remaining' => $service->getRemainingToday((int) $user->id),
            'limit'     => $service->getDailyLimit(),
        ]);
    }
}
