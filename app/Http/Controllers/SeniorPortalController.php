<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Senior\Concerns\SeniorPortalTrait;
use App\Models\BusinessContract;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\InternalNote;
use App\Models\MarketingTask;
use App\Models\ProcessOutcome;
use App\Models\ProcessStepTask;
use App\Models\StudentAssignment;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentLanguageCourse;
use App\Models\StudentProcessTaskCompletion;
use App\Models\StudentUniversityApplication;
use App\Models\StudentVisaApplication;
use App\Models\StudentAccommodation;
use App\Services\LeadScoreService;
use App\Services\NotificationService;
use App\Services\PipelineProgressService;
use App\Services\ProcessOutcomeService;
use App\Support\FileUploadRules;
use App\Support\SchemaCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * SeniorPortalController — yalnızca core domain metodları.
 *
 * Çıkarılan metodlar:
 *   • Pipeline  → Senior\SeniorPipelineController
 *   • Öğrenci   → Senior\SeniorStudentController
 *   • Randevu   → Senior\SeniorAppointmentController
 *   • Profil    → Senior\SeniorProfileController
 *
 * Paylaşılan özel metodlar (seniorEmail, assignedStudentIds, sidebarStats, vb.)
 * → Senior\Concerns\SeniorPortalTrait içinde.
 */
class SeniorPortalController extends Controller
{
    use SeniorPortalTrait;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    // ── Unified Inbox ────────────────────────────────────────────────────────

    public function unifiedInbox(Request $request)
    {
        $user        = $request->user();
        $seniorEmail = $this->seniorEmail($request);
        $assignedIds = $this->assignedStudentIds($request);

        $dmThreads = $assignedIds->isEmpty() ? collect() : \App\Models\DmThread::query()
            ->whereIn('student_id', $assignedIds->all())
            ->latest('last_message_at')
            ->limit(30)
            ->get();

        $guestIds = $assignedIds->isEmpty() ? collect() : GuestApplication::query()
            ->whereIn('converted_student_id', $assignedIds->all())
            ->pluck('id');

        $tickets = $guestIds->isEmpty() ? collect() : GuestTicket::query()
            ->whereIn('guest_application_id', $guestIds->all())
            ->whereIn('status', ['open', 'in_progress'])
            ->latest()
            ->limit(30)
            ->get(['id', 'guest_application_id', 'subject', 'status', 'priority', 'department', 'created_at', 'updated_at']);

        $internalConvs = collect();
        if (class_exists(\App\Models\ConversationParticipant::class)) {
            $convIds = \App\Models\ConversationParticipant::query()
                ->where('user_id', (int) $user->id)
                ->pluck('conversation_id');
            if ($convIds->isNotEmpty()) {
                $internalConvs = \App\Models\Conversation::query()
                    ->whereIn('id', $convIds->all())
                    ->latest('last_message_at')
                    ->limit(20)
                    ->get(['id', 'title', 'type', 'last_message_at', 'last_message_preview']);
            }
        }

        $items = collect();

        foreach ($dmThreads as $t) {
            $items->push([
                'type'    => 'dm',
                'id'      => $t->id,
                'subject' => $t->last_message_preview
                    ? Str::limit((string) $t->last_message_preview, 60)
                    : ('DM: ' . ($t->student_id ?? '-')),
                'from'    => $t->student_id ?? '-',
                'status'  => $t->status,
                'unread'  => ($t->last_participant_message_at && (!$t->last_advisor_reply_at || $t->last_participant_message_at > $t->last_advisor_reply_at)),
                'at'      => $t->last_message_at,
                'url'     => '/senior/messages',
                'icon'    => '💬',
            ]);
        }

        foreach ($tickets as $t) {
            $items->push([
                'type'    => 'ticket',
                'id'      => $t->id,
                'subject' => $t->subject,
                'from'    => 'ticket #' . $t->id,
                'status'  => $t->status,
                'unread'  => ($t->status === 'open'),
                'at'      => $t->updated_at,
                'url'     => '/senior/tickets',
                'icon'    => '🎫',
            ]);
        }

        foreach ($internalConvs as $c) {
            $items->push([
                'type'    => 'internal',
                'id'      => $c->id,
                'subject' => $c->title ?? ('Konuşma #' . $c->id),
                'from'    => $c->type ?? 'dm',
                'status'  => 'open',
                'unread'  => false,
                'at'      => $c->last_message_at,
                'url'     => '/im',
                'icon'    => '🔔',
            ]);
        }

        $sorted = $items->sortByDesc(fn ($a) => ((int) $a['unread'] * 1_000_000_000) + ($a['at'] ? $a['at']->timestamp : 0))->values();

        return view('senior.inbox', [
            'items'        => $sorted,
            'unreadCount'  => $sorted->where('unread', true)->count(),
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }

    // ── Quick Note Widget ────────────────────────────────────────────────────

    public function quickNote(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['nullable', 'string', 'max:50'],
            'content'    => ['required', 'string', 'max:2000'],
            'category'   => ['nullable', 'in:registration,document,visa,housing,language,general'],
            'priority'   => ['nullable', 'in:low,medium,high'],
        ]);

        $assignedIds = $this->assignedStudentIds($request);
        $studentId   = $data['student_id'] ?? null;

        if ($studentId && !$assignedIds->contains($studentId)) {
            return response()->json(['ok' => false, 'error' => 'Bu öğrenci size atanmamış.'], 403);
        }

        $note = InternalNote::create([
            'student_id'      => $studentId ?? 'GENERAL',
            'created_by'      => $request->user()?->email,
            'created_by_role' => 'senior',
            'category'        => $data['category'] ?? 'general',
            'priority'        => $data['priority'] ?? 'medium',
            'content'         => $data['content'],
            'is_pinned'       => false,
        ]);

        return response()->json(['ok' => true, 'id' => $note->id]);
    }

