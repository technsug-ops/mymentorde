<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\Document;
use App\Models\DmMessage;
use App\Models\DmThread;
use App\Models\FieldRuleApproval;
use App\Models\InternalNote;
use App\Models\MarketingCampaign;
use App\Models\MarketingTask;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentRevenue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard KPI Service
 *
 * Manager ve Senior dashboard'larındaki ağır KPI sorgularını
 * Cache::remember() ile 5 dakika süreyle önbelleğe alır.
 *
 * Önce önbellekte arar; bulamazsa hesaplar ve yazar.
 * Her sayfa yüklenişinde 35-40 DB sorgusu yerine yalnızca
 * önbellekte bulunmayan hesaplamalar çalıştırılır.
 *
 * Cache key'leri: kpi:mgr:* ve kpi:senior:{user_id}
 * TTL: 300 saniye (5 dakika)
 */
class DashboardKPIService
{
    /** Cache TTL in seconds */
    private const TTL = 300;

    // ─────────────────────────────────────────────────────────────────────────
    // Manager — Cached aggregations
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Manager KPI stats + conversion funnel.
     * Stats ve funnel aynı alt-sorguları paylaştığından birlikte hesaplanır.
     *
     * @return array{stats: array<string,mixed>, funnel: list<array{label:string,count:int,rate:float}>}
     */
    public function managerStatsAndFunnel(Carbon $start, Carbon $end, string $seniorFilter): array
    {
        $cid = $this->currentCompanyId();
        $key = 'kpi:mgr:sf:c' . $cid . ':' . $start->toDateString() . ':' . $end->toDateString() . ':' . ($seniorFilter ?: 'all');

        return Cache::remember($key, self::TTL, fn () =>
            $this->computeManagerStatsAndFunnel($start, $end, $seniorFilter)
        );
    }

    /**
     * Aylık gelir + onay sayısı trendi (12 aya kadar).
     * Cache key: sadece tarih aralığı — senior filtresi yok.
     *
     * @return list<array{label:string,revenue:float,approval_count:int}>
     */
    public function managerTrend(Carbon $start, Carbon $end): array
    {
        $cid = $this->currentCompanyId();
        $key = 'kpi:mgr:trend:c' . $cid . ':' . $start->toDateString() . ':' . $end->toDateString();

        return Cache::remember($key, self::TTL, fn () =>
            $this->computeManagerTrend($start, $end)
        );
    }

    /**
     * Senior başına onay ve not istatistikleri.
     *
     * @return Collection<int, array{name:string,email:string,resolved_approvals:int,notes_written:int,last_action_at:?string}>
     */
    public function managerSeniorPerformance(Carbon $start, Carbon $end, string $seniorFilter): Collection
    {
        $cid = $this->currentCompanyId();
        $key = 'kpi:mgr:perf:c' . $cid . ':' . $start->toDateString() . ':' . $end->toDateString() . ':' . ($seniorFilter ?: 'all');

        return Cache::remember($key, self::TTL, fn () =>
            $this->computeSeniorPerformance($start, $end, $seniorFilter)
        );
    }

    /**
     * Görev sayımları ve departman özeti (global — tarih bağımsız).
     *
     * @return array{taskOverview: array<string,int>, taskDepartmentOverview: Collection}
     */
    public function managerTaskOverview(): array
    {
        $cid = $this->currentCompanyId();
        return Cache::remember('kpi:mgr:tasks:c' . $cid, self::TTL, fn () =>
            $this->computeTaskOverview()
        );
    }

    /**
     * DM mesaj/thread özet sayımları (global).
     *
     * @return array<string,int>
     */
    public function managerMessageOverview(): array
    {
        $cid = $this->currentCompanyId();
        return Cache::remember('kpi:mgr:messages:c' . $cid, self::TTL, fn () =>
            $this->computeMessageOverview()
        );
    }

