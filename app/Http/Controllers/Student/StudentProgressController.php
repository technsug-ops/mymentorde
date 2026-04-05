<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentPortalTrait;
use App\Models\ProcessOutcome;
use App\Models\StudentAccommodation;
use App\Models\StudentAppointment;
use App\Models\StudentChecklist;
use App\Models\StudentFeedback;
use App\Models\StudentInstitutionDocument;
use App\Models\StudentOnboardingStep;
use App\Models\StudentUniversityApplication;
use App\Models\StudentVisaApplication;
use App\Services\StudentGuestResolver;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    use StudentPortalTrait;

    public function processTracking(Request $request)
    {
        $base      = $this->baseData($request, 'process_tracking', 'Surec Takibi', '6 ana surecin adim bazli ilerleme takibi.');
        $studentId = (string) ($base['studentId'] ?? '');
        $rows      = collect();

        if ($studentId !== '') {
            $rows = ProcessOutcome::query()
                ->where('student_id', $studentId)
                ->where('is_visible_to_student', true)
                ->orderByDesc('id')
                ->limit(150)
                ->with('document:id,original_file_name,standard_file_name,student_id,storage_path')
                ->get(['id', 'process_step', 'outcome_type', 'university', 'program', 'details_tr', 'deadline', 'document_id', 'created_at']);
        }

        $map = [
            'application_prep'  => 'Başvuru Hazırlık',
            'uni_assist'        => 'Uni Assist',
            'visa_application'  => 'Vize Başvurusu',
            'language_course'   => 'Dil Kursu',
            'residence'         => 'İkamet',
            'official_services' => 'Resmi Hizmetler',
        ];

        $summary = collect($map)->map(function ($label, $step) use ($rows) {
            $subset = $rows->where('process_step', $step);
            return [
                'step'  => $step,
                'label' => $label,
                'count' => (int) $subset->count(),
                'last'  => optional($subset->first())->created_at,
            ];
        })->values();

        $stepOrder        = ['application_prep', 'uni_assist', 'visa_application', 'language_course', 'residence', 'official_services'];
        $activeStep       = $rows->isNotEmpty() ? (string) $rows->first()->process_step : 'application_prep';
        $activeIdx        = array_search($activeStep, $stepOrder, true);
        $nextExpectedStep = ($activeIdx !== false && $activeIdx < count($stepOrder) - 1)
            ? ['step' => $stepOrder[$activeIdx + 1], 'label' => $map[$stepOrder[$activeIdx + 1]] ?? '']
            : null;

        $timeline = $rows->map(fn (ProcessOutcome $o) => [
            'id'            => $o->id,
            'step'          => $o->process_step,
            'step_label'    => $map[$o->process_step] ?? $o->process_step,
            'outcome'       => $o->outcome_type,
            'outcome_label' => match ($o->outcome_type) {
                'acceptance'             => 'Kabul',
                'conditional_acceptance' => 'Şartlı Kabul',
                'rejection'              => 'Red',
                'correction_request'     => 'Düzeltme Talebi',
                'waitlist'               => 'Bekleme Listesi',
                default                  => $o->outcome_type,
            },
            'university'    => $o->university,
            'program'       => $o->program,
            'details'       => $o->details_tr,
            'deadline'      => $o->deadline?->format('d.m.Y'),
            'date'          => $o->created_at->format('d.m.Y H:i'),
            'relative'      => $o->created_at->diffForHumans(),
            'has_document'  => $o->document_id !== null,
            'document_name' => $o->document?->original_file_name,
            'color'         => match ($o->outcome_type) {
                'acceptance'             => 'ok',
                'conditional_acceptance' => 'info',
                'rejection'              => 'danger',
                'correction_request'     => 'warn',
                default                  => '',
            },
            'icon'          => match ($o->outcome_type) {
                'acceptance'             => '✅',
                'conditional_acceptance' => '🔵',
                'rejection'              => '❌',
                'correction_request'     => '🔄',
                'waitlist'               => '⏳',
                default                  => '📌',
            },
        ]);

        return view('student.process-tracking', array_merge($base, [
            'outcomes'         => $rows,
            'processSummary'   => $summary,
            'timeline'         => $timeline,
            'activeStep'       => $activeStep,
            'nextExpectedStep' => $nextExpectedStep,
        ]));
    }

    public function checklist(Request $request)
    {
        $base      = $this->baseData($request, 'checklist', 'Yapılacaklar Listesi', 'Danışmanınız tarafından oluşturulan kişisel görev listeniz.');
        $studentId = (string) ($base['studentId'] ?? '');

        $items = $studentId !== ''
            ? StudentChecklist::where('student_id', $studentId)->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        return view('student.checklist', array_merge($base, [
            'checklistItems'   => $items,
            'checklistSummary' => [
                'total'   => $items->count(),
                'done'    => $items->where('is_done', true)->count(),
                'percent' => $items->count() > 0
                    ? (int) round($items->where('is_done', true)->count() / $items->count() * 100)
                    : 0,
                'overdue' => $items->where('is_done', false)->filter(fn ($c) => $c->due_date && $c->due_date->lt(today()))->count(),
            ],
            'categories'       => StudentChecklist::CATEGORIES,
        ]));
    }

    public function onboarding(Request $request)
    {
        $base      = $this->baseData($request, 'onboarding', 'Hoş Geldiniz!', 'Başlamak için birkaç adımı tamamlayın.');
        $studentId = (string) ($base['studentId'] ?? '');
        $steps     = [];
        $allDone   = true;

        foreach (StudentOnboardingStep::STEPS as $code) {
            $record = StudentOnboardingStep::where('student_id', $studentId)
                ->where('step_code', $code)
                ->first();
            $done = $record?->isDone() ?? false;
            if (!$done) {
                $allDone = false;
            }
            $steps[] = [
                'code'    => $code,
                'label'   => StudentOnboardingStep::STEP_LABELS[$code] ?? $code,
                'desc'    => StudentOnboardingStep::STEP_DESCS[$code] ?? '',
                'done'    => $done,
                'skipped' => $record !== null && $record->skipped_at !== null,
            ];
        }

        if ($allDone && $studentId !== '') {
            return redirect('/student/dashboard')->with('success', 'Onboarding tamamlandı!');
        }

        return view('student.onboarding', array_merge($base, [
            'steps'   => $steps,
            'allDone' => $allDone,
        ]));
    }

    public function calendar(Request $request)
    {
        $base      = $this->baseData($request, 'calendar', 'Takvimim', 'Randevular, son tarihler ve görevler.');
        $studentId = (string) ($base['studentId'] ?? '');
        $guest     = $base['guestApplication'] ?? null;
        if ($studentId === '' && $guest) {
            $studentId = (string) ($guest->converted_student_id ?? '');
        }

        $events = collect();

        if ($studentId !== '') {
            StudentAppointment::where('student_id', $studentId)
                ->whereNotNull('scheduled_at')
                ->whereIn('status', ['pending', 'confirmed'])
                ->limit(50)
                ->get(['id', 'scheduled_at', 'duration_minutes', 'topic', 'status', 'meeting_url'])
                ->each(function ($a) use (&$events) {
                    $end = $a->scheduled_at->copy()->addMinutes((int) ($a->duration_minutes ?? 45));
                    $events->push([
                        'id'    => 'apt-' . $a->id,
                        'title' => '📅 ' . (($a->topic ?? '') ?: 'Randevu'),
                        'start' => $a->scheduled_at->toIso8601String(),
                        'end'   => $end->toIso8601String(),
                        'color' => $a->status === 'confirmed' ? '#22c55e' : '#f59e0b',
                        'url'   => $a->meeting_url ?: '/student/appointments',
                    ]);
                });

            ProcessOutcome::where('student_id', $studentId)
                ->where('is_visible_to_student', true)
                ->whereNotNull('deadline')
                ->limit(30)
                ->get(['id', 'process_step', 'university', 'program', 'deadline'])
                ->each(function ($o) use (&$events) {
                    $label = trim(($o->university ?: '') . ' ' . ($o->program ?: '')) ?: ($o->process_step ?? 'Deadline');
                    $events->push([
                        'id'      => 'dl-' . $o->id,
                        'title'   => '⏰ ' . $label,
                        'start'   => $o->deadline->toDateString(),
                        'allDay'  => true,
                        'color'   => '#ef4444',
                        'url'     => '/student/process-tracking',
                        'display' => 'background',
                    ]);
                });

            StudentChecklist::where('student_id', $studentId)
                ->where('is_done', false)
                ->whereNotNull('due_date')
                ->limit(20)
                ->get(['id', 'label', 'due_date'])
                ->each(function ($c) use (&$events) {
                    $events->push([
                        'id'    => 'cl-' . $c->id,
                        'title' => '✅ ' . $c->label,
                        'start' => $c->due_date->toDateString(),
                        'allDay' => true,
                        'color' => '#6366f1',
                        'url'   => '/student/checklist',
                    ]);
                });
        }

        return view('student.calendar', array_merge($base, [
            'initialEvents' => $events->values(),
            'studentId'     => $studentId,
        ]));
    }

    public function feedback(Request $request)
    {
        $base      = $this->baseData($request, 'feedback', 'Geri Bildirim', 'Deneyimlerinizi ve memnuniyetinizi paylaşın.');
        $studentId = (string) ($base['studentId'] ?? '');
        $companyId = (int) (app()->bound('current_company_id') ? app('current_company_id') : 1);

        $existing = $studentId !== ''
            ? StudentFeedback::where('student_id', $studentId)->latest()->get()
            : collect();

        return view('student.feedback', array_merge($base, [
            'stepLabels' => StudentFeedback::STEP_LABELS,
            'existing'   => $existing,
            'companyId'  => $companyId,
        ]));
    }

    public function institutionDocuments(Request $request, StudentGuestResolver $resolver)
    {
        $studentId = (string) ($resolver->resolveForUser(auth()->user())?->student_id ?? '');
        if ($studentId === '') {
            return view('student.institution-documents', [
                'grouped' => collect(),
                'catalog' => [],
            ]);
        }

        $docs    = StudentInstitutionDocument::query()->forStudent($studentId)->visibleToStudent()->latest()->get();
        $catalog = config('institution_document_catalog.categories', []);
        $grouped = $docs->groupBy('institution_category');

        return view('student.institution-documents', compact('grouped', 'catalog'));
    }

    public function universityApplications(Request $request, StudentGuestResolver $resolver): \Illuminate\View\View
    {
        $studentId        = (string) ($resolver->resolveForUser(auth()->user())?->student_id ?? '');
        $catalog          = config('institution_document_catalog.categories', []);
        $guestApplication = $resolver->resolveForUser(auth()->user());
        $progressSteps    = [];
        $dmUnread         = 0;

        if ($studentId === '') {
            return view('student.university-applications', [
                'applications'     => collect(),
                'institutionDocs'  => collect(),
                'catalog'          => $catalog,
                'studentId'        => $studentId,
                'guestApplication' => null,
                'progressSteps'    => $progressSteps,
                'dmUnread'         => $dmUnread,
            ]);
        }

        $applications    = StudentUniversityApplication::query()->forStudent($studentId)->visibleToStudent()->orderBy('priority')->get();
        $institutionDocs = StudentInstitutionDocument::query()->forStudent($studentId)->visibleToStudent()->get();

        return view('student.university-applications', compact(
            'applications', 'institutionDocs', 'catalog',
            'studentId', 'guestApplication', 'progressSteps', 'dmUnread'
        ));
    }

    public function visa(Request $request)
    {
        $base      = $this->baseData($request, 'visa', 'Vize Başvurusu', 'Vize sürecinizi buradan takip edin.');
        $studentId = $base['studentId'];

        $visa = $studentId
            ? StudentVisaApplication::where('student_id', $studentId)
                ->where('is_visible_to_student', true)
                ->latest('id')
                ->first()
            : null;

        return view('student.visa', array_merge($base, [
            'visa'           => $visa,
            'statusLabels'   => StudentVisaApplication::STATUS_LABELS,
            'visaTypeLabels' => StudentVisaApplication::VISA_TYPE_LABELS,
            'documentLabels' => StudentVisaApplication::COMMON_DOCUMENTS,
        ]));
    }

    public function housing(Request $request)
    {
        $base      = $this->baseData($request, 'housing', 'Konut & Barınma', 'Almanya\'daki konut durumunuzu takip edin.');
        $studentId = $base['studentId'];

        $accommodation = $studentId
            ? StudentAccommodation::where('student_id', $studentId)
                ->where('is_visible_to_student', true)
                ->latest('id')
                ->first()
            : null;

        return view('student.housing', array_merge($base, [
            'accommodation' => $accommodation,
            'typeLabels'    => StudentAccommodation::TYPE_LABELS,
            'statusLabels'  => StudentAccommodation::STATUS_LABELS,
        ]));
    }
}