    public function recentNotes(Request $request): \Illuminate\Http\JsonResponse
    {
        $email = $this->seniorEmail($request);
        $notes = InternalNote::query()
            ->where('created_by', $email)
            ->latest()
            ->limit(8)
            ->get(['id', 'student_id', 'category', 'priority', 'content', 'created_at']);

        return response()->json([
            'notes' => $notes->map(fn ($n) => [
                'id'         => $n->id,
                'student_id' => $n->student_id,
                'category'   => $n->category,
                'priority'   => $n->priority,
                'content'    => Str::limit((string) $n->content, 100),
                'created_at' => $n->created_at?->format('d.m H:i'),
            ])->values(),
        ]);
    }

    // ── Kayıt Belgeleri ──────────────────────────────────────────────────────

    public function registrationDocuments(Request $request)
    {
        $studentIds = $this->assignedStudentIds($request);
        $q      = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $documents = $studentIds->isEmpty()
            ? collect()
            : Document::query()
                ->whereIn('student_id', $studentIds->all())
                ->when($q !== '', function ($w) use ($q) {
                    $w->where(function ($x) use ($q) {
                        $x->where('student_id', 'like', "%{$q}%")
                            ->orWhere('document_id', 'like', "%{$q}%")
                            ->orWhere('standard_file_name', 'like', "%{$q}%");
                    });
                })
                ->when($status !== '' && $status !== 'all', fn ($w) => $w->where('status', $status))
                ->latest()
                ->limit(200)
                ->get(['id', 'student_id', 'document_id', 'standard_file_name', 'status', 'updated_at']);

        return view('senior.registration-documents', [
            'documents'    => $documents,
            'filters'      => compact('q', 'status'),
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }

    // ── Süreç Takibi ─────────────────────────────────────────────────────────

    public function processTracking(Request $request)
    {
        $studentIds = $this->assignedStudentIds($request);
        $filterSid  = trim((string) $request->query('student_id', ''));

        $guestMap = GuestApplication::query()
            ->whereIn('converted_student_id', $studentIds->all())
            ->latest('id')
            ->get(['first_name', 'last_name', 'converted_student_id'])
            ->keyBy('converted_student_id');

        $studentOptions = $studentIds->map(function ($sid) use ($guestMap) {
            $g    = $guestMap->get($sid);
            $name = $g ? trim((string) $g->first_name . ' ' . (string) $g->last_name) : '';
            return ['id' => $sid, 'label' => $name !== '' ? $name : $sid];
        })->values();

        $allDocs    = collect();
        $nameMap    = collect();
        $docFilters = [];
        if ($filterSid === '') {
            $dCat    = trim((string) $request->query('category', ''));
            $dStatus = trim((string) $request->query('status', ''));
            $dQ      = trim((string) $request->query('q', ''));
            $dSid    = trim((string) $request->query('doc_student', ''));

            $allDocs = StudentInstitutionDocument::query()
                ->whereIn('student_id', $studentIds)
                ->when($dQ !== '', fn($w) => $w->where(fn($w2) =>
                    $w2->where('document_type_label', 'like', "%{$dQ}%")
                       ->orWhere('institution_name', 'like', "%{$dQ}%")
                       ->orWhere('notes', 'like', "%{$dQ}%")))
                ->when($dCat    !== '', fn($w) => $w->where('institution_category', $dCat))
                ->when($dStatus !== '', fn($w) => $w->where('status', $dStatus))
                ->when($dSid    !== '', fn($w) => $w->where('student_id', $dSid))
                ->latest()
                ->paginate(40)
                ->withQueryString();

            $nameMap = GuestApplication::query()
                ->whereIn('converted_student_id', $studentIds->all())
                ->get(['converted_student_id', 'first_name', 'last_name'])
                ->mapWithKeys(fn($g) => [
                    (string) $g->converted_student_id => trim("{$g->first_name} {$g->last_name}"),
                ]);

            $docFilters = compact('dCat', 'dStatus', 'dQ', 'dSid');
        }

        $selectedStudent = null;
        $uniApplications = collect();
        $institutionDocs = collect();
        $outcomes        = collect();
        $ptVisa          = null;
        $ptAccommodation = null;
        $languageCourses = collect();
        $guestApp        = null;
        $registrationDocs = collect();

        if ($filterSid !== '' && $studentIds->contains($filterSid)) {
            $selectedStudent = $studentOptions->firstWhere('id', $filterSid);

            $uniApplications = StudentUniversityApplication::query()
                ->forStudent($filterSid)->orderBy('priority')->get();

            $institutionDocs = StudentInstitutionDocument::query()
                ->forStudent($filterSid)->latest()->get();

            $outcomes = ProcessOutcome::query()
                ->where('student_id', $filterSid)
                ->latest()
                ->with('document:id,original_file_name,standard_file_name')
                ->get(['id', 'student_id', 'process_step', 'outcome_type', 'university', 'program', 'details_tr', 'deadline', 'is_visible_to_student', 'document_id', 'created_at']);

            $ptVisa          = StudentVisaApplication::where('student_id', $filterSid)->latest('id')->first();
            $ptAccommodation = StudentAccommodation::where('student_id', $filterSid)->latest('id')->first();
            $languageCourses = StudentLanguageCourse::where('student_id', $filterSid)->latest()->get();
            $guestApp        = GuestApplication::where('converted_student_id', $filterSid)->latest('id')->first();
            $registrationDocs = Document::where('student_id', $filterSid)->with('category')->latest()->get();
        }

        // Statik referans veriler — 10 dk cache
        $processDefinitions = Cache::remember('process_definitions_active', 600, fn() =>
            \App\Models\ProcessDefinition::where('is_active', true)->orderBy('sort_order')->get()
        );

        $allProcessTasks = Cache::remember('process_step_tasks_active', 600, fn() =>
            ProcessStepTask::where('is_active', true)
                ->orderBy('process_definition_id')->orderBy('sort_order')->get()
        );

        $completedTaskIds = ($filterSid !== '' && $studentIds->contains($filterSid))
            ? StudentProcessTaskCompletion::where('student_id', $filterSid)
                ->whereNotNull('completed_at')->pluck('task_id')->flip()
            : collect();

        $tasksByStep = $allProcessTasks->groupBy('process_definition_id');

        $taskProgress = $tasksByStep->map(function ($tasks) use ($completedTaskIds) {
            $total = $tasks->count();
            $done  = $tasks->filter(fn($t) => $completedTaskIds->has($t->id))->count();
            return compact('total', 'done');
        });

        $institutionCatalog = config('institution_document_catalog.categories', []);

        $doneTasks = collect();
        if ($filterSid !== '' && $studentIds->contains($filterSid)) {
            $guestApp  = GuestApplication::query()
                ->where('converted_student_id', $filterSid)->latest('id')->first();

            $guestSourceTypes = [
                'guest_registration_submit', 'guest_contract_requested',
                'guest_contract_sales_followup', 'guest_contract_signed_uploaded',
                'guest_document_uploaded',
            ];

            $doneTasks = MarketingTask::query()
                ->withoutGlobalScope('company')
                ->where('status', 'done')
                ->where('is_auto_generated', true)
                ->where(function ($q) use ($filterSid, $guestApp, $guestSourceTypes): void {
                    $q->where('related_student_id', $filterSid);
                    if ($guestApp) {
                        $q->orWhere(function ($q2) use ($guestApp, $guestSourceTypes): void {
                            $q2->whereIn('source_type', $guestSourceTypes)
                               ->where('source_id', (string) $guestApp->id);
                        });
                    }
                })
                ->orderByDesc('completed_at')
                ->limit(20)
                ->get(['id', 'title', 'process_type', 'workflow_stage', 'priority', 'department', 'completed_at']);
        }

        return view('senior.process-tracking', [
            'outcomes'           => $outcomes,
            'uniApplications'    => $uniApplications,
            'institutionDocs'    => $institutionDocs,
            'institutionCatalog' => $institutionCatalog,
            'studentOptions'     => $studentOptions,
            'selectedStudent'    => $selectedStudent,
            'filterSid'          => $filterSid,
            'doneTasks'          => $doneTasks,
            'allDocs'            => $allDocs,
            'nameMap'            => $nameMap,
            'docFilters'         => $docFilters,
            'ptVisa'             => $ptVisa,
            'ptAccommodation'    => $ptAccommodation,
            'languageCourses'    => $languageCourses,
            'guestApp'           => $guestApp,
            'registrationDocs'   => $registrationDocs,
            'processDefinitions' => $processDefinitions,
            'tasksByStep'        => $tasksByStep,
            'completedTaskIds'   => $completedTaskIds,
            'taskProgress'       => $taskProgress,
            'sidebarStats'       => $this->sidebarStats($request),
        ]);
    }

    // ── Guest Detay (salt okunur) ────────────────────────────────────────────

    public function guestDetail(\App\Models\GuestApplication $guest): \Illuminate\Contracts\View\View
    {
        $cid = auth()->user()->company_id ?? 0;
        abort_if($cid > 0 && (int) $guest->company_id !== $cid, 403);

        $student = ($guest->converted_to_student && $guest->converted_student_id)
            ? \App\Models\StudentAssignment::where('student_id', $guest->converted_student_id)->first()
            : null;

        return view('senior.guest-detail', compact('guest', 'student'));
    }

    // ── Sub-task Toggle ──────────────────────────────────────────────────────

    public function toggleProcessTask(Request $request, ProcessStepTask $task): \Illuminate\Http\JsonResponse
    {
        $studentId  = trim((string) $request->input('student_id', ''));
        $studentIds = $this->assignedStudentIds($request);

        if ($studentId === '' || !$studentIds->contains($studentId)) {
            return response()->json(['error' => 'Yetkisiz'], 403);
        }

        $existing = StudentProcessTaskCompletion::where('student_id', $studentId)
            ->where('task_id', $task->id)
            ->first();

        if ($existing && $existing->completed_at) {
            $existing->update(['completed_at' => null, 'completed_by' => null]);
            return response()->json(['completed' => false]);
        }

        StudentProcessTaskCompletion::updateOrCreate(
            ['student_id' => $studentId, 'task_id' => $task->id],
            ['completed_at' => now(), 'completed_by' => $request->user()?->email ?? '']
        );

        return response()->json(['completed' => true]);
    }

    // ── Dil Kursu CRUD ───────────────────────────────────────────────────────

    public function languageCourseStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();
        $data = $request->validate([
            'student_id'         => ['required', 'string', 'max:64', Rule::in($studentIds)],
            'school_name'        => ['required', 'string', 'max:255'],
            'city'               => ['nullable', 'string', 'max:100'],
            'course_type'        => ['required', Rule::in(['DSH', 'TestDaF', 'Goethe', 'other'])],
            'level_target'       => ['nullable', 'string', 'max:10'],
            'level_achieved'     => ['nullable', 'string', 'max:10'],
            'start_date'         => ['nullable', 'date'],
            'end_date'           => ['nullable', 'date'],
            'certificate_status' => ['required', Rule::in(['none', 'pending', 'received', 'submitted'])],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        StudentLanguageCourse::create(array_merge($data, [
            'company_id' => $request->user()?->company_id,
            'added_by'   => $request->user()?->email,
        ]));

        return redirect("/senior/process-tracking?student_id={$data['student_id']}&tab=dil")->with('status', 'Dil kursu eklendi.');
    }

    public function languageCourseUpdate(Request $request, StudentLanguageCourse $course): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();
        abort_unless(in_array($course->student_id, $studentIds), 403);

        $data = $request->validate([
            'school_name'           => ['required', 'string', 'max:255'],
            'city'                  => ['nullable', 'string', 'max:100'],
            'course_type'           => ['required', Rule::in(['DSH', 'TestDaF', 'Goethe', 'other'])],
            'level_target'          => ['nullable', 'string', 'max:10'],
            'level_achieved'        => ['nullable', 'string', 'max:10'],
            'start_date'            => ['nullable', 'date'],
            'end_date'              => ['nullable', 'date'],
            'certificate_status'    => ['required', Rule::in(['none', 'pending', 'received', 'submitted'])],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'is_visible_to_student' => ['boolean'],
        ]);

        $course->update($data);
        return redirect("/senior/process-tracking?student_id={$course->student_id}&tab=dil")->with('status', 'Dil kursu güncellendi.');
    }

    public function languageCourseDelete(StudentLanguageCourse $course, Request $request): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();
        abort_unless(in_array($course->student_id, $studentIds), 403);
        $sid = $course->student_id;
        $course->delete();
        return redirect("/senior/process-tracking?student_id={$sid}&tab=dil")->with('status', 'Dil kursu silindi.');
    }

    // ── Süreç Sonucu Kaydet ──────────────────────────────────────────────────

    public function storeProcessOutcome(Request $request): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();

        $data = $request->validate([
            'student_id'    => ['required', 'string', 'max:64', Rule::in($studentIds)],
            'process_step'  => ['required', 'string', 'max:64'],
            'outcome_type'  => ['required', 'in:acceptance,rejection,correction_request,conditional_acceptance,waitlist'],
            'university'    => ['nullable', 'string', 'max:255'],
            'program'       => ['nullable', 'string', 'max:255'],
            'details_tr'    => ['required', 'string', 'max:2000'],
            'deadline'      => ['nullable', 'date'],
            'document_file' => FileUploadRules::documentOptional(),
        ]);

        $data['added_by']              = $this->seniorEmail($request);
        $data['is_visible_to_student'] = false;
        unset($data['document_file']);

        if ($request->hasFile('document_file')) {
            $file   = $request->file('document_file');
            $folder = 'process-docs/' . date('Y-m');
            $stored = $file->store($folder, 'public');
            $doc    = Document::create([
                'student_id'         => $data['student_id'],
                'original_file_name' => $file->getClientOriginalName(),
                'standard_file_name' => $file->getClientOriginalName(),
                'storage_path'       => $stored,
                'mime_type'          => $file->getMimeType(),
                'status'             => 'approved',
                'uploaded_by'        => $this->seniorEmail($request),
            ]);
            $data['document_id'] = $doc->id;
        }

        $outcome = ProcessOutcome::create($data);

        app(LeadScoreService::class)->recalculateForStudent($data['student_id']);
        app(PipelineProgressService::class)->advanceFromProcessOutcome($outcome);

        return back()->with('status', 'Süreç kaydı eklendi. Hazır olunca "Öğrenciye Göster" tıklayın.');
    }

