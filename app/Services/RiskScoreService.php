<?php

namespace App\Services;

use App\Models\FieldRuleApproval;
use App\Models\InternalNote;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentRevenue;
use App\Models\StudentRiskScore;
use Carbon\Carbon;

class RiskScoreService
{
    public function calculate(?int $limit = null): array
    {
        $limit = max(1, (int) ($limit ?? 200));
        $rows = StudentAssignment::query()
            ->where('is_archived', false)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $updated = 0;
        foreach ($rows as $row) {
            $this->calculateForStudent((string) $row->student_id);
            $updated++;
        }

        return ['processed' => $rows->count(), 'updated' => $updated];
    }

    public function calculateForStudent(string $studentId): StudentRiskScore
    {
        $assignment = StudentAssignment::query()->where('student_id', $studentId)->first();
        if (!$assignment) {
            abort(404, 'Student assignment bulunamadi.');
        }

        $factors = [];
        $score = 0;
        $now = Carbon::now();

        if (empty($assignment->senior_email)) {
            $factors[] = ['factor' => 'no_senior', 'points' => 15, 'description' => 'Senior atamasi yok'];
            $score += 15;
        }

        if ((string) ($assignment->payment_status ?? 'ok') !== 'ok') {
            $factors[] = ['factor' => 'payment_issue', 'points' => 20, 'description' => 'Odeme durumu sorunlu'];
            $score += 20;
        }

        $pendingApprovals = (int) FieldRuleApproval::query()
            ->where('student_id', $studentId)
            ->where('status', 'pending')
            ->count();
        if ($pendingApprovals > 0) {
            $points = min(30, $pendingApprovals * 10);
            $factors[] = ['factor' => 'pending_approvals', 'points' => $points, 'description' => "Pending approval: {$pendingApprovals}"];
            $score += $points;
        }

        $overdueOutcomes = (int) ProcessOutcome::query()
            ->where('student_id', $studentId)
            ->where('is_visible_to_student', false)
            ->whereNotNull('deadline')
            ->where('deadline', '<', $now)
            ->count();
        if ($overdueOutcomes > 0) {
            $points = min(30, $overdueOutcomes * 10);
            $factors[] = ['factor' => 'overdue_outcomes', 'points' => $points, 'description' => "Geciken outcome: {$overdueOutcomes}"];
            $score += $points;
        }

        $recentNoteExists = InternalNote::query()
            ->where('student_id', $studentId)
            ->where('created_at', '>=', $now->copy()->subDays(14))
            ->exists();
        if (!$recentNoteExists) {
            $factors[] = ['factor' => 'no_recent_note', 'points' => 10, 'description' => 'Son 14 gunde not yok'];
            $score += 10;
        }

        $pendingAmount = (float) StudentRevenue::query()
            ->where('student_id', $studentId)
            ->value('total_pending');
        if ($pendingAmount > 0) {
            $points = min(20, max(5, (int) floor($pendingAmount / 250)));
            $factors[] = ['factor' => 'pending_amount', 'points' => $points, 'description' => "Acik tahsilat: {$pendingAmount}"];
            $score += $points;
        }

        $score = max(0, min(100, $score));
        $level = $this->levelFromScore($score);

        $existing = StudentRiskScore::query()->where('student_id', $studentId)->first();
        $history = collect($existing?->history ?? [])->values()->all();
        $history[] = [
            'date' => $now->toDateTimeString(),
            'score' => $score,
            'level' => $level,
        ];
        if (count($history) > 30) {
            $history = array_slice($history, -30);
        }

        $risk = StudentRiskScore::query()->updateOrCreate(
            ['student_id' => $studentId],
            [
                'current_score' => $score,
                'risk_level' => $level,
                'factors' => $factors,
                'last_calculated_at' => $now,
                'history' => $history,
            ]
        );

        $assignment->update(['risk_level' => $level]);

        return $risk->fresh();
    }

    private function levelFromScore(int $score): string
    {
        if ($score >= 61) {
            return 'critical';
        }
        if ($score >= 41) {
            return 'high';
        }
        if ($score >= 21) {
            return 'medium';
        }

        return 'low';
    }
}

