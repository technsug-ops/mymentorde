<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Services\Marketing\PredictiveService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly PredictiveService $predictive)
    {
    }

    /**
     * GET /mktg-admin/analytics/conversion-probability/{guestId}
     */
    public function conversionProbability(Request $request, int $guestId): \Illuminate\Http\JsonResponse
    {
        $result = $this->predictive->conversionProbability($guestId);
        return response()->json(['ok' => true, 'data' => $result]);
    }

    /**
     * GET /mktg-admin/analytics/revenue-projection?months=3
     */
    public function revenueProjection(Request $request): \Illuminate\Http\JsonResponse
    {
        $months = (int) $request->query('months', 3);
        $months = max(1, min(12, $months));

        $result = $this->predictive->revenueProjection($months);
        return response()->json(['ok' => true, 'data' => $result]);
    }

    /**
     * GET /mktg-admin/analytics/churn-risk
     */
    public function churnRisk(): \Illuminate\Http\JsonResponse
    {
        $result = $this->predictive->churnRisk();
        return response()->json(['ok' => true, 'data' => $result]);
    }
}
