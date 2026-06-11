<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Platform Owner — MRR (Monthly Recurring Revenue) trend & projection.
 *
 * Snapshot tablo (companies_mrr_snapshots) henüz yok — bu yüzden geçmiş aylar
 * mevcut Company state'inden simüle edilir:
 *   - created_at < monthEnd && (subscription_renews_at IS NULL || > monthEnd)
 *     koşulunu sağlayan company'ler o ayda aktif kabul edilir.
 *   - O ayın MRR'ı tier MRR toplamı.
 *
 * Snapshot tablo sonradan eklenirse buradan oraya bakılacak (TODO).
 *
 * Cache: 5dk.
 */
class PlatformMRRController extends Controller
{
    private const RANGES = [
        '6m'  => 6,
        '12m' => 12,
        '24m' => 24,
    ];

    private const CACHE_TTL = 300;

    // Ortalama subscription ömrü (ay) — LTV hesabı için
    private const AVG_SUBSCRIPTION_MONTHS = 12;

    public function index(Request $request): View
    {
        $range = $this->normalizeRange($request->query('range', '12m'));
        $data  = $this->computeMrr($range);

        return view('platform.mrr-trend.index', $data + [
            'range'  => $range,
            'ranges' => array_keys(self::RANGES),
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        $range = $this->normalizeRange($request->query('range', '12m'));
        $data  = $this->computeMrr($range);

        $filename = 'platform-mrr-' . $range . '-' . now()->format('Ymd-His') . '.csv';

        $csv   = [];
        $csv[] = ['MentorDE Platform — MRR Trend'];
        $csv[] = ['Range', $range];
        $csv[] = ['Generated', now()->toIso8601String()];
        $csv[] = [];
        $csv[] = ['== KPI =='];
        $csv[] = ['current_mrr_eur',  $data['currentMrr']];
        $csv[] = ['arr_projection',   $data['arrProjection']];
        $csv[] = ['arpu_eur',         $data['arpu']];
        $csv[] = ['ltv_eur',          $data['ltv']];
        $csv[] = ['churn_rate_pct',   $data['churnRatePct']];
        $csv[] = ['active_companies', $data['activeCompanies']];
        $csv[] = [];
        $csv[] = ['== MONTHLY TREND =='];
        $csv[] = ['month', 'mrr_eur', 'active_companies', 'new', 'churn', 'net_new'];
        foreach ($data['trend'] as $row) {
            $csv[] = [$row['month'], $row['mrr'], $row['active'], $row['new'], $row['churn'], $row['net_new']];
        }
        $csv[] = [];
        $csv[] = ['== TIER BREAKDOWN =='];
        $csv[] = ['tier', 'companies', 'unit_mrr_eur', 'total_mrr_eur'];
        foreach ($data['tierBreakdown'] as $tier => $row) {
            $csv[] = [$tier, $row['companies'], $row['unit_mrr'], $row['total_mrr']];
        }

        $handle = fopen('php://temp', 'w+');
        foreach ($csv as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $body = stream_get_contents($handle);
        fclose($handle);

        return response($body, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // MRR HESAPLAMA
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string,mixed>
     */
    private function computeMrr(string $range): array
    {
        $key = 'platform_mrr_' . $range;
        return Cache::remember($key, self::CACHE_TTL, function () use ($range): array {
            return $this->buildMrr($range);
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function buildMrr(string $range): array
    {
        $now    = CarbonImmutable::now();
        $months = self::RANGES[$range] ?? 12;

        $companies = Company::query()->withoutGlobalScopes()->get();

        // Tier MRR tablosu — config'ten
        $tierMrr = [];
        foreach (Company::TIERS as $tier) {
            $tierMrr[$tier] = (float) (config("subscription_tiers.{$tier}.mrr_eur", 0));
        }

        // ── Current MRR (active companies)
        $activeNow = $companies->where('is_active', true);
        $activeCompaniesCount = $activeNow->count();

        // Company.mrr_eur explicit set ise onu kullan, yoksa tier default
        $currentMrr = $activeNow->sum(function ($c) use ($tierMrr): float {
            $explicit = (float) ($c->mrr_eur ?? 0);
            if ($explicit > 0) return $explicit;
            return (float) ($tierMrr[$c->subscription_tier] ?? 0);
        });

        // ── ARR Projection = MRR × 12
        $arrProjection = $currentMrr * 12;

        // ── ARPU = MRR / active companies
        $arpu = $activeCompaniesCount > 0 ? $currentMrr / $activeCompaniesCount : 0;

        // ── LTV = ARPU × ortalama abonelik süresi (ay)
        $ltv = $arpu * self::AVG_SUBSCRIPTION_MONTHS;

        // ── Tier breakdown (current)
        $tierBreakdown = [];
        foreach (Company::TIERS as $tier) {
            $count = $activeNow->where('subscription_tier', $tier)->count();
            $unit  = $tierMrr[$tier];
            $tierBreakdown[$tier] = [
                'companies' => $count,
                'unit_mrr'  => round($unit, 2),
                'total_mrr' => round($unit * $count, 2),
            ];
        }
        $tierMaxTotal = 1;
        foreach ($tierBreakdown as $row) {
            $tierMaxTotal = max($tierMaxTotal, $row['total_mrr']);
        }

        // ── Aylık MRR trend (geriye doğru simulate)
        // Snapshot tablo yok → mevcut state'ten heuristic:
        //  - company o ayda var mıydı: created_at <= monthEnd
        //  - hala active mi: is_active=true && (renews_at IS NULL || renews_at >= monthEnd)
        //  - tier MRR = company.mrr_eur veya tier default
        $trend = [];
        $prevMrr      = null;
        $prevActiveCt = null;
        $totalChurnAll = 0;
        $totalActiveAvg = 0;

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->subMonths($i)->startOfMonth();
            $monthEnd   = $now->subMonths($i)->endOfMonth();
            $monthLabel = $monthStart->format('M Y');

            $monthlyActive = $companies->filter(function ($c) use ($monthStart, $monthEnd): bool {
                if (!$c->created_at) return false;
                if ($c->created_at->gt($monthEnd)) return false;
                if (!$c->is_active) {
                    // hala active değil — sadece updated_at ay sonundan önceyse "o ay aktif sayılmasın"
                    if ($c->updated_at && $c->updated_at->lt($monthStart)) return false;
                }
                // subscription_renews_at o ayda dolmuş mu?
                if ($c->subscription_renews_at && $c->subscription_renews_at->lt($monthStart)) {
                    return false;
                }
                return true;
            });

            $monthMrr = $monthlyActive->sum(function ($c) use ($tierMrr): float {
                $explicit = (float) ($c->mrr_eur ?? 0);
                if ($explicit > 0) return $explicit;
                return (float) ($tierMrr[$c->subscription_tier] ?? 0);
            });

            $newSignup = $companies->filter(function ($c) use ($monthStart, $monthEnd): bool {
                return $c->created_at && $c->created_at->between($monthStart, $monthEnd);
            })->count();

            $activeCt = $monthlyActive->count();
            $churnEstimate = ($prevActiveCt !== null && $activeCt < $prevActiveCt)
                ? ($prevActiveCt - $activeCt + $newSignup)
                : 0;
            // churn_estimate negatif olmasın
            $churnEstimate = max(0, $churnEstimate);

            $netNew = $newSignup - $churnEstimate;

            $trend[] = [
                'month'   => $monthLabel,
                'mrr'     => round($monthMrr, 2),
                'active'  => $activeCt,
                'new'     => $newSignup,
                'churn'   => $churnEstimate,
                'net_new' => $netNew,
            ];

            $totalChurnAll  += $churnEstimate;
            $totalActiveAvg += $activeCt;
            $prevMrr      = $monthMrr;
            $prevActiveCt = $activeCt;
        }

        // Trend max MRR (chart için)
        $trendMaxMrr = 1;
        foreach ($trend as $row) {
            $trendMaxMrr = max($trendMaxMrr, $row['mrr']);
        }

        // Churn rate = sum(churn) / sum(active) over period
        $churnRatePct = $totalActiveAvg > 0 ? round(($totalChurnAll / $totalActiveAvg) * 100, 2) : 0;

        // MRR delta (geçen aya göre)
        $mrrDelta    = 0;
        $mrrDeltaPct = 0;
        if (count($trend) >= 2) {
            $last = end($trend);
            $prev = $trend[count($trend) - 2];
            $mrrDelta    = $last['mrr'] - $prev['mrr'];
            $mrrDeltaPct = $prev['mrr'] > 0 ? round(($mrrDelta / $prev['mrr']) * 100, 1) : 0;
        }

        return [
            'currentMrr'      => round($currentMrr, 2),
            'arrProjection'   => round($arrProjection, 2),
            'arpu'            => round($arpu, 2),
            'ltv'             => round($ltv, 2),
            'activeCompanies' => $activeCompaniesCount,
            'tierBreakdown'   => $tierBreakdown,
            'tierMaxTotal'    => $tierMaxTotal,
            'trend'           => $trend,
            'trendMaxMrr'     => $trendMaxMrr,
            'churnRatePct'    => $churnRatePct,
            'totalChurnAll'   => $totalChurnAll,
            'mrrDelta'        => round($mrrDelta, 2),
            'mrrDeltaPct'     => $mrrDeltaPct,
            'avgSubMonths'    => self::AVG_SUBSCRIPTION_MONTHS,
            'tierLabels'      => $this->tierLabels(),
            'months'          => $months,
        ];
    }

    private function normalizeRange(?string $range): string
    {
        $range = (string) $range;
        return array_key_exists($range, self::RANGES) ? $range : '12m';
    }

    private function tierLabels(): array
    {
        $tiers = config('subscription_tiers', []);
        $out = [];
        foreach ($tiers as $key => $cfg) {
            $out[$key] = $cfg['label'] ?? ucfirst($key);
        }
        return $out;
    }
}
