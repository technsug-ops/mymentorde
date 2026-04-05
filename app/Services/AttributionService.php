<?php

namespace App\Services;

use App\Models\LeadTouchpoint;
use Illuminate\Support\Facades\DB;

class AttributionService
{
    private const MODELS = ['first_touch', 'last_touch', 'linear', 'time_decay', 'position_based'];

    public function recordTouchpoint(int|string $guestId, string $type, string $channel, array $metadata = []): LeadTouchpoint
    {
        return LeadTouchpoint::create([
            'guest_application_id' => $guestId,
            'touchpoint_type'      => $type,
            'channel'              => $channel,
            'campaign_id'          => $metadata['campaign_id'] ?? null,
            'utm_source'           => $metadata['utm_source'] ?? null,
            'utm_medium'           => $metadata['utm_medium'] ?? null,
            'utm_campaign'         => $metadata['utm_campaign'] ?? null,
            'utm_content'          => $metadata['utm_content'] ?? null,
            'utm_term'             => $metadata['utm_term'] ?? null,
            'referrer_url'         => $metadata['referrer_url'] ?? null,
            'landing_page'         => $metadata['landing_page'] ?? null,
            'device_type'          => $metadata['device_type'] ?? null,
            'is_converting_touch'  => false,
            'touched_at'           => now(),
        ]);
    }

    /**
     * Calculate attribution credits for a guest using the given model.
     * Returns ['channel' => fraction, ...]
     */
    public function calculateAttribution(int|string $guestId, string $model = 'position_based'): array
    {
        $touches = LeadTouchpoint::where('guest_application_id', $guestId)
            ->orderBy('touched_at')
            ->pluck('channel')
            ->toArray();

        if (empty($touches)) {
            return [];
        }

        $n = count($touches);

        return match ($model) {
            'first_touch'    => [$touches[0] => 1.0],
            'last_touch'     => [$touches[$n - 1] => 1.0],
            'linear'         => $this->linearAttribution($touches),
            'time_decay'     => $this->timeDecayAttribution($guestId),
            'position_based' => $this->positionBasedAttribution($touches),
            default          => $this->linearAttribution($touches),
        };
    }

    /**
     * Aggregate channel attribution across all converted guests.
     */
    public function getChannelSummary(string $model, string $startDate, string $endDate): array
    {
        $guests = DB::table('guest_applications')
            ->where('contract_status', 'approved')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->pluck('id');

        $totals = [];
        foreach ($guests as $guestId) {
            $credits = $this->calculateAttribution($guestId, $model);
            foreach ($credits as $channel => $fraction) {
                $totals[$channel] = ($totals[$channel] ?? 0) + $fraction;
            }
        }

        $total = array_sum($totals);
        if ($total <= 0) {
            return [];
        }

        $result = [];
        foreach ($totals as $channel => $credit) {
            $result[] = [
                'channel'  => $channel,
                'credit'   => round($credit, 2),
                'share_pct' => round($credit / $total * 100, 1),
            ];
        }

        usort($result, fn ($a, $b) => $b['credit'] <=> $a['credit']);

        return $result;
    }

    private function linearAttribution(array $touches): array
    {
        $fraction = 1.0 / count($touches);
        $result   = [];
        foreach ($touches as $channel) {
            $result[$channel] = ($result[$channel] ?? 0) + $fraction;
        }
        return $result;
    }

    private function timeDecayAttribution(int|string $guestId): array
    {
        $rows = LeadTouchpoint::where('guest_application_id', $guestId)
            ->orderBy('touched_at')
            ->get(['channel', 'touched_at']);

        if ($rows->isEmpty()) {
            return [];
        }

        $lastDate = $rows->last()->touched_at;
        $weights  = [];

        foreach ($rows as $row) {
            $daysDiff = $lastDate->diffInDays($row->touched_at);
            $weight   = pow(0.5, $daysDiff / 7); // 7-day half-life
            $weights[] = ['channel' => $row->channel, 'weight' => $weight];
        }

        $total  = array_sum(array_column($weights, 'weight'));
        $result = [];

        foreach ($weights as $w) {
            $channel          = $w['channel'];
            $result[$channel] = ($result[$channel] ?? 0) + $w['weight'] / $total;
        }

        return $result;
    }

    private function positionBasedAttribution(array $touches): array
    {
        $n      = count($touches);
        $result = [];

        if ($n === 1) {
            return [$touches[0] => 1.0];
        }

        if ($n === 2) {
            $result[$touches[0]] = ($result[$touches[0]] ?? 0) + 0.5;
            $result[$touches[1]] = ($result[$touches[1]] ?? 0) + 0.5;
            return $result;
        }

        // First: 40%, Last: 40%, Middle: 20% split evenly
        $middleCredit = 0.20 / ($n - 2);

        foreach ($touches as $i => $channel) {
            $credit = match ($i) {
                0       => 0.40,
                $n - 1  => 0.40,
                default => $middleCredit,
            };
            $result[$channel] = ($result[$channel] ?? 0) + $credit;
        }

        return $result;
    }
}
