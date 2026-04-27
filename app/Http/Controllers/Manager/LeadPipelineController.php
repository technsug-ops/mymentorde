<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\GuestPipelineLog;
use App\Services\EventLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Manager portal — Lead Pipeline Kanban.
 *
 * Marketing Admin modülü kapalı şirketlerde dahi pipeline yönetimi yapılabilsin
 * diye Manager'a taşınmış sadeleştirilmiş kanban. Marketing Admin'deki
 * SalesPipelineController premium özellikleri (UTM, attribution, kampanya
 * tetik) içerir; bu controller sadece operasyonel kanban + log düşer.
 *
 * Veri modeli ve stage enum'u Marketing Admin ile aynı (GuestApplication.lead_status,
 * GuestPipelineLog) — her iki panelden gelen değişiklikler tutarlı.
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
            'readonly' => false,
            'cards'    => $guests->where('lead_status', $code)->values(),
        ])->values();

        $stats = [
            'total'     => $guests->count(),
            'open'      => $guests->whereNotIn('lead_status', ['converted', 'lost'])->count(),
            'hot'       => $guests->where('lead_score_tier', 'hot')->count(),
            'converted' => $guests->where('lead_status', 'converted')->count(),
            'lost'      => $guests->where('lead_status', 'lost')->count(),
        ];

        return view('manager.lead-pipeline.kanban', compact('columns', 'stats'));
    }

    public function kanbanPoll(Request $request): JsonResponse
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

    public function kanbanMove(Request $request, string $guest): JsonResponse
    {
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
        $mover = $request->user()?->name ?: $request->user()?->email ?: 'Manager';

        DB::table('guest_applications')
            ->where('id', $guestModel->id)
            ->update([
                'lead_status'       => $stage,
                'pipeline_moved_by' => $mover,
                'pipeline_moved_at' => now(),
                'updated_at'        => now(),
            ]);

        GuestPipelineLog::create([
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
            app(EventLogService::class)->log(
                event_type: 'guest_pipeline_move',
                entityType: 'guest_application',
                entityId:   (string) $guestModel->id,
                message:    "Lead pipeline aşaması değiştirildi: {$oldStage} → {$stage} | Aday: {$guestModel->first_name} {$guestModel->last_name}",
                meta:       ['from' => $oldStage, 'to' => $stage, 'guest_id' => $guestModel->id, 'panel' => 'manager'],
                actorEmail: $request->user()?->email,
            );
        } catch (\Throwable) {
        }

        return response()->json(['ok' => true, 'stage' => $stage]);
    }
}
