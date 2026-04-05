<?php

namespace App\Http\Controllers\MarketingAdmin;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\StudentRevenue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesPipelineController extends Controller
{
    private const DEFAULT_PACKAGE_PRICE = 3000.0;

    private const STAGE_WEIGHTS = [
        'new'         => 0.10,
        'contacted'   => 0.20,
        'verified'    => 0.30,
        'follow_up'   => 0.35,
        'interested'  => 0.45,
        'qualified'   => 0.55,
        'sales_ready' => 0.70,
        'champion'    => 0.90,
    ];

    public function index()
    {
        $totalGuests = (int) GuestApplication::query()->count();
        $openGuests = (int) $this->openGuestQuery()->count();
        $convertedGuests = (int) GuestApplication::query()->where('converted_to_student', true)->count();
        $archivedGuests = (int) GuestApplication::query()->where('is_archived', true)->count();

        $statusRows = $this->openGuestQuery()
            ->selectRaw("COALESCE(NULLIF(lead_status, ''), 'new') as lead_status_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('lead_status_key')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'status' => (string) $row->lead_status_key,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $sourceRows = $this->openGuestQuery()
            ->selectRaw("COALESCE(NULLIF(lead_source, ''), 'organic') as source_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('source_key')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'source' => (string) $row->source_key,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $branchRows = $this->openGuestQuery()
            ->selectRaw("COALESCE(NULLIF(branch, ''), '-') as branch_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('branch_key')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'branch' => (string) $row->branch_key,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $recentOpen = $this->openGuestQuery()
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id', 'first_name', 'last_name', 'lead_source', 'lead_status', 'created_at'])
            ->map(fn (GuestApplication $row): array => [
                'id' => (int) $row->id,
                'name' => trim(((string) $row->first_name).' '.((string) $row->last_name)),
                'lead_source' => (string) ($row->lead_source ?: 'organic'),
                'lead_status' => (string) ($row->lead_status ?: 'new'),
                'created_at' => optional($row->created_at)->toDateTimeString(),
            ])
            ->values()
            ->all();

        $conversionRate = $totalGuests > 0 ? round(($convertedGuests / $totalGuests) * 100, 1) : 0.0;

        return view('marketing-admin.pipeline.index', [
            'pageTitle' => 'Sales Pipeline',
            'title' => 'Funnel Genel Bakis',
            'summary' => [
                'total_guests' => $totalGuests,
                'open_guests' => $openGuests,
                'converted_guests' => $convertedGuests,
                'archived_guests' => $archivedGuests,
                'conversion_rate' => $conversionRate,
            ],
            'statusRows' => $statusRows,
            'sourceRows' => $sourceRows,
            'branchRows' => $branchRows,
            'recentOpen' => $recentOpen,
        ]);
    }

    public function pipelineValue()
    {
        $openCount = (int) $this->openGuestQuery()->count();

        $averagePackage = (float) StudentRevenue::query()
            ->where('package_total_price', '>', 0)
            ->avg('package_total_price');
        if ($averagePackage <= 0) {
            $averagePackage = self::DEFAULT_PACKAGE_PRICE;
        }

        $weights = self::STAGE_WEIGHTS;

        // DB-level aggregation — PHP'ye tek satır yüklenmez
        $statusRows = $this->openGuestQuery()
            ->selectRaw("COALESCE(NULLIF(lead_status, ''), 'new') as status, COUNT(*) as count")
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(function ($row) use ($weights, $averagePackage) {
                $weight = $weights[$row->status] ?? 0.20;
                return [
                    'status'         => $row->status,
                    'count'          => (int) $row->count,
                    'weight'         => $weight,
                    'weighted_value' => round((int) $row->count * $averagePackage * $weight, 2),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $openPotentialValue = round(array_sum(array_map(fn ($r) => (float) $r['weighted_value'], $statusRows)), 2);
        $realizedRevenue = (float) StudentRevenue::query()->sum('total_earned');
        $pendingRevenue = (float) StudentRevenue::query()->sum('total_pending');

        return view('marketing-admin.pipeline.value', [
            'pageTitle' => 'Pipeline Degeri',
            'title' => 'Pipeline Value',
            'summary' => [
                'open_count' => $openCount,
                'average_package' => $averagePackage,
                'open_potential_value' => $openPotentialValue,
                'realized_revenue' => $realizedRevenue,
                'pending_revenue' => $pendingRevenue,
            ],
            'statusRows' => $statusRows,
        ]);
    }

    public function lossAnalysis()
    {
        $staleCutoff = now()->subDays(45);

        $explicitLost = GuestApplication::query()
            ->where('is_archived', true)
            ->get(['id', 'lead_source', 'lead_status', 'archive_reason', 'created_at', 'archived_at']);
        $staleLost = GuestApplication::query()
            ->where('converted_to_student', false)
            ->where(function (Builder $q): void {
                $q->whereNull('is_archived')->orWhere('is_archived', false);
            })
            ->where('created_at', '<', $staleCutoff)
            ->get(['id', 'lead_source', 'lead_status', 'created_at']);

        $reasonRows = $explicitLost
            ->groupBy(fn (GuestApplication $g) => trim((string) ($g->archive_reason ?: 'unspecified')))
            ->map(fn (Collection $rows, string $reason): array => ['reason' => $reason, 'total' => $rows->count()])
            ->values()
            ->sortByDesc('total')
            ->values()
            ->all();

        $staleByStatus = $staleLost
            ->groupBy(fn (GuestApplication $g) => trim((string) ($g->lead_status ?: 'new')))
            ->map(fn (Collection $rows, string $status): array => ['status' => $status, 'total' => $rows->count()])
            ->values()
            ->sortByDesc('total')
            ->values()
            ->all();

        $staleBySource = $staleLost
            ->groupBy(fn (GuestApplication $g) => trim((string) ($g->lead_source ?: 'organic')))
            ->map(fn (Collection $rows, string $source): array => ['source' => $source, 'total' => $rows->count()])
            ->values()
            ->sortByDesc('total')
            ->values()
            ->all();

        $recoveryCandidates = GuestApplication::query()
            ->where('converted_to_student', false)
            ->where(function (Builder $q): void {
                $q->whereNull('is_archived')->orWhere('is_archived', false);
            })
            ->whereBetween('created_at', [now()->subDays(45), now()->subDays(14)])
            ->orderBy('created_at')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'lead_source', 'lead_status', 'created_at'])
            ->map(fn (GuestApplication $g): array => [
                'id' => (int) $g->id,
                'name' => trim(((string) $g->first_name).' '.((string) $g->last_name)),
                'lead_source' => (string) ($g->lead_source ?: 'organic'),
                'lead_status' => (string) ($g->lead_status ?: 'new'),
                'created_at' => optional($g->created_at)->toDateTimeString(),
            ])
            ->values()
            ->all();

        return view('marketing-admin.pipeline.loss', [
            'pageTitle' => 'Kayip Analizi',
            'title' => 'Loss Analysis',
            'summary' => [
                'explicit_lost' => $explicitLost->count(),
                'stale_lost' => $staleLost->count(),
                'recovery_candidate_count' => count($recoveryCandidates),
            ],
            'reasonRows' => $reasonRows,
            'staleByStatus' => $staleByStatus,
            'staleBySource' => $staleBySource,
            'recoveryCandidates' => $recoveryCandidates,
        ]);
    }

    public function conversionTime()
    {
        $converted = GuestApplication::query()
            ->where('converted_to_student', true)
            ->get(['id', 'lead_source', 'created_at', 'updated_at']);

        $days = $converted
            ->map(function (GuestApplication $row): int {
                if (!$row->created_at || !$row->updated_at) {
                    return 0;
                }
                $diff = (int) $row->created_at->diffInDays($row->updated_at);
                return max(0, $diff);
            })
            ->sort()
            ->values();

        $avg = $days->count() > 0 ? round($days->avg(), 1) : 0.0;
        $median = $this->percentile($days, 50);
        $p90 = $this->percentile($days, 90);
        $max = $days->count() > 0 ? (int) $days->max() : 0;
        $min = $days->count() > 0 ? (int) $days->min() : 0;

        $bySource = $converted
            ->groupBy(fn (GuestApplication $row) => trim((string) ($row->lead_source ?: 'organic')))
            ->map(function (Collection $rows, string $source): array {
                $durations = $rows
                    ->map(function (GuestApplication $row): int {
                        if (!$row->created_at || !$row->updated_at) {
                            return 0;
                        }
                        return max(0, (int) $row->created_at->diffInDays($row->updated_at));
                    })
                    ->values();
                return [
                    'source' => $source,
                    'count' => $durations->count(),
                    'avg_days' => $durations->count() > 0 ? round($durations->avg(), 1) : 0.0,
                ];
            })
            ->values()
            ->sortByDesc('count')
            ->values()
            ->all();

        $byMonth = $converted
            ->groupBy(fn (GuestApplication $row) => optional($row->updated_at)->format('Y-m') ?: '-')
            ->map(function (Collection $rows, string $month): array {
                $durations = $rows
                    ->map(function (GuestApplication $row): int {
                        if (!$row->created_at || !$row->updated_at) {
                            return 0;
                        }
                        return max(0, (int) $row->created_at->diffInDays($row->updated_at));
                    })
                    ->values();
                return [
                    'month' => $month,
                    'count' => $durations->count(),
                    'avg_days' => $durations->count() > 0 ? round($durations->avg(), 1) : 0.0,
                ];
            })
            ->values()
            ->sortBy('month')
            ->values()
            ->all();

        return view('marketing-admin.pipeline.conversion-time', [
            'pageTitle' => 'Donusum Suresi',
            'title' => 'Conversion Time',
            'summary' => [
                'count' => $days->count(),
                'avg_days' => $avg,
                'median_days' => $median,
                'p90_days' => $p90,
                'min_days' => $min,
                'max_days' => $max,
            ],
            'bySource' => $bySource,
            'byMonth' => $byMonth,
        ]);
    }

    public function reEngagement(): \Illuminate\View\View
    {
        // Guests in re_engagement pool: non-converted, inactive 90+ days
        $cutoff = now()->subDays(90);

        $cid  = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $pool = GuestApplication::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->where('converted_to_student', false)
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->where('updated_at', '<', $cutoff)
            ->orderBy('lead_score', 'desc')
            ->get(['id', 'first_name', 'last_name', 'lead_source', 'lead_status', 'lead_score', 'lead_score_tier', 'updated_at', 'created_at']);

        $totalPool = $pool->count();

        // Group by tier
        $byTier = $pool->groupBy('lead_score_tier')
            ->map(fn ($rows, $tier) => ['tier' => $tier, 'count' => $rows->count()])
            ->values()
            ->all();

        // Group by source
        $bySource = $pool->groupBy(fn ($g) => $g->lead_source ?: '(doğrudan)')
            ->map(fn ($rows, $src) => ['source' => $src, 'count' => $rows->count()])
            ->sortByDesc('count')
            ->values()
            ->all();

        $tierLabels = [
            'cold' => 'Cold', 'warm' => 'Warm', 'hot' => 'Hot',
            'sales_ready' => 'Sales Ready', 'champion' => 'Champion',
        ];

        $poolRows = $pool->take(30)->map(fn ($g) => [
            'id'         => $g->id,
            'name'       => trim("{$g->first_name} {$g->last_name}"),
            'source'     => $g->lead_source ?: '—',
            'status'     => $g->lead_status ?: 'new',
            'score'      => $g->lead_score,
            'tier'       => $g->lead_score_tier ?? 'cold',
            'last_active' => optional($g->updated_at)->toDateString(),
            'days_inactive' => (int) now()->diffInDays($g->updated_at),
        ])->values()->all();

        return view('marketing-admin.pipeline.re-engagement', compact(
            'totalPool', 'byTier', 'bySource', 'poolRows', 'tierLabels'
        ));
    }

    public function scoreAnalysis(): \Illuminate\View\View
    {
        // Score range breakdown
        $ranges = [
            ['label' => 'Cold (0-19)',        'min' => 0,   'max' => 19],
            ['label' => 'Warm (20-49)',        'min' => 20,  'max' => 49],
            ['label' => 'Hot (50-79)',         'min' => 50,  'max' => 79],
            ['label' => 'Sales Ready (80-99)', 'min' => 80,  'max' => 99],
            ['label' => 'Champion (100+)',     'min' => 100, 'max' => 999],
        ];

        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $guestBase = fn () => GuestApplication::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid));

        // Score range breakdown — single aggregate query instead of per-range queries
        $rangeCounts = $guestBase()
            ->selectRaw("
                SUM(CASE WHEN lead_score BETWEEN 0 AND 19 THEN 1 ELSE 0 END) as r0,
                SUM(CASE WHEN lead_score BETWEEN 0 AND 19 AND contract_status = 'approved' THEN 1 ELSE 0 END) as c0,
                SUM(CASE WHEN lead_score BETWEEN 20 AND 49 THEN 1 ELSE 0 END) as r1,
                SUM(CASE WHEN lead_score BETWEEN 20 AND 49 AND contract_status = 'approved' THEN 1 ELSE 0 END) as c1,
                SUM(CASE WHEN lead_score BETWEEN 50 AND 79 THEN 1 ELSE 0 END) as r2,
                SUM(CASE WHEN lead_score BETWEEN 50 AND 79 AND contract_status = 'approved' THEN 1 ELSE 0 END) as c2,
                SUM(CASE WHEN lead_score BETWEEN 80 AND 99 THEN 1 ELSE 0 END) as r3,
                SUM(CASE WHEN lead_score BETWEEN 80 AND 99 AND contract_status = 'approved' THEN 1 ELSE 0 END) as c3,
                SUM(CASE WHEN lead_score >= 100 THEN 1 ELSE 0 END) as r4,
                SUM(CASE WHEN lead_score >= 100 AND contract_status = 'approved' THEN 1 ELSE 0 END) as c4
            ")
            ->first();

        $scoreRows = [];
        foreach ($ranges as $i => $range) {
            $total     = (int) ($rangeCounts->{"r{$i}"} ?? 0);
            $converted = (int) ($rangeCounts->{"c{$i}"} ?? 0);
            $scoreRows[] = [
                'label'     => $range['label'],
                'total'     => $total,
                'converted' => $converted,
                'conv_rate' => $total > 0 ? round($converted / $total * 100, 1) : 0,
            ];
        }

        // Top scored leads
        $topLeads = $guestBase()
            ->orderByDesc('lead_score')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'lead_score', 'lead_score_tier', 'contract_status', 'lead_score_updated_at']);

        // Average + median score
        $avgScore = round((float) ($guestBase()->avg('lead_score') ?? 0), 1);
        $medScore = round((float) ($guestBase()
            ->orderBy('lead_score')
            ->pluck('lead_score')
            ->median() ?? 0), 1);

        return view('marketing-admin.pipeline.score-analysis', compact(
            'scoreRows', 'topLeads', 'avgScore', 'medScore'
        ));
    }

    public function managerView()
    {
        return $this->index();
    }

    // ── Guest Pipeline Kanban ─────────────────────────────────────────────────

    private const KANBAN_STAGES = [
        'new'             => 'Yeni',
        'contacted'       => 'İletişime Geçildi',
        'docs_pending'    => 'Evrak Bekliyor',
        'in_progress'     => 'İşlemde',
        'evaluating'      => 'Değerlendiriliyor',
        'contract_signed' => 'Sözleşme İmzalandı',
        'converted'       => 'Dönüştürüldü',
        'lost'            => 'Kaybedildi',
    ];

    private const KANBAN_READONLY = [];

    public function kanban(Request $request)
    {
        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $guests = GuestApplication::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->get(['id', 'first_name', 'last_name', 'lead_status', 'lead_score_tier',
                   'application_type', 'application_country', 'communication_language',
                   'assigned_senior_email', 'updated_at', 'pipeline_moved_by', 'pipeline_moved_at']);

        $columns = collect(self::KANBAN_STAGES)->map(fn ($label, $code) => [
            'code'     => $code,
            'label'    => $label,
            'readonly' => in_array($code, self::KANBAN_READONLY),
            'cards'    => $guests->where('lead_status', $code)->values(),
        ])->values();

        $stats = [
            'total'     => $guests->count(),
            'open'      => $guests->whereNotIn('lead_status', ['converted', 'lost'])->count(),
            'hot'       => $guests->where('lead_score_tier', 'hot')->count(),
            'converted' => $guests->where('lead_status', 'converted')->count(),
            'lost'      => $guests->where('lead_status', 'lost')->count(),
        ];

        return view('marketing-admin.pipeline.kanban', compact('columns', 'stats'));
    }

    public function kanbanPoll(Request $request)
    {
        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $rows = GuestApplication::query()
            ->when($cid > 0, fn($q) => $q->where('company_id', $cid))
            ->whereNull('deleted_at')
            ->get(['id', 'lead_status', 'pipeline_moved_by', 'updated_at']);

        return response()->json($rows->map(fn($g) => [
            'id'                => $g->id,
            'lead_status'       => $g->lead_status,
            'pipeline_moved_by' => $g->pipeline_moved_by,
            'updated_at'        => $g->updated_at?->toISOString(),
        ]));
    }

    public function kanbanMove(Request $request, string $guest)
    {
        // Route model binding (SubstituteBindings) runs before company.context middleware,
        // so BelongsToCompany scope doesn't have current_company_id yet → 404.
        // Manual lookup with withoutGlobalScope bypasses this ordering issue.
        $guestModel = GuestApplication::withoutGlobalScope('company')
            ->whereKey($guest)
            ->whereNull('deleted_at')
            ->first();

        if (!$guestModel) {
            return response()->json(['ok' => false, 'error' => 'guest not found'], 404);
        }

        $stage = (string) $request->input('stage', '');

        if ($stage === '') {
            return response()->json(['ok' => false, 'error' => 'stage required'], 422);
        }

        if (!array_key_exists($stage, self::KANBAN_STAGES)) {
            return response()->json(['ok' => false, 'error' => 'unknown stage'], 422);
        }

        $oldStage = $guestModel->lead_status;

        $mover = $request->user()?->name ?: $request->user()?->email ?: 'Marketing';
        // Use DB update to avoid global scope on save()
        \Illuminate\Support\Facades\DB::table('guest_applications')
            ->where('id', $guestModel->id)
            ->update([
                'lead_status'       => $stage,
                'pipeline_moved_by' => $mover,
                'pipeline_moved_at' => now(),
                'updated_at'        => now(),
            ]);

        // Pipeline log kaydı
        \App\Models\GuestPipelineLog::create([
            'guest_application_id' => $guestModel->id,
            'from_stage'           => $oldStage,
            'to_stage'             => $stage,
            'moved_by_name'        => $mover,
            'moved_by_email'       => $request->user()?->email,
            'contact_method'       => $request->input('contact_method'),
            'contact_result'       => $request->input('contact_result'),
            'lost_reason'          => $request->input('lost_reason'),
            'follow_up_date'       => $request->input('follow_up_date') ?: null,
            'notes'                => $request->input('notes') ?: null,
            'meta'                 => $request->input('meta') ?: null,
        ]);

        try {
            app(\App\Services\EventLogService::class)->log(
                event_type: 'guest_pipeline_move',
                entityType: 'guest_application',
                entityId:   (string) $guestModel->id,
                message:    "Sales pipeline aşaması değiştirildi: {$oldStage} → {$stage} | Aday: {$guestModel->first_name} {$guestModel->last_name}",
                meta:       ['from' => $oldStage, 'to' => $stage, 'guest_id' => $guestModel->id],
                actorEmail: $request->user()?->email,
            );
        } catch (\Throwable) {
            // Log kaydı başarısız olsa bile pipeline move tamamlandı
        }

        return response()->json(['ok' => true, 'stage' => $stage]);
    }

    private function openGuestQuery(): Builder
    {
        return GuestApplication::query()
            ->where('converted_to_student', false)
            ->where(function (Builder $q): void {
                $q->whereNull('is_archived')->orWhere('is_archived', false);
            });
    }

    private function percentile(Collection $values, int $percent): float
    {
        if ($values->isEmpty()) {
            return 0.0;
        }
        $count = $values->count();
        $index = (int) ceil(($percent / 100) * $count) - 1;
        $index = max(0, min($count - 1, $index));
        return (float) $values->values()->get($index, 0);
    }
}
