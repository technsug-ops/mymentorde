<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\LeadSourceDatum;
use App\Models\MarketingTrackingLink;
use App\Support\CsvExportHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeadSourceController extends Controller
{
    public function index()
    {
        $rows = LeadSourceDatum::query()
            ->select('initial_source')
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN source_match = 1 THEN 1 ELSE 0 END) as matched_count')
            ->groupBy('initial_source')
            ->orderByDesc('lead_count')
            ->get()
            ->map(function ($row): array {
                $lead = (int) $row->lead_count;
                $verified = (int) $row->verified_count;
                return [
                    'source' => (string) ($row->initial_source ?: '-'),
                    'lead_count' => $lead,
                    'verified_count' => $verified,
                    'matched_count' => (int) $row->matched_count,
                    'conversion_rate' => $lead > 0 ? round(($verified / $lead) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        return view('marketing-admin.lead-sources.index', [
            'pageTitle' => 'Lead Kaynagi Analizi',
            'title' => 'Kaynak Ozeti',
            'mode' => 'summary',
            'rows' => $rows,
            'total' => (int) LeadSourceDatum::query()->count(),
        ]);
    }

    public function funnel()
    {
        $total = (int) LeadSourceDatum::query()->count();
        $stages = [
            'registered' => (int) LeadSourceDatum::query()->where('funnel_registered', true)->count(),
            'form_completed' => (int) LeadSourceDatum::query()->where('funnel_form_completed', true)->count(),
            'documents_uploaded' => (int) LeadSourceDatum::query()->where('funnel_documents_uploaded', true)->count(),
            'package_selected' => (int) LeadSourceDatum::query()->where('funnel_package_selected', true)->count(),
            'contract_signed' => (int) LeadSourceDatum::query()->where('funnel_contract_signed', true)->count(),
            'converted' => (int) LeadSourceDatum::query()->where('funnel_converted', true)->count(),
        ];

        $rows = collect($stages)
            ->map(function (int $count, string $stage) use ($total): array {
                return [
                    'stage' => $stage,
                    'count' => $count,
                    'rate' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        return view('marketing-admin.lead-sources.index', [
            'pageTitle' => 'Lead Funnel',
            'title' => 'Funnel Analizi',
            'mode' => 'funnel',
            'rows' => $rows,
            'total' => $total,
            'dropped' => (int) LeadSourceDatum::query()->whereNotNull('funnel_dropped_at_stage')->count(),
        ]);
    }

    public function utmPerformance()
    {
        $rows = LeadSourceDatum::query()
            ->selectRaw("COALESCE(NULLIF(utm_source, ''), '-') as utm_source_key")
            ->selectRaw("COALESCE(NULLIF(utm_medium, ''), '-') as utm_medium_key")
            ->selectRaw("COALESCE(NULLIF(utm_campaign, ''), '-') as utm_campaign_key")
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
            ->groupBy('utm_source_key', 'utm_medium_key', 'utm_campaign_key')
            ->orderByDesc('lead_count')
            ->paginate(50)
            ->withQueryString()
            ->through(function ($row): array {
                $lead = (int) $row->lead_count;
                return [
                    'utm_source'      => (string) $row->utm_source_key,
                    'utm_medium'      => (string) $row->utm_medium_key,
                    'utm_campaign'    => (string) $row->utm_campaign_key,
                    'lead_count'      => $lead,
                    'verified_count'  => (int) $row->verified_count,
                    'converted_count' => (int) $row->converted_count,
                    'conversion_rate' => $lead > 0 ? round(((int) $row->converted_count / $lead) * 100, 1) : 0.0,
                ];
            });

        $topCampaigns = LeadSourceDatum::query()
            ->whereNotNull('utm_campaign')
            ->where('utm_campaign', '!=', '')
            ->select('utm_campaign')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('utm_campaign')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'campaign' => (string) $row->utm_campaign,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return view('marketing-admin.lead-sources.index', [
            'pageTitle' => 'UTM Performansi',
            'title' => 'UTM Raporu',
            'mode' => 'utm',
            'rows' => $rows,
            'topCampaigns' => $topCampaigns,
        ]);
    }

    public function trackingCodes(Request $request)
    {
        [$startAt, $endAt, $filters] = $this->resolveTrackingCodeRange($request);
        $result = $this->buildTrackingCodeRows($startAt, $endAt);

        return view('marketing-admin.lead-sources.index', [
            'pageTitle' => 'Tracking Code Performansi',
            'title' => 'Tracking Code Raporu',
            'mode' => 'tracking_codes',
            'rows' => $result,
            'total' => count($result),
            'trackingFilters' => $filters,
        ]);
    }

    public function trackingCodesCsv(Request $request)
    {
        [$startAt, $endAt] = $this->resolveTrackingCodeRange($request);
        $rows = $this->buildTrackingCodeRows($startAt, $endAt);
        $filename = 'tracking-codes-'.now()->format('Ymd_His').'.csv';

        return CsvExportHelper::download($filename, function ($out) use ($rows): void {
            fputcsv($out, [
                'code',
                'title',
                'status',
                'campaign_code',
                'source_code',
                'click_count',
                'lead_count',
                'verified_count',
                'converted_count',
                'lead_from_click_rate',
                'conversion_rate',
                'last_lead_at',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    (string) ($row['code'] ?? ''),
                    (string) ($row['title'] ?? ''),
                    (string) ($row['status'] ?? ''),
                    (string) ($row['campaign_code'] ?? ''),
                    (string) ($row['source_code'] ?? ''),
                    (string) ($row['click_count'] ?? 0),
                    (string) ($row['lead_count'] ?? 0),
                    (string) ($row['verified_count'] ?? 0),
                    (string) ($row['converted_count'] ?? 0),
                    (string) ($row['lead_from_click_rate'] ?? ''),
                    (string) ($row['conversion_rate'] ?? 0),
                    (string) ($row['last_lead_at'] ?? ''),
                ]);
            }

        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTrackingCodeRows(?Carbon $startAt = null, ?Carbon $endAt = null): array
    {
        $rows = LeadSourceDatum::query()
            ->whereNotNull('referral_link_id')
            ->where('referral_link_id', '!=', '')
            ->when($startAt !== null && $endAt !== null, fn ($q) => $q->whereBetween('created_at', [$startAt, $endAt]))
            ->select('referral_link_id')
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
            ->selectRaw('MAX(created_at) as last_lead_at')
            ->groupBy('referral_link_id')
            ->orderByDesc('lead_count')
            ->get();

        $codes = $rows
            ->pluck('referral_link_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values();

        $linksByCode = MarketingTrackingLink::query()
            ->whereIn('code', $codes)
            ->get(['code', 'title', 'status', 'click_count', 'campaign_code', 'source_code'])
            ->keyBy('code');

        return $rows
            ->map(function ($row) use ($linksByCode): array {
                $code = trim((string) $row->referral_link_id);
                $link = $linksByCode->get($code);

                $leadCount = (int) $row->lead_count;
                $verifiedCount = (int) $row->verified_count;
                $convertedCount = (int) $row->converted_count;
                $clickCount = (int) ($link?->click_count ?? 0);

                return [
                    'code' => $code,
                    'title' => (string) ($link?->title ?: '-'),
                    'status' => (string) ($link?->status ?: 'unknown'),
                    'campaign_code' => (string) ($link?->campaign_code ?: '-'),
                    'source_code' => (string) ($link?->source_code ?: '-'),
                    'click_count' => $clickCount,
                    'lead_count' => $leadCount,
                    'verified_count' => $verifiedCount,
                    'converted_count' => $convertedCount,
                    'lead_from_click_rate' => $clickCount > 0 ? round(($leadCount / $clickCount) * 100, 1) : null,
                    'conversion_rate' => $leadCount > 0 ? round(($convertedCount / $leadCount) * 100, 1) : 0.0,
                    'last_lead_at' => (string) ($row->last_lead_at ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{0:?Carbon,1:?Carbon,2:array{start_date:string,end_date:string}}
     */
    private function resolveTrackingCodeRange(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $startRaw = trim((string) ($validated['start_date'] ?? ''));
        $endRaw = trim((string) ($validated['end_date'] ?? ''));

        if ($startRaw === '' && $endRaw === '') {
            return [null, null, ['start_date' => '', 'end_date' => '']];
        }

        $startAt = $startRaw !== '' ? Carbon::parse($startRaw)->startOfDay() : null;
        $endAt = $endRaw !== '' ? Carbon::parse($endRaw)->endOfDay() : null;

        if ($startAt === null && $endAt !== null) {
            $startAt = $endAt->copy()->startOfDay();
        }
        if ($endAt === null && $startAt !== null) {
            $endAt = $startAt->copy()->endOfDay();
        }
        if ($startAt !== null && $endAt !== null && $startAt->gt($endAt)) {
            [$startAt, $endAt] = [$endAt->copy()->startOfDay(), $startAt->copy()->endOfDay()];
        }

        return [
            $startAt,
            $endAt,
            [
                'start_date' => $startAt?->toDateString() ?? '',
                'end_date' => $endAt?->toDateString() ?? '',
            ],
        ];
    }

    public function dropoffAnalysis()
    {
        $explicitDrops = LeadSourceDatum::query()
            ->whereNotNull('funnel_dropped_at_stage')
            ->where('funnel_dropped_at_stage', '!=', '')
            ->select('funnel_dropped_at_stage')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('funnel_dropped_at_stage')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'stage' => (string) $row->funnel_dropped_at_stage,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $pendingRows = LeadSourceDatum::query()
            ->where(function ($q): void {
                $q->whereNull('funnel_converted')->orWhere('funnel_converted', false);
            })
            ->get([
                'funnel_registered',
                'funnel_form_completed',
                'funnel_documents_uploaded',
                'funnel_package_selected',
                'funnel_contract_signed',
                'funnel_converted',
            ]);
        $inferred = [
            'registered' => 0,
            'form_completed' => 0,
            'documents_uploaded' => 0,
            'package_selected' => 0,
            'contract_signed' => 0,
            'converted' => 0,
        ];

        foreach ($pendingRows as $row) {
            if (!(bool) $row->funnel_registered) {
                $inferred['registered']++;
                continue;
            }
            if (!(bool) $row->funnel_form_completed) {
                $inferred['form_completed']++;
                continue;
            }
            if (!(bool) $row->funnel_documents_uploaded) {
                $inferred['documents_uploaded']++;
                continue;
            }
            if (!(bool) $row->funnel_package_selected) {
                $inferred['package_selected']++;
                continue;
            }
            if (!(bool) $row->funnel_contract_signed) {
                $inferred['contract_signed']++;
                continue;
            }
            if (!(bool) $row->funnel_converted) {
                $inferred['converted']++;
            }
        }

        return view('marketing-admin.lead-sources.index', [
            'pageTitle' => 'Dropoff Analizi',
            'title' => 'Kayip Noktalari',
            'mode' => 'dropoff',
            'explicitRows' => $explicitDrops,
            'inferredRows' => collect($inferred)
                ->map(fn (int $total, string $stage): array => ['stage' => $stage, 'total' => $total])
                ->filter(fn (array $row): bool => $row['total'] > 0)
                ->values()
                ->all(),
        ]);
    }

    public function sourceVerification()
    {
        $total = (int) LeadSourceDatum::query()->count();
        $verifiedTotal = (int) LeadSourceDatum::query()->whereNotNull('verified_source')->count();
        $matchedTotal = (int) LeadSourceDatum::query()->where('source_match', true)->count();
        $mismatchRows = LeadSourceDatum::query()
            ->whereNotNull('initial_source')
            ->whereNotNull('verified_source')
            ->whereRaw('LOWER(initial_source) != LOWER(verified_source)')
            ->selectRaw('initial_source, verified_source, COUNT(*) as total')
            ->groupBy('initial_source', 'verified_source')
            ->orderByDesc('total')
            ->limit(30)
            ->get()
            ->map(fn ($row): array => [
                'initial_source' => (string) $row->initial_source,
                'verified_source' => (string) $row->verified_source,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return view('marketing-admin.lead-sources.index', [
            'pageTitle' => 'Source Verification',
            'title' => 'Ilk ve dogrulanmis kaynak karsilastirmasi',
            'mode' => 'verification',
            'total' => $total,
            'verifiedTotal' => $verifiedTotal,
            'matchedTotal' => $matchedTotal,
            'mismatchTotal' => max(0, $verifiedTotal - $matchedTotal),
            'mismatchRows' => $mismatchRows,
        ]);
    }
}
