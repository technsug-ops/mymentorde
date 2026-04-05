<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentWorkflowTrait;
use App\Models\KnowledgeBaseArticle;
use App\Models\StudentChecklist;
use App\Models\StudentMaterialRead;
use App\Models\StudentOnboardingStep;
use App\Services\StudentAchievementService;
use App\Services\StudentGuestResolver;
use Illuminate\Http\Request;

class StudentLearningController extends Controller
{
    use StudentWorkflowTrait;

    public function markMaterialRead(Request $request, KnowledgeBaseArticle $article)
    {
        $guest     = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        abort_if($studentId === '', 422, 'Student ID bulunamadi.');

        StudentMaterialRead::query()->updateOrCreate(
            [
                'student_id'                => $studentId,
                'knowledge_base_article_id' => (int) $article->id,
            ],
            [
                'company_id' => (int) ($guest->company_id ?: 1),
                'read_at'    => now(),
            ]
        );

        return redirect('/student/materials')->with('status', 'Materyal okundu olarak isaretlendi.');
    }

    public function toggleChecklist(Request $request, StudentChecklist $item): \Illuminate\Http\JsonResponse
    {
        $guest     = $this->resolveStudentGuest($request);
        $studentId = (string) ($request->user()?->student_id ?? $guest?->converted_student_id ?? '');
        abort_if($item->student_id !== $studentId, 403);

        $newState = ! $item->is_done;
        $item->update([
            'is_done' => $newState,
            'done_at' => $newState ? now() : null,
        ]);

        return response()->json(['ok' => true, 'is_done' => $item->is_done]);
    }

    public function completeOnboardingStep(Request $request, string $stepCode): \Illuminate\Http\JsonResponse
    {
        abort_unless(in_array($stepCode, StudentOnboardingStep::STEPS, true), 404);

        $user      = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $guest     = app(StudentGuestResolver::class)->resolveForUser($user);
            $studentId = trim((string) ($guest?->converted_student_id ?? ''));
        }
        abort_if($studentId === '', 422);

        StudentOnboardingStep::updateOrCreate(
            ['student_id' => $studentId, 'step_code' => $stepCode],
            ['completed_at' => now(), 'skipped_at' => null]
        );

        $companyId = (int) (app()->bound('current_company_id') ? app('current_company_id') : 1);
        app(StudentAchievementService::class)->checkAndAward($studentId, (string) $companyId);

        $remaining = collect(StudentOnboardingStep::STEPS)->filter(function ($code) use ($studentId) {
            $r = StudentOnboardingStep::where('student_id', $studentId)->where('step_code', $code)->first();
            return ! ($r?->isDone() ?? false);
        })->count();

        return response()->json(['ok' => true, 'remaining' => $remaining]);
    }

    public function skipOnboardingStep(Request $request, string $stepCode): \Illuminate\Http\JsonResponse
    {
        abort_unless(in_array($stepCode, StudentOnboardingStep::STEPS, true), 404);

        $user      = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $guest     = app(StudentGuestResolver::class)->resolveForUser($user);
            $studentId = trim((string) ($guest?->converted_student_id ?? ''));
        }
        abort_if($studentId === '', 422);

        StudentOnboardingStep::updateOrCreate(
            ['student_id' => $studentId, 'step_code' => $stepCode],
            ['skipped_at' => now(), 'completed_at' => null]
        );

        return response()->json(['ok' => true]);
    }
}
