<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingTrackingLink;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class TrackingLinkController extends Controller
{
    public function index()
    {
        $status = trim((string) request()->query('status', 'all'));
        if (!in_array($status, ['all', 'active', 'paused', 'archived'], true)) {
            $status = 'all';
        }

        $campaignIdRaw = trim((string) request()->query('campaign_id', ''));
        $campaignId = ctype_digit($campaignIdRaw) ? (int) $campaignIdRaw : null;
        if ($campaignId !== null && $campaignId <= 0) {
            $campaignId = null;
        }

        $search = trim((string) request()->query('q', ''));

        $query = MarketingTrackingLink::query()
            ->with('campaign:id,name');

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($campaignId !== null) {
            $query->where('campaign_id', $campaignId);
        }
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('code', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('campaign_code', 'like', $like)
                    ->orWhere('source_code', 'like', $like)
                    ->orWhere('dealer_code', 'like', $like)
                    ->orWhere('utm_campaign', 'like', $like);
            });
        }

        $links = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $codes = $links->getCollection()
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        $summaryByCode = collect();
        if ($codes !== []) {
            $summaryByCode = LeadSourceDatum::query()
                ->whereIn('referral_link_id', $codes)
                ->selectRaw('referral_link_id as code')
                ->selectRaw('COUNT(*) as lead_count')
                ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
                ->groupBy('referral_link_id')
                ->get()
                ->mapWithKeys(fn ($row) => [
                    (string) $row->code => [
                        'lead_count' => (int) $row->lead_count,
                        'converted_count' => (int) $row->converted_count,
                    ],
                ]);
        }

        return view('marketing-admin.tracking-links.index', [
            'pageTitle' => 'Tracking Link Envanteri',
            'title' => 'Reklam Link Envanteri',
            'links' => $links,
            'summaryByCode' => $summaryByCode,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'codeSchema' => $this->codeSchema(),
            'filters' => [
                'q' => $search,
                'status' => $status,
                'campaign_id' => $campaignId,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $variationNo = $this->resolveVariationNo(
            (string) $data['category_code'],
            (string) $data['platform_code'],
            (string) $data['placement_code'],
            isset($data['variation_no']) ? (int) $data['variation_no'] : null
        );
        $data['variation_no'] = $variationNo;
        $data['code'] = $this->buildCode(
            (string) $data['category_code'],
            (string) $data['platform_code'],
            (string) $data['placement_code'],
            $variationNo
        );
        $data['created_by'] = (string) optional($request->user())->email;

        MarketingTrackingLink::query()->create($data);

        return redirect('/mktg-admin/tracking-links')->with('status', 'Tracking link eklendi.');
    }

    public function update(Request $request, int $id)
    {
        $row = MarketingTrackingLink::query()->findOrFail($id);
        if ($request->boolean('status_only')) {
            $statusData = $request->validate([
                'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            ]);
            $row->forceFill(['status' => (string) $statusData['status']])->save();
            return redirect('/mktg-admin/tracking-links')->with('status', "Tracking link durumu guncellendi (#{$row->id}).");
        }

        $data = $this->validatePayload($request, $row->id);
        $variationNo = $this->resolveVariationNo(
            (string) $data['category_code'],
            (string) $data['platform_code'],
            (string) $data['placement_code'],
            isset($data['variation_no']) ? (int) $data['variation_no'] : null,
            $row->id
        );
        $data['variation_no'] = $variationNo;
        $data['code'] = $this->buildCode(
            (string) $data['category_code'],
            (string) $data['platform_code'],
            (string) $data['placement_code'],
            $variationNo
        );

        $row->fill($data)->save();

        return redirect('/mktg-admin/tracking-links')->with('status', "Tracking link guncellendi (#{$row->id}).");
    }

    public function destroy(int $id)
    {
        $row = MarketingTrackingLink::query()->findOrFail($id);

        // Prevent deleting active links that still have clicks to avoid broken attribution
        if ($row->status === 'active' && (int) ($row->click_count ?? 0) > 0) {
            return redirect('/mktg-admin/tracking-links')
                ->with('error', "Aktif ve tıklama almış link silinemez. Önce 'archived' yapın (#{$row->id}).");
        }

        $row->delete();

        return redirect('/mktg-admin/tracking-links')->with('status', "Tracking link silindi (#{$id}).");
    }

    // ─── 2.4 UTM Stats ──────────────────────────────────────────────────────

    /**
     * GET /mktg-admin/tracking-links/{id}/stats
     */
    public function stats(int $id): \Illuminate\Http\JsonResponse
    {
        $link = MarketingTrackingLink::findOrFail($id);

        $clicks = \App\Models\MarketingTrackingClick::where('tracking_link_id', $id);

        $totalClicks   = $link->click_count ?? $clicks->count();
        $uniqueVisitors = $clicks->distinct('ip')->count('ip');
        $conversionCount = \App\Models\GuestApplication::where('tracking_link_code', $link->code)->count();
        $ctr = $totalClicks > 0 && $uniqueVisitors > 0
            ? round($conversionCount / $uniqueVisitors * 100, 2)
            : 0;

        $dailyClicks = $clicks->selectRaw('DATE(clicked_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();

        return response()->json([
            'ok'                => true,
            'tracking_link_id'  => $id,
            'code'              => $link->code,
            'title'             => $link->title,
            'total_clicks'      => $totalClicks,
            'unique_visitors'   => $uniqueVisitors,
            'conversion_count'  => $conversionCount,
            'ctr_pct'           => $ctr,
            'daily_clicks'      => $dailyClicks,
        ]);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        if (trim((string) $request->input('campaign_id', '')) === '') {
            $request->merge(['campaign_id' => null]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category_code' => ['required', 'string', 'size:2'],
            'platform_code' => ['required', 'string', 'size:2'],
            'placement_code' => ['required', 'string', 'size:1'],
            'variation_no' => ['nullable', 'integer', 'min:1', 'max:99'],
            'destination_path' => ['required', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'integer', 'exists:marketing_campaigns,id'],
            'campaign_code' => ['nullable', 'string', 'max:191'],
            'dealer_code' => ['nullable', 'string', 'max:64'],
            'source_code' => ['nullable', 'string', 'max:64'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:191'],
            'utm_term' => ['nullable', 'string', 'max:191'],
            'utm_content' => ['nullable', 'string', 'max:191'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $category = strtolower((string) $data['category_code']);
        $platform = strtolower((string) $data['platform_code']);
        $placement = strtolower((string) $data['placement_code']);

        $schema = $this->codeSchema();
        if (!isset($schema[$category])) {
            throw ValidationException::withMessages([
                'category_code' => 'Gecersiz kategori kodu.',
            ]);
        }
        if (!isset($schema[$category]['platforms'][$platform])) {
            throw ValidationException::withMessages([
                'platform_code' => 'Gecersiz platform kodu.',
            ]);
        }
        if (!isset($schema[$category]['platforms'][$platform]['placements'][$placement])) {
            throw ValidationException::withMessages([
                'placement_code' => 'Gecersiz tip/yerlesim kodu.',
            ]);
        }

        $data['category_code'] = $category;
        $data['platform_code'] = $platform;
        $data['placement_code'] = $placement;

        $expectedPrefix = $this->codePrefix($category, $platform, $placement);
        $requestedVariation = isset($data['variation_no']) ? (int) $data['variation_no'] : null;
        if ($requestedVariation !== null) {
            $candidateCode = $this->buildCode($category, $platform, $placement, $requestedVariation);
            $exists = MarketingTrackingLink::query()
                ->where('code', $candidateCode)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'variation_no' => "Bu varyasyon dolu ({$candidateCode}).",
                ]);
            }
        } else {
            $hasAny = MarketingTrackingLink::query()
                ->where('code', 'like', $expectedPrefix.'__')
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
            if ($hasAny) {
                $next = $this->resolveVariationNo($category, $platform, $placement, null, $ignoreId);
                $data['variation_no'] = $next;
            } else {
                $data['variation_no'] = 1;
            }
        }

        return $data;
    }

    private function resolveVariationNo(
        string $category,
        string $platform,
        string $placement,
        ?int $requestedVariation,
        ?int $ignoreId = null
    ): int
    {
        if ($requestedVariation !== null && $requestedVariation >= 1 && $requestedVariation <= 99) {
            return $requestedVariation;
        }

        $prefix = $this->codePrefix($category, $platform, $placement);
        $used = MarketingTrackingLink::query()
            ->where('code', 'like', $prefix.'__')
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->pluck('code')
            ->map(function ($code) use ($prefix): int {
                $raw = (string) $code;
                if (!str_starts_with($raw, $prefix)) {
                    return 0;
                }
                return (int) substr($raw, 5, 2);
            })
            ->filter(fn ($v) => $v >= 1 && $v <= 99)
            ->values()
            ->all();

        $usedMap = array_fill_keys($used, true);
        for ($i = 1; $i <= 99; $i++) {
            if (!isset($usedMap[$i])) {
                return $i;
            }
        }

        throw ValidationException::withMessages([
            'variation_no' => 'Bu kombinasyon icin 01-99 varyasyonlari dolu.',
        ]);
    }

    private function buildCode(string $category, string $platform, string $placement, int $variationNo): string
    {
        return sprintf(
            '%s%s%s%02d',
            strtolower(trim($category)),
            strtolower(trim($platform)),
            strtolower(trim($placement)),
            max(1, min(99, $variationNo))
        );
    }

    private function codePrefix(string $category, string $platform, string $placement): string
    {
        return strtolower(trim($category).trim($platform).trim($placement));
    }

    private function codeSchema(): array
    {
        return [
            'wb' => [
                'label' => 'Web',
                'platforms' => [
                    'bl' => ['label' => 'Blog', 'placements' => ['a' => 'Article', 'b' => 'Banner', 's' => 'Sidebar', 'u' => 'Button', 'p' => 'Popup']],
                    'em' => ['label' => 'Email', 'placements' => ['n' => 'Newsletter', 'w' => 'Welcome', 'c' => 'Broadcast', 's' => 'Signature']],
                    'fr' => ['label' => 'Forum', 'placements' => ['c' => 'Comment', 't' => 'Topic', 'p' => 'Profile']],
                    'lp' => ['label' => 'Landing Page', 'placements' => ['h' => 'Hero', 'c' => 'CTA Section', 'v' => 'Video']],
                ],
            ],
            'ad' => [
                'label' => 'Reklam',
                'platforms' => [
                    'go' => ['label' => 'Google Ads', 'placements' => ['k' => 'Search', 'd' => 'Display', 's' => 'Shopping']],
                    'ig' => ['label' => 'Instagram Ads', 'placements' => ['r' => 'Reels', 's' => 'Story', 'f' => 'Feed', 'i' => 'Influencer']],
                    'yt' => ['label' => 'YouTube Ads', 'placements' => ['v' => 'In-Stream', 's' => 'Shorts', 'd' => 'Discovery', 'i' => 'Influencer']],
                    'fb' => ['label' => 'Facebook Ads', 'placements' => ['f' => 'Feed', 'v' => 'Video', 'm' => 'Marketplace']],
                    'tt' => ['label' => 'TikTok Ads', 'placements' => ['f' => 'In-Feed', 's' => 'Spark Ads', 'i' => 'Influencer']],
                ],
            ],
            'sm' => [
                'label' => 'Sosyal Medya',
                'platforms' => [
                    'tg' => ['label' => 'Telegram', 'placements' => ['c' => 'Channel', 'g' => 'Group', 'b' => 'Bot', 'd' => 'DM']],
                    'ig' => ['label' => 'Instagram', 'placements' => ['l' => 'Bio Link', 's' => 'Story', 'r' => 'Reel', 'd' => 'DM']],
                    'yt' => ['label' => 'YouTube', 'placements' => ['d' => 'Description', 'c' => 'Community', 's' => 'Shorts', 'm' => 'Comment']],
                    'tw' => ['label' => 'Twitter / X', 'placements' => ['p' => 'Tweet', 't' => 'Thread', 'b' => 'Bio']],
                    'tt' => ['label' => 'TikTok', 'placements' => ['l' => 'Bio Link', 'v' => 'Video']],
                    'fb' => ['label' => 'Facebook', 'placements' => ['g' => 'Group', 'p' => 'Page Post']],
                ],
            ],
        ];
    }
}
