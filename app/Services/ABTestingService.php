<?php

namespace App\Services;

use App\Models\ABTest;
use App\Models\ABTestAssignment;
use App\Models\ABTestVariant;
use App\Models\NotificationDispatch;

class ABTestingService
{
    public function assignVariant(int $testId, int|string $guestId): ?string
    {
        $test = ABTest::find($testId);
        if (! $test || ! $test->isRunning()) {
            return null;
        }

        // Sticky — return existing assignment
        $existing = ABTestAssignment::where('ab_test_id', $testId)
            ->where('guest_application_id', $guestId)
            ->first();
        if ($existing) {
            return $existing->variant_code;
        }

        // Assign based on traffic split
        $split    = $test->traffic_split; // e.g. {"A":50,"B":50}
        $rand     = rand(1, 100);
        $cumulative = 0;
        $chosen   = array_key_first($split);

        foreach ($split as $variant => $pct) {
            $cumulative += $pct;
            if ($rand <= $cumulative) {
                $chosen = $variant;
                break;
            }
        }

        ABTestAssignment::create([
            'ab_test_id'           => $testId,
            'guest_application_id' => $guestId,
            'variant_code'         => $chosen,
            'assigned_at'          => now(),
        ]);

        // Increment impressions
        ABTestVariant::where('ab_test_id', $testId)
            ->where('variant_code', $chosen)
            ->increment('impressions');

        return $chosen;
    }

    public function recordConversion(int $testId, int|string $guestId): bool
    {
        $assignment = ABTestAssignment::where('ab_test_id', $testId)
            ->where('guest_application_id', $guestId)
            ->where('converted', false)
            ->first();

        if (! $assignment) {
            return false;
        }

        $assignment->update([
            'converted'    => true,
            'converted_at' => now(),
        ]);

        // Update variant stats
        $variant = ABTestVariant::where('ab_test_id', $testId)
            ->where('variant_code', $assignment->variant_code)
            ->first();

        if ($variant) {
            $variant->increment('conversions');
            $impressions = max(1, $variant->impressions);
            $variant->update(['conversion_rate' => round($variant->conversions / $impressions * 100, 2)]);
        }

        return true;
    }

    public function checkSignificance(int $testId): array
    {
        $test     = ABTest::with('variants')->find($testId);
        if (! $test) {
            return ['significant' => false];
        }

        $variants = $test->variants;
        if ($variants->count() < 2) {
            return ['significant' => false, 'reason' => 'need_2_variants'];
        }

        $a = $variants->firstWhere('variant_code', 'A');
        $b = $variants->firstWhere('variant_code', 'B');

        if (! $a || ! $b) {
            return ['significant' => false, 'reason' => 'missing_variant'];
        }

        // Check minimum sample size
        if ($a->impressions < $test->min_sample_size || $b->impressions < $test->min_sample_size) {
            return [
                'significant' => false,
                'reason'      => 'insufficient_sample',
                'a_sample'    => $a->impressions,
                'b_sample'    => $b->impressions,
                'required'    => $test->min_sample_size,
            ];
        }

        // Z-test for proportions
        $pA = $a->conversions / max(1, $a->impressions);
        $pB = $b->conversions / max(1, $b->impressions);
        $p  = ($a->conversions + $b->conversions) / max(1, $a->impressions + $b->impressions);

        $se = sqrt($p * (1 - $p) * (1 / $a->impressions + 1 / $b->impressions));
        if ($se == 0) {
            return ['significant' => false, 'reason' => 'zero_se'];
        }

        $z       = abs($pA - $pB) / $se;
        $pValue  = 2 * (1 - $this->normalCDF($z));
        $alpha   = 1 - $test->confidence_level;
        $isSignificant = $pValue < $alpha;
        $winner  = $isSignificant ? ($pA >= $pB ? 'A' : 'B') : null;

        return [
            'significant'    => $isSignificant,
            'p_value'        => round($pValue, 4),
            'z_score'        => round($z, 3),
            'winner'         => $winner,
            'rate_a'         => round($pA * 100, 2),
            'rate_b'         => round($pB * 100, 2),
        ];
    }

    public function applyWinner(int $testId): bool
    {
        $check = $this->checkSignificance($testId);
        if (! $check['significant'] || ! $check['winner']) {
            return false;
        }

        ABTest::where('id', $testId)->update([
            'status'         => 'winner_applied',
            'winner_variant' => $check['winner'],
            'completed_at'   => now(),
        ]);

        return true;
    }

    public function checkAndAutoApplyWinners(): int
    {
        $applied = 0;

        ABTest::where('status', 'running')->where('auto_winner', true)->each(function (ABTest $test) use (&$applied): void {
            $result = $this->checkSignificance($test->id);

            if ($result['significant'] && $result['winner']) {
                $this->applyWinner($test->id);
                $applied++;
            } elseif ($result['significant'] && ! $test->auto_winner) {
                // Notify admin
                NotificationDispatch::create([
                    'type'      => 'ab_test_significant',
                    'channel'   => 'in_app',
                    'recipient' => 'marketing_admin',
                    'subject'   => "A/B Test Anlamlı: {$test->name}",
                    'body'      => "Kazanan: {$result['winner']} — lütfen sonucu inceleyin.",
                    'status'    => 'pending',
                ]);
            }
        });

        return $applied;
    }

    /** Approximation of the standard normal CDF */
    private function normalCDF(float $z): float
    {
        return 0.5 * (1 + $this->erf($z / sqrt(2)));
    }

    private function erf(float $x): float
    {
        $t    = 1 / (1 + 0.3275911 * abs($x));
        $poly = $t * (0.254829592 + $t * (-0.284496736 + $t * (1.421413741 + $t * (-1.453152027 + $t * 1.061405429))));
        $y    = 1 - $poly * exp(-$x * $x);
        return $x < 0 ? -$y : $y;
    }
}
