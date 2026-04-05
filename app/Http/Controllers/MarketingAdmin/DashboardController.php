<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\LeadSourceDatum;
use App\Models\MarketingCampaign;
use App\Models\MarketingExternalMetric;
use App\Models\MarketingTask;
use App\Models\NotificationDispatch;
use App\Models\StudentRevenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /** @param mixed $current @param mixed $previous */
    private static function delta(mixed $current, mixed $previous): array
    {
        $c = (float) $current;
        $p = (float) $previous;
        $d = $p > 0 ? round(($c - $p) / $p * 100, 1) : ($c > 0 ? 100.0 : 0.0);
        return ['val' => $c, 'prev' => $p, 'delta' => $d, 'up' => $c >= $p];
    }

    public function index()
    {
        if (session('mktg_panel_mode') === 'sales') {
            return $this->salesIndex();
        }

        $cid  = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = Cache::remember('kpi:mktg:main:c' . $cid, 300, function (): array {
            $windowStart = now()->subDays(30);
            $prevStart   = now()->subDays(60);
            $prevEnd     = now()->subDays(31);

            $guestCount = (int) LeadSourceDatum::query()
                ->where('created_at', '>=', $windowStart)
                ->count();
            $verifiedCount = (int) LeadSourceDatum::query()
                ->where('created_at', '>=', $windowStart)
                ->whereNotNull('verified_source')
                ->count();
            $conversionRate = $guestCount > 0 ? round(($verifiedCount / $guestCount) * 100, 1) : 0.0;

            // Önceki dönem (31-60 gün)
            $prevGuestCount = (int) LeadSourceDatum::query()
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->count();
            $prevVerifiedCount = (int) LeadSourceDatum::query()
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->whereNotNull('verified_source')
                ->count();
            $prevConversionRate = $prevGuestCount > 0
                ? round(($prevVerifiedCount / $prevGuestCount) * 100, 1)
                : 0.0;

            $campaignCount = (int) MarketingCampaign::query()->count();
            $totalSpent = (float) MarketingCampaign::query()
                ->selectRaw('COALESCE(SUM(COALESCE(spent_amount, budget, 0)), 0) as total')
                ->value('total');
            $costPerAcquisition = $verifiedCount > 0 ? round($totalSpent / $verifiedCount, 2) : 0.0;

            $totalRevenue = (float) StudentRevenue::query()
                ->selectRaw('COALESCE(SUM(total_earned), 0) as total')
                ->value('total');
            $roi = $totalSpent > 0 ? round((($totalRevenue - $totalSpent) / $totalSpent) * 100, 1) : 0.0;

            // Önceki dönem external
            $prevExternalQuery = MarketingExternalMetric::query()
                ->whereBetween('metric_date', [$prevStart->toDateString(), $prevEnd->toDateString()]);
            $prevExternalSpend = round((float) (clone $prevExternalQuery)->sum('spend'), 2);
            $prevExternalConversions = (int) (clone $prevExternalQuery)->sum('conversions');

            $externalStart = now()->subDays(30)->toDateString();
            $externalEnd = now()->toDateString();
            $externalQuery = MarketingExternalMetric::query()
                ->whereBetween('metric_date', [$externalStart, $externalEnd]);
            $externalTotals = [
                'spend' => round((float) (clone $externalQuery)->sum('spend'), 2),
                'impressions' => (int) (clone $externalQuery)->sum('impressions'),
                'clicks' => (int) (clone $externalQuery)->sum('clicks'),
                'leads' => (int) (clone $externalQuery)->sum('leads'),
                'conversions' => (int) (clone $externalQuery)->sum('conversions'),
                'rows' => (int) (clone $externalQuery)->count(),
            ];
            $externalTotals['cpc'] = $externalTotals['clicks'] > 0
                ? round($externalTotals['spend'] / $externalTotals['clicks'], 2)
                : 0.0;
            $externalTotals['cpa'] = $externalTotals['conversions'] > 0
                ? round($externalTotals['spend'] / $externalTotals['conversions'], 2)
                : 0.0;
            $externalTotals['ctr'] = $externalTotals['impressions'] > 0
                ? round(($externalTotals['clicks'] / $externalTotals['impressions']) * 100, 2)
                : 0.0;

            $externalByProvider = (clone $externalQuery)
                ->selectRaw("COALESCE(NULLIF(provider, ''), 'unknown') as provider_key")
                ->selectRaw('COUNT(*) as row_count')
                ->selectRaw('COALESCE(SUM(spend), 0) as spend_total')
                ->selectRaw('COALESCE(SUM(clicks), 0) as click_total')
                ->selectRaw('COALESCE(SUM(conversions), 0) as conversion_total')
                ->groupBy('provider_key')
                ->orderByDesc('spend_total')
                ->get()
                ->map(fn ($row): array => [
                    'provider' => (string) $row->provider_key,
                    'rows' => (int) $row->row_count,
                    'spend' => round((float) $row->spend_total, 2),
                    'clicks' => (int) $row->click_total,
                    'conversions' => (int) $row->conversion_total,
                ])
                ->values()
                ->all();

            $externalTopCampaigns = (clone $externalQuery)
                ->selectRaw("COALESCE(NULLIF(campaign_name, ''), NULLIF(campaign_key, ''), 'unknown') as campaign_label")
                ->selectRaw('COALESCE(SUM(spend), 0) as spend_total')
                ->selectRaw('COALESCE(SUM(clicks), 0) as click_total')
                ->selectRaw('COALESCE(SUM(conversions), 0) as conversion_total')
                ->groupBy('campaign_label')
                ->orderByDesc('spend_total')
                ->limit(6)
                ->get()
                ->map(fn ($row): array => [
                    'campaign' => (string) $row->campaign_label,
                    'spend' => round((float) $row->spend_total, 2),
                    'clicks' => (int) $row->click_total,
                    'conversions' => (int) $row->conversion_total,
                ])
                ->values()
                ->all();

            $sourcePerformance = LeadSourceDatum::query()
                ->select('initial_source')
                ->selectRaw('COUNT(*) as guest_count')
                ->selectRaw('SUM(CASE WHEN verified_source IS NOT NULL THEN 1 ELSE 0 END) as student_count')
                ->groupBy('initial_source')
                ->orderByDesc('guest_count')
                ->limit(8)
                ->get()
                ->map(function ($row): array {
                    $guest = (int) $row->guest_count;
                    $student = (int) $row->student_count;
                    return [
                        'source' => (string) $row->initial_source,
                        'guest_count' => $guest,
                        'student_count' => $student,
                        'conversion_rate' => $guest > 0 ? round(($student / $guest) * 100, 1) : 0.0,
                    ];
                })
                ->values()
                ->all();

            $topCampaigns = LeadSourceDatum::query()
                ->whereNotNull('utm_campaign')
                ->where('utm_campaign', '!=', '')
                ->select('utm_campaign')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('utm_campaign')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(fn ($row): array => [
                    'name' => (string) $row->utm_campaign,
                    'total' => (int) $row->total,
                ])
                ->values()
                ->all();

            $sourceMatchRate = LeadSourceDatum::query()
                ->whereNotNull('source_match')
                ->selectRaw('AVG(CASE WHEN source_match = 1 THEN 1.0 ELSE 0.0 END) as ratio')
                ->value('ratio');
            $sourceMatchRate = $sourceMatchRate !== null ? round(((float) $sourceMatchRate) * 100, 1) : null;

            $benchmark = [
                'guests'      => self::delta($guestCount, $prevGuestCount),
                'conversions' => self::delta($verifiedCount, $prevVerifiedCount),
                'conv_rate'   => self::delta($conversionRate, $prevConversionRate),
                'spend'       => self::delta($externalTotals['spend'], $prevExternalSpend),
                'ext_conv'    => self::delta($externalTotals['conversions'], $prevExternalConversions),
            ];

            return [
                'kpis' => [
                    'guest_count' => $guestCount,
                    'conversion_rate' => $conversionRate,
                    'cpa' => $costPerAcquisition,
                    'roi' => $roi,
                    'verified_count' => $verifiedCount,
                    'campaign_count' => $campaignCount,
                    'source_match_rate' => $sourceMatchRate,
                    'external_spend' => $externalTotals['spend'],
                    'external_clicks' => $externalTotals['clicks'],
                    'external_conversions' => $externalTotals['conversions'],
                    'external_rows' => $externalTotals['rows'],
                ],
                'benchmark'            => $benchmark,
                'sourcePerformance'    => $sourcePerformance,
                'topCampaigns'         => $topCampaigns,
                'externalTotals'       => $externalTotals,
                'externalByProvider'   => $externalByProvider,
                'externalTopCampaigns' => $externalTopCampaigns,
            ];
        });

        return view('marketing-admin.dashboard.index', array_merge(
            ['pageTitle' => 'Marketing+Sales Dashboard'],
            $data,
        ));
    }

    private function salesIndex()
    {
        $cid  = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = Cache::remember('kpi:mktg:sales:c' . $cid, 300, function () use ($cid): array {
            $windowStart = now()->subDays(30);
            $prevStart   = now()->subDays(60);
            $prevEnd     = now()->subDays(31);

            $guestBase = fn () => GuestApplication::query()
                ->when($cid > 0, fn ($q) => $q->where('company_id', $cid));

            // Yeni leadler (guest_applications son 30g)
            $newLeads = (int) $guestBase()
                ->where('created_at', '>=', $windowStart)
                ->count();
            $prevLeads = (int) $guestBase()
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->count();

            // Dönüşüm: approved olanlar (approved = sözleşme onaylandı = student)
            $converted = (int) $guestBase()
                ->where('contract_status', 'approved')
                ->where('created_at', '>=', $windowStart)
                ->count();
            $prevConverted = (int) $guestBase()
                ->where('contract_status', 'approved')
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->count();

            $convRate     = $newLeads > 0 ? round($converted / $newLeads * 100, 1) : 0.0;
            $prevConvRate = $prevLeads > 0 ? round($prevConverted / $prevLeads * 100, 1) : 0.0;

            // Pipeline aşamaları (tüm aktif guest_applications)
            $pipelineStages = $guestBase()
                ->selectRaw("COALESCE(NULLIF(contract_status,''),'not_requested') as stage, COUNT(*) as cnt")
                ->whereNull('deleted_at')
                ->groupBy('stage')
                ->orderByDesc('cnt')
                ->get()
                ->map(fn ($r) => ['stage' => (string) $r->stage, 'count' => (int) $r->cnt])
                ->values()
                ->all();

            // Lead kaynak dağılımı (son 30g)
            $sourceBreakdown = LeadSourceDatum::query()
                ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
                ->where('created_at', '>=', $windowStart)
                ->select('initial_source')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted')
                ->whereNotNull('initial_source')
                ->groupBy('initial_source')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn ($r) => [
                    'source'    => (string) $r->initial_source,
                    'total'     => (int) $r->total,
                    'converted' => (int) $r->converted,
                    'rate'      => (int) $r->total > 0 ? round((int) $r->converted / (int) $r->total * 100, 1) : 0.0,
                ])
                ->values()
                ->all();

            // Aylık gelir (öğrenci gelirleri) — company_id kolonu yoksa atla
            try {
                $monthlyRevenue = (float) StudentRevenue::query()
                    ->where('created_at', '>=', $windowStart)
                    ->selectRaw('COALESCE(SUM(total_earned), 0) as total')
                    ->value('total');
            } catch (\Throwable) {
                $monthlyRevenue = 0.0;
            }

            // Lead Score Tier dağılımı (v3.0 — migration henüz çalıştırılmamışsa boş döner)
            try {
                $scoreTierRows = $guestBase()
                    ->selectRaw("COALESCE(NULLIF(lead_score_tier,''), 'cold') as tier, COUNT(*) as total")
                    ->whereNull('deleted_at')
                    ->groupBy('tier')
                    ->get()
                    ->keyBy('tier')
                    ->map(fn ($r) => (int) $r->total)
                    ->all();

                $avgLeadScore = round((float) ($guestBase()->avg('lead_score') ?? 0), 1);
            } catch (\Throwable) {
                $scoreTierRows = [];
                $avgLeadScore  = 0.0;
            }

            return [
                'newLeads'       => $newLeads,
                'prevLeads'      => $prevLeads,
                'converted'      => $converted,
                'prevConverted'  => $prevConverted,
                'convRate'       => $convRate,
                'prevConvRate'   => $prevConvRate,
                'pipelineStages' => $pipelineStages,
                'sourceBreakdown'=> $sourceBreakdown,
                'monthlyRevenue' => $monthlyRevenue,
                'scoreTierRows'  => $scoreTierRows,
                'avgLeadScore'   => $avgLeadScore,
                'benchmark'      => [
                    'leads' => self::delta($newLeads, $prevLeads),
                    'conv'  => self::delta($converted, $prevConverted),
                    'rate'  => self::delta($convRate, $prevConvRate),
                ],
            ];
        });

        // Kullanıcıya özel veriler cache dışında tutulur
        $userId = (int) (auth()->id() ?? 0);

        $myTasks = $userId > 0
            ? MarketingTask::query()
                ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
                ->where('assigned_user_id', $userId)
                ->whereNotIn('status', ['done', 'cancelled'])
                ->whereNull('deleted_at')
                ->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END")
                ->orderBy('due_date')
                ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
                ->limit(20)
                ->get(['id', 'title', 'status', 'priority', 'due_date', 'department'])
            : collect();

        $myNotifications = $userId > 0
            ? NotificationDispatch::query()
                ->where('user_id', $userId)
                ->where('channel', 'in_app')
                ->where('status', 'pending')
                ->latest()
                ->limit(10)
                ->get(['id', 'subject', 'body', 'created_at'])
            : collect();

        return view('marketing-admin.dashboard.sales', array_merge($data, [
            'pageTitle'       => 'Sales Dashboard',
            'stageLabels'     => [
                'not_requested'   => 'Sözleşme Başlatılmadı',
                'pending_manager' => 'Manager Onayı Bekliyor',
                'requested'       => 'Öğrenci Davet Edildi',
                'signed_uploaded' => 'İmzalı Yüklendi',
                'approved'        => 'Onaylandı (Öğrenci)',
                'cancelled'       => 'İptal Edildi',
                'reopen_requested'=> 'Yeniden Değerlendirme',
            ],
            'myTasks'         => $myTasks,
            'myNotifications' => $myNotifications,
            'today'           => now()->toDateString(),
            'scoreTierRows'   => collect($data['scoreTierRows']),
        ]));
    }

    public function managerView()
    {
        return $this->index();
    }

    public function audienceSuggestions(Request $request): JsonResponse
    {
        $campaignType = trim((string) $request->query('campaign_type', ''));
        $companyId    = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $cacheKey     = "audience_suggestions_{$companyId}_{$campaignType}";

        $suggestions = Cache::remember($cacheKey, 3600, function () use ($campaignType): array {
            $since = now()->subDays(90)->toDateString();

            // Top converting sources (lead_source_option_id / initial_source)
            $rows = LeadSourceDatum::query()
                ->select('initial_source')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN funnel_converted = 1 THEN 1 ELSE 0 END) as converted')
                ->where('created_at', '>=', $since)
                ->whereNotNull('initial_source')
                ->where('initial_source', '!=', '')
                ->groupBy('initial_source')
                ->orderByDesc('converted')
                ->limit(10)
                ->get();

            $suggestions = [];
            foreach ($rows as $row) {
                $total     = (int) $row->total;
                $converted = (int) $row->converted;
                if ($total === 0) continue;

                $convRate  = round($converted / $total * 100, 1);
                $types     = $this->relevantCampaignTypes($row->initial_source, $convRate);

                // Filter by requested campaign_type if specified
                if ($campaignType !== '' && ! in_array($campaignType, $types, true)) {
                    continue;
                }

                $suggestions[] = [
                    'source_label'    => (string) $row->initial_source,
                    'conversion_rate' => $convRate,
                    'student_count'   => $converted,
                    'lead_count'      => $total,
                    'campaign_types'  => $types,
                ];
            }

            // Sort by conversion rate descending
            usort($suggestions, fn ($a, $b) => $b['conversion_rate'] <=> $a['conversion_rate']);

            return $suggestions;
        });

        return response()->json($suggestions);
    }

    /**
     * Derive relevant campaign types from source characteristics.
     *
     * @return string[]
     */
    private function relevantCampaignTypes(string $source, float $convRate): array
    {
        $types = [];
        $src   = strtolower($source);

        if (str_contains($src, 'organic') || str_contains($src, 'seo')) {
            $types[] = 'awareness';
        }
        if (str_contains($src, 'paid') || str_contains($src, 'google') || str_contains($src, 'meta') || str_contains($src, 'facebook')) {
            $types[] = 'lead_gen';
        }
        if ($convRate >= 20) {
            $types[] = 'conversion';
        }
        if (str_contains($src, 'referral') || str_contains($src, 'dealer') || str_contains($src, 'email')) {
            $types[] = 'retention';
        }

        // Always include awareness + lead_gen as fallback
        if ($types === []) {
            $types = ['awareness', 'lead_gen'];
        }

        return array_values(array_unique($types));
    }
}
