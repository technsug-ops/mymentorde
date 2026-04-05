<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcessOutcome;
use App\Services\ProcessOutcomeService;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProcessOutcomeController extends Controller
{
    public function showForStudent(ProcessOutcome $processOutcome)
    {
        return response()->json([
            'id' => $processOutcome->id,
            'student_id' => $processOutcome->student_id,
            'process_step' => $processOutcome->process_step,
            'outcome_type' => $processOutcome->outcome_type,
            'university' => $processOutcome->university,
            'program' => $processOutcome->program,
            'details_tr' => $processOutcome->details_tr,
            'conditions' => $processOutcome->conditions,
            'deadline' => optional($processOutcome->deadline)?->toDateString(),
            'created_at' => optional($processOutcome->created_at)?->toDateTimeString(),
        ]);
    }

    public function index(Request $request)
    {
        $studentId = $request->query('student_id');

        return ProcessOutcome::query()
            ->when($studentId, fn ($q) => $q->where('student_id', (string) $studentId))
            ->latest()
            ->limit(100)
            ->get();
    }

    public function store(Request $request, TaskAutomationService $taskAutomation)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'application_id' => ['nullable', 'string', 'max:64'],
            'process_step' => ['required', 'string', 'max:64'],
            'outcome_type' => ['required', 'in:acceptance,rejection,correction_request,conditional_acceptance,waitlist'],
            'university' => ['nullable', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'document_id' => ['nullable', 'integer'],
            'details_tr' => ['required', 'string'],
            'details_de' => ['nullable', 'string'],
            'details_en' => ['nullable', 'string'],
            'conditions' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
        ]);

        $data['added_by'] = (string) optional($request->user())->email;

        $row = ProcessOutcome::create($data);
        $taskAutomation->ensureStudentOutcomeTask($row);

        return response()->json($row, Response::HTTP_CREATED);
    }

    public function makeVisible(Request $request, ProcessOutcome $processOutcome, ProcessOutcomeService $service)
    {
        $actor = (string) optional($request->user())->email;
        return response()->json($service->makeVisibleToStudent($processOutcome, $actor));
    }
}
