<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Senior\Concerns\SeniorPortalTrait;
use App\Models\GuestApplication;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SeniorPipelineController extends Controller
{
    use SeniorPortalTrait;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    // ── Guest Pipeline (Kanban) ──────────────────────────────────────────────

    public function guestPipeline(Request $request)
    {
        $seniorEmail = $this->seniorEmail($request);
        $companyId   = $request->user()?->company_id;

        // SECURITY: Senior yalnızca kendisine atanmış guest'leri görür.
        // Manager/Marketing tüm pipeline'a erişebilir; bu controller senior'a özel.
        $guests = GuestApplication::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('assigned_senior_email', $seniorEmail)
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->limit(1000)
            ->get(['id', 'first_name', 'last_name', 'lead_status', 'lead_score_tier',
                   'application_type', 'application_country', 'communication_language',
                   'updated_at', 'pipeline_moved_by', 'pipeline_moved_at']);

        $readonly = ['converted', 'lost'];

        $columns = collect(self::GUEST_PIPELINE_STAGES)->map(fn ($label, $code) => [
            'code'     => $code,
            'label'    => $label,
            'readonly' => in_array($code, $readonly),
            'cards'    => $guests->where('lead_status', $code)->values(),
        ])->values();

        $stats = [
            'total'      => $guests->count(),
            'new'        => $guests->where('lead_status', 'new')->count(),
            'active'     => $guests->whereNotIn('lead_status', ['new', 'converted', 'lost'])->count(),
            'contracted' => $guests->where('lead_status', 'contract_signed')->count(),
            'converted'  => $guests->where('lead_status', 'converted')->count(),
        ];

        return view('senior.guest-pipeline', compact('columns', 'stats'));
    }

    public function guestPipelinePoll(Request $request)
    {
        $companyId   = $request->user()?->company_id;
        $seniorEmail = $this->seniorEmail($request);
        $since       = $request->query('since');

        // SECURITY: Cache key senior'a özel — başka senior'un cache'ini görmesin.
        $cacheKey = 'pipeline_poll_' . (int) $companyId . '_' . md5($seniorEmail);

        $rows = \Illuminate\Support\Facades\Cache::remember($cacheKey, 10, function () use ($companyId, $seniorEmail) {
            return GuestApplication::query()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('assigned_senior_email', $seniorEmail)
                ->whereNull('deleted_at')
                ->limit(1000)
                ->get(['id', 'lead_status', 'pipeline_moved_by', 'updated_at']);
        });

        if ($since) {
            $sinceTime = \Carbon\Carbon::parse($since);
            $rows = $rows->filter(fn($g) => $g->updated_at && $g->updated_at->gt($sinceTime))->values();
        }

        return response()->json($rows->map(fn($g) => [
            'id'                => $g->id,
            'lead_status'       => $g->lead_status,
            'pipeline_moved_by' => $g->pipeline_moved_by,
            'updated_at'        => $g->updated_at?->toISOString(),
        ]));
    }

    public function guestPipelineMove(Request $request, string $guest)
    {
        $stage = $request->input('stage');
        abort_unless(array_key_exists($stage, self::GUEST_PIPELINE_STAGES), 422);

        $guestModel = GuestApplication::withoutGlobalScope('company')
            ->whereKey($guest)
            ->whereNull('deleted_at')
            ->first();
        abort_if(!$guestModel, 404);

        // SECURITY: Senior yalnızca kendisine atanmış guest'i taşıyabilir.
        // Cross-tenant ve cross-senior data manipulation engelle.
        $seniorEmail = $this->seniorEmail($request);
        abort_if(
            (string) ($guestModel->assigned_senior_email ?? '') !== $seniorEmail,
            403,
            'Bu aday size atanmamış — pipeline aşaması değiştiremezsiniz.'
        );

        $guest = $guestModel;

        $oldStage = $guest->lead_status;
        $mover = $request->user()?->name ?: $request->user()?->email ?: 'Senior';
        \DB::table('guest_applications')->where('id', $guest->id)->update([
            'lead_status'       => $stage,
            'pipeline_moved_by' => $mover,
            'pipeline_moved_at' => now(),
            'updated_at'        => now(),
        ]);

        \App\Models\GuestPipelineLog::create([
            'guest_application_id' => $guest->id,
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
                entityId:   (string) $guest->id,
                message:    "Guest pipeline aşaması değiştirildi: {$oldStage} → {$stage} | Aday: {$guest->first_name} {$guest->last_name}",
                meta:       ['from' => $oldStage, 'to' => $stage, 'guest_id' => $guest->id],
                actorEmail: $request->user()?->email,
            );
        } catch (\Throwable $e) {
            \Log::warning('EventLogService failed in guestPipelineMove: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    // ── Student Pipeline (Kanban) ────────────────────────────────────────────

    public function studentPipeline(Request $request)
    {
        $studentIds = $this->assignedStudentIds($request);

        $assignMap = $studentIds->isEmpty() ? collect()
            : StudentAssignment::whereIn('student_id', $studentIds->all())
                ->get(['student_id', 'display_name'])
                ->keyBy('student_id');

        $guestMap = $studentIds->isEmpty() ? collect()
            : GuestApplication::whereIn('converted_student_id', $studentIds->all())
                ->whereNotNull('converted_student_id')
                ->latest('id')
                ->get(['converted_student_id', 'first_name', 'last_name', 'contract_status', 'lead_score_tier'])
                ->keyBy('converted_student_id');

        $userMap = $studentIds->isEmpty() ? collect()
            : User::whereIn('student_id', $studentIds->all())
                ->get(['student_id', 'name'])
                ->keyBy('student_id');

        $allStudents = $studentIds->map(function ($sid) use ($assignMap, $guestMap, $userMap) {
            $a = $assignMap->get($sid);
            $g = $guestMap->get($sid);
            $u = $userMap->get($sid);
            $guestName = $g ? trim($g->first_name . ' ' . $g->last_name) : null;
            return (object) [
                'student_id'      => $sid,
                'name'            => $a?->display_name ?: ($guestName ?: ($u?->name ?: $sid)),
                'contract_status' => $g?->contract_status ?? null,
                'lead_score_tier' => $g?->lead_score_tier ?? null,
            ];
        });

        $riskMap = $studentIds->isEmpty() ? collect()
            : StudentAssignment::whereIn('student_id', $studentIds->all())
                ->pluck('risk_level', 'student_id');

        $outcomes = $studentIds->isEmpty() ? collect()
            : ProcessOutcome::whereIn('student_id', $studentIds->all())
                ->orderByDesc('created_at')
                ->get(['student_id', 'process_step', 'created_at'])
                ->groupBy('student_id');

        $primarySteps = $outcomes->map(fn ($rows) => $rows->first()?->process_step ?? 'application_prep');

        $cutoff = now()->subDays(60);
        $parallelSteps = $outcomes->map(function ($rows, $sid) use ($primarySteps, $cutoff) {
            $primary = $primarySteps[$sid] ?? 'application_prep';
            return $rows
                ->filter(fn ($r) => $r->created_at >= $cutoff
                    && $r->process_step !== $primary
                    && $r->process_step !== 'completed')
                ->pluck('process_step')
                ->unique()
                ->values()
                ->all();
        });

        $columns = collect(self::PIPELINE_STEPS)->map(fn ($label, $stepCode) => [
            'step'  => $stepCode,
            'label' => $label,
            'cards' => $allStudents
                ->filter(fn ($s) => ($primarySteps[$s->student_id] ?? 'application_prep') === $stepCode)
                ->map(fn ($s) => [
                    'student_id'     => $s->student_id,
                    'name'           => $s->name,
                    'contract'       => $s->contract_status,
                    'tier'           => $s->lead_score_tier,
                    'risk'           => $riskMap[$s->student_id] ?? 'low',
                    'detail_url'     => '/senior/students/' . $s->student_id,
                    'parallel_steps' => $parallelSteps[$s->student_id] ?? [],
                ])->values(),
        ])->values();

        return view('senior.student-pipeline', [
            'columns'       => $columns,
            'totalStudents' => $allStudents->count(),
            'sidebarStats'  => $this->sidebarStats($request),
        ]);
    }

    public function advanceStudentStep(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'student_id' => 'required|string|max:20',
            'new_step'   => 'required|in:application_prep,uni_assist,visa_application,language_course,residence,official_services,completed',
            'note'       => 'nullable|string|max:500',
        ]);

        abort_if(!$this->assignedStudentIds($request)->contains($data['student_id']), 403);

        ProcessOutcome::create([
            'student_id'            => $data['student_id'],
            'process_step'          => $data['new_step'],
            'outcome_type'          => 'in_progress',
            'details_tr'            => $data['note'] ?? 'Kanban üzerinden aşama değiştirildi.',
            'is_visible_to_student' => false,
            'added_by'              => $this->seniorEmail($request),
        ]);

        return response()->json(['ok' => true]);
    }
}
