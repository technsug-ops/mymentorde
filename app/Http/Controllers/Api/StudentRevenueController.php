<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentRevenue;
use App\Services\DealerRevenueService;
use App\Services\RevenueMilestoneService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentRevenueController extends Controller
{
    public function __construct(private readonly RevenueMilestoneService $service)
    {
    }

    public function show(string $studentId)
    {
        return StudentRevenue::where('student_id', $studentId)->firstOrFail();
    }

    public function initialize(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'package_id' => ['nullable', 'string', 'max:64'],
            'package_total_price' => ['required', 'numeric', 'min:0'],
            'package_currency' => ['nullable', 'string', 'max:8'],
        ]);

        $result = $this->service->initializeStudentRevenue(
            $data['student_id'],
            $data['package_id'] ?? null,
            (float) $data['package_total_price'],
            $data['package_currency'] ?? 'EUR'
        );

        return response()->json($result, Response::HTTP_CREATED);
    }

    public function trigger(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'event_type' => ['required', 'string', 'max:64'],
            'event_data' => ['nullable', 'array'],
        ]);

        $result = $this->service->checkAndTriggerMilestones(
            $data['student_id'],
            $data['event_type'],
            $data['event_data'] ?? []
        );

        try {
            app(DealerRevenueService::class)->triggerMilestonesForStudent(
                $data['student_id'],
                $data['event_type']
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return $result;
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'milestone_id' => ['required', 'string', 'max:64'],
        ]);

        $by = (string) optional($request->user())->email;

        return $this->service->confirmMilestone(
            $data['student_id'],
            $data['milestone_id'],
            $by
        );
    }

    public function pay(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:64'],
            'milestone_id' => ['required', 'string', 'max:64'],
        ]);

        $result = $this->service->markMilestonePaid(
            $data['student_id'],
            $data['milestone_id']
        );

        $sr = StudentRevenue::query()->where('student_id', $data['student_id'])->first();
        try {
            app(DealerRevenueService::class)->syncMilestonePaidForStudent(
                $data['student_id'],
                $data['milestone_id'],
                (float) ($sr->package_total_price ?? 0)
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return $result;
    }
}
