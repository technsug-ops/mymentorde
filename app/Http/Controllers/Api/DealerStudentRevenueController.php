<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DealerStudentRevenue;
use App\Services\DealerRevenueService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DealerStudentRevenueController extends Controller
{
    public function __construct(private readonly DealerRevenueService $service)
    {
    }

    public function show(string $dealerId, string $studentId)
    {
        return DealerStudentRevenue::where('dealer_id', $dealerId)
            ->where('student_id', $studentId)
            ->firstOrFail();
    }

    public function initialize(Request $request)
    {
        $data = $request->validate([
            'dealer_id' => ['required', 'string', 'max:64'],
            'student_id' => ['required', 'string', 'max:64'],
            'dealer_type' => ['required', 'string', 'max:64'],
        ]);

        $result = $this->service->initializeDealerStudentRevenue(
            $data['dealer_id'],
            $data['student_id'],
            $data['dealer_type']
        );

        if ($result === null) {
            return response()->json(['error' => 'dealer_module_disabled'], Response::HTTP_FORBIDDEN);
        }

        return response()->json($result, Response::HTTP_CREATED);
    }
}
