<?php

namespace App\Http\Controllers\Senior;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\StudentAssignment;
use App\Models\StudentChecklist;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentUniversityApplication;
use App\Services\LeadScoreService;
use App\Services\NotificationService;
use App\Services\PipelineProgressService;
use App\Support\FileUploadRules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeniorInstitutionController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function seniorEmail(Request $request): string
    {
        return strtolower((string) ($request->user()?->email ?? ''));
    }

    private function assignedStudentIds(Request $request): \Illuminate\Support\Collection
    {
        $email     = $this->seniorEmail($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        return StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('lower(senior_email) = ?', [$email])
            ->pluck('student_id')
            ->filter()->unique()->values();
    }

    // ── Kurumsal Belgeler ─────────────────────────────────────────────────────

    public function institutionDocuments(Request $request)
    {
        $studentIds = $this->assignedStudentIds($request);

        $q            = trim((string) $request->query('q', ''));
        $catFilter    = trim((string) $request->query('category', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $sidFilter    = trim((string) $request->query('student_id', ''));

        $records = StudentInstitutionDocument::query()
            ->whereIn('student_id', $studentIds)
            ->when($q !== '', fn ($w) => $w->where('document_type_label', 'like', "%{$q}%")
                ->orWhere('institution_name', 'like', "%{$q}%")
                ->orWhere('notes', 'like', "%{$q}%"))
            ->when($catFilter !== '', fn ($w) => $w->where('institution_category', $catFilter))
            ->when($statusFilter !== '', fn ($w) => $w->where('status', $statusFilter))
            ->when($sidFilter !== '', fn ($w) => $w->where('student_id', $sidFilter))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $nameMap = GuestApplication::query()
            ->whereIn('converted_student_id', $studentIds)
            ->get(['converted_student_id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($g) => [
                (string) $g->converted_student_id => trim("{$g->first_name} {$g->last_name}"),
            ]);

        $catalog = config('institution_document_catalog.categories', []);

        return view('senior.institution-documents', [
            'records'    => $records,
            'studentIds' => $studentIds,
            'nameMap'    => $nameMap,
            'catalog'    => $catalog,
            'filters'    => compact('q', 'catFilter', 'statusFilter', 'sidFilter'),
        ]);
    }

    public function institutionDocumentStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $studentIds = $this->assignedStudentIds($request)->all();
        $catalogKeys = array_keys(config('institution_document_catalog.categories', []));

        $data = $request->validate([
            'student_id'           => ['required', 'string', 'max:64', Rule::in($studentIds)],
            'institution_category' => ['required', 'string', Rule::in($catalogKeys)],
            'document_type_code'   => ['required', 'string', 'max:32'],
            'document_type_label'  => ['required', 'string', 'max:255'],
            'institution_name'     => ['nullable', 'string', 'max:255'],
            'received_date'        => ['nullable', 'date'],
            'status'               => ['required', 'in:expected,received,action_required,completed,archived'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'document_file'        => FileUploadRules::documentOptional(),
        ]);

        $data['added_by']   = $request->user()->id;
        $data['company_id'] = $request->user()->company_id ?? null;
        $data['file_id']    = null;
        unset($data['document_file']);

        if ($request->hasFile('document_file')) {
            $file   = $request->file('document_file');
            $stored = $file->store('institution-docs/' . date('Y-m'), 'public');
            $doc    = Document::create([
                'student_id'         => $data['student_id'],
                'original_file_name' => $file->getClientOriginalName(),
                'standard_file_name' => $file->getClientOriginalName(),
                'storage_path'       => $stored,
                'mime_type'          => $file->getMimeType(),
                'status'             => 'approved',
                'uploaded_by'        => $this->seniorEmail($request),
                'process_tags'       => ['institution_document'],
            ]);
            $data['file_id'] = $doc->id;
        }

        $instDoc = StudentInstitutionDocument::create($data);

        app(LeadScoreService::class)->recalculateForStudent($data['student_id']);
        app(PipelineProgressService::class)->advanceFromInstitutionDocument($instDoc);

        if ($data['document_type_code'] === 'VIS-ERTEIL') {
            app(\App\Services\SeniorAutomationService::class)->onEvent(
                'institution_document.VIS-ERTEIL',
                $data['student_id'],
                ['student_name' => $data['student_id']]
            );
        }

        return back()->with('status', 'Belge kaydedildi.');
    }

    public function institutionDocumentUpdate(Request $request, StudentInstitutionDocument $institutionDoc): \Illuminate\Http\RedirectResponse
    {
        abort_unless(in_array((string) $institutionDoc->student_id, $this->assignedStudentIds($request)->all(), true), 403);

        $data = $request->validate([
            'status'           => ['required', 'in:expected,received,action_required,completed,archived'],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'received_date'    => ['nullable', 'date'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $institutionDoc->update($data);

        return back()->with('status', 'Belge güncellendi.');
    }

    public function institutionDocumentDelete(Request $request, StudentInstitutionDocument $institutionDoc): \Illuminate\Http\RedirectResponse
    {
        abort_unless(in_array((string) $institutionDoc->student_id, $this->assignedStudentIds($request)->all(), true), 403);
        $institutionDoc->delete();
        return back()->with('status', 'Belge silindi.');
    }

    public function institutionDocumentToggleVisibility(Request $request, StudentInstitutionDocument $institutionDoc): \Illuminate\Http\RedirectResponse
    {
        abort_unless(in_array((string) $institutionDoc->student_id, $this->assignedStudentIds($request)->all(), true), 403);

        $data = $request->validate([
            'target' => ['required', 'in:student,dealer'],
            'value'  => ['required', 'boolean'],
        ]);

        $field   = $data['target'] === 'student' ? 'is_visible_to_student' : 'is_visible_to_dealer';
        $visible = (bool) $data['value'];

        $institutionDoc->update([
            $field            => $visible,
            'made_visible_at' => $visible ? now() : $institutionDoc->made_visible_at,
            'made_visible_by' => $visible ? $request->user()->id : $institutionDoc->made_visible_by,
        ]);

        if ($data['target'] === 'student' && $visible) {
            $this->notificationService->send([
                'channel'      => 'in_app',
                'category'     => 'institution_document_shared',
                'company_id'   => $request->user()->company_id ?? null,
                'student_id'   => $institutionDoc->student_id,
                'body'         => 'Yeni bir kurumsal belge paylaşıldı: ' . ($institutionDoc->document_type_label ?? ''),
                'variables'    => [
                    'document_label'       => $institutionDoc->document_type_label,
                    'institution_category' => $institutionDoc->institution_category,
                    'institution_name'     => $institutionDoc->institution_name,
                    'received_date'        => $institutionDoc->received_date?->format('Y-m-d'),
                ],
                'source_type'  => 'institution_document',
                'source_id'    => (string) $institutionDoc->id,
                'triggered_by' => (string) optional($request->user())->email,
            ]);
        }

        app(LeadScoreService::class)->recalculateForStudent((string) $institutionDoc->student_id);
        app(PipelineProgressService::class)->advanceFromInstitutionDocument($institutionDoc->fresh());

        $label = $data['target'] === 'student' ? 'Öğrenciye' : 'Dealer\'a';
        $msg   = $visible ? "{$label} görünür yapıldı." : "{$label} görünürlük kapatıldı.";

        return back()->with('status', $msg);
    }

    // ── Üniversite Başvuru Takibi ─────────────────────────────────────────────

    public function universityApplications(Request $r): \Illuminate\View\View
    {
        $user               = $r->user();
        $assignedStudentIds = StudentAssignment::where('senior_email', $user->email)->pluck('student_id');

        $query = StudentUniversityApplication::whereIn('student_id', $assignedStudentIds);

        if ($sid    = $r->query('student_id'))  { $query->where('student_id', $sid); }
        if ($status = $r->query('status'))       { $query->where('status', $status); }
        if ($degree = $r->query('degree_type'))  { $query->where('degree_type', $degree); }
        if ($q      = $r->query('q')) {
            $query->where(fn ($q2) => $q2->where('university_name', 'like', "%{$q}%")
                ->orWhere('department_name', 'like', "%{$q}%")
                ->orWhere('city', 'like', "%{$q}%"));
        }

        $applications = $query->orderBy('student_id')->orderBy('priority')->paginate(50)->withQueryString();
        $students     = GuestApplication::whereIn('tracking_token', $assignedStudentIds)
            ->orderBy('first_name')->get(['tracking_token', 'first_name', 'last_name']);
        $catalog = config('university_catalog', ['universities' => []]);

        return view('senior.university-applications', compact('applications', 'students', 'catalog'));
    }

    public function universityApplicationStore(Request $r): \Illuminate\Http\RedirectResponse
    {
        $user               = $r->user();
        $assignedStudentIds = StudentAssignment::where('senior_email', $user->email)->pluck('student_id')->toArray();

        $data = $r->validate([
            'student_id'         => ['required', 'string', Rule::in($assignedStudentIds)],
            'university_name'    => ['required', 'string', 'max:255'],
            'city'               => ['nullable', 'string', 'max:100'],
            'state'              => ['nullable', 'string', 'max:100'],
            'department_name'    => ['required', 'string', 'max:255'],
            'degree_type'        => ['required', Rule::in(['bachelor', 'master', 'phd', 'ausbildung', 'weiterbildung'])],
            'semester'           => ['nullable', 'string', 'max:20'],
            'application_portal' => ['nullable', Rule::in(['uni_assist', 'hochschulstart', 'direct', 'other'])],
            'application_number' => ['nullable', 'string', 'max:100'],
            'status'             => ['required', Rule::in(['planned', 'submitted', 'under_review', 'accepted', 'conditional_accepted', 'rejected', 'withdrawn'])],
            'priority'           => ['nullable', 'integer', 'min:1', 'max:99'],
            'deadline'           => ['nullable', 'date'],
            'submitted_at'       => ['nullable', 'date'],
            'result_at'          => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);

        $uniApp = StudentUniversityApplication::create(array_merge($data, [
            'company_id'            => $user->company_id ?? null,
            'added_by'              => $user->id,
            'is_visible_to_student' => true,
            'is_visible_to_dealer'  => false,
        ]));

        app(LeadScoreService::class)->recalculateForStudent($data['student_id']);
        app(PipelineProgressService::class)->advanceFromUniversityApplication($uniApp);

        return back()->with('status', 'Başvuru kaydedildi.');
    }

    public function universityApplicationUpdate(Request $r, StudentUniversityApplication $uniApp): \Illuminate\Http\RedirectResponse
    {
        $assignedStudentIds = StudentAssignment::where('senior_email', $r->user()->email)->pluck('student_id')->toArray();
        abort_unless(in_array($uniApp->student_id, $assignedStudentIds), 403);

        $data = $r->validate([
            'university_name'    => ['sometimes', 'string', 'max:255'],
            'city'               => ['nullable', 'string', 'max:100'],
            'state'              => ['nullable', 'string', 'max:100'],
            'department_name'    => ['sometimes', 'string', 'max:255'],
            'degree_type'        => ['sometimes', Rule::in(['bachelor', 'master', 'phd', 'ausbildung', 'weiterbildung'])],
            'semester'           => ['nullable', 'string', 'max:20'],
            'application_portal' => ['nullable', Rule::in(['uni_assist', 'hochschulstart', 'direct', 'other'])],
            'application_number' => ['nullable', 'string', 'max:100'],
            'status'             => ['sometimes', Rule::in(['planned', 'submitted', 'under_review', 'accepted', 'conditional_accepted', 'rejected', 'withdrawn'])],
            'priority'           => ['nullable', 'integer', 'min:1', 'max:99'],
            'deadline'           => ['nullable', 'date'],
            'submitted_at'       => ['nullable', 'date'],
            'result_at'          => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);

        $uniApp->update($data);
        app(LeadScoreService::class)->recalculateForStudent($uniApp->student_id);
        app(PipelineProgressService::class)->advanceFromUniversityApplication($uniApp->fresh());

        return back()->with('status', 'Başvuru güncellendi.');
    }

    public function universityApplicationDelete(Request $r, StudentUniversityApplication $uniApp): \Illuminate\Http\RedirectResponse
    {
        $assignedStudentIds = StudentAssignment::where('senior_email', $r->user()->email)->pluck('student_id')->toArray();
        abort_unless(in_array($uniApp->student_id, $assignedStudentIds), 403);
        $uniApp->delete();
        return back()->with('status', 'Başvuru silindi.');
    }

    public function universityApplicationToggleVisibility(Request $r, StudentUniversityApplication $uniApp): \Illuminate\Http\RedirectResponse
    {
        $assignedStudentIds = StudentAssignment::where('senior_email', $r->user()->email)->pluck('student_id')->toArray();
        abort_unless(in_array($uniApp->student_id, $assignedStudentIds), 403);

        $data    = $r->validate(['target' => ['required', Rule::in(['student', 'dealer'])], 'value' => ['required', 'boolean']]);
        $field   = $data['target'] === 'student' ? 'is_visible_to_student' : 'is_visible_to_dealer';
        $visible = (bool) $data['value'];
        $uniApp->update([$field => $visible]);

        $label = $data['target'] === 'student' ? 'Öğrenciye' : 'Dealer\'a';
        return back()->with('status', $visible ? "{$label} görünür yapıldı." : "{$label} görünürlük kapatıldı.");
    }

    // ── Student Checklist ─────────────────────────────────────────────────────

    public function storeChecklist(Request $request, string $studentId)
    {
        abort_if(!$this->assignedStudentIds($request)->contains($studentId), 403);

        $data = $request->validate([
            'label'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category'    => ['nullable', 'in:registration,document,visa,housing,language,general'],
            'due_date'    => ['nullable', 'date'],
        ]);

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : null;
        $maxOrder  = StudentChecklist::where('student_id', $studentId)->max('sort_order') ?? 0;

        StudentChecklist::create([
            'student_id'       => $studentId,
            'company_id'       => $companyId,
            'label'            => $data['label'],
            'description'      => $data['description'] ?? null,
            'category'         => $data['category'] ?? 'general',
            'due_date'         => $data['due_date'] ?? null,
            'sort_order'       => $maxOrder + 1,
            'created_by_email' => $this->seniorEmail($request),
        ]);

        return back()->with('status', 'Checklist öğesi eklendi.');
    }

    public function deleteChecklist(Request $request, string $studentId, StudentChecklist $checklist)
    {
        abort_if(!$this->assignedStudentIds($request)->contains($studentId), 403);
        abort_if($checklist->student_id !== $studentId, 403);
        $checklist->delete();
        return back()->with('status', 'Checklist öğesi silindi.');
    }
}
