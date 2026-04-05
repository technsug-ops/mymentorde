<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FieldRuleApproval;
use Illuminate\Http\Request;

class FieldRuleApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $studentId = $request->query('student_id');

        return FieldRuleApproval::query()
            ->with('rule')
            ->when($status, fn ($q) => $q->where('status', (string) $status))
            ->when($studentId, fn ($q) => $q->where('student_id', (string) $studentId))
            ->latest()
            ->limit(200)
            ->get();
    }

    public function bulkArchive(Request $request)
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'student_id' => ['nullable', 'string', 'max:64'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ]);

        $status = (string) ($data['status'] ?? 'pending');
        $studentId = $data['student_id'] ?? null;
        $ids = $data['ids'] ?? [];

        $query = FieldRuleApproval::query()
            ->where('status', $status)
            ->when($studentId, fn ($q) => $q->where('student_id', (string) $studentId))
            ->when(!empty($ids), fn ($q) => $q->whereIn('id', $ids));

        $affected = $query->update([
            'status' => 'archived',
            'approved_by' => (string) optional($request->user())->email,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'affected' => $affected,
            'status' => $status,
            'student_id' => $studentId,
        ]);
    }

    public function bulkCleanup(Request $request)
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'student_id' => ['nullable', 'string', 'max:64'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ]);

        $status = (string) ($data['status'] ?? 'pending');
        $studentId = $data['student_id'] ?? null;
        $ids = $data['ids'] ?? [];

        $query = FieldRuleApproval::query()
            ->where('status', $status)
            ->when($studentId, fn ($q) => $q->where('student_id', (string) $studentId))
            ->when(!empty($ids), fn ($q) => $q->whereIn('id', $ids));

        $affected = $query->delete();

        return response()->json([
            'affected' => $affected,
            'status' => $status,
            'student_id' => $studentId,
        ]);
    }

    public function approve(FieldRuleApproval $fieldRuleApproval, Request $request)
    {
        $fieldRuleApproval->update([
            'status' => 'approved',
            'approved_by' => (string) optional($request->user())->email,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json($fieldRuleApproval->fresh());
    }

    public function reject(FieldRuleApproval $fieldRuleApproval, Request $request)
    {
        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string'],
        ]);

        $fieldRuleApproval->update([
            'status' => 'rejected',
            'approved_by' => (string) optional($request->user())->email,
            'approved_at' => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        return response()->json($fieldRuleApproval->fresh());
    }
}
