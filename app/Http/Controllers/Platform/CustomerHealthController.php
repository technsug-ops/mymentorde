<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CustomerHealthScore;
use App\Services\Platform\CustomerHealthService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Platform Owner — Customer Health & Churn Risk.
 *
 * Listeleme + detay + manuel recompute. Cross-company.
 */
class CustomerHealthController extends Controller
{
    public function __construct(private readonly CustomerHealthService $health)
    {
    }

    // ────────────────────────────────────────────────────────────────────────
    // INDEX — Risk listesi + 4 KPI + sortable tablo
    // ────────────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        // Filters
        $scoreMin   = (int) $request->query('score_min', 0);
        $scoreMax   = (int) $request->query('score_max', 100);
        $trend      = trim((string) $request->query('trend', ''));
        $flag       = trim((string) $request->query('flag', ''));
        $sort       = (string) $request->query('sort', 'overall_score');
        $direction  = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSort = ['overall_score', 'login_score', 'payment_score', 'usage_score', 'support_score', 'computed_at'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'overall_score';
        }

        $query = CustomerHealthScore::query()
            ->with('company')
            ->whereBetween('overall_score', [max(0, $scoreMin), min(100, $scoreMax)]);

        if ($trend !== '' && in_array($trend, [
            CustomerHealthScore::TREND_RISING,
            CustomerHealthScore::TREND_STABLE,
            CustomerHealthScore::TREND_DECLINING,
        ], true)) {
            $query->where('trend', $trend);
        }

        if ($flag !== '' && in_array($flag, CustomerHealthScore::FLAGS, true)) {
            // JSON column'da string aramasi
            $query->where('risk_flags', 'like', '%"' . $flag . '"%');
        }

        $scores = $query->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        // ── KPI'lar (4 kategori)
        $healthyCount  = (int) CustomerHealthScore::query()->healthy()->count();
        $warningCount  = (int) CustomerHealthScore::query()->warning()->count();
        $atRiskCount   = (int) CustomerHealthScore::query()
            ->whereBetween('overall_score', [20, 49])
            ->count();
        $criticalCount = (int) CustomerHealthScore::query()->critical()->count();

        $totalCount   = $healthyCount + $warningCount + $atRiskCount + $criticalCount;
        $lastComputed = (string) (CustomerHealthScore::query()->max('computed_at') ?? '');

        // ── En kritik 10 (risk priorities) — overall asc
        $criticalCompanies = CustomerHealthScore::query()
            ->with('company')
            ->orderBy('overall_score')
            ->orderBy('id')
            ->limit(10)
            ->get();

