<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentPortalTrait;
use App\Models\GuestAiConversation;
use App\Services\AiGuestAssistantService;
use App\Services\StudentGuestResolver;
use Illuminate\Http\Request;

class StudentEngagementController extends Controller
{
    use StudentPortalTrait;

    public function aiAssistantPage(Request $request)
    {
        $base = $this->baseData($request, 'ai-assistant', 'AI Asistan', 'Almanya eğitim sürecinizde size yardımcı olan yapay zeka asistanı.');
        return view('student.ai-assistant', $base);
    }

    public function aiAssistantAsk(Request $request): \Illuminate\Http\JsonResponse
    {
        $user  = $request->user();
        $guest = app(StudentGuestResolver::class)->resolveForUser($user);

        if (!$guest) {
            return response()->json(['ok' => false, 'answer' => 'Başvuru kaydınıza erişilemiyor.'], 401);
        }

        $question = trim((string) $request->input('question', ''));
        if (mb_strlen($question) < 3) {
            return response()->json(['ok' => false, 'answer' => 'Lütfen bir soru yazın.'], 422);
        }

        $context = [
            'first_name'       => trim((string) ($guest->first_name ?? $user->name ?? '')),
            'full_name'        => trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')) ?: (string) ($user->name ?? ''),
            'email'            => (string) ($user->email ?? $guest->email ?? ''),
            'student_id'       => trim((string) ($user->student_id ?? $guest->converted_student_id ?? '')),
            'application_type' => (string) ($guest->application_type ?? ''),
            'target_city'      => (string) ($guest->target_city ?? ''),
            'package_code'     => (string) ($guest->selected_package_code ?? ''),
        ];

        // AI Labs modülü açıksa gelişmiş RAG akışına geç (3-seviye + citation)
        $companyId = (int) ($guest->company_id ?? 0);
        if ($companyId > 0 && \App\Support\ModuleAccess::enabled('ai_labs', $companyId)) {
            $result = app(\App\Services\AiLabs\AiLabsAssistantService::class)
                ->ask($companyId, 'student', (int) ($user->id ?? 0), (int) $guest->id, $question, $context);
            return response()->json($result);
        }

        // Eski akış
        $service = app(AiGuestAssistantService::class);
        $result = $service->ask($guest, $question, $context);

        return response()->json($result);
    }

    public function aiAssistantHistory(Request $request): \Illuminate\Http\JsonResponse
    {
        $user  = $request->user();
        $guest = app(StudentGuestResolver::class)->resolveForUser($user);

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
        $user  = $request->user();
        $guest = app(StudentGuestResolver::class)->resolveForUser($user);

        $companyId = (int) ($guest->company_id ?? 0);
        if ($companyId > 0 && \App\Support\ModuleAccess::enabled('ai_labs', $companyId) && $guest) {
            $labs = app(\App\Services\AiLabs\AiLabsAssistantService::class);
            return response()->json([
                'remaining' => $labs->remainingToday($companyId, 'student', (int) ($user->id ?? 0), (int) $guest->id),
                'limit'     => $labs->dailyLimit($companyId, 'student'),
            ]);
        }

        $service = app(AiGuestAssistantService::class);
        return response()->json([
            'remaining' => $service->getRemainingToday($guest),
            'limit'     => $service->getDailyLimit($guest),
        ]);
    }
}
