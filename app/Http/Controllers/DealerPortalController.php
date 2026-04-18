<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Dealer\Concerns\DealerPortalTrait;
use App\Models\Dealer;
use App\Models\DealerStudentRevenue;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use App\Support\DealerTierPermissions;
use Illuminate\Http\Request;

class DealerPortalController extends Controller
{
    use DealerPortalTrait;

    public function __construct(
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function dashboard(Request $request)
    {
        $user       = $request->user();
        $dealerCode = strtoupper(trim((string) ($user->dealer_code ?? '')));

        $dealer              = null;
        $students            = collect();
        $revenues            = collect();
        $guestLeads          = collect();
        $recentActivity      = collect();
        $typeDistribution    = collect();
        $channelDistribution = collect();

        if ($dealerCode !== '') {
            $dealer = Dealer::query()->where('code', $dealerCode)->first();

            $students = StudentAssignment::query()
                ->where('dealer_id', $dealerCode)
                ->latest('updated_at')->limit(12)
                ->get(['student_id', 'senior_email', 'branch', 'risk_level', 'payment_status', 'updated_at']);

            $revenues = DealerStudentRevenue::query()
                ->where('dealer_id', $dealerCode)
                ->latest('updated_at')->limit(20)
                ->get(['student_id', 'dealer_type', 'milestone_progress', 'total_earned', 'total_pending', 'updated_at']);

            $guestLeads = GuestApplication::query()
                ->where('dealer_code', $dealerCode)->latest()->limit(500)
                ->get(['id', 'first_name', 'last_name', 'application_type', 'lead_source',
                       'lead_status', 'contract_status', 'converted_student_id', 'selected_package_code',
                       'referral_type', 'converted_at', 'created_at', 'updated_at']);

            $typeDistribution    = $guestLeads->groupBy(fn ($g) => (string) ($g->application_type ?: 'other'))->map(fn ($items) => $items->count())->sortDesc();
            $channelDistribution = $guestLeads->groupBy(fn ($g) => (string) ($g->lead_source ?: 'unknown'))->map(fn ($items) => $items->count())->sortDesc();
            $recentActivity      = $guestLeads->take(10)->map(fn ($g) => [
                'label'      => '#'.$g->id.' '.$g->first_name.' '.$g->last_name,
                'meta'       => trim(($g->application_type ?: '-').' | '.($g->lead_status ?: '-').' | '.($g->contract_status ?: '-')),
                'created_at' => $g->created_at,
            ]);
        }

        $totalRevenue     = (float) $revenues->sum(fn ($r) => (float) ($r->total_earned ?? 0));
        $totalPending     = (float) $revenues->sum(fn ($r) => (float) ($r->total_pending ?? 0));
        $convertedCount   = $guestLeads->filter(fn ($g) => filled($g->converted_student_id))->count();
        $leadCount        = $guestLeads->count();
        $conversionRate   = $leadCount > 0 ? round(($convertedCount / $leadCount) * 100, 1) : 0.0;
        $thisMonthRevenue = (float) $revenues
            ->filter(fn ($r) => optional($r->updated_at)?->greaterThanOrEqualTo(now()->startOfMonth()))
            ->sum(fn ($r) => (float) ($r->total_earned ?? 0));

        $monthlyRevenue = $revenues
            ->groupBy(fn ($r) => optional($r->updated_at)?->format('Y-m') ?: 'unknown')
            ->map(fn ($items) => round((float) $items->sum(fn ($r) => (float) ($r->total_earned ?? 0)), 2))
            ->sortKeysDesc()->take(6);

        $motivationCard = match(true) {
            $leadCount === 0        => ['emoji' => '🚀', 'text' => 'İlk yönlendirmeni oluştur ve kazanmaya başla!',            'cta' => 'Lead Oluştur',  'url' => '/dealer/lead-create'],
            $conversionRate === 0.0 => ['emoji' => '📈', 'text' => "{$leadCount} yönlendirmen var. İlk dönüşümü bekliyoruz!", 'cta' => 'Durumları Gör', 'url' => '/dealer/leads'],
            $conversionRate < 20.0  => ['emoji' => '💪', 'text' => "Dönüşüm oranın %{$conversionRate}. Yeni referanslar ekleyerek artır!", 'cta' => 'Yeni Lead',    'url' => '/dealer/lead-create'],
            $conversionRate < 50.0  => ['emoji' => '🔥', 'text' => "Harika gidiyorsun! %{$conversionRate} dönüşüm oranı. Devam et!",       'cta' => 'Kazançlarım', 'url' => '/dealer/earnings'],
            default                 => ['emoji' => '🏆', 'text' => "Mükemmel! %{$conversionRate} dönüşüm oranı — en iyilerden birisin!",    'cta' => 'Performansım','url' => '/dealer/earnings'],
        };

        $earningsHero = [
            'month_earned'   => round($thisMonthRevenue, 2),
            'total_earned'   => round($totalRevenue, 2),
            'pending'        => round($totalPending, 2),
            'next_milestone' => $this->nextMilestone($dealerCode, $totalRevenue),
            'month_label'    => now()->format('F Y'),
        ];

        $leadPipeline = [
            'new'            => $guestLeads->where('lead_status', 'new')->count(),
            'contacted'      => $guestLeads->whereIn('lead_status', ['contacted', 'meeting_scheduled'])->count(),
            'docs_pending'   => $guestLeads->whereIn('lead_status', ['docs_pending', 'docs_submitted'])->count(),
            'contract_stage' => $guestLeads->whereIn('contract_status', ['requested', 'pending_manager', 'signed_uploaded'])->count(),
            'converted'      => $convertedCount,
        ];

        // ── Analytics metrikleri ──────────────────────────────────────────
        // Referral type karşılaştırması
        $recLeads = $guestLeads->where('referral_type', 'recommendation');
        $confLeads = $guestLeads->where('referral_type', 'confirmed_referral');
        $referralAnalysis = [
            'recommendation' => [
                'count' => $recLeads->count(),
                'converted' => $recLeads->filter(fn ($g) => filled($g->converted_student_id) || $g->lead_status === 'converted')->count(),
                'rate' => $recLeads->count() > 0 ? round($recLeads->filter(fn ($g) => filled($g->converted_student_id) || $g->lead_status === 'converted')->count() / $recLeads->count() * 100, 1) : 0,
            ],
            'confirmed' => [
                'count' => $confLeads->count(),
                'converted' => $confLeads->filter(fn ($g) => filled($g->converted_student_id) || $g->lead_status === 'converted')->count(),
                'rate' => $confLeads->count() > 0 ? round($confLeads->filter(fn ($g) => filled($g->converted_student_id) || $g->lead_status === 'converted')->count() / $confLeads->count() * 100, 1) : 0,
            ],
        ];

        // Time-to-conversion (ortalama gün)
        $convertedWithDates = $guestLeads->filter(fn ($g) => $g->converted_at && $g->created_at);
        $avgConversionDays = $convertedWithDates->count() > 0
            ? round($convertedWithDates->avg(fn ($g) => $g->created_at->diffInDays($g->converted_at)), 1)
            : null;

        // Haftalık lead trendi (son 8 hafta)
        $weeklyLeads = [];
        for ($w = 7; $w >= 0; $w--) {
            $weekStart = now()->subWeeks($w)->startOfWeek();
            $weekEnd = now()->subWeeks($w)->endOfWeek();
            $label = $weekStart->format('d.m');
            $count = $guestLeads->filter(fn ($g) => $g->created_at && $g->created_at->between($weekStart, $weekEnd))->count();
            $weeklyLeads[] = ['label' => $label, 'count' => $count];
        }

        // Lead status dağılımı (pipeline detaylı)
        $statusDistribution = $guestLeads->groupBy(fn ($g) => (string) ($g->lead_status ?: 'new'))
            ->map(fn ($items, $key) => ['status' => $key, 'count' => $items->count()])
            ->values()->all();

        // Aylık aktivite (son 6 ay — lead + dönüşüm)
        $monthlyActivity = [];
        for ($m = 5; $m >= 0; $m--) {
            $monthStart = now()->subMonths($m)->startOfMonth();
            $monthEnd = now()->subMonths($m)->endOfMonth();
            $label = $monthStart->translatedFormat('M');
            $leads = $guestLeads->filter(fn ($g) => $g->created_at && $g->created_at->between($monthStart, $monthEnd))->count();
            $convs = $guestLeads->filter(fn ($g) => $g->converted_at && $g->converted_at->between($monthStart, $monthEnd))->count();
            $monthlyActivity[] = ['label' => $label, 'leads' => $leads, 'conversions' => $convs];
        }

        // Training progress
        $trainingProgress = 0;
        try {
            if (\App\Support\SchemaCache::hasTable('knowledge_base_articles')) {
                $totalArticles = \App\Models\KnowledgeBaseArticle::where('is_published', true)
                    ->where(fn ($q) => $q->whereNull('target_roles')->orWhereJsonContains('target_roles', 'dealer'))
                    ->count();
                $readArticles = $totalArticles > 0 && $user
                    ? \App\Models\DealerMaterialRead::where('dealer_user_id', $user->id)->distinct('article_id')->count('article_id')
                    : 0;
                $trainingProgress = $totalArticles > 0 ? round($readArticles / $totalArticles * 100) : 0;
            }
        } catch (\Throwable $e) { $trainingProgress = 0; }

        // Son 5 aktivite (zenginleştirilmiş)
        $activityFeed = $guestLeads->take(5)->map(fn ($g) => [
            'icon'  => match ($g->lead_status) {
                'new'       => '🆕',
                'contacted' => '📞',
                'qualified' => '⭐',
                'converted' => '🎓',
                'lost'      => '❌',
                default     => '📌',
            },
            'text'  => $g->first_name . ' ' . $g->last_name . ' — ' . match ($g->lead_status) {
                'new'       => 'Yeni yönlendirme',
                'contacted' => 'İletişime geçildi',
                'qualified' => 'Nitelikli lead',
                'converted' => 'Öğrenciye dönüştü',
                'lost'      => 'Kayıp',
                default     => $g->lead_status ?? '-',
            },
            'date'  => $g->created_at?->diffForHumans() ?? '',
        ])->values()->all();

        return view('dealer.dashboard', [
            'dealerCode'         => $dealerCode,
            'dealer'             => $dealer,
            'studentCount'       => $students->count(),
            'students'           => $students,
            'revenues'           => $revenues,
            'guestLeads'         => $guestLeads,
            'dealerLink'         => $dealerCode !== '' ? url('/apply').'?ref='.urlencode($dealerCode) : null,
            'kpis' => [
                'lead_total'      => $leadCount,
                'converted_total' => $convertedCount,
                'conversion_rate' => $conversionRate,
                'student_total'   => $students->pluck('student_id')->filter()->unique()->count(),
                'revenue_total'   => round($totalRevenue, 2),
                'revenue_pending' => round($totalPending, 2),
                'revenue_month'   => round($thisMonthRevenue, 2),
            ],
            'typeDistribution'    => $typeDistribution,
            'channelDistribution' => $channelDistribution,
            'recentActivity'      => $recentActivity,
            'monthlyRevenue'      => $monthlyRevenue,
            'motivationCard'      => $motivationCard,
            'earningsHero'        => $earningsHero,
            'leadPipeline'        => $leadPipeline,
            'tierPerms'           => DealerTierPermissions::for($dealer),
            'referralAnalysis'    => $referralAnalysis,
            'avgConversionDays'   => $avgConversionDays,
            'weeklyLeads'         => $weeklyLeads,
            'statusDistribution'  => $statusDistribution,
            'monthlyActivity'     => $monthlyActivity,
            'trainingProgress'    => $trainingProgress,
            'activityFeed'        => $activityFeed,
            'bonus'               => [
                'amount'   => (float) ($dealer?->signup_bonus_amount ?? 100),
                'status'   => (string) ($dealer?->signup_bonus_status ?? 'locked'),
                'unlocked_at' => $dealer?->signup_bonus_unlocked_at,
                'label'    => match ((string) ($dealer?->signup_bonus_status ?? 'locked')) {
                    'locked'   => 'Kilitli',
                    'pending'  => 'Beklemede',
                    'unlocked' => 'Çekilebilir',
                    default    => '-',
                },
                'progress' => match ((string) ($dealer?->signup_bonus_status ?? 'locked')) {
                    'locked'   => 0,
                    'pending'  => 50,
                    'unlocked' => 100,
                    default    => 0,
                },
            ],
        ]);
    }
}
