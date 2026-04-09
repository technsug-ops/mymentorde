<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DmMessage;
use App\Models\DmThread;
use App\Models\GuestRequiredDocument;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\Marketing\CmsContent;
use App\Models\MarketingTask;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentAchievement;
use App\Models\StudentChecklist;
use App\Models\StudentMaterialRead;
use App\Models\StudentOnboardingStep;
use App\Services\StudentAchievementService;
use App\Services\StudentGuestResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\SchemaCache;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        $guestApplication = app(StudentGuestResolver::class)->resolveForUser($user);

        if ($studentId === '') {
            $studentId = trim((string) ($guestApplication?->converted_student_id ?? ''));
        }

        if ($studentId === '') {
            return view('student.dashboard', [
                'user'                => $user,
                'studentId'           => null,
                'guestApplication'    => $guestApplication,
                'assignment'          => null,
                'outcomes'            => collect(),
                'documents'           => collect(),
                'progressSteps'       => [],
                'docSummary'          => ['total' => 0, 'approved' => 0, 'uploaded' => 0, 'rejected' => 0],
                'requiredChecklist'   => collect(),
                'notifications'       => collect(),
                'notificationSummary' => ['total' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0],
                'onboardingTasks'     => collect(),
                'dmThread'            => null,
                'dmUnread'            => 0,
                'greeting'            => 'Hoş geldiniz!',
                'greetingSub'         => '',
                'countdowns'          => collect(),
                'alerts'              => collect(),
                'weekActivity'        => ['documents_uploaded' => 0, 'messages_received' => 0, 'outcomes_added' => 0, 'materials_read' => 0],
                'checklistSummary'    => ['total' => 0, 'done' => 0, 'percent' => 0, 'overdue' => 0],
                'checklistItems'      => collect(),
                'achievements'        => [],
                'achievementPoints'   => 0,
                'onboardingPending'   => false,
            ]);
        }

        if (!$guestApplication && $studentId !== '') {
            $guestApplication = GuestApplication::query()
                ->where('converted_student_id', $studentId)
                ->where('converted_to_student', true)
                ->latest('id')
                ->first();
        }

        $assignment = StudentAssignment::query()
            ->where('student_id', $studentId)
            ->first();

        $outcomes = ProcessOutcome::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(20)
            ->with('document:id,original_file_name,student_id')
            ->get(['id', 'process_step', 'outcome_type', 'details_tr', 'created_at', 'is_visible_to_student', 'document_id']);

        $documentOwnerIds = $this->documentOwnerIds($studentId, $guestApplication);
        $documents = Document::query()
            ->whereIn('student_id', $documentOwnerIds)
            ->with('category:id,code')
            ->latest()
            ->limit(30)
            ->get(['id', 'student_id', 'category_id', 'document_id', 'original_file_name', 'status', 'review_note', 'updated_at']);

        $notifications = NotificationDispatch::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(20)
            ->get(['id', 'channel', 'category', 'status', 'subject', 'queued_at', 'sent_at', 'failed_at', 'fail_reason']);

        $docSummary = [
            'total' => (int) $documents->count(),
            'uploaded' => (int) $documents->where('status', 'uploaded')->count(),
            'approved' => (int) $documents->where('status', 'approved')->count(),
            'rejected' => (int) $documents->where('status', 'rejected')->count(),
        ];

        $requiredDocs = GuestRequiredDocument::query()
            ->where('is_active', true)
            ->where('application_type', (string) ($guestApplication?->application_type ?: 'bachelor'))
            ->when(
                SchemaCache::hasColumn('guest_required_documents', 'stage'),
                fn ($q) => $q->where('stage', 'student')
            )
            ->orderBy('sort_order')
            ->limit(30)
            ->get(['category_code', 'name', 'is_required']);

        $uploadedCodes = $documents->map(fn ($d) => strtoupper(trim((string) ($d->category->code ?? ''))))
            ->filter()->values()->all();

        // Kategori → top_category eşlemesi
        $catCodes = $requiredDocs->pluck('category_code')->filter()->unique()->values()->all();
        $topCatMap = [];
        if ($catCodes && Schema::hasTable('document_categories') && Schema::hasColumn('document_categories', 'top_category_code')) {
            $topCatMap = DB::table('document_categories')
                ->whereIn('code', $catCodes)
                ->pluck('top_category_code', 'code')
                ->all();
        }

        $requiredChecklist = $requiredDocs->map(function ($row) use ($uploadedCodes, $topCatMap) {
            $code = strtoupper(trim((string) ($row->category_code ?? '')));
            return [
                'code'         => $code,
                'name'         => (string) ($row->name ?? $code),
                'done'         => in_array($code, $uploadedCodes, true),
                'is_required'  => (bool) ($row->is_required ?? false),
                'top_category' => (string) ($topCatMap[$code] ?? 'diger_dokumanlar'),
            ];
        })->values();

        $notificationSummary = [
            'total' => (int) $notifications->count(),
            'queued' => (int) $notifications->where('status', 'queued')->count(),
            'sent' => (int) $notifications->where('status', 'sent')->count(),
            'failed' => (int) $notifications->where('status', 'failed')->count(),
        ];

        // ── 6-Step Funnel Progress ────────────────────────────────────────
        $contractStatus = (string) ($guestApplication?->contract_status ?? 'not_requested');
        $contractDone   = $contractStatus === 'approved';
        $docsDone       = (bool) ($guestApplication?->docs_ready ?? false);
        $uniOutcomes    = $outcomes->whereIn('process_step', ['application_prep', 'uni_assist']);
        $uniDone        = $uniOutcomes->where('outcome_type', 'acceptance')->isNotEmpty()
                       || $uniOutcomes->where('outcome_type', 'conditional_acceptance')->isNotEmpty();
        $visaOutcomes   = $outcomes->where('process_step', 'visa_application');
        $visaDone       = $visaOutcomes->where('outcome_type', 'acceptance')->isNotEmpty();
        $abroadOutcomes = $outcomes->whereIn('process_step', ['residence', 'official_services']);
        $abroadDone     = $abroadOutcomes->isNotEmpty() && $visaDone;

        $funnelSteps = [
            ['key' => 'application', 'label' => 'Basvuru',    'done' => true],
            ['key' => 'contract',    'label' => 'Sozlesme',   'done' => $contractDone],
            ['key' => 'documents',   'label' => 'Belgeler',   'done' => $docsDone],
            ['key' => 'uni_assist',  'label' => 'Uni-Assist', 'done' => $uniDone],
            ['key' => 'visa',        'label' => 'Vize',       'done' => $visaDone],
            ['key' => 'abroad',      'label' => 'Almanya',    'done' => $abroadDone],
        ];

        // Aktif adimi bul (ilk tamamlanmamis adim)
        $currentStageIdx = collect($funnelSteps)->search(fn ($s) => !$s['done']);
        if ($currentStageIdx === false) {
            $currentStageIdx = count($funnelSteps) - 1;
        }
        $currentStage = $funnelSteps[$currentStageIdx]['key'] ?? 'application';
        $funnelPct = (int) round(collect($funnelSteps)->where('done', true)->count() / max(1, count($funnelSteps)) * 100);

        // Eski progressSteps uyumu
        $progressSteps = $funnelSteps;

        $outcomeByStep = $outcomes
            ->groupBy(fn ($o) => (string) ($o->process_step ?? 'unknown'))
            ->map(fn ($rows, $step) => [
                'step' => $step,
                'total' => (int) $rows->count(),
                'visible' => (int) $rows->where('is_visible_to_student', true)->count(),
                'last_at' => optional($rows->first())->created_at,
            ])
            ->values();

        $onboardingTasks = MarketingTask::query()
            ->where('source_type', 'student_onboarding_auto')
            ->where('source_id', 'like', addcslashes($studentId, '%_') . ':%')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'title', 'status', 'priority', 'due_date', 'completed_at']);

        $dmThread = DmThread::query()
            ->where('thread_type', 'student')
            ->where('student_id', $studentId)
            ->latest('id')
            ->first(['id', 'status', 'last_message_at']);
        $dmUnread = 0;
        if ($dmThread) {
            $dmUnread = (int) DmMessage::query()
                ->where('thread_id', (int) $dmThread->id)
                ->where('is_read_by_participant', false)
                ->count();
        }

        // ── 1.1 Akıllı Dashboard ─────────────────────────────────────────────

        // Kişiselleştirilmiş selamlama
        $hour     = (int) now()->format('H');
        $greeting = match(true) {
            $hour < 12 => 'Günaydın',
            $hour < 18 => 'İyi günler',
            default    => 'İyi akşamlar',
        };
        $firstName    = trim((string) ($guestApplication?->first_name ?? $user->name ?? ''));
        $todayTaskCnt = MarketingTask::query()
            ->where('assigned_user_id', (int) $user->id)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereDate('due_date', today())
            ->count();

        // Countdown widget'ları — yaklaşan deadline'lar
        $countdowns = collect();
        if ($studentId !== '') {
            $countdowns = ProcessOutcome::query()
                ->where('student_id', $studentId)
                ->where('is_visible_to_student', true)
                ->whereNotNull('deadline')
                ->where('deadline', '>', now())
                ->where('deadline', '<', now()->addDays(60))
                ->orderBy('deadline')
                ->limit(5)
                ->get(['process_step', 'university', 'program', 'deadline', 'outcome_type'])
                ->map(function ($o) {
                    $daysLeft = max(0, (int) now()->startOfDay()->diffInDays($o->deadline->startOfDay(), false));
                    return [
                        'label'    => trim(($o->university ?: '') . ' ' . ($o->program ?: '')) ?: $o->process_step,
                        'deadline' => $o->deadline->format('d.m.Y'),
                        'days_left'=> $daysLeft,
                        'urgency'  => $daysLeft <= 7 ? 'urgent' : ($daysLeft <= 14 ? 'warning' : 'normal'),
                    ];
                });
        }

        // Kritik uyarı kartları
        $alerts = collect();
        if ($guestApplication) {
            $draft = is_array($guestApplication->registration_form_draft) ? $guestApplication->registration_form_draft : [];

            // 1. Pasaport bitiş uyarısı
            $passportExpiry = $draft['passport_expiry_date'] ?? null;
            if ($passportExpiry && Carbon::parse($passportExpiry)->lt(now()->addMonths(3))) {
                $alerts->push(['type' => 'danger', 'icon' => '🛂', 'message' => 'Pasaportunuzun bitmesine 3 aydan az kaldı! Yenileme işlemini başlatın.', 'action_url' => '/student/registration/documents', 'action_text' => 'Belgelerime Git']);
            }

            // 2. Reddedilen belgeler
            $rejectedDocs = (int) $documents->where('status', 'rejected')->count();
            if ($rejectedDocs > 0) {
                $alerts->push(['type' => 'warning', 'icon' => '📄', 'message' => "{$rejectedDocs} belgeniz reddedildi. Lütfen düzeltip yeniden yükleyin.", 'action_url' => '/student/registration/documents', 'action_text' => 'Belgeleri Gör']);
            }

            // 3. Eksik zorunlu belgeler
            $missingDocs = (int) $requiredChecklist->where('is_required', true)->where('done', false)->count();
            if ($missingDocs > 0) {
                $alerts->push(['type' => 'warning', 'icon' => '📋', 'message' => "{$missingDocs} zorunlu belgeniz eksik.", 'action_url' => '/student/registration/documents', 'action_text' => 'Yükle']);
            }

            // 4. Yanıt bekleyen biletler
            $waitingTickets = (int) GuestTicket::where('guest_application_id', $guestApplication->id)->where('status', 'waiting_response')->count();
            if ($waitingTickets > 0) {
                $alerts->push(['type' => 'info', 'icon' => '💬', 'message' => "{$waitingTickets} biletinizde yanıtınız bekleniyor.", 'action_url' => '/student/tickets', 'action_text' => 'Yanıtla']);
            }
        }

        // 5. Okunmamış DM
        if ($dmUnread > 0) {
            $alerts->push(['type' => 'info', 'icon' => '✉️', 'message' => "{$dmUnread} okunmamış mesajınız var.", 'action_url' => '/student/messages', 'action_text' => 'Mesajlara Git']);
        }

        // Haftalık aktivite özeti
        $sevenDaysAgo = now()->subDays(7);
        $weekActivity = [
            'documents_uploaded' => (int) Document::whereIn('student_id', $documentOwnerIds)
                ->where('created_at', '>=', $sevenDaysAgo)->count(),
            'messages_received'  => $dmThread
                ? (int) DmMessage::where('thread_id', (int) $dmThread->id)
                    ->where('sender_role', '!=', 'student')
                    ->where('created_at', '>=', $sevenDaysAgo)->count()
                : 0,
            'outcomes_added'     => $studentId !== ''
                ? (int) ProcessOutcome::where('student_id', $studentId)
                    ->where('is_visible_to_student', true)
                    ->where('created_at', '>=', $sevenDaysAgo)->count()
                : 0,
            'materials_read'     => $studentId !== ''
                ? (int) StudentMaterialRead::where('student_id', $studentId)
                    ->where('created_at', '>=', $sevenDaysAgo)->count()
                : 0,
        ];

        // Checklist özeti (dashboard widget)
        $checklistItems = $studentId !== ''
            ? StudentChecklist::where('student_id', $studentId)->orderBy('sort_order')->get()
            : collect();
        $checklistSummary = [
            'total'   => $checklistItems->count(),
            'done'    => $checklistItems->where('is_done', true)->count(),
            'percent' => $checklistItems->count() > 0
                ? (int) round($checklistItems->where('is_done', true)->count() / $checklistItems->count() * 100)
                : 0,
            'overdue' => $checklistItems->where('is_done', false)->filter(fn ($c) => $c->due_date && $c->due_date->lt(today()))->count(),
        ];

        // ── 2.5 Rozet Sistemi ────────────────────────────────────────────────────
        $achievementService = app(StudentAchievementService::class);
        $companyId          = (int) (app()->bound('current_company_id') ? app('current_company_id') : 1);
        $achievementService->checkAndAward($studentId, (string) $companyId);
        $achievements      = $achievementService->getEarnedBadges($studentId);
        $achievementPoints = $achievementService->totalPoints($studentId);

        // ── 2.1 Onboarding Prompt + Steps ───────────────────────────────────
        $onboardingPending = false;
        $onboardingSteps   = [];
        if ($studentId !== '' && SchemaCache::hasTable('student_onboarding_steps')) {
            $doneCount = StudentOnboardingStep::where('student_id', $studentId)
                ->where(fn ($q) => $q->whereNotNull('completed_at')->orWhereNotNull('skipped_at'))
                ->count();
            $onboardingPending = $doneCount === 0;

            // Modal için adım listesi
            foreach (StudentOnboardingStep::STEPS as $code) {
                $record = StudentOnboardingStep::where('student_id', $studentId)
                    ->where('step_code', $code)->first();
                $done   = $record?->isDone() ?? false;
                $onboardingSteps[] = [
                    'code'    => $code,
                    'label'   => StudentOnboardingStep::STEP_LABELS[$code] ?? $code,
                    'desc'    => StudentOnboardingStep::STEP_DESCS[$code] ?? '',
                    'done'    => $done,
                    'skipped' => $record !== null && $record->skipped_at !== null,
                ];
            }
        }

        $applicationType = (string) ($guestApplication?->application_type ?? '');
        $banners = CmsContent::query()
            ->where('status', 'published')
            ->where('category', 'student_banner')
            ->where('is_featured', true)
            ->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('archived_at')->orWhere('archived_at', '>', now()))
            ->where(function ($q) use ($applicationType) {
                $q->whereNull('target_student_types')
                  ->orWhereJsonContains('target_student_types', $applicationType);
            })
            ->orderBy('featured_order')
            ->limit(5)
            ->get(['id', 'title_tr', 'summary_tr', 'cover_image_url', 'seo_canonical_url', 'slug']);

        return view('student.dashboard', [
            'user' => $user,
            'studentId' => $studentId,
            'guestApplication' => $guestApplication,
            'assignment' => $assignment,
            'outcomes' => $outcomes,
            'documents' => $documents,
            'progressSteps' => $progressSteps,
            'funnelSteps'  => $funnelSteps,
            'funnelPct'    => $funnelPct,
            'currentStage' => $currentStage,
            'docSummary' => $docSummary,
            'requiredChecklist' => $requiredChecklist,
            'notifications' => $notifications,
            'notificationSummary' => $notificationSummary,
            'outcomeByStep' => $outcomeByStep,
            'onboardingTasks' => $onboardingTasks,
            'dmThread'         => $dmThread,
            'dmUnread'         => $dmUnread,
            'banners'          => $banners,
            'greeting'         => "{$greeting}" . ($firstName !== '' ? ", {$firstName}!" : '!'),
            'greetingSub'      => $todayTaskCnt > 0 ? "Bugün {$todayTaskCnt} görevin var." : 'Bugün bekleyen görevin yok.',
            'countdowns'       => $countdowns,
            'alerts'           => $alerts,
            'weekActivity'     => $weekActivity,
            'checklistSummary'   => $checklistSummary,
            'checklistItems'     => $checklistItems,
            'achievements'       => $achievements,
            'achievementPoints'  => $achievementPoints,
            'onboardingPending'  => $onboardingPending,
            'onboardingSteps'    => $onboardingSteps,
        ]);
    }

    public function bannerClick(int $id): \Illuminate\Http\JsonResponse
    {
        CmsContent::where('id', $id)
            ->where('category', 'student_banner')
            ->increment('view_count');

        return response()->json(['ok' => true]);
    }

    private function documentOwnerIds(string $studentId, ?GuestApplication $guestApplication): Collection
    {
        $ids = collect([$studentId])->filter(fn ($v) => trim((string) $v) !== '');
        if ($guestApplication) {
            $ids->push('GST-' . str_pad((string) $guestApplication->id, 8, '0', STR_PAD_LEFT));
        }

        return $ids->map(fn ($v) => trim((string) $v))->filter()->unique()->values();
    }
}
