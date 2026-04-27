<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\DealerPayoutRequest;
use App\Models\DealerRevenueMilestone;
use App\Models\DealerStudentRevenue;
use App\Support\ModuleAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dealer revenue tracking servisi.
 *
 * Modül guard: `dealer` modülü kapalı şirketlerde initialize/trigger/sync
 * entry method'ları no-op döner. Caller'ın `if (ModuleAccess::enabled('dealer'))`
 * ile sarmasına gerek yok — service kendisi self-aware. Stripe webhook,
 * guest→student conversion ve milestone akışları modül kapalı bile olsa
 * exception fırlatmadan devam eder.
 */
class DealerRevenueService
{
    public function getActiveMilestones(): Collection
    {
        return DealerRevenueMilestone::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function initializeDealerStudentRevenue(string $dealerId, string $studentId, string $dealerType): ?DealerStudentRevenue
    {
        if (!ModuleAccess::enabled('dealer')) {
            return null;
        }

        $milestones = $this->getActiveMilestones()->filter(function (DealerRevenueMilestone $m) use ($dealerType) {
            $types = $m->applicable_dealer_types ?? [];
            return empty($types) || in_array($dealerType, $types, true);
        });

        $progress = $milestones->map(function (DealerRevenueMilestone $m): array {
            return [
                'milestone_id' => $m->external_id,
                'status' => 'pending',
                'calculated_amount' => 0,
                'currency' => $m->fixed_currency ?: 'EUR',
                'triggered_at' => null,
            ];
        })->values()->all();

        return DealerStudentRevenue::query()->updateOrCreate(
            ['dealer_id' => $dealerId, 'student_id' => $studentId],
            [
                'dealer_type' => $dealerType,
                'milestone_progress' => $progress,
                'total_earned' => 0,
                'total_pending' => 0,
            ]
        );
    }

    /**
     * StudentRevenue trigger event'i ile aynı eventType için tüm dealer milestone'larını tetikle.
     */
    public function triggerMilestonesForStudent(string $studentId, string $eventType): void
    {
        if (!ModuleAccess::enabled('dealer')) {
            return;
        }

        $dsRevs = DealerStudentRevenue::query()->where('student_id', $studentId)->get();
        if ($dsRevs->isEmpty()) {
            return;
        }

        $triggerableIds = DealerRevenueMilestone::query()
            ->where('is_active', true)
            ->where('trigger_type', $eventType)
            ->pluck('external_id')
            ->flip();

        if ($triggerableIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($dsRevs, $triggerableIds): void {
            foreach ($dsRevs as $dsRev) {
                $progress = collect($dsRev->milestone_progress ?? [])
                    ->map(function (array $item) use ($triggerableIds): array {
                        if (($item['status'] ?? 'pending') === 'pending'
                            && $triggerableIds->has($item['milestone_id'] ?? '')) {
                            $item['status'] = 'triggered';
                            $item['triggered_at'] = now()->toIso8601String();
                        }
                        return $item;
                    })->values()->all();

                $dsRev->milestone_progress = $progress;
                $this->recalculateDealerTotals($dsRev);
                $dsRev->save();
            }
        });
    }

    /**
     * Student milestone 'paid' olduğunda ilgili dealer milestone'larını da paid yap,
     * tutarları hesapla ve gerekirse otomatik DealerPayoutRequest oluştur.
     */
    public function syncMilestonePaidForStudent(
        string $studentId,
        string $milestoneExternalId,
        float $packageTotal
    ): void {
        if (!ModuleAccess::enabled('dealer')) {
            return;
        }

        $dsRevs = DealerStudentRevenue::query()->where('student_id', $studentId)->get();
        if ($dsRevs->isEmpty()) {
            return;
        }

        $milestone = DealerRevenueMilestone::query()
            ->where('external_id', $milestoneExternalId)
            ->where('is_active', true)
            ->first();

        DB::transaction(function () use ($dsRevs, $milestoneExternalId, $milestone, $packageTotal): void {
            foreach ($dsRevs as $dsRev) {
                $progress = collect($dsRev->milestone_progress ?? [])
                    ->map(function (array $item) use ($milestoneExternalId, $milestone, $packageTotal): array {
                        if (($item['milestone_id'] ?? '') !== $milestoneExternalId) {
                            return $item;
                        }
                        if (($item['status'] ?? 'pending') === 'paid') {
                            return $item;
                        }

                        $calculated = $milestone
                            ? $this->calculateAmount(
                                (string) $milestone->revenue_type,
                                $milestone->percentage !== null ? (float) $milestone->percentage : null,
                                $milestone->fixed_amount !== null ? (float) $milestone->fixed_amount : null,
                                $packageTotal
                            )
                            : 0.0;

                        $item['status'] = 'paid';
                        $item['calculated_amount'] = $calculated;
                        $item['paid_at'] = now()->toIso8601String();
                        return $item;
                    })->values()->all();

                $dsRev->milestone_progress = $progress;
                $this->recalculateDealerTotals($dsRev);
                $dsRev->save();
            }
        });

        // Auto-payout checks run after transaction commits
        foreach ($dsRevs as $dsRev) {
            $dsRev->refresh();
            $this->checkAndCreateAutoPayoutRequest($dsRev);
        }
    }

    private function recalculateDealerTotals(DealerStudentRevenue $dsRev): void
    {
        $progress = collect($dsRev->milestone_progress ?? []);
        $dsRev->total_earned = $progress->where('status', 'paid')->sum('calculated_amount');
        $dsRev->total_pending = $progress->whereIn('status', ['triggered', 'confirmed'])->sum('calculated_amount');
    }

    private function calculateAmount(
        string $type,
        ?float $percentage,
        ?float $fixed,
        float $packageTotal
    ): float {
        return match ($type) {
            'percentage' => round($packageTotal * ((float) $percentage / 100), 2),
            'fixed'      => round((float) $fixed, 2),
            'hybrid'     => round(($packageTotal * ((float) $percentage / 100)) + (float) $fixed, 2),
            default      => 0.0,
        };
    }

    /**
     * Tüm dealer milestone'ları paid olduğunda otomatik DealerPayoutRequest oluştur.
     */
    private function checkAndCreateAutoPayoutRequest(DealerStudentRevenue $dsRev): void
    {
        $progress = collect($dsRev->milestone_progress ?? []);
        if ($progress->isEmpty()) {
            return;
        }
        if ($progress->where('status', '!=', 'paid')->isNotEmpty()) {
            return;
        }
        if ((float) $dsRev->total_earned <= 0) {
            return;
        }

        $dealer = Dealer::query()->where('id', $dsRev->dealer_id)->first();
        if (!$dealer) {
            return;
        }

        $hasOpenRequest = DealerPayoutRequest::query()
            ->where('dealer_code', $dealer->code)
            ->whereIn('status', ['requested', 'approved'])
            ->exists();

        if ($hasOpenRequest) {
            return;
        }

        DealerPayoutRequest::query()->create([
            'dealer_code'          => $dealer->code,
            'amount'               => $dsRev->total_earned,
            'currency'             => 'EUR',
            'status'               => 'requested',
            'requested_by_email'   => 'system',
        ]);
    }
}

