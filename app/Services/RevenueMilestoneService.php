<?php

namespace App\Services;

use App\Models\RevenueMilestone;
use App\Models\StudentRevenue;
use Illuminate\Support\Collection;

class RevenueMilestoneService
{
    public function getActiveMilestones(): Collection
    {
        return RevenueMilestone::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function initializeStudentRevenue(
        string $studentId,
        ?string $packageId,
        float $packageTotalPrice,
        string $currency = 'EUR'
    ): StudentRevenue {
        $milestones = $this->getActiveMilestones();
        $progress = $milestones->map(function (RevenueMilestone $m) use ($packageTotalPrice, $currency) {
            return [
                'milestone_id' => $m->external_id,
                'milestone_name' => $m->name_tr,
                'status' => 'pending',
                'revenue_type' => $m->revenue_type,
                'calculated_amount' => $this->calculateAmount($m->revenue_type, $m->percentage, $m->fixed_amount, $packageTotalPrice),
                'currency' => $m->fixed_currency ?: $currency,
                'triggered_at' => null,
                'confirmed_by' => null,
                'confirmed_at' => null,
                'paid_at' => null,
                'note' => null,
            ];
        })->values()->all();

        $totalRemaining = collect($progress)->sum('calculated_amount');

        return StudentRevenue::updateOrCreate(
            ['student_id' => $studentId],
            [
                'package_id' => $packageId,
                'package_total_price' => $packageTotalPrice,
                'package_currency' => $currency,
                'milestone_progress' => $progress,
                'total_earned' => 0,
                'total_pending' => 0,
                'total_remaining' => $totalRemaining,
            ]
        );
    }

    public function checkAndTriggerMilestones(string $studentId, string $eventType, array $eventData): StudentRevenue
    {
        $studentRevenue = StudentRevenue::where('student_id', $studentId)->firstOrFail();
        $progress = collect($studentRevenue->milestone_progress ?? []);
        $milestones = $this->getActiveMilestones()->keyBy('external_id');

        $progress = $progress->map(function (array $item) use ($milestones, $eventType, $eventData) {
            if (($item['status'] ?? 'pending') !== 'pending') {
                return $item;
            }

            $milestone = $milestones->get($item['milestone_id'] ?? '');
            if (!$milestone) {
                return $item;
            }

            if ($this->conditionMet($milestone->trigger_type, $milestone->trigger_condition ?? [], $eventType, $eventData)) {
                $item['status'] = 'triggered';
                $item['triggered_at'] = now()->toIso8601String();
            }

            return $item;
        })->values();

        $studentRevenue->milestone_progress = $progress->all();
        $this->recalculateTotals($studentRevenue);
        $studentRevenue->save();

        return $studentRevenue;
    }

    public function confirmMilestone(string $studentId, string $milestoneId, ?string $confirmedBy = null): StudentRevenue
    {
        $studentRevenue = StudentRevenue::where('student_id', $studentId)->firstOrFail();
        $progress = collect($studentRevenue->milestone_progress ?? []);

        $progress = $progress->map(function (array $item) use ($milestoneId, $confirmedBy) {
            if (($item['milestone_id'] ?? '') !== $milestoneId) {
                return $item;
            }

            if (!in_array($item['status'] ?? 'pending', ['triggered', 'confirmed'], true)) {
                return $item;
            }

            $item['status'] = 'confirmed';
            $item['confirmed_by'] = $confirmedBy;
            $item['confirmed_at'] = now()->toIso8601String();
            return $item;
        })->values();

        $studentRevenue->milestone_progress = $progress->all();
        $this->recalculateTotals($studentRevenue);
        $studentRevenue->save();

        return $studentRevenue;
    }

    public function markMilestonePaid(string $studentId, string $milestoneId): StudentRevenue
    {
        $studentRevenue = StudentRevenue::where('student_id', $studentId)->firstOrFail();
        $progress = collect($studentRevenue->milestone_progress ?? []);

        $progress = $progress->map(function (array $item) use ($milestoneId) {
            if (($item['milestone_id'] ?? '') !== $milestoneId) {
                return $item;
            }

            if (!in_array($item['status'] ?? 'pending', ['confirmed', 'paid'], true)) {
                return $item;
            }

            $item['status'] = 'paid';
            $item['paid_at'] = now()->toIso8601String();
            return $item;
        })->values();

        $studentRevenue->milestone_progress = $progress->all();
        $this->recalculateTotals($studentRevenue);
        $studentRevenue->save();

        return $studentRevenue;
    }

    private function conditionMet(string $triggerType, array $condition, string $eventType, array $eventData): bool
    {
        if ($triggerType !== $eventType) {
            return false;
        }

        if ($triggerType === 'manual') {
            return true;
        }

        $field = $condition['field'] ?? null;
        if (!$field) {
            return true;
        }

        $expected = $condition['value'] ?? null;
        return ($eventData[$field] ?? null) === $expected;
    }

    private function calculateAmount(string $type, ?float $percentage, ?float $fixed, float $packageTotal): float
    {
        return match ($type) {
            'percentage' => round($packageTotal * ((float) $percentage / 100), 2),
            'fixed' => round((float) $fixed, 2),
            'hybrid' => round(($packageTotal * ((float) $percentage / 100)) + (float) $fixed, 2),
            default => 0.0,
        };
    }

    private function recalculateTotals(StudentRevenue $studentRevenue): void
    {
        $progress = collect($studentRevenue->milestone_progress ?? []);
        $earned = $progress->where('status', 'paid')->sum('calculated_amount');
        $pending = $progress->whereIn('status', ['triggered', 'confirmed'])->sum('calculated_amount');
        $remaining = $progress->where('status', 'pending')->sum('calculated_amount');

        $studentRevenue->total_earned = $earned;
        $studentRevenue->total_pending = $pending;
        $studentRevenue->total_remaining = $remaining;
    }
}
