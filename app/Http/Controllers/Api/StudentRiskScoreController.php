<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentRiskScore;
use App\Services\RiskScoreService;
use Illuminate\Http\Request;

class StudentRiskScoreController extends Controller
{
    public function index(Request $request)
    {
        $studentId = trim((string) $request->query('student_id', ''));
        $level = trim((string) $request->query('risk_level', ''));

        return StudentRiskScore::query()
            ->when($studentId !== '', fn ($q) => $q->where('student_id', 'like', "%{$studentId}%"))
            ->when($level !== '', fn ($q) => $q->where('risk_level', $level))
            ->orderByDesc('current_score')
            ->limit(300)
            ->get();
    }

    public function calculateNow(Request $request, RiskScoreService $service)
    {
        $data = $request->validate([
            'student_id' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $studentId = trim((string) ($data['student_id'] ?? ''));
        if ($studentId !== '') {
            return response()->json($service->calculateForStudent($studentId));
        }

        return response()->json($service->calculate((int) ($data['limit'] ?? 200)));
    }
}

