<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingExternalMetric;
use App\Models\MarketingReport;
use App\Models\StudentRevenue;
use App\Support\CsvExportHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KPIReportController extends Controller
{
    public function index(Request $request)
    {
        [$start, $end] = $this->resolveRange($request);
        $payload = $this->buildPayload($start, $end);

        return view('marketing-admin.kpi.index', [
            'pageTitle' => 'KPI Dashboard',
            'title' => 'KPI & Raporlar',
            'range' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'kpis' => $payload['kpis'],
            'sourceSummary' => $payload['source_summary'],
            'pipelineSummary' => $payload['pipeline_summary'],
            'trend' => $payload['trend'],
            'externalProviderSummary' => $payload['external_provider_summary'],
            'externalCampaignSummary' => $payload['external_campaign_summary'],
            'recentReports' => MarketingReport::query()
                ->latest()
                ->limit(8)
                ->get(['id', 'report_type', 'period_start', 'period_end', 'created_by', 'created_at']),
        ]);
    }

    public function generate(Request $request)
    {
        [$start, $end] = $this->resolveRange($request);
        $payload = $this->buildPayload($start, $end);
        $reportType = trim((string) $request->input('report_type', 'kpi_snapshot')) ?: 'kpi_snapshot';

        $report = MarketingReport::query()->create([
            'report_type' => $reportType,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'kpis' => $payload['kpis'],
            'source_summary' => $payload['source_summary'],
            'pipeline_summary' => $payload['pipeline_summary'],
            'trend' => $payload['trend'],
            'created_by' => (string) optional($request->user())->email,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'report_id' => $report->id,
            ]);
        }

        return redirect('/mktg-admin/reports')
            ->with('status', "KPI raporu olusturuldu (#{$report->id}).");
    }

    public function list(Request $request)
    {
        $type = trim((string) $request->query('report_type', ''));

        $reports = MarketingReport::query()
            ->when($type !== '', fn ($q) => $q->where('report_type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('marketing-admin.kpi.list', [
            'pageTitle' => 'Rapor Listesi',
            'title' => 'Olusturulan Raporlar',
            'reports' => $reports,
            'reportType' => $type,
        ]);
    }

    public function download(string $id, string $format)
    {
        $report = MarketingReport::query()->findOrFail($id);
        $fmt = strtolower(trim($format));

        if ($fmt === 'json') {
            return response()->json([
                'id' => $report->id,
                'report_type' => $report->report_type,
                'period_start' => optional($report->period_start)->toDateString(),
                'period_end' => optional($report->period_end)->toDateString(),
                'filters' => $report->filters,
                'kpis' => $report->kpis,
                'source_summary' => $report->source_summary,
                'pipeline_summary' => $report->pipeline_summary,
                'trend' => $report->trend,
                'created_by' => $report->created_by,
                'created_at' => optional($report->created_at)->toDateTimeString(),
            ]);
        }

        if ($fmt !== 'csv') {
            abort(422, 'Desteklenen formatlar: csv, json');
        }

        $filename = sprintf(
            'marketing-report-%d-%s_%s.csv',
            $report->id,
            optional($report->period_start)->toDateString(),
            optional($report->period_end)->toDateString()
        );

        $kpis = is_array($report->kpis) ? $report->kpis : [];
        $sourceSummary = is_array($report->source_summary) ? $report->source_summary : [];
        $pipelineSummary = is_array($report->pipeline_summary) ? $report->pipeline_summary : [];
        $trend = is_array($report->trend) ? $report->trend : [];

        return CsvExportHelper::download($filename, function ($out) use ($report, $kpis, $sourceSummary, $pipelineSummary, $trend): void {
            fputcsv($out, ['MentorDE Marketing Report']);
            fputcsv($out, ['Report ID', (string) $report->id]);
            fputcsv($out, ['Type', (string) $report->report_type]);
            fputcsv($out, ['Period', (string) optional($report->period_start)->toDateString(), (string) optional($report->period_end)->toDateString()]);
            fputcsv($out, ['Created By', (string) ($report->created_by ?: '-')]);
            fputcsv($out, []);

            fputcsv($out, ['KPI', 'Value']);
            foreach ($kpis as $key => $value) {
                fputcsv($out, [(string) $key, is_array($value) ? json_encode($value) : (string) $value]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Source', 'Lead', 'Verified', 'Converted', 'Conversion %']);
            foreach ($sourceSummary as $row) {
                fputcsv($out, [
                    (string) ($row['source'] ?? ''),
                    (string) ($row['lead_count'] ?? 0),
                    (string) ($row['verified_count'] ?? 0),
                    (string) ($row['converted_count'] ?? 0),
                    (string) ($row['conversion_rate'] ?? 0),
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Lead Status', 'Count']);
            foreach ($pipelineSummary as $row) {
                fputcsv($out, [
                    (string) ($row['status'] ?? ''),
                    (string) ($row['count'] ?? 0),
                ]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Trend Date', 'Leads', 'Converted', 'Revenue']);
            foreach ($trend as $row) {
                fputcsv($out, [
                    (string) ($row['label'] ?? ''),
                    (string) ($row['leads'] ?? 0),
                    (string) ($row['converted'] ?? 0),
                    (string) ($row['revenue'] ?? 0),
                ]);
            }

        });
    }

    public function managerView()
    {
        return $this->index(request());
    }

    private function resolveRange(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $start = trim((string) $request->input('start_date', ''));
        $end = trim((string) $request->input('end_date', ''));

        $startDate = $start !== '' ? Carbon::parse($start)->startOfDay() : now()->subDays(29)->startOfDay();
        $endDate = $end !== '' ? Carbon::parse($end)->endOfDay() : now()->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function buildPayload(Carbon $start, Carbon $end): array
    {
        $leadQuery = LeadSourceDatum::query()->whereBetween('created_at', [$start, $end]);
        $guestQuery = GuestApplication::query()->whereBetween('created_at', [$start, $end]);

        $leadCount = (int) (clone $leadQuery)->count();
        $verifiedCount = (int) (clone $leadQuery)->whereNotNull('verified_source')->count();
        $convertedCount = (int) (clone $leadQuery)->where('funnel_converted', true)->count();

        $campaigns = MarketingCampaign::query()->get(['budget', 'spent_amount', 'status']);
        $totalSpent = $campaigns->sum(function (MarketingCampaign $campaign): float {
            $spent = (float) ($campaign->spent_amount ?? 0);
            if ($spent > 0) {
                return $spent;
            }
            return (float) ($campaign->budget ?? 0);
        });
        $activeCampaignCount = $campaigns
            ->whereIn('status', ['active', 'running', 'scheduled'])
            ->count();

        $revenueInRange = (float) StudentRevenue::query()
            ->whereBetween('updated_at', [$start, $end])
            ->sum('total_earned');

        $openGuests = (int) (clone $guestQuery)
            ->where('converted_to_student', false)
            ->where(function ($q): void {
                $q->whereNull('is_archived')->orWhere('is_archived', false);
            })
            ->count();
        $archivedGuests = (int) (clone $guestQuery)
            ->where('is_archived', true)
            ->count();

        $sourceSummary = (clone $leadQuery)
            ->selectRaw("COALESCE(NULLIF(initial_source, ''), 'organic') as source_key")
            ->selectRaw('COUNT(*) as lead_count')
            ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as verified_count')
            ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted_count')
            ->groupBy('source_key')
            ->orderByDesc('lead_count')
            ->get()
            ->map(function ($row): array {
                $lead = (int) $row->lead_count;
                $converted = (int) $row->converted_count;
                return [
                    'source' => (string) $row->source_key,
                    'lead_count' => $lead,
                    'verified_count' => (int) $row->verified_count,
                    'converted_count' => $converted,
                    'conversion_rate' => $lead > 0 ? round(($converted / $lead) * 100, 2) : 0.0,
                ];
            })
            ->values()
            ->all();

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $externalQuery = MarketingExternalMetric::query()
            ->whereBetween('metric_date', [$startDate, $endDate]);
        $externalSpend = round((float) (clone $externalQuery)->sum('spend'), 2);
        $externalImpressions = (int) (clone $externalQuery)->sum('impressions');
        $externalClicks = (int) (clone $externalQuery)->sum('clicks');
        $externalLeads = (int) (clone $externalQuery)->sum('leads');
        $externalConversions = (int) (clone $externalQuery)->sum('conversions');
        $externalRows = (int) (clone $externalQuery)->count();

        $externalProviderSummary = (clone $externalQuery)
            ->selectRaw("COALESCE(NULLIF(provider, ''), 'unknown') as provider_key")
            ->selectRaw('COUNT(*) as row_count')
            ->selectRaw('COALESCE(SUM(spend), 0) as spend_total')
            ->selectRaw('COALESCE(SUM(impressions), 0) as impression_total')
            ->selectRaw('COALESCE(SUM(clicks), 0) as click_total')
            ->selectRaw('COALESCE(SUM(conversions), 0) as conversion_total')
            ->groupBy('provider_key')
            ->orderByDesc('spend_total')
            ->get()
            ->map(fn ($row): array => [
                'provider' => (string) $row->provider_key,
                'rows' => (int) $row->row_count,
                'spend' => round((float) $row->spend_total, 2),
                'impressions' => (int) $row->impression_total,
                'clicks' => (int) $row->click_total,
                'conversions' => (int) $row->conversion_total,
            ])
            ->values()
            ->all();

        $externalCampaignSummary = (clone $externalQuery)
            ->selectRaw("COALESCE(NULLIF(campaign_name, ''), NULLIF(campaign_key, ''), 'unknown') as campaign_label")
            ->selectRaw('COALESCE(SUM(spend), 0) as spend_total')
            ->selectRaw('COALESCE(SUM(clicks), 0) as click_total')
            ->selectRaw('COALESCE(SUM(conversions), 0) as conversion_total')
            ->groupBy('campaign_label')
            ->orderByDesc('spend_total')
            ->limit(12)
            ->get()
            ->map(fn ($row): array => [
                'campaign' => (string) $row->campaign_label,
                'spend' => round((float) $row->spend_total, 2),
                'clicks' => (int) $row->click_total,
                'conversions' => (int) $row->conversion_total,
            ])
            ->values()
            ->all();

        $pipelineSummary = (clone $guestQuery)
            ->where('converted_to_student', false)
            ->where(function ($q): void {
                $q->whereNull('is_archived')->orWhere('is_archived', false);
            })
            ->selectRaw("COALESCE(NULLIF(lead_status, ''), 'new') as status_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status_key')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'status' => (string) $row->status_key,
                'count' => (int) $row->total,
            ])
            ->values()
            ->all();

        $trend = $this->buildTrend($start, $end);

        $kpis = [
            'lead_count' => $leadCount,
            'verified_count' => $verifiedCount,
            'converted_count' => $convertedCount,
            'conversion_rate' => $leadCount > 0 ? round(($convertedCount / $leadCount) * 100, 2) : 0.0,
            'active_campaign_count' => (int) $activeCampaignCount,
            'spent_total' => round((float) $totalSpent, 2),
            'revenue_total' => round($revenueInRange, 2),
            'roi' => $totalSpent > 0 ? round((($revenueInRange - $totalSpent) / $totalSpent) * 100, 2) : 0.0,
            'cpl' => $leadCount > 0 ? round($totalSpent / $leadCount, 2) : 0.0,
            'cpa' => $convertedCount > 0 ? round($totalSpent / $convertedCount, 2) : 0.0,
            'open_guest_count' => $openGuests,
            'archived_guest_count' => $archivedGuests,
            'external_rows' => $externalRows,
            'external_spend' => $externalSpend,
            'external_impressions' => $externalImpressions,
            'external_clicks' => $externalClicks,
            'external_leads' => $externalLeads,
            'external_conversions' => $externalConversions,
            'external_ctr' => $externalImpressions > 0 ? round(($externalClicks / $externalImpressions) * 100, 2) : 0.0,
            'external_cpc' => $externalClicks > 0 ? round($externalSpend / $externalClicks, 2) : 0.0,
            'external_cpa' => $externalConversions > 0 ? round($externalSpend / $externalConversions, 2) : 0.0,
            'external_provider_summary' => $externalProviderSummary,
            'external_campaign_summary' => $externalCampaignSummary,
        ];

        return [
            'kpis' => $kpis,
            'source_summary' => $sourceSummary,
            'pipeline_summary' => $pipelineSummary,
            'trend' => $trend,
            'external_provider_summary' => $externalProviderSummary,
            'external_campaign_summary' => $externalCampaignSummary,
        ];
    }

    private function buildTrend(Carbon $start, Carbon $end): array
    {
        $days = max(1, $start->diffInDays($end));
        $rows = [];

        if ($days <= 45) {
            $cursor = $start->copy()->startOfDay();
            $limit = 0;
            while ($cursor->lte($end) && $limit < 60) {
                $dayStart = $cursor->copy()->startOfDay();
                $dayEnd = $cursor->copy()->endOfDay();
                $rows[] = [
                    'label' => $dayStart->toDateString(),
                    'leads' => (int) LeadSourceDatum::query()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                    'converted' => (int) LeadSourceDatum::query()->where('funnel_converted', true)->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                    'revenue' => (float) StudentRevenue::query()->whereBetween('updated_at', [$dayStart, $dayEnd])->sum('total_earned'),
                ];
                $cursor->addDay();
                $limit++;
            }
            return $rows;
        }

        $cursor = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();
        $limit = 0;
        while ($cursor->lte($endMonth) && $limit < 18) {
            $bucketStart = $cursor->copy()->startOfMonth();
            $bucketEnd = $cursor->copy()->endOfMonth();
            $rows[] = [
                'label' => $bucketStart->format('Y-m'),
                'leads' => (int) LeadSourceDatum::query()->whereBetween('created_at', [$bucketStart, $bucketEnd])->count(),
                'converted' => (int) LeadSourceDatum::query()->where('funnel_converted', true)->whereBetween('created_at', [$bucketStart, $bucketEnd])->count(),
                'revenue' => (float) StudentRevenue::query()->whereBetween('updated_at', [$bucketStart, $bucketEnd])->sum('total_earned'),
            ];
            $cursor->addMonth();
            $limit++;
        }

        return $rows;
    }

    public function scheduled(Request $request)
    {
        $reports = MarketingReport::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('marketing-admin.kpi.scheduled', [
            'pageTitle' => 'Zamanlanmış Raporlar',
            'title'     => 'Zamanlanmış Raporlar',
            'reports'   => $reports,
        ]);
    }
}