    public function makeOutcomeVisible(Request $request, ProcessOutcome $outcome, ProcessOutcomeService $service): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();
        if (!in_array((string) $outcome->student_id, $studentIds, true)) {
            abort(403);
        }

        $service->makeVisibleToStudent($outcome, $this->seniorEmail($request));

        return back()->with('status', "Outcome #{$outcome->id} öğrenciye görünür yapıldı ve bildirim kuyruğa alındı.");
    }

    // ── Biletler ─────────────────────────────────────────────────────────────

    public function tickets(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $status   = trim((string) $request->query('status', 'all'));
        $priority = trim((string) $request->query('priority', 'all'));
        $seniorUserId = (int) $request->user()->id;
        $seniorEmail  = strtolower(trim((string) ($request->user()->email ?? '')));

        $assignedStudentIds = StudentAssignment::query()
            ->where('senior_email', $seniorEmail)
            ->pluck('student_id')
            ->all();

        $studentGuestAppIds = empty($assignedStudentIds) ? [] : GuestApplication::query()
            ->whereIn('converted_student_id', $assignedStudentIds)
            ->pluck('id')
            ->all();

        $tickets = GuestTicket::query()
            ->where(function ($w) use ($seniorUserId, $studentGuestAppIds) {
                $w->where('assigned_user_id', $seniorUserId);
                if (!empty($studentGuestAppIds)) {
                    $w->orWhereIn('guest_application_id', $studentGuestAppIds);
                }
            })
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('subject', 'like', "%{$q}%")
                        ->orWhere('department', 'like', "%{$q}%")
                        ->orWhere('guest_application_id', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', fn ($w) => $w->where('status', $status))
            ->when($priority !== '' && $priority !== 'all', fn ($w) => $w->where('priority', $priority))
            ->latest()
            ->limit(200)
            ->get(['id', 'subject', 'status', 'priority', 'department', 'last_replied_at', 'guest_application_id']);

        $guestMap = GuestApplication::query()
            ->whereIn('id', $tickets->pluck('guest_application_id')->filter()->all())
            ->get(['id', 'first_name', 'last_name', 'email', 'converted_student_id'])
            ->keyBy('id');

        return view('senior.tickets', [
            'tickets'      => $tickets,
            'guestMap'     => $guestMap,
            'filters'      => compact('q', 'status', 'priority'),
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }

    // ── Sözleşmeler ──────────────────────────────────────────────────────────

    public function contracts(Request $request)
    {
        $email      = $this->seniorEmail($request);
        $studentIds = $this->assignedStudentIds($request);
        $q          = trim((string) $request->query('q', ''));
        $status     = trim((string) $request->query('status', 'all'));
        $type       = trim((string) $request->query('type', 'all'));

        $baseQuery = GuestApplication::query()
            ->where(function ($w) use ($studentIds, $email) {
                if ($studentIds->isNotEmpty()) {
                    $w->whereIn('converted_student_id', $studentIds->all());
                }
                $w->orWhereRaw('lower(assigned_senior_email) = ?', [strtolower($email)]);
            })
            ->where('contract_status', '!=', 'not_requested')
            ->whereNotNull('contract_status');

        $countQuery = clone $baseQuery;
        $counts = [
            'all'             => (clone $countQuery)->count(),
            'requested'       => (clone $countQuery)->where('contract_status', 'requested')->count(),
            'signed_uploaded' => (clone $countQuery)->where('contract_status', 'signed_uploaded')->count(),
            'approved'        => (clone $countQuery)->where('contract_status', 'approved')->count(),
            'rejected'        => (clone $countQuery)->where('contract_status', 'rejected')->count(),
        ];

        $contracts = $baseQuery
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('converted_student_id', 'like', "%{$q}%")
                        ->orWhere('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', fn ($w) => $w->where('contract_status', $status))
            ->when($type === 'guest',   fn ($w) => $w->whereNull('converted_student_id'))
            ->when($type === 'student', fn ($w) => $w->whereNotNull('converted_student_id'))
            ->latest('contract_requested_at')
            ->limit(200)
            ->get([
                'id', 'converted_student_id', 'first_name', 'last_name', 'email',
                'contract_status', 'contract_requested_at', 'contract_signed_file_path',
                'selected_package_title', 'selected_extra_services',
            ]);

        return view('senior.contracts', [
            'contracts'    => $contracts,
            'counts'       => $counts,
            'filters'      => compact('q', 'status', 'type'),
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }

    // ── Servisler ────────────────────────────────────────────────────────────

    public function services(Request $request)
    {
        $studentIds = $this->assignedStudentIds($request);
        $q          = trim((string) $request->query('q', ''));
        $package    = trim((string) $request->query('package', 'all'));

        $allRows = $studentIds->isEmpty()
            ? collect()
            : GuestApplication::query()
                ->whereIn('converted_student_id', $studentIds->all())
                ->get(['selected_package_code', 'selected_extra_services']);

        $pkgCounts   = $allRows->countBy('selected_package_code');
        $extraCounts = $allRows->flatMap(function ($r) {
            return is_array($r->selected_extra_services)
                ? collect($r->selected_extra_services)->pluck('code')
                : [];
        })->countBy()->all();

        $services = $studentIds->isEmpty()
            ? collect()
            : GuestApplication::query()
                ->whereIn('converted_student_id', $studentIds->all())
                ->when($q !== '', function ($w) use ($q) {
                    $w->where(function ($x) use ($q) {
                        $x->where('converted_student_id', 'like', "%{$q}%")
                            ->orWhere('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%");
                    });
                })
                ->when($package !== '' && $package !== 'all', fn ($w) => $w->where('selected_package_code', $package))
                ->latest()
                ->limit(200)
                ->get([
                    'converted_student_id', 'first_name', 'last_name',
                    'selected_package_code', 'selected_package_title', 'selected_extra_services',
                ]);

        $packages          = collect(config('service_packages.packages', []))->where('is_active', true)->sortBy('sort_order')->values();
        $allExtras         = collect(config('service_packages.extra_services', []))->where('is_active', true);
        $serviceCategories = collect(config('service_packages.service_categories', []))
            ->map(fn ($cat) => array_merge($cat, [
                'services' => $allExtras->where('category', $cat['key'])->sortBy('sort_order')->values()->all(),
            ]))
            ->filter(fn ($cat) => !empty($cat['services']))
            ->values();

        return view('senior.services', [
            'services'          => $services,
            'filters'           => compact('q', 'package'),
            'packages'          => $packages,
            'serviceCategories' => $serviceCategories,
            'pkgCounts'         => $pkgCounts,
            'extraCounts'       => $extraCounts,
            'totalStudents'     => $studentIds->count(),
            'sidebarStats'      => $this->sidebarStats($request),
        ]);
    }

    // ── Notlar ───────────────────────────────────────────────────────────────

    public function notes(Request $request)
    {
        $studentIds = $this->assignedStudentIds($request);
        $q          = trim((string) $request->query('q', ''));
        $priority   = trim((string) $request->query('priority', 'all'));

        $notes = $studentIds->isEmpty()
            ? collect()
            : InternalNote::query()
                ->whereIn('student_id', $studentIds->all())
                ->when($q !== '', function ($w) use ($q) {
                    $w->where(function ($x) use ($q) {
                        $x->where('student_id', 'like', "%{$q}%")
                            ->orWhere('category', 'like', "%{$q}%")
                            ->orWhere('content', 'like', "%{$q}%");
                    });
                })
                ->when($priority !== '' && $priority !== 'all', fn ($w) => $w->where('priority', $priority))
                ->latest()
                ->limit(200)
                ->get(['id', 'student_id', 'category', 'priority', 'content', 'is_pinned', 'created_at']);

        return view('senior.notes', [
            'notes'        => $notes,
            'filters'      => compact('q', 'priority'),
            'sidebarStats' => $this->sidebarStats($request),
        ]);
    }
}
