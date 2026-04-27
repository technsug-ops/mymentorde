<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager portal — Lead Pipeline Oversight.
 *
 * Operasyonel pipeline (drag-drop, aşama değişikliği) Senior portal'da yaşar.
 * Bu sayfa Manager için ekip-level GÖZETIM panelidir:
 * - Senior başına bekleyen iş yükü
 * - Atanmamış lead'ler (intervention)
 * - Hot ama kontak edilmemiş lead'ler
 * - Geciken lead'ler (5+ gün hareketsiz)
 *
 * Manager'ın yapacağı tek operasyonel iş: lead'i başka senior'a yeniden atamak
 * (LeadActionController::assignSenior endpoint'i mevcut). Aşama değiştirme
 * Senior'ın işidir; manager pipeline kartlarını sürüklemez.
 */
class LeadPipelineController extends Controller
{
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

    private const STAGE_CONTACTED = ['contacted', 'docs_pending', 'in_progress', 'evaluating', 'contract_signed'];
    private const OVERDUE_DAYS = 5;

    public function oversight(Request $request)
    {
        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $filterSenior = trim((string) $request->query('senior', ''));
        $filterRisk   = trim((string) $request->query('risk', ''));

        $base = GuestApplication::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->whereNull('deleted_at');

        // Senior filter
        if ($filterSenior === '__unassigned__') {
            $base->whereNull('assigned_senior_email');
        } elseif ($filterSenior !== '') {
            $base->where('assigned_senior_email', $filterSenior);
        }

        // Risk filter
        $overdueCutoff = now()->subDays(self::OVERDUE_DAYS);
        if ($filterRisk === 'hot_no_contact') {
            $base->where('lead_score_tier', 'hot')
                 ->where('lead_status', 'new');
        } elseif ($filterRisk === 'unassigned') {
            $base->whereNull('assigned_senior_email');
        } elseif ($filterRisk === 'overdue') {
            $base->whereNotIn('lead_status', ['converted', 'lost'])
                 ->where('updated_at', '<', $overdueCutoff);
        }

        $guests = $base->orderByDesc('updated_at')->get([
            'id', 'first_name', 'last_name', 'lead_status', 'lead_score_tier',
            'application_type', 'application_country', 'communication_language',
            'assigned_senior_email', 'updated_at', 'pipeline_moved_by', 'pipeline_moved_at',
        ]);

        $columns = collect(self::KANBAN_STAGES)->map(fn ($label, $code) => [
            'code'     => $code,
            'label'    => $label,
            'readonly' => true,
            'cards'    => $guests->where('lead_status', $code)->values(),
        ])->values();

        // ── Intervention KPI'ları (filtre uygulanmamış orijinal sayılar) ─────
        $allGuests = GuestApplication::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->whereNull('deleted_at')
            ->get(['id', 'lead_status', 'lead_score_tier', 'assigned_senior_email', 'updated_at']);

        $kpis = [
            'active'         => $allGuests->whereNotIn('lead_status', ['converted', 'lost'])->count(),
            'unassigned'     => $allGuests->whereNull('assigned_senior_email')
                                    ->whereNotIn('lead_status', ['converted', 'lost'])->count(),
            'hot_no_contact' => $allGuests->where('lead_score_tier', 'hot')
                                    ->where('lead_status', 'new')->count(),
            'overdue'        => $allGuests->whereNotIn('lead_status', ['converted', 'lost'])
                                    ->filter(fn ($g) => $g->updated_at && $g->updated_at->lt($overdueCutoff))
                                    ->count(),
        ];

        // ── Senior workload — her senior'un atanmış aktif lead sayısı ────────
        $seniorWorkload = $allGuests
            ->whereNotIn('lead_status', ['converted', 'lost'])
            ->whereNotNull('assigned_senior_email')
            ->groupBy('assigned_senior_email')
            ->map(fn ($rows, $email) => [
                'email'    => $email,
                'count'    => $rows->count(),
                'overdue'  => $rows->filter(fn ($g) => $g->updated_at && $g->updated_at->lt($overdueCutoff))->count(),
                'hot'      => $rows->where('lead_score_tier', 'hot')->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->values();

        // ── Senior dropdown listesi (atama için) ─────────────────────────────
        $seniorList = StudentAssignment::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->distinct()
            ->pluck('senior_email')
            ->filter()
            ->sort()
            ->values();

        return view('manager.lead-pipeline.oversight', compact(
            'columns', 'kpis', 'seniorWorkload', 'seniorList',
            'filterSenior', 'filterRisk'
        ));
    }

    /**
     * Real-time poll endpoint — senior'lar pipeline'ı taşırken oversight güncel kalsın.
     */
    public function poll(Request $request): JsonResponse
    {
        $cid = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $rows = GuestApplication::query()
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->whereNull('deleted_at')
            ->get(['id', 'lead_status', 'pipeline_moved_by', 'updated_at']);

        return response()->json($rows->map(fn ($g) => [
            'id'                => $g->id,
            'lead_status'       => $g->lead_status,
            'pipeline_moved_by' => $g->pipeline_moved_by,
            'updated_at'        => $g->updated_at?->toISOString(),
        ]));
    }
}
