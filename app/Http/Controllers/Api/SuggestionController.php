<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\GuestApplication;
use App\Models\MarketingCampaign;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\EntityCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SuggestionController extends Controller
{
    public function __construct(private readonly EntityCatalogService $catalog)
    {
    }

    public function index(Request $request)
    {
        $limit = max(20, min(500, (int) $request->query('limit', 200)));
        $cacheKey = 'config.suggestions.'.$limit;
        $payload  = Cache::remember($cacheKey, 120, fn () => $this->buildPayload($limit));

        return response()->json($payload);
    }

    private function buildPayload(int $limit): array
    {
        $studentIds = StudentAssignment::query()
            ->whereNotNull('student_id')
            ->where('student_id', '!=', '')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('student_id')
            ->unique()
            ->values();

        $dealerIds = Dealer::query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('code')
            ->unique()
            ->values();

        $seniorEmails = User::query()
            ->whereIn('role', ['senior', 'mentor'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('email')
            ->unique()
            ->values();

        $seniorIds = User::query()
            ->whereIn('role', ['senior', 'mentor'])
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        $branchSuggestions = collect()
            ->merge(
                StudentAssignment::query()
                    ->whereNotNull('branch')
                    ->where('branch', '!=', '')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->pluck('branch')
            )
            ->merge(
                GuestApplication::query()
                    ->whereNotNull('branch')
                    ->where('branch', '!=', '')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->pluck('branch')
            )
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();

        $campaignCodes = GuestApplication::query()
            ->whereNotNull('campaign_code')
            ->where('campaign_code', '!=', '')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('campaign_code')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();

        $campaignNames = MarketingCampaign::query()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('name')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();

        $guestDealerCodes = GuestApplication::query()
            ->whereNotNull('dealer_code')
            ->where('dealer_code', '!=', '')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('dealer_code')
            ->unique()
            ->values();

        $searchHints = collect()
            ->merge($studentIds)
            ->merge($branchSuggestions)
            ->merge($campaignCodes)
            ->merge($campaignNames)
            ->merge(
                GuestApplication::query()
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->pluck('email')
            )
            ->merge(
                GuestApplication::query()
                    ->whereNotNull('first_name')
                    ->whereNotNull('last_name')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get(['first_name', 'last_name'])
                    ->map(fn ($r) => trim((string) $r->first_name.' '.(string) $r->last_name))
            )
            ->filter()
            ->unique()
            ->values();

        return [
            'student_ids'    => $studentIds,
            'dealer_ids'     => $dealerIds->merge($guestDealerCodes)->unique()->values(),
            'senior_emails'  => $seniorEmails,
            'senior_ids'     => $seniorIds,
            'branches'       => $branchSuggestions,
            'campaign_codes' => $campaignCodes,
            'campaign_names' => $campaignNames,
            'search_hints'   => $searchHints,
            'catalog'        => $this->catalog->snapshot(min($limit, 200)),
        ];
    }
}
