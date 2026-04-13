<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\InternalNote;
use App\Models\Marketing\CmsContent;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAppointment;
use App\Models\StudentAssignment;
use App\Services\CvTemplateService;
use App\Services\DashboardKPIService;
use App\Services\DocumentBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SeniorDashboardController extends Controller
{
    public function __construct(
        private readonly CvTemplateService      $cvTemplateService,
        private readonly DashboardKPIService    $kpi,
        private readonly DocumentBuilderService $documentBuilderService,
    ) {
    }

    public function index(Request $request)
    {
        $user        = $request->user();
        $seniorEmail = strtolower((string) ($user->email ?? ''));
        $userId      = (int) $user->id;

        // ── Cached KPIs (DashboardKPIService — 5 dakika TTL) ─────────────────
        $kpis = $this->kpi->seniorKPIs($userId, $seniorEmail);
        $activeStudentCount   = $kpis['activeStudentCount'];
        $archivedStudentCount = $kpis['archivedStudentCount'];
        $pendingApprovalCount = $kpis['pendingApprovalCount'];
        $taskSummary          = $kpis['taskSummary'];
        $dmSummary            = $kpis['dmSummary'];

        // ── Non-cached: gerçek zamanlı son kayıt listeleri ───────────────────
        $base = StudentAssignment::query()
            ->whereRaw('lower(senior_email) = ?', [$seniorEmail]);

        $studentIds = (clone $base)->pluck('student_id')->filter()->unique()->values();

        // İsim haritası: student_id → "Ad Soyad"
        $guestMap = $studentIds->isEmpty() ? collect() : GuestApplication::query()
            ->whereIn('converted_student_id', $studentIds->all())
            ->get(['converted_student_id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($g) => [$g->converted_student_id => trim($g->first_name . ' ' . $g->last_name)]);

        $recentStudents = (clone $base)
            ->latest('updated_at')
            ->limit(10)
            ->get(['student_id', 'branch', 'dealer_id', 'risk_level', 'payment_status', 'is_archived', 'updated_at']);

        $recentOutcomes = $studentIds->isEmpty()
            ? collect()
            : ProcessOutcome::query()
                ->whereIn('student_id', $studentIds->all())
                ->latest()
                ->limit(8)
                ->get(['student_id', 'process_step', 'outcome_type', 'details_tr', 'created_at']);

        $recentNotes = $studentIds->isEmpty()
            ? collect()
            : InternalNote::query()
                ->whereIn('student_id', $studentIds->all())
                ->latest()
                ->limit(8)
                ->get(['student_id', 'category', 'priority', 'is_pinned', 'created_at']);

        $recentNotifications = $studentIds->isEmpty()
            ? collect()
            : NotificationDispatch::query()
                ->whereIn('student_id', $studentIds->all())
                ->latest()
                ->limit(8)
                ->get(['student_id', 'channel', 'category', 'status', 'queued_at', 'sent_at', 'failed_at']);

        $recentTasks = \App\Models\MarketingTask::query()
            ->where('assigned_user_id', $userId)
            ->latest()
            ->limit(8)
            ->get(['id', 'title', 'status', 'priority', 'due_date', 'updated_at']);

        // Bekleyen sözleşme talepleri — bu senior'a atanmış guest/student
        $pendingContracts = GuestApplication::query()
            ->whereRaw('lower(assigned_senior_email) = ?', [$seniorEmail])
            ->whereIn('contract_status', ['requested', 'signed_uploaded'])
            ->latest('contract_requested_at')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'contract_status', 'contract_requested_at']);

        $banners = CmsContent::query()
            ->where('status', 'published')
            ->where('category', 'senior_banner')
            ->where('is_featured', true)
            ->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('archived_at')->orWhere('archived_at', '>', now()))
            ->orderBy('featured_order')
            ->limit(5)
            ->get(['id', 'title_tr', 'summary_tr', 'cover_image_url', 'seo_canonical_url', 'slug']);

        // ── 1.1 Smart Command Center ──────────────────────────────────────────
        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();

        // Bugün atanan guest'ler (assigned_at TODAY AND assigned_senior_email = me)
        $todayAssignedGuests = GuestApplication::query()
            ->whereRaw('lower(assigned_senior_email) = ?', [$seniorEmail])
            ->whereBetween('assigned_at', [$todayStart, $todayEnd])
            ->latest('assigned_at')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'application_type', 'converted_to_student', 'converted_student_id', 'assigned_at']);

        // Bugün yüklenen & inceleme bekleyen belgeler (bu senior'ın öğrencilerine ait)
        $todayDocsForReview = $studentIds->isEmpty() ? collect() : Document::query()
            ->whereIn('student_id', $studentIds->all())
            ->where('status', 'uploaded')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'student_id', 'category_id', 'original_file_name', 'created_at', 'document_id']);

        $todayAppointments = $studentIds->isEmpty() ? collect() : StudentAppointment::query()
            ->whereIn('student_id', $studentIds->all())
            ->whereBetween('scheduled_at', [$todayStart, $todayEnd])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get(['id', 'student_id', 'scheduled_at', 'title', 'status', 'note']);

        $todayTasks = \App\Models\MarketingTask::query()
            ->where('assigned_user_id', $userId)
            ->whereDate('due_date', now()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderBy('priority', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'status', 'priority', 'due_date']);

        $pendingTickets = $studentIds->isEmpty() ? collect() : GuestTicket::query()
            ->whereHas('guestApplication', fn ($q) => $q->whereIn('converted_student_id', $studentIds->all()))
            ->whereIn('status', ['open', 'in_progress'])
            ->latest()
            ->limit(10)
            ->get(['id', 'subject', 'status', 'priority', 'created_at', 'guest_application_id']);

        $riskRadar = $studentIds->isEmpty() ? collect() : StudentAssignment::query()
            ->whereIn('student_id', $studentIds->all())
            ->whereIn('risk_level', ['high', 'critical'])
            ->get(['student_id', 'risk_level', 'payment_status', 'branch', 'updated_at']);

        // Critical actions: aggregate from multiple sources
        $criticalActions = collect();

        // 1. Overdue tasks
        $overdueTasks = \App\Models\MarketingTask::query()
            ->where('assigned_user_id', $userId)
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();
        if ($overdueTasks > 0) {
            $criticalActions->push(['type' => 'overdue_tasks', 'label' => 'Geciken Task', 'count' => $overdueTasks, 'url' => '/tasks', 'icon' => '⏰']);
        }

        // 2. Pending contracts
        $pendingContractCount = GuestApplication::query()
            ->whereRaw('lower(assigned_senior_email) = ?', [$seniorEmail])
            ->whereIn('contract_status', ['requested', 'signed_uploaded'])
            ->count();
        if ($pendingContractCount > 0) {
            $criticalActions->push(['type' => 'pending_contracts', 'label' => 'Bekleyen Sözleşme', 'count' => $pendingContractCount, 'url' => '/senior/contracts', 'icon' => '📄']);
        }

        // 3. Documents pending review
        $docsForReview = $studentIds->isEmpty() ? 0 : Document::query()
            ->whereIn('student_id', $studentIds->all())
            ->where('status', 'uploaded')
            ->count();
        if ($docsForReview > 0) {
            $criticalActions->push(['type' => 'docs_review', 'label' => 'İnceleme Bekleyen Belge', 'count' => $docsForReview, 'url' => '/senior/batch-review', 'icon' => '📋']);
        }

        // 4. Unanswered DMs (unread)
        if ((int) ($dmSummary['unread'] ?? 0) > 0) {
            $criticalActions->push(['type' => 'unread_dm', 'label' => 'Okunmamış Mesaj', 'count' => (int) $dmSummary['unread'], 'url' => '/im', 'icon' => '💬']);
        }

        // 5. Open tickets
        if ($pendingTickets->count() > 0) {
            $criticalActions->push(['type' => 'open_tickets', 'label' => 'Açık Ticket', 'count' => $pendingTickets->count(), 'url' => '/senior/tickets', 'icon' => '🎫']);
        }

        // Weekly performance
        $weekStart = now()->startOfWeek();
        $weeklyOutcomes = $studentIds->isEmpty() ? 0 : ProcessOutcome::query()
            ->whereIn('student_id', $studentIds->all())
            ->where('created_at', '>=', $weekStart)
            ->count();
        $weeklyDocsApproved = $studentIds->isEmpty() ? 0 : Document::query()
            ->whereIn('student_id', $studentIds->all())
            ->where('status', 'approved')
            ->where('approved_at', '>=', $weekStart)
            ->count();
        $weeklyPerformance = [
            'outcomes'      => $weeklyOutcomes,
            'docs_approved' => $weeklyDocsApproved,
            'tasks_done'    => \App\Models\MarketingTask::query()
                ->where('assigned_user_id', $userId)
                ->where('status', 'done')
                ->where('updated_at', '>=', $weekStart)
                ->count(),
        ];

        return view('senior.dashboard', [
            'activeStudentCount'   => $activeStudentCount,
            'archivedStudentCount' => $archivedStudentCount,
            'pendingApprovalCount' => $pendingApprovalCount,
            'guestMap'             => $guestMap,
            'recentStudents'       => $recentStudents,
            'recentOutcomes'       => $recentOutcomes,
            'recentNotes'          => $recentNotes,
            'recentNotifications'  => $recentNotifications,
            'taskSummary'          => $taskSummary,
            'recentTasks'          => $recentTasks,
            'pendingContracts'     => $pendingContracts,
            'dmSummary'            => $dmSummary,
            'banners'              => $banners,
            // 1.1 Smart Command Center
            'todayAssignedGuests'  => $todayAssignedGuests,
            'todayDocsForReview'   => $todayDocsForReview,
            'todayAppointments'    => $todayAppointments,
            'todayTasks'           => $todayTasks,
            'pendingTickets'       => $pendingTickets,
            'riskRadar'            => $riskRadar,
            'criticalActions'      => $criticalActions,
            'weeklyPerformance'    => $weeklyPerformance,
        ]);
    }

    public function bannerClick(int $id): \Illuminate\Http\JsonResponse
    {
        CmsContent::where('id', $id)
            ->where('category', 'senior_banner')
            ->increment('view_count');

        return response()->json(['ok' => true]);
    }

    public function documentBuilder(Request $request)
    {
        $user = $request->user();
        $seniorEmail = strtolower((string) ($user->email ?? ''));
        $studentIds = StudentAssignment::query()
            ->whereRaw('lower(senior_email) = ?', [$seniorEmail])
            ->pluck('student_id')
            ->filter()
            ->values();

        $guests = GuestApplication::query()
            ->whereIn('converted_student_id', $studentIds->all())
            ->where('converted_to_student', true)
            ->orderByDesc('id')
            ->get(['id', 'converted_student_id', 'first_name', 'last_name', 'email', 'registration_form_draft']);

        $selectedGuestId = (int) $request->query('guest_id', $guests->first()?->id ?? 0);
        $selectedGuest = $selectedGuestId > 0 ? $guests->firstWhere('id', $selectedGuestId) : null;
        $builderDraft = is_array($selectedGuest?->registration_form_draft) ? $selectedGuest->registration_form_draft : [];

        return view('senior.document-builder', [
            'students' => $guests,
            'builderDocuments' => $studentIds->isEmpty()
                ? collect()
                : Document::query()
                    ->whereIn('student_id', $studentIds->all())
                    ->latest()
                    ->limit(200)
                    ->get(),
            'builderDraft' => $builderDraft,
            'selectedGuestId' => $selectedGuestId,
            'documentBuilderBridge' => [
                'role' => 'senior',
                'students' => $guests->map(fn ($g) => [
                    'id' => $g->id,
                    'student_id' => $g->converted_student_id,
                    'name' => trim(($g->first_name ?? '').' '.($g->last_name ?? '')),
                    'email' => $g->email,
                ])->values(),
            ],
        ]);
    }

    public function generateDocumentBuilderFile(Request $request)
    {
        $data = $request->validate([
            'guest_application_id' => ['required', 'integer'],
            'document_type' => ['required', 'in:cv,motivation,reference'],
            'language' => ['required', 'in:tr,de,en'],
            'output_format' => ['nullable', 'in:docx,md'],
            'title' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'ai_mode' => ['nullable', 'in:template,ai_assist'],
            'motivation_text' => ['nullable', 'string', 'max:10000'],
            'target_program' => ['nullable', 'string', 'max:180'],
            'reference_teacher_contact' => ['nullable', 'string', 'max:180'],
        ]);

        $user = $request->user();
        $seniorEmail = strtolower((string) ($user->email ?? ''));

        $guest = GuestApplication::query()
            ->where('id', (int) $data['guest_application_id'])
            ->where('converted_to_student', true)
            ->firstOrFail();

        $allowed = StudentAssignment::query()
            ->where('student_id', (string) ($guest->converted_student_id ?? ''))
            ->whereRaw('lower(senior_email) = ?', [$seniorEmail])
            ->exists();
        abort_if(!$allowed, 403, 'Bu ogrenci size atali degil.');

        // Payload'daki override alanları draft'a merge edilir (DocumentBuilderService draft'tan okur)
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        foreach (['target_program', 'reference_teacher_contact', 'motivation_text'] as $field) {
            if (isset($data[$field]) && (string) $data[$field] !== '') {
                $draft[$field] = $data[$field];
            }
        }

        $docType    = (string) ($data['document_type'] ?? 'cv');
        $language   = (string) ($data['language'] ?? 'de');
        $extraNotes = (string) ($data['notes'] ?? '');
        $aiMode     = (string) ($data['ai_mode'] ?? 'template');

        // resolveBuilderCategory / buildDocumentText / applyAiAssist / composeReviewNote
        // → DocumentBuilderService'e devredildi
        $built          = $this->documentBuilderService->buildDocumentText($guest, $draft, $docType, $language, $extraNotes, $aiMode);
        $aiAssistResult = null;
        if ($aiMode === 'ai_assist') {
            $aiAssistResult = $this->documentBuilderService->applyAiAssist($docType, $built, $guest, $draft, $extraNotes);
            $built          = $aiAssistResult['built'];
        }

        $ownerId      = trim((string) ($guest->converted_student_id ?: ('GST-'.$guest->id)));
        $outputFormat = (string) ($data['output_format'] ?? 'docx');
        $extension    = $outputFormat === 'docx' ? 'docx' : 'md';
        $categoryCode = strtoupper($docType === 'cv' ? 'DOC-CV__' : ($docType === 'motivation' ? 'DOC-MOTV' : 'DOC-REF'));
        $fileName     = app(\App\Services\DocumentNamingService::class)->buildStandardFileName(
            $ownerId,
            $categoryCode,
            (string) ($guest->first_name ?? ''),
            (string) ($guest->last_name ?? ''),
            $extension,
        );
        $path         = "student-builder/{$guest->id}/{$fileName}";
        if ($outputFormat === 'docx') {
            Storage::disk('public')->put($path, $this->cvTemplateService->buildDocxFromText((string) $built['content']));
        } else {
            Storage::disk('public')->put($path, (string) $built['content']);
        }

        $category = $this->documentBuilderService->resolveBuilderCategory($docType);
        $row      = Document::query()->create([
            'student_id'          => $ownerId,
            'category_id'         => (int) $category->id,
            'process_tags'        => ['student_document_builder', $docType, $language, 'senior_generate'],
            'original_file_name'  => ($data['title'] ?? '') !== '' ? (string) $data['title'] . '.' . $extension : 'Builder.' . $extension,
            'standard_file_name'  => $fileName,
            'storage_path'        => $path,
            'mime_type'           => $outputFormat === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'text/markdown',
            'status'              => 'generated',
            'uploaded_by'         => (string) ($user->email ?? 'senior'),
            'review_note'         => $this->documentBuilderService->composeReviewNote(trim((string) ($data['notes'] ?? '')), $aiAssistResult),
        ]);
        $row->forceFill([
            'document_id' => 'DOC-STB-' . str_pad((string) $row->id, 7, '0', STR_PAD_LEFT),
        ])->save();

        return redirect('/senior/document-builder?guest_id=' . (int) $guest->id)->with('status', 'Belge olusturuldu.');
    }

    // resolveBuilderCategory / buildDocumentText / applyAiAssist / composeReviewNote / translateLineTrToDe / extractStructuredAnswers
    // → DocumentBuilderService'e taşındı (app/Services/DocumentBuilderService.php)

    public function downloadDocument(Request $request, Document $document)
    {
        $user = $request->user();
        $seniorEmail = strtolower((string) ($user->email ?? ''));
        $studentIds = StudentAssignment::query()
            ->whereRaw('lower(senior_email) = ?', [$seniorEmail])
            ->pluck('student_id')
            ->filter()
            ->values();

        abort_if($studentIds->isEmpty(), 403, 'Bu belgeye erisim yetkiniz yok.');
        abort_if(!$studentIds->contains((string) $document->student_id), 403, 'Bu belgeye erisim yetkiniz yok.');

        $path = trim((string) ($document->storage_path ?? ''));
        abort_if($path === '', 404, 'Belge dosya yolu bulunamadi.');
        abort_unless(Storage::disk('local')->exists($path), 404, 'Belge dosyasi bulunamadi.');

        $downloadName = trim((string) ($document->standard_file_name ?: $document->original_file_name ?: basename($path)));
        if ($downloadName === '') {
            $downloadName = basename($path);
        }

        return Storage::disk('local')->download($path, $downloadName);
    }

    // ── K2 Document Builder Preview ───────────────────────────────────────────
    public function previewDocumentBuilder(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $studentId = trim((string) $request->input('student_id', ''));
        $guest = null;

        if ($studentId !== '') {
            $guest = GuestApplication::query()
                ->whereNotNull('converted_student_id')
                ->where('converted_student_id', $studentId)
                ->first();
        }

        if (!$guest) {
            return response()->json(['error' => 'Öğrenci profili bulunamadı.'], 404);
        }

        $draft   = (array) $request->input('draft', []);
        $docType = trim((string) $request->input('doc_type', 'motivation'));
        $lang    = trim((string) $request->input('language', 'de'));
        $notes   = trim((string) $request->input('extra_notes', ''));

        $result  = $this->documentBuilderService->preview($guest, $draft, $docType, $lang, $notes);
        $quality = $this->documentBuilderService->qualityScore((string) ($result['content'] ?? ''), $docType);

        return response()->json(['ok' => true, 'preview' => $result, 'quality' => $quality]);
    }
}
