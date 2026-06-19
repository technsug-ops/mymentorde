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

        $row = DealerStudentRevenue::query()->updateOrCreate(
            ['dealer_id' => $dealerId, 'student_id' => $studentId],
            [
                'dealer_type' => $dealerType,
                'is_override' => false,
                'milestone_progress' => $progress,
                'total_earned' => 0,
                'total_pending' => 0,
            ]
        );

        // Getiriyi alt bayi yaptıysa bölge bayisi override satırını da hazırla.
        $this->syncOverrideForStudent($studentId);

        return $row;
    }

    /**
     * StudentRevenue trigger event'i ile aynı eventType için tüm dealer milestone'larını tetikle.
     */
    public function triggerMilestonesForStudent(string $studentId, string $eventType): void
    {
        if (!ModuleAccess::enabled('dealer')) {
            return;
        }

        // Override satırları ayrı yönetilir (syncOverrideForStudent) — burada hariç.
        $dsRevs = DealerStudentRevenue::query()
            ->where('student_id', $studentId)
            ->where('is_override', false)
            ->get();
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

        // Alt bayilerin getirisi değişti — bölge bayisi override satırlarını senkronla.
        $this->syncOverrideForStudent($studentId);
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

        // Override satırları ayrı yönetilir (syncOverrideForStudent) — burada hariç.
        $dsRevs = DealerStudentRevenue::query()
            ->where('student_id', $studentId)
            ->where('is_override', false)
            ->get();
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

        // Alt bayilerin getirisi değişti — bölge bayisi override satırlarını senkronla.
        $this->syncOverrideForStudent($studentId);

        // Auto-payout checks run after transaction commits — normal + override satırları.
        $allRevs = DealerStudentRevenue::query()->where('student_id', $studentId)->get();
        foreach ($allRevs as $dsRev) {
            $this->checkAndCreateAutoPayoutRequest($dsRev);
        }
    }

    /**
     * Bir öğrenciyi ALT bayi getirdiyse, bölge bayisi (parent) için override
     * DealerStudentRevenue satırını oluştur/güncelle. Override tutarı alt bayinin
     * toplamından türetilir (mirror milestone yok):
     *   - percent_of_sub: alt bayi earned/pending × override_rate_percent
     *   - fixed_eur:      öğrenci başına sabit override_rate_eur (alt earned>0 ise earned)
     */
    private function syncOverrideForStudent(string $studentId): void
    {
        $subRevs = DealerStudentRevenue::query()
            ->where('student_id', $studentId)
            ->where('is_override', false)
            ->get();

        foreach ($subRevs as $subRev) {
            $sub = Dealer::query()->where('code', $subRev->dealer_id)->first();
            if (!$sub || !$sub->parent_dealer_id) {
                continue; // sadece ALT bayilerin getirisi override üretir
            }
            $regional = $sub->parent;
            if (!$regional) {
                continue;
            }

            $basis   = (string) ($regional->override_basis ?: 'percent_of_sub');
            $earned  = 0.0;
            $pending = 0.0;

            if ($basis === 'fixed_eur') {
                $fixed   = (float) ($regional->override_rate_eur ?? 0);
                $earned  = ((float) $subRev->total_earned) > 0 ? $fixed : 0.0;
                $pending = ($earned <= 0 && ((float) $subRev->total_pending) > 0) ? $fixed : 0.0;
            } else { // percent_of_sub (varsayılan)
                $pct     = (float) ($regional->override_rate_percent ?? 0) / 100;
                $earned  = round(((float) $subRev->total_earned) * $pct, 2);
                $pending = round(((float) $subRev->total_pending) * $pct, 2);
            }

            // Override yapılandırılmamışsa (oran 0) satır oluşturma — gürültü olmasın.
            if ($earned <= 0 && $pending <= 0) {
                continue;
            }

            $status = $pending > 0 ? 'triggered' : 'paid';

            DealerStudentRevenue::query()->updateOrCreate(
                ['dealer_id' => $regional->code, 'student_id' => $studentId],
                [
                    'origin_dealer_id'   => $sub->code,
                    'is_override'        => true,
                    'dealer_type'        => (string) ($regional->dealer_type_code ?? $subRev->dealer_type),
                    'milestone_progress' => [[
                        'milestone_id'      => 'override',
                        'status'            => $status,
                        'calculated_amount' => $earned > 0 ? $earned : $pending,
                        'currency'          => 'EUR',
                        'origin'            => $sub->code,
                    ]],
                    'total_earned'  => $earned,
                    'total_pending' => $pending,
                ]
            );
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

        // dealer_id CODE tutar (id değil) — code ile eşle. Bu hem normal hem
        // override satırlarda doğru bayiye gider.
        $dealer = Dealer::query()->where('code', $dsRev->dealer_id)->first();
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