    /**
     * Senior kullanıcı listesi (nadiren değişir).
     *
     * @return Collection<int, \App\Models\User>
     */
    public function managerSeniors(string $seniorFilter): Collection
    {
        $cid = $this->currentCompanyId();
        $key = 'kpi:mgr:seniors:c' . $cid . ':' . ($seniorFilter ?: 'all');

        return Cache::remember($key, self::TTL, function () use ($seniorFilter) {
            $q = User::query()->whereIn('role', ['senior', 'mentor'])->orderBy('name');
            if ($seniorFilter !== '') {
                $q->where('email', $seniorFilter);
            }
            return $q->get(['name', 'email']);
        });
    }

    /**
     * Manager önizleme paneli için student/senior/dealer ID önerileri.
     * Büyük pluck sorguları (600 + 300 satır).
     *
     * @return array{student_ids:list<string>,senior_emails:list<string>,dealer_codes:list<string>}
     */
    public function managerPreviewSuggestions(Collection $seniors): array
    {
        $cid = $this->currentCompanyId();
        return Cache::remember('kpi:mgr:preview:c' . $cid, self::TTL, function () use ($seniors) {
            return [
                'student_ids' => StudentAssignment::query()
                    ->orderByDesc('updated_at')
                    ->limit(600)
                    ->pluck('student_id')
                    ->filter()->unique()->values()->all(),
                'senior_emails' => $seniors->pluck('email')
                    ->filter()->unique()->values()->all(),
                'dealer_codes' => Dealer::query()
                    ->orderByDesc('updated_at')
                    ->limit(300)
                    ->pluck('code')
                    ->filter()->unique()->values()->all(),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Senior — Cached aggregations
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Senior dashboard KPI sayımları (kullanıcı başına önbellek).
     *
     * @return array{activeStudentCount:int,archivedStudentCount:int,pendingApprovalCount:int,taskSummary:array<string,int>,dmSummary:array<string,int>}
     */
    public function seniorKPIs(int $userId, string $seniorEmail): array
    {
        return Cache::remember('kpi:senior:' . $userId, self::TTL, fn () =>
            $this->computeSeniorKPIs($userId, $seniorEmail)
        );
    }

    /**
     * Öğrenci atama değiştiğinde Senior önbelleğini temizle.
     */
    public function forgetSeniorCache(int $userId): void
    {
        Cache::forget('kpi:senior:' . $userId);
    }

    /**
     * En yüksek riske sahip öğrenciler (student_id üzerinden şirkete filtrelenir).
     */
    public function riskyStudents(int $companyId, int $limit = 5): \Illuminate\Support\Collection
    {
        return \App\Models\StudentRiskScore::with(['student:id,name,email,company_id'])
            ->when($companyId > 0, function ($q) use ($companyId) {
                $q->whereHas('student', fn($u) => $u->where('company_id', $companyId));
            })
            ->orderByDesc('current_score')
            ->limit($limit)
            ->get();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function currentCompanyId(): int
    {
        return app()->has('current_company_id') ? (int) app('current_company_id') : 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private compute methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{stats: array<string,mixed>, funnel: list<array{label:string,count:int,rate:float}>}
     */
    private function computeManagerStatsAndFunnel(Carbon $start, Carbon $end, string $seniorFilter): array
    {
        $now = Carbon::now();

        // Ortak alt-sorgular — stats ve funnel tarafından paylaşılır
        $revenueBase = StudentRevenue::query()->whereBetween('updated_at', [$start, $end]);
        $activeStudents = (clone $revenueBase)->count();

        $docQuery = Document::query()->whereBetween('created_at', [$start, $end]);
        if ($seniorFilter !== '') {
            $docQuery->where('uploaded_by', $seniorFilter);
        }
        $studentsWithDocs = $docQuery->whereNotNull('student_id')->distinct()->count('student_id');

        $outcomeQuery = ProcessOutcome::query()->whereBetween('created_at', [$start, $end]);
        if ($seniorFilter !== '') {
            $outcomeQuery->where('added_by', $seniorFilter);
        }
        $studentsWithVisibleOutcome = $outcomeQuery
            ->where('is_visible_to_student', true)
            ->whereNotNull('student_id')
            ->distinct()->count('student_id');

        $studentsPendingApproval = FieldRuleApproval::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'pending')
            ->whereNotNull('student_id')
            ->distinct()->count('student_id');

        $conversionRate = $activeStudents > 0
            ? round(($studentsWithVisibleOutcome / $activeStudents) * 100, 1)
            : 0.0;

        $monthlyRevenue    = (float) (clone $revenueBase)->sum('total_earned');
        $openPendingAmount = (float) (clone $revenueBase)->sum('total_pending');

        $pendingApprovalCount = FieldRuleApproval::query()
            ->where('status', 'pending')
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $archivedApprovalCount = FieldRuleApproval::query()
            ->where('status', 'archived')
            ->whereBetween('updated_at', [$start, $end])
            ->count();
        $overdueOutcomeCount = ProcessOutcome::query()
            ->whereNotNull('deadline')
            ->where('deadline', '<', $now)
            ->where('is_visible_to_student', false)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $upcomingOutcomeCount = ProcessOutcome::query()
            ->whereBetween('deadline', [$now, $now->copy()->addDays(7)])
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Risk skoru
        $activeBase     = max(1, $activeStudents);
        $pendingRate    = min(100, ($pendingApprovalCount / $activeBase) * 100);
        $overdueRate    = min(100, ($overdueOutcomeCount / $activeBase) * 100);
        $collectionRate = $monthlyRevenue > 0
            ? min(100, ($openPendingAmount / $monthlyRevenue) * 100)
            : ($openPendingAmount > 0 ? 100 : 0);

        $riskScore = min(100, max(0, (int) round(
            ($pendingRate * 0.35) + ($overdueRate * 0.45) + ($collectionRate * 0.20)
        )));
        $riskLevel = $riskScore >= 60 ? 'critical' : ($riskScore >= 30 ? 'warning' : 'good');

        $stats = [
            'month_label'          => $start->format('F Y'),
            'monthly_revenue'      => $monthlyRevenue,
            'active_students'      => $activeStudents,
            'conversion_rate'      => $conversionRate,
            'pending_approvals'    => $pendingApprovalCount,
            'archived_approvals'   => $archivedApprovalCount,
            'overdue_outcomes'     => $overdueOutcomeCount,
            'upcoming_outcomes'    => $upcomingOutcomeCount,
            'open_pending_amount'  => $openPendingAmount,
            'active_campaigns'     => MarketingCampaign::query()
                ->whereIn('status', ['active', 'running', 'scheduled'])
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'notification_queued'    => NotificationDispatch::query()->where('status', 'queued')->count(),
            'notification_failed'    => NotificationDispatch::query()->where('status', 'failed')->count(),
            'notification_sent_24h'  => NotificationDispatch::query()
                ->where('status', 'sent')
                ->where('sent_at', '>=', $now->copy()->subHours(24))
                ->count(),
            'risk_score'     => $riskScore,
            'risk_level'     => $riskLevel,
            'risk_breakdown' => [
                'pending_rate'    => round($pendingRate, 1),
                'overdue_rate'    => round($overdueRate, 1),
                'collection_rate' => round($collectionRate, 1),
            ],
        ];

        $funnelBase = max($activeStudents, 1);
        $funnel = [
            [
                'label' => 'Aktif Ogrenci',
                'count' => $activeStudents,
                'rate'  => $activeStudents > 0 ? 100.0 : 0.0,
            ],
            [
                'label' => 'Belge Yukleyen',
                'count' => $studentsWithDocs,
                'rate'  => round(($studentsWithDocs / $funnelBase) * 100, 1),
            ],
            [
                'label' => 'Sonuc Ogrenciye Acik',
                'count' => $studentsWithVisibleOutcome,
                'rate'  => round(($studentsWithVisibleOutcome / $funnelBase) * 100, 1),
            ],
            [
                'label' => 'Pending Approval Ogrencisi',
                'count' => $studentsPendingApproval,
                'rate'  => round(($studentsPendingApproval / $funnelBase) * 100, 1),
            ],
        ];

        return compact('stats', 'funnel');
    }

    /**
     * @return list<array{label:string,revenue:float,approval_count:int}>
     */
    private function computeManagerTrend(Carbon $start, Carbon $end): array
    {
        $trendStart = $start->copy()->startOfMonth();
        $trendEnd   = $end->copy()->startOfMonth();
        $trend      = [];
        $maxPoints  = 12;
        $points     = 0;

        while ($trendStart->lte($trendEnd) && $points < $maxPoints) {
            $bucketStart = $trendStart->copy()->startOfMonth();
            $bucketEnd   = $trendStart->copy()->endOfMonth();

            $trend[] = [
                'label'          => $bucketStart->format('Y-m'),
                'revenue'        => (float) StudentRevenue::query()
                    ->whereBetween('updated_at', [$bucketStart, $bucketEnd])
                    ->sum('total_earned'),
                'approval_count' => (int) FieldRuleApproval::query()
                    ->whereBetween('created_at', [$bucketStart, $bucketEnd])
                    ->count(),
            ];

            $trendStart->addMonth();
            $points++;
        }

        return $trend;
    }

    /**
     * @return Collection<int, array{name:string,email:string,resolved_approvals:int,notes_written:int,last_action_at:?string}>
     */
    private function computeSeniorPerformance(Carbon $start, Carbon $end, string $seniorFilter): Collection
    {
        $approvalQ = FieldRuleApproval::query()
            ->selectRaw('approved_by, COUNT(*) as total, MAX(approved_at) as last_at')
            ->whereIn('status', ['approved', 'rejected', 'archived'])
            ->whereNotNull('approved_by')
            ->whereBetween('updated_at', [$start, $end]);
        if ($seniorFilter !== '') {
            $approvalQ->where('approved_by', $seniorFilter);
        }
        $approvalByUser = $approvalQ->groupBy('approved_by')->get()->keyBy('approved_by');

        $notesQ = InternalNote::query()
            ->selectRaw('created_by, COUNT(*) as total, MAX(created_at) as last_at')
            ->whereNotNull('created_by')
            ->whereBetween('created_at', [$start, $end]);
        if ($seniorFilter !== '') {
            $notesQ->where('created_by', $seniorFilter);
        }
        $notesByUser = $notesQ->groupBy('created_by')->get()->keyBy('created_by');

        $seniorQ = User::query()->whereIn('role', ['senior', 'mentor'])->orderBy('name');
        if ($seniorFilter !== '') {
            $seniorQ->where('email', $seniorFilter);
        }

        return $seniorQ->get(['name', 'email'])->map(function (User $senior) use ($approvalByUser, $notesByUser) {
            $approvalAgg = $approvalByUser->get($senior->email);
            $noteAgg     = $notesByUser->get($senior->email);

            $lastApprovalAt = $approvalAgg?->last_at ? Carbon::parse($approvalAgg->last_at) : null;
            $lastNoteAt     = $noteAgg?->last_at     ? Carbon::parse($noteAgg->last_at)     : null;
            $lastActionAt   = $lastApprovalAt;
            if (!$lastActionAt || ($lastNoteAt && $lastNoteAt->gt($lastActionAt))) {
                $lastActionAt = $lastNoteAt;
            }

            return [
                'name'                => $senior->name,
                'email'               => $senior->email,
                'resolved_approvals'  => (int) ($approvalAgg?->total ?? 0),
                'notes_written'       => (int) ($noteAgg?->total ?? 0),
                'last_action_at'      => $lastActionAt?->toDateTimeString(),
            ];
        })->values();
    }

    /**
     * @return array{taskOverview: array<string,int>, taskDepartmentOverview: Collection}
     */
    private function computeTaskOverview(): array
    {
        $taskOverview = [
            'total'       => (int) MarketingTask::query()->count(),
            'todo'        => (int) MarketingTask::query()->where('status', 'todo')->count(),
            'in_progress' => (int) MarketingTask::query()->where('status', 'in_progress')->count(),
            'blocked'     => (int) MarketingTask::query()->where('status', 'blocked')->count(),
            'overdue'     => (int) MarketingTask::query()
                ->where('status', '!=', 'done')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];

        $taskDepartmentOverview = collect([
            'operations' => 'Operasyon',
            'finance'    => 'Finans',
            'advisory'   => 'Danismanlik',
            'marketing'  => 'Marketing',
            'system'     => 'Sistem',
        ])->map(fn (string $label, string $code) => [
            'code'  => $code,
            'label' => $label,
            'open'  => (int) MarketingTask::query()
                ->where('department', $code)
                ->whereIn('status', ['todo', 'in_progress', 'blocked'])
                ->count(),
        ])->values();

        return compact('taskOverview', 'taskDepartmentOverview');
    }

    /**
     * @return array<string,int>
     */
    private function computeMessageOverview(): array
    {
        return [
            'threads_total'          => (int) DmThread::query()->count(),
            'threads_open'           => (int) DmThread::query()->where('status', 'open')->count(),
            'unread_for_advisor'     => (int) DmMessage::query()->where('is_read_by_advisor', false)->count(),
            'unread_for_participant' => (int) DmMessage::query()->where('is_read_by_participant', false)->count(),
            'sla_overdue'            => (int) DmThread::query()
                ->where('status', 'open')
                ->whereNotNull('next_response_due_at')
                ->where('next_response_due_at', '<', now())
                ->count(),
        ];
    }

    /**
     * @return array{activeStudentCount:int,archivedStudentCount:int,pendingApprovalCount:int,taskSummary:array<string,int>,dmSummary:array<string,int>}
     */
    private function computeSeniorKPIs(int $userId, string $seniorEmail): array
    {
        $base = StudentAssignment::query()->whereRaw('lower(senior_email) = ?', [$seniorEmail]);

        $activeStudentCount   = (clone $base)->where('is_archived', false)->count();
        $archivedStudentCount = (clone $base)->where('is_archived', true)->count();
        $studentIds           = (clone $base)->pluck('student_id')->filter()->unique()->values();

        $pendingApprovalCount = $studentIds->isEmpty()
            ? 0
            : FieldRuleApproval::query()
                ->whereIn('student_id', $studentIds->all())
                ->where('status', 'pending')
                ->count();

        $taskBase    = MarketingTask::query()->where('assigned_user_id', $userId);
        $taskSummary = [
            'todo'        => (int) (clone $taskBase)->where('status', 'todo')->count(),
            'in_progress' => (int) (clone $taskBase)->where('status', 'in_progress')->count(),
            'blocked'     => (int) (clone $taskBase)->where('status', 'blocked')->count(),
            'overdue'     => (int) (clone $taskBase)
                ->where('status', '!=', 'done')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];

        $threadIds = DmThread::query()
            ->where('advisor_user_id', $userId)
            ->pluck('id');

        $dmSummary = [
            'threads' => (int) $threadIds->count(),
            'open'    => (int) DmThread::query()->whereIn('id', $threadIds->all())->where('status', 'open')->count(),
            'unread'  => (int) DmMessage::query()->whereIn('thread_id', $threadIds->all())->where('is_read_by_advisor', false)->count(),
            'overdue' => (int) DmThread::query()
                ->whereIn('id', $threadIds->all())
                ->where('status', 'open')
                ->whereNotNull('next_response_due_at')
                ->where('next_response_due_at', '<', now())
                ->count(),
        ];

        return compact(
            'activeStudentCount',
            'archivedStudentCount',
            'pendingApprovalCount',
            'taskSummary',
            'dmSummary',
        );
    }
}