        return view('platform.customer-health.index', [
            'scores'             => $scores,
            'filters'            => [
                'score_min' => $scoreMin,
                'score_max' => $scoreMax,
                'trend'     => $trend,
                'flag'      => $flag,
                'sort'      => $sort,
                'direction' => $direction,
            ],
            'healthyCount'       => $healthyCount,
            'warningCount'       => $warningCount,
            'atRiskCount'        => $atRiskCount,
            'criticalCount'      => $criticalCount,
            'totalCount'         => $totalCount,
            'lastComputed'       => $lastComputed,
            'criticalCompanies'  => $criticalCompanies,
            'availableFlags'     => CustomerHealthScore::FLAGS,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SHOW — Detay: breakdown + risk flags + timeline
    // ────────────────────────────────────────────────────────────────────────

    public function show(int $companyId): View
    {
        $company = Company::query()->findOrFail($companyId);

        // Mevcut skor (yoksa anlik compute et)
        $score = $this->health->getHealthForCompany($companyId);
        if (!$score) {
            $this->health->computeForCompany($companyId);
            $score = $this->health->getHealthForCompany($companyId);
        }

        // Activity timeline — son 30g'lik basit kayitlar
        $timeline = $this->buildTimeline($company);

        // Risk flag remediation suggestions
        $flagDetails = [];
        foreach ((array) ($score?->risk_flags ?? []) as $flag) {
            $flagDetails[] = array_merge(['flag' => $flag], CustomerHealthScore::flagMeta($flag));
        }

        // Sub-score detaylari
        $breakdown = $score ? [
            ['key' => 'login',   'label' => 'Login Aktivitesi', 'score' => $score->login_score,   'weight' => CustomerHealthService::WEIGHT_LOGIN * 100,   'icon' => 'user-check'],
            ['key' => 'payment', 'label' => 'Ödeme Sağlığı',    'score' => $score->payment_score, 'weight' => CustomerHealthService::WEIGHT_PAYMENT * 100, 'icon' => 'credit-card'],
            ['key' => 'usage',   'label' => 'Feature Kullanımı', 'score' => $score->usage_score,   'weight' => CustomerHealthService::WEIGHT_USAGE * 100,   'icon' => 'activity'],
            ['key' => 'support', 'label' => 'Destek Yükü',       'score' => $score->support_score, 'weight' => CustomerHealthService::WEIGHT_SUPPORT * 100, 'icon' => 'life-buoy'],
        ] : [];

        return view('platform.customer-health.show', [
            'company'     => $company,
            'score'       => $score,
            'breakdown'   => $breakdown,
            'flagDetails' => $flagDetails,
            'timeline'    => $timeline,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // RECOMPUTE — manuel tetik (tek company)
    // ────────────────────────────────────────────────────────────────────────

    public function recompute(int $companyId): RedirectResponse
    {
        try {
            $this->health->computeForCompany($companyId);
            return back()->with('success', 'Sağlık skoru yeniden hesaplandı.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Hesaplama başarısız: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // RECOMPUTE ALL — tum company'ler
    // ────────────────────────────────────────────────────────────────────────

    public function recomputeAll(): RedirectResponse
    {
        try {
            $ok = $this->health->recomputeAll();
            return redirect()->route('platform.customer-health')
                ->with('success', "{$ok} şirket için sağlık skoru yeniden hesaplandı.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Toplu hesaplama başarısız: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Internal
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Activity timeline — basit, defansif. Son 30g icindeki onemli olaylar.
     *
     * @return array<int,array{at:string,icon:string,label:string,kind:string}>
     */
    protected function buildTimeline(Company $company): array
    {
        $now    = CarbonImmutable::now();
        $events = [];

        // 1) Son login (last_activity_at)
        try {
            if (Schema::hasColumn('users', 'last_activity_at')) {
                $lastActive = DB::table('users')
                    ->where('company_id', $company->id)
                    ->whereNotNull('last_activity_at')
                    ->orderByDesc('last_activity_at')
                    ->value('last_activity_at');
                if ($lastActive) {
                    $events[] = [
                        'at'    => (string) $lastActive,
                        'icon'  => 'user',
                        'label' => 'Son kullanıcı aktivitesi',
                        'kind'  => 'login',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 2) Son fatura
        try {
            if (Schema::hasTable('platform_invoices')) {
                $lastInvoice = DB::table('platform_invoices')
                    ->where('company_id', $company->id)
                    ->orderByDesc('id')
                    ->first();
                if ($lastInvoice) {
                    $events[] = [
                        'at'    => (string) ($lastInvoice->paid_at ?? $lastInvoice->sent_at ?? $lastInvoice->created_at),
                        'icon'  => 'credit-card',
                        'label' => "Fatura {$lastInvoice->invoice_number} — durum: {$lastInvoice->status}",
                        'kind'  => 'payment',
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        // 3) Son booking
        try {
            if (Schema::hasTable('public_bookings')) {
                $lastBooking = DB::table('public_bookings')
                    ->where('company_id', $company->id)
                    ->where('created_at', '>=', $now->subDays(30))
                    ->orderByDesc('created_at')
                    ->first();
                if ($lastBooking) {
                    $events[] = [
                        'at'    => (string) $lastBooking->created_at,
                        'icon'  => 'calendar',
                        'label' => 'Son booking kaydı',
                        'kind'  => 'usage',
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        // 4) Acik ticket
        try {
            if (Schema::hasTable('guest_tickets')) {
                $openTicket = DB::table('guest_tickets')
                    ->where('company_id', $company->id)
                    ->whereIn('status', ['open', 'in_progress', 'pending', 'waiting_customer'])
                    ->orderByDesc('created_at')
                    ->first();
                if ($openTicket) {
                    $events[] = [
                        'at'    => (string) $openTicket->created_at,
                        'icon'  => 'life-buoy',
                        'label' => 'Açık destek talebi',
                        'kind'  => 'support',
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        // En yeni once
        usort($events, fn($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        return array_slice($events, 0, 10);
    }
}
