<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\LeadSourceDatum;
use App\Models\Marketing\CampaignChannelPlan;
use App\Models\MarketingCampaign;
use App\Models\StudentRevenue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', 'all'),
            'channel' => (string) $request->query('channel', 'all'),
        ];

        $query = MarketingCampaign::query()->orderByDesc('created_at');
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q): void {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('channel', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$q}%");
            });
        }
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['channel'] !== 'all') {
            $query->where('channel', $filters['channel']);
        }

        $campaigns = $query->get();

        $byCampaignId = LeadSourceDatum::query()
            ->whereNotNull('campaign_id')
            ->select('campaign_id')
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->campaign_id);

        $fallbackByKey = LeadSourceDatum::query()
            ->whereNull('campaign_id')
            ->selectRaw("LOWER(COALESCE(NULLIF(utm_campaign, ''), NULLIF(initial_source_detail, ''))) as fallback_key")
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
            ->groupBy('fallback_key')
            ->get()
            ->filter(fn ($row) => trim((string) $row->fallback_key) !== '')
            ->keyBy(fn ($row) => (string) $row->fallback_key);

        $rows = $campaigns->map(function (MarketingCampaign $campaign) use ($byCampaignId, $fallbackByKey): array {
            $metrics = is_array($campaign->metrics) ? $campaign->metrics : [];
            $impressions = max(0, (int) ($metrics['impressions'] ?? 0));
            $clicks = max(0, (int) ($metrics['clicks'] ?? 0));
            $spent = (float) ($campaign->spent_amount ?? 0);
            if ($spent <= 0) {
                $spent = (float) ($campaign->budget ?? 0);
            }

            $explicit = $byCampaignId->get((string) $campaign->id);
            $leadCount = (int) ($explicit->lead_count ?? 0);
            $verifiedCount = (int) ($explicit->verified_count ?? 0);
            $convertedCount = (int) ($explicit->converted_count ?? 0);

            $keys = $this->campaignMatchKeys($campaign);
            foreach ($keys as $key) {
                $fallback = $fallbackByKey->get($key);
                if (!$fallback) {
                    continue;
                }
                $leadCount += (int) ($fallback->lead_count ?? 0);
                $verifiedCount += (int) ($fallback->verified_count ?? 0);
                $convertedCount += (int) ($fallback->converted_count ?? 0);
            }

            return [
                'id' => (int) $campaign->id,
                'name' => (string) $campaign->name,
                'channel' => (string) ($campaign->channel ?: '-'),
                'status' => (string) ($campaign->status ?: 'draft'),
                'budget' => (float) ($campaign->budget ?? 0),
                'spent_amount' => $spent,
                'currency' => (string) ($campaign->currency ?: 'EUR'),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'lead_count' => $leadCount,
                'verified_count' => $verifiedCount,
                'converted_count' => $convertedCount,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0,
                'lead_to_conversion_rate' => $leadCount > 0 ? round(($convertedCount / $leadCount) * 100, 2) : 0.0,
                'cpl' => $leadCount > 0 ? round($spent / $leadCount, 2) : 0.0,
                'cpa' => $convertedCount > 0 ? round($spent / $convertedCount, 2) : 0.0,
                'match_keys' => $keys,
                'created_at' => optional($campaign->created_at)->toDateTimeString(),
                'start_date' => optional($campaign->start_date)->toDateString(),
                'end_date' => optional($campaign->end_date)->toDateString(),
            ];
        })->values();

        $totals = [
            'campaign_count' => $rows->count(),
            'impressions' => (int) $rows->sum('impressions'),
            'clicks' => (int) $rows->sum('clicks'),
            'leads' => (int) $rows->sum('lead_count'),
            'verified' => (int) $rows->sum('verified_count'),
            'converted' => (int) $rows->sum('converted_count'),
            'spent' => (float) $rows->sum('spent_amount'),
        ];
        $totals['ctr'] = $totals['impressions'] > 0 ? round(($totals['clicks'] / $totals['impressions']) * 100, 2) : 0.0;
        $totals['lead_to_conversion_rate'] = $totals['leads'] > 0 ? round(($totals['converted'] / $totals['leads']) * 100, 2) : 0.0;
        $totals['cpl'] = $totals['leads'] > 0 ? round($totals['spent'] / $totals['leads'], 2) : 0.0;
        $totals['cpa'] = $totals['converted'] > 0 ? round($totals['spent'] / $totals['converted'], 2) : 0.0;

        $editId = (int) $request->query('edit_id', 0);
        $editing = $editId > 0 ? MarketingCampaign::query()->find($editId) : null;

        return view('marketing-admin.campaigns.index', [
            'pageTitle' => 'Kampanya Yonetimi',
            'title' => 'Kampanyalar',
            'description' => 'Aktif, taslak ve tamamlanan kampanyalarin canli metrik yonetimi.',
            'rows' => $rows,
            'totals' => $totals,
            'filters' => $filters,
            'editing' => $editing,
            'statusOptions' => ['draft', 'active', 'paused', 'completed', 'cancelled'],
            'channelOptions' => ['google_ads', 'instagram_ads', 'facebook_ads', 'youtube_ads', 'tiktok_ads', 'email', 'other'],
        ]);
    }

    public function create()
    {
        return redirect('/mktg-admin/campaigns');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'channel' => ['nullable', Rule::in(['google_ads', 'instagram_ads', 'facebook_ads', 'youtube_ads', 'tiktok_ads', 'email', 'other'])],
            'channels' => ['nullable', 'array'],
            'budget' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'target_country' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'paused', 'completed', 'cancelled'])],
            'metrics' => ['nullable', 'array'],
            'utm_params' => ['nullable', 'array'],
            'linked_cms_content_ids' => ['nullable', 'array'],
            'image_url' => ['nullable', 'string'],
        ]);

        $data['created_by'] = (string) optional($request->user())->email;
        $data['status'] = $data['status'] ?? 'draft';
        if (!isset($data['channel']) && isset($data['channels'][0])) {
            $data['channel'] = (string) $data['channels'][0];
        }
        $data['metrics'] = $data['metrics'] ?? ['impressions' => 0, 'clicks' => 0, 'guestRegistrations' => 0, 'conversions' => 0, 'costPerAcquisition' => 0];

        $row = MarketingCampaign::query()->create($data);
        return $this->responseFor($request, [
            'ok' => true,
            'id' => $row->id,
            'campaign' => $row,
        ], 'Kampanya olusturuldu.', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        return redirect('/mktg-admin/campaigns?edit_id='.$id);
    }

    public function edit(string $id)
    {
        return redirect('/mktg-admin/campaigns?edit_id='.$id);
    }

    public function update(Request $request, string $id)
    {
        $campaign = MarketingCampaign::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'channel' => ['nullable', Rule::in(['google_ads', 'instagram_ads', 'facebook_ads', 'youtube_ads', 'tiktok_ads', 'email', 'other'])],
            'budget' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'paused', 'completed', 'cancelled'])],
            'metrics' => ['nullable', 'array'],
            'linked_cms_content_ids' => ['nullable', 'array'],
        ]);
        $campaign->update($data);

        return $this->responseFor($request, [
            'ok' => true,
            'id' => $campaign->id,
            'campaign' => $campaign->refresh(),
        ], 'Kampanya guncellendi.');
    }

    public function destroy(Request $request, string $id)
    {
        $campaign = MarketingCampaign::query()->findOrFail($id);
        $campaign->delete();
        return $this->responseFor($request, ['ok' => true], 'Kampanya silindi.');
    }

    public function pause(Request $request, string $id)
    {
        $campaign = MarketingCampaign::query()->findOrFail($id);
        $campaign->update(['status' => 'paused']);
        return $this->responseFor($request, [
            'ok' => true,
            'id' => $campaign->id,
            'campaign' => $campaign->refresh(),
        ], 'Kampanya duraklatildi.');
    }

    public function resume(Request $request, string $id)
    {
        $campaign = MarketingCampaign::query()->findOrFail($id);
        $campaign->update(['status' => 'active']);
        return $this->responseFor($request, [
            'ok' => true,
            'id' => $campaign->id,
            'campaign' => $campaign->refresh(),
        ], 'Kampanya aktif edildi.');
    }

    public function report(string $id)
    {
        $campaign = MarketingCampaign::query()->findOrFail($id);
        $matchKeys = $this->campaignMatchKeys($campaign);

        $leadQuery = LeadSourceDatum::query()
            ->where(function ($q) use ($campaign, $matchKeys): void {
                $q->where('campaign_id', $campaign->id);
                if (!empty($matchKeys)) {
                    $q->orWhere(function ($qq) use ($matchKeys): void {
                        $qq->whereNull('campaign_id')
                            ->whereIn(DB::raw("LOWER(COALESCE(NULLIF(utm_campaign, ''), NULLIF(initial_source_detail, '')))"), $matchKeys);
                    });
                }
            });

        $summary = [
            'lead_count' => (int) (clone $leadQuery)->count(),
            'verified_count' => (int) (clone $leadQuery)->whereNotNull('verified_source')->count(),
            'converted_count' => (int) (clone $leadQuery)->where('funnel_converted', true)->count(),
        ];

        $daily = (clone $leadQuery)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'day' => (string) $row->day,
                'lead_count' => (int) $row->lead_count,
                'verified_count' => (int) $row->verified_count,
                'converted_count' => (int) $row->converted_count,
            ])
            ->values()
            ->all();

        return view('marketing-admin.campaigns.report', [
            'pageTitle' => 'Kampanya Raporu',
            'title' => $campaign->name.' Raporu',
            'campaign' => $campaign,
            'summary' => $summary,
            'daily' => $daily,
            'matchKeys' => $matchKeys,
        ]);
    }

    public function dailyMetrics(string $id)
    {
        $campaign = MarketingCampaign::query()->findOrFail($id);
        $matchKeys = $this->campaignMatchKeys($campaign);
        $query = LeadSourceDatum::query()
            ->where(function ($q) use ($campaign, $matchKeys): void {
                $q->where('campaign_id', $campaign->id);
                if (!empty($matchKeys)) {
                    $q->orWhere(function ($qq) use ($matchKeys): void {
                        $qq->whereNull('campaign_id')
                            ->whereIn(DB::raw("LOWER(COALESCE(NULLIF(utm_campaign, ''), NULLIF(initial_source_detail, '')))"), $matchKeys);
                    });
                }
            });

        $daily = $query
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'day' => (string) $row->day,
                'lead_count' => (int) $row->lead_count,
                'verified_count' => (int) $row->verified_count,
                'converted_count' => (int) $row->converted_count,
            ])
            ->values()
            ->all();

        return response()->json([
            'campaign_id' => $campaign->id,
            'daily_metrics' => $daily,
        ]);
    }

    public function managerView(Request $request)
    {
        return $this->index($request);
    }

    private function campaignMatchKeys(MarketingCampaign $campaign): array
    {
        $keys = [];
        $name = trim((string) $campaign->name);
        if ($name !== '') {
            $keys[] = mb_strtolower($name);
        }

        $utm = is_array($campaign->utm_params) ? $campaign->utm_params : [];
        foreach (['campaign_code', 'utm_campaign', 'code'] as $field) {
            $value = trim((string) ($utm[$field] ?? ''));
            if ($value !== '') {
                $keys[] = mb_strtolower($value);
            }
        }

        return array_values(array_unique($keys));
    }

    private function responseFor(Request $request, array $payload, string $statusMessage, int $statusCode = Response::HTTP_OK)
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $statusCode);
        }

        return redirect('/mktg-admin/campaigns')->with('status', $statusMessage);
    }

    // ─── 3.4 Omnichannel Kampanya — Channel Plans ────────────────────────────

    /**
     * GET /mktg-admin/campaigns/{id}/channel-plan
     */
    public function channelPlan(int $id): \Illuminate\Http\JsonResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $plans    = CampaignChannelPlan::where('campaign_id', $campaign->id)
            ->orderBy('sort_order')
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'channel'      => $p->channel,
                'channel_label' => CampaignChannelPlan::$CHANNEL_LABELS[$p->channel] ?? $p->channel,
                'scheduled_at' => $p->scheduled_at?->toDateTimeString(),
                'content_id'   => $p->content_id,
                'content_type' => $p->content_type,
                'status'       => $p->status,
                'status_label' => CampaignChannelPlan::$STATUS_LABELS[$p->status] ?? $p->status,
                'notes'        => $p->notes,
                'sort_order'   => $p->sort_order,
            ]);

        return response()->json(['ok' => true, 'campaign_id' => $id, 'plans' => $plans]);
    }

    /**
     * POST /mktg-admin/campaigns/{id}/channel-plan
     */
    public function channelPlanStore(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);

        $data = $request->validate([
            'channel'      => 'required|string|in:email,social_facebook,social_instagram,social_linkedin,whatsapp,event,sms',
            'scheduled_at' => 'nullable|date',
            'content_id'   => 'nullable|integer',
            'content_type' => 'nullable|string|in:cms_content,email_campaign,social_post,marketing_event',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $plan = CampaignChannelPlan::create([
            'campaign_id'  => $campaign->id,
            'channel'      => $data['channel'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'content_id'   => $data['content_id'] ?? null,
            'content_type' => $data['content_type'] ?? null,
            'status'       => 'planned',
            'notes'        => $data['notes'] ?? null,
            'sort_order'   => CampaignChannelPlan::where('campaign_id', $campaign->id)->count(),
            'created_at'   => now(),
        ]);

        return response()->json(['ok' => true, 'plan_id' => $plan->id]);
    }

    /**
     * PUT /mktg-admin/campaigns/{id}/channel-plan/{planId}
     */
    public function channelPlanUpdate(Request $request, int $id, int $planId): \Illuminate\Http\JsonResponse
    {
        $plan = CampaignChannelPlan::where('campaign_id', $id)->findOrFail($planId);

        $data = $request->validate([
            'channel'      => 'sometimes|string|in:email,social_facebook,social_instagram,social_linkedin,whatsapp,event,sms',
            'scheduled_at' => 'nullable|date',
            'content_id'   => 'nullable|integer',
            'content_type' => 'nullable|string|in:cms_content,email_campaign,social_post,marketing_event',
            'status'       => 'sometimes|string|in:planned,scheduled,sent,completed,cancelled',
            'notes'        => 'nullable|string|max:1000',
            'sort_order'   => 'nullable|integer',
        ]);

        $plan->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /mktg-admin/campaigns/{id}/channel-plan/{planId}
     */
    public function channelPlanDelete(int $id, int $planId): \Illuminate\Http\JsonResponse
    {
        $plan = CampaignChannelPlan::where('campaign_id', $id)->findOrFail($planId);
        $plan->delete();
        return response()->json(['ok' => true]);
    }

    // ─── 2.1 Kampanya ROI Analiz Dashboard ──────────────────────────────────

    /**
     * GET /mktg-admin/campaigns/roi
     */
    public function roiDashboard(Request $request): \Illuminate\View\View
    {
        $start = Carbon::parse($request->query('start', now()->subMonths(3)->toDateString()));
        $end   = Carbon::parse($request->query('end', now()->toDateString()));

        $campaigns = MarketingCampaign::query()
            ->whereBetween('start_date', [$start, $end])
            ->get();

        $roi = $campaigns->map(function ($c) {
            $spent     = (float) ($c->spent_amount ?? 0);
            $leads     = GuestApplication::where('campaign_code', $c->id)->count();
            $converted = GuestApplication::where('campaign_code', $c->id)->where('converted_to_student', true)->count();
            $revenue   = (float) StudentRevenue::whereIn('student_id',
                GuestApplication::where('campaign_code', $c->id)
                    ->whereNotNull('converted_student_id')
                    ->pluck('converted_student_id')
            )->sum('total_earned');

            return [
                'campaign_id'     => $c->id,
                'name'            => $c->name,
                'channel'         => $c->channel,
                'status'          => $c->status,
                'spent'           => $spent,
                'leads'           => $leads,
                'converted'       => $converted,
                'revenue'         => $revenue,
                'cost_per_lead'   => $leads > 0 ? round($spent / $leads, 2) : null,
                'roi_pct'         => $spent > 0 ? round(($revenue - $spent) / $spent * 100, 1) : null,
                'conversion_rate' => $leads > 0 ? round($converted / $leads * 100, 1) : 0,
            ];
        })->sortByDesc('roi_pct')->values();

        $byChannel = $roi->groupBy('channel')->map(fn ($rows, $ch) => [
            'channel'       => $ch,
            'total_spent'   => $rows->sum('spent'),
            'total_leads'   => $rows->sum('leads'),
            'total_revenue' => $rows->sum('revenue'),
            'avg_roi'       => round((float) $rows->avg('roi_pct'), 1),
            'campaigns'     => $rows->count(),
        ])->values();

        return view('marketing-admin.campaigns.roi-dashboard', compact('roi', 'byChannel', 'start', 'end'));
    }
}

