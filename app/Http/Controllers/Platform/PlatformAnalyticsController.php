<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\ModuleAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Platform Owner — Cross-company analytics.
 *
 * Bu controller `platform.owner` middleware ile korunur. Tüm sorgular
 * BelongsToCompany global scope'unu BYPASS eder (`withoutGlobalScopes()`).
 *
 * Tüm pahalı KPI hesaplamaları 5dk cache'lenir — key: range + segment hash.
 */
class PlatformAnalyticsController extends Controller
{
    // Tanınan date range'ler (gün cinsinden)
    private const RANGES = [
        '7d'   => 7,
        '30d'  => 30,
        '90d'  => 90,
        '365d' => 365,
    ];

    private const CACHE_TTL = 300; // 5dk

    public function index(Request $request): View
    {
        $range   = $this->normalizeRange($request->query('range', '90d'));
        $segment = $this->normalizeSegment($request->query('segment', 'all'));

        $data = $this->computeAnalytics($range, $segment);

        return view('platform.analytics.index', $data + [
            'range'    => $range,
            'segment'  => $segment,
            'ranges'   => array_keys(self::RANGES),
            'segments' => ['all', 'trial', 'paid'],
        ]);
    }

    /**
     * CSV export — full analytics snapshot.
     */
    public function exportCsv(Request $request): Response
    {
        $range   = $this->normalizeRange($request->query('range', '90d'));
        $segment = $this->normalizeSegment($request->query('segment', 'all'));
        $data    = $this->computeAnalytics($range, $segment);

        $filename = 'platform-analytics-' . $range . '-' . $segment . '-' . now()->format('Ymd-His') . '.csv';

        $csv = [];
        $csv[] = ['MentorDE Platform Analytics'];
        $csv[] = ['Range', $range];
        $csv[] = ['Segment', $segment];
        $csv[] = ['Generated', now()->toIso8601String()];
        $csv[] = [];
        $csv[] = ['== KPI =='];
        foreach ($data['kpis'] as $k => $v) {
            $csv[] = [$k, is_array($v) ? json_encode($v) : (string) $v];
        }
        $csv[] = [];
        $csv[] = ['== TIER DAGILIM =='];
        $csv[] = ['tier', 'companies', 'total_users', 'avg_users_per_company'];
        foreach ($data['tierDistribution'] as $tier => $row) {
            $csv[] = [$tier, $row['companies'], $row['total_users'], $row['avg_users']];
        }
        $csv[] = [];
        $csv[] = ['== MODUL KULLANIM =='];
        $csv[] = ['module', 'company_count', 'percent'];
        foreach ($data['moduleUsage'] as $m => $row) {
            $csv[] = [$m, $row['count'], $row['percent']];
        }
        $csv[] = [];
        $csv[] = ['== TOP COMPANIES =='];
        $csv[] = ['rank', 'name', 'tier', 'users', 'bookings_30d', 'score'];
        foreach ($data['topCompanies'] as $i => $row) {
            $csv[] = [$i + 1, $row['name'], $row['tier'], $row['users'], $row['bookings_30d'], $row['score']];
        }
        $csv[] = [];
        $csv[] = ['== BOOKING FUNNEL =='];
        foreach ($data['bookingFunnel'] as $stage => $count) {
            $csv[] = [$stage, $count];
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
    // ANALYTICS HESAPLAMA (cache'li)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string,mixed>
     */
    private function computeAnalytics(string $range, string $segment): array
    {
        $key = sprintf('platform_analytics_%s_%s', $range, $segment);
        return Cache::remember($key, self::CACHE_TTL, function () use ($range, $segment): array {
            return $this->buildAnalytics($range, $segment);
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function buildAnalytics(string $range, string $segment): array
    {
        $now    = CarbonImmutable::now();
        $days   = self::RANGES[$range] ?? 90;
        $from   = $now->subDays($days);
        $last30 = $now->subDays(30);

        // Segment'e göre company filter
        $companyQuery = Company::query()->withoutGlobalScopes();
        if ($segment === 'trial') {
            $companyQuery->where('subscription_tier', Company::TIER_TRIAL);
        } elseif ($segment === 'paid') {
            $companyQuery->whereIn('subscription_tier', [
                Company::TIER_BASIC,
                Company::TIER_GOLD,
                Company::TIER_PREMIUM,
            ]);
        }
        $companies   = $companyQuery->get();
        $companyIds  = $companies->pluck('id')->all();

        // ── KPI 1: Aktif kullanıcı sayıları (role bazlı)
        $userByRole = [];
        if (Schema::hasTable('users')) {
            $rows = User::query()->withoutGlobalScopes()
                ->whereIn('company_id', $companyIds)
                ->where('is_active', true)
                ->groupBy('role')
                ->select('role', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'role')
                ->all();
            foreach ($rows as $role => $total) {
                $userByRole[$role] = (int) $total;
            }
        }
        $totalActiveUsers = array_sum($userByRole);

        // ── KPI 2 & 3: Booking sayısı + revenue (period)
        $bookingCount   = 0;
        $bookingRevenue = 0;
        if (Schema::hasTable('public_bookings')) {
            $bq = DB::table('public_bookings')
                ->whereIn('company_id', $companyIds)
                ->where('created_at', '>=', $from);
            $bookingCount = (int) $bq->count();

            if (Schema::hasColumn('public_bookings', 'amount_gross_cents')) {
                $bookingRevenue = (int) DB::table('public_bookings')
                    ->whereIn('company_id', $companyIds)
                    ->where('created_at', '>=', $from)
                    ->where(function ($q): void {
                        if (Schema::hasColumn('public_bookings', 'payment_status')) {
                            $q->whereIn('payment_status', ['paid', 'refunded'])
                              ->orWhere('amount_gross_cents', '>', 0);
                        }
                    })
                    ->sum('amount_gross_cents');
            }
        }
        $bookingRevenueEur = round($bookingRevenue / 100, 2);

        // ── KPI 4: Active trial company
        $activeTrials = $companies->where('subscription_tier', Company::TIER_TRIAL)
            ->where('is_active', true)
            ->count();

        // ── KPI 5: Yeni signup (period)
        $newSignups = $companies->filter(function ($c) use ($from): bool {
            return $c->created_at && $c->created_at->gte($from);
        })->count();

        // ── KPI 6: Churn (proxy — trial bitiş geçmiş + hala trial)
        $churnCount = $companies->filter(function ($c) use ($now): bool {
            return $c->subscription_tier === Company::TIER_TRIAL
                && $c->trial_ends_at
                && $c->trial_ends_at->lt($now);
        })->count();

        $kpis = [
            'total_active_users'   => $totalActiveUsers,
            'bookings_period'      => $bookingCount,
            'booking_revenue_eur'  => $bookingRevenueEur,
            'active_trials'        => $activeTrials,
            'new_signups_period'   => $newSignups,
            'churn_count'          => $churnCount,
        ];

        // ── Tier dağılımı — company sayısı × ortalama user
        $tierDistribution = [];
        foreach (Company::TIERS as $tier) {
            $tierCompanies   = $companies->where('subscription_tier', $tier);
            $tierCompanyIds  = $tierCompanies->pluck('id')->all();
            $totalUsersTier  = 0;
            if (!empty($tierCompanyIds) && Schema::hasTable('users')) {
                $totalUsersTier = (int) User::query()->withoutGlobalScopes()
                    ->whereIn('company_id', $tierCompanyIds)
                    ->where('is_active', true)
                    ->count();
            }
            $companyCount = $tierCompanies->count();
            $tierDistribution[$tier] = [
                'companies'    => $companyCount,
                'total_users'  => $totalUsersTier,
                'avg_users'    => $companyCount > 0 ? round($totalUsersTier / $companyCount, 1) : 0,
            ];
        }

        // ── Modül heatmap
        $totalCompanies = max(1, count($companyIds));
        $moduleUsage    = [];
        foreach (array_keys(ModuleAccess::MODULE_META) as $moduleKey) {
            $moduleUsage[$moduleKey] = ['count' => 0, 'percent' => 0];
        }
        foreach ($companies as $c) {
            $enabled = ModuleAccess::enabledModules((int) $c->id);
            foreach ($enabled as $m) {
                if (isset($moduleUsage[$m])) {
                    $moduleUsage[$m]['count']++;
                }
            }
        }
        foreach ($moduleUsage as $m => $row) {
            $moduleUsage[$m]['percent'] = $totalCompanies > 0
                ? round($row['count'] / $totalCompanies * 100)
                : 0;
        }
        uasort($moduleUsage, fn ($a, $b) => $b['count'] <=> $a['count']);

        // ── Top 10 active company (user count + booking_30d)
        $userCountsByCompany = [];
        if (Schema::hasTable('users')) {
            $userCountsByCompany = User::query()->withoutGlobalScopes()
                ->whereIn('company_id', $companyIds)
                ->where('is_active', true)
                ->groupBy('company_id')
                ->select('company_id', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'company_id')
                ->all();
        }
        $bookingCountsByCompany = [];
        if (Schema::hasTable('public_bookings')) {
            $bookingCountsByCompany = DB::table('public_bookings')
                ->whereIn('company_id', $companyIds)
                ->where('created_at', '>=', $last30)
                ->groupBy('company_id')
                ->select('company_id', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'company_id')
                ->all();
        }

        $topCompanies = $companies->map(function ($c) use ($userCountsByCompany, $bookingCountsByCompany): array {
            $users    = (int) ($userCountsByCompany[$c->id] ?? 0);
            $bookings = (int) ($bookingCountsByCompany[$c->id] ?? 0);
            return [
                'id'           => (int) $c->id,
                'name'         => (string) $c->name,
                'tier'         => (string) $c->subscription_tier,
                'users'        => $users,
                'bookings_30d' => $bookings,
                // Skor: user count + 3× booking → aktiflik ağırlıklı
                'score'        => $users + 3 * $bookings,
            ];
        })->sortByDesc('score')->take(10)->values()->all();

        // ── Booking funnel: created → paid → completed → canceled
        $bookingFunnel = [
            'created'   => 0,
            'pending'   => 0,
            'paid'      => 0,
            'completed' => 0,
            'canceled'  => 0,
        ];
        if (Schema::hasTable('public_bookings')) {
            $bookingFunnel['created'] = (int) DB::table('public_bookings')
                ->whereIn('company_id', $companyIds)
                ->where('created_at', '>=', $from)
                ->count();

            if (Schema::hasColumn('public_bookings', 'payment_status')) {
                $bookingFunnel['pending'] = (int) DB::table('public_bookings')
                    ->whereIn('company_id', $companyIds)
                    ->where('created_at', '>=', $from)
                    ->whereIn('payment_status', ['pending_payment'])
                    ->count();
                $bookingFunnel['paid'] = (int) DB::table('public_bookings')
                    ->whereIn('company_id', $companyIds)
                    ->where('created_at', '>=', $from)
                    ->whereIn('payment_status', ['paid'])
                    ->count();
            } else {
                $bookingFunnel['paid'] = $bookingFunnel['created'];
            }

            $bookingFunnel['completed'] = (int) DB::table('public_bookings')
                ->whereIn('company_id', $companyIds)
                ->where('created_at', '>=', $from)
                ->where('status', 'completed')
                ->count();

            $bookingFunnel['canceled'] = (int) DB::table('public_bookings')
                ->whereIn('company_id', $companyIds)
                ->where('created_at', '>=', $from)
                ->whereIn('status', ['canceled_by_invitee', 'canceled_by_senior'])
                ->count();
        }
        // Funnel max
        $funnelMax = max(1, max(array_values($bookingFunnel)));

        return [
            'kpis'             => $kpis,
            'userByRole'       => $userByRole,
            'tierDistribution' => $tierDistribution,
            'moduleUsage'      => $moduleUsage,
            'moduleMeta'       => ModuleAccess::MODULE_META,
            'topCompanies'     => $topCompanies,
            'bookingFunnel'    => $bookingFunnel,
            'funnelMax'        => $funnelMax,
            'tierLabels'       => $this->tierLabels(),
            'rangeDays'        => $days,
            'periodFrom'       => $from,
            'periodTo'         => $now,
            'totalCompanies'   => count($companyIds),
        ];
    }

    private function normalizeRange(?string $range): string
    {
        $range = (string) $range;
        return array_key_exists($range, self::RANGES) ? $range : '90d';
    }

    private function normalizeSegment(?string $segment): string
    {
        $segment = (string) $segment;
        return in_array($segment, ['all', 'trial', 'paid'], true) ? $segment : 'all';
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
