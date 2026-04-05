<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingExternalMetric;
use Illuminate\Http\Request;

class MarketingAnalyticsController extends Controller
{
    public function kpis()
    {
        $guestCount = LeadSourceDatum::count();
        $verifiedCount = LeadSourceDatum::whereNotNull('verified_source')->count();
        $campaignCount = MarketingCampaign::count();

        $totalSpend = MarketingCampaign::sum('budget');
        $cpa = $guestCount > 0 ? round(((float) $totalSpend / $guestCount), 2) : 0;

        $externalStart = now()->subDays(30)->toDateString();
        $externalEnd = now()->toDateString();
        $externalQuery = MarketingExternalMetric::query()
            ->whereBetween('metric_date', [$externalStart, $externalEnd]);
        $externalSpend = round((float) (clone $externalQuery)->sum('spend'), 2);
        $externalImpressions = (int) (clone $externalQuery)->sum('impressions');
        $externalClicks = (int) (clone $externalQuery)->sum('clicks');
        $externalConversions = (int) (clone $externalQuery)->sum('conversions');

        return [
            'guest_count' => $guestCount,
            'verified_source_count' => $verifiedCount,
            'campaign_count' => $campaignCount,
            'total_spend' => (float) $totalSpend,
            'cost_per_acquisition' => $cpa,
            'external_spend_30d' => $externalSpend,
            'external_impressions_30d' => $externalImpressions,
            'external_clicks_30d' => $externalClicks,
            'external_conversions_30d' => $externalConversions,
            'external_ctr_30d' => $externalImpressions > 0 ? round(($externalClicks / $externalImpressions) * 100, 2) : 0.0,
        ];
    }

    public function sourcePerformance()
    {
        $rows = LeadSourceDatum::query()
            ->select('initial_source')
            ->selectRaw('COUNT(*) as guest_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as student_count')
            ->groupBy('initial_source')
            ->orderByDesc('guest_count')
            ->get()
            ->map(function ($row) {
                $guest = (int) $row->guest_count;
                $student = (int) $row->student_count;
                $conversion = $guest > 0 ? round(($student / $guest) * 100, 2) : 0;
                return [
                    'source' => $row->initial_source,
                    'guest_count' => $guest,
                    'student_count' => $student,
                    'conversion_rate' => $conversion,
                ];
            })
            ->values();

        return $rows;
    }

    public function externalPerformance(Request $request)
    {
        $days = max(1, min(90, (int) $request->query('days', 30)));
        $start = now()->subDays($days - 1)->toDateString();
        $end = now()->toDateString();

        $query = MarketingExternalMetric::query()
            ->whereBetween('metric_date', [$start, $end]);

        $totals = [
            'spend' => round((float) (clone $query)->sum('spend'), 2),
            'impressions' => (int) (clone $query)->sum('impressions'),
            'clicks' => (int) (clone $query)->sum('clicks'),
            'leads' => (int) (clone $query)->sum('leads'),
            'conversions' => (int) (clone $query)->sum('conversions'),
            'rows' => (int) (clone $query)->count(),
        ];
        $totals['ctr'] = $totals['impressions'] > 0 ? round(($totals['clicks'] / $totals['impressions']) * 100, 2) : 0.0;
        $totals['cpc'] = $totals['clicks'] > 0 ? round($totals['spend'] / $totals['clicks'], 2) : 0.0;
        $totals['cpa'] = $totals['conversions'] > 0 ? round($totals['spend'] / $totals['conversions'], 2) : 0.0;

        $providers = (clone $query)
            ->selectRaw("COALESCE(NULLIF(provider, ''), 'unknown') as provider_key")
            ->selectRaw('COUNT(*) as row_count')
            ->selectRaw('COALESCE(SUM(spend), 0) as spend_total')
            ->selectRaw('COALESCE(SUM(clicks), 0) as click_total')
            ->selectRaw('COALESCE(SUM(conversions), 0) as conversion_total')
            ->groupBy('provider_key')
            ->orderByDesc('spend_total')
            ->get()
            ->map(fn ($row) => [
                'provider' => (string) $row->provider_key,
                'rows' => (int) $row->row_count,
                'spend' => round((float) $row->spend_total, 2),
                'clicks' => (int) $row->click_total,
                'conversions' => (int) $row->conversion_total,
            ])
            ->values();

        return [
            'window' => [
                'days' => $days,
                'start_date' => $start,
                'end_date' => $end,
            ],
            'totals' => $totals,
            'providers' => $providers,
        ];
    }
}
