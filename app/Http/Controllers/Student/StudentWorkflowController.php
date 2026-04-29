<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentWorkflowTrait;
use App\Models\GuestRegistrationSnapshot;
use App\Models\InternalNote;
use App\Models\MarketingTask;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\EventLogService;
use App\Services\GuestRegistrationFieldSchemaService;
use Illuminate\Http\Request;

class StudentWorkflowController extends Controller
{
    use StudentWorkflowTrait;

    public function __construct(private readonly EventLogService $eventLogService) {}

    public function requestNextStep(Request $request)
    {
        $data = $request->validate([
            'current_step' => ['required', 'string', 'max:80'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ]);

        $user      = $request->user();
        $studentId = trim((string) ($user->student_id ?? ''));
        if ($studentId === '') {
            $guest     = $this->resolveStudentGuest($request);
            $studentId = trim((string) ($guest->converted_student_id ?? ''));
        }
        abort_if($studentId === '', 422, 'Student kaydi bulunamadi.');

        $assignment  = StudentAssignment::query()->where('student_id', $studentId)->first();
        $companyId   = (int) ($assignment->company_id ?? (app()->bound('current_company_id') ? (int) app('current_company_id') : 1));

        $assignee    = null;
        $seniorEmail = strtolower(trim((string) ($assignment->senior_email ?? '')));
        if ($seniorEmail !== '') {
            $assignee = User::query()
                ->where('email', strtolower($seniorEmail))
                ->where('is_active', true)
                ->first();
        }
        if (! $assignee) {
            $assignee = User::query()
                ->where('company_id', $companyId)
                ->where('role', User::ROLE_MANAGER)
                ->where('is_active', true)
                ->first();
        }

        $task = MarketingTask::query()->create([
            'company_id'           => $companyId > 0 ? $companyId : 1,
            'title'                => 'Student surec adimi talebi',
            'description'          => "Student {$studentId} sonraki adimi talep etti | current_step: " . trim((string) $data['current_step']) . (($data['note'] ?? '') ? ' | not: ' . trim((string) $data['note']) : ''),
            'status'               => 'todo',
            'priority'             => 'normal',
            'due_date'             => now()->addDay()->toDateString(),
            'assigned_user_id'     => (int) ($assignee->id ?? 0) ?: null,
            'created_by_user_id'   => (int) ($user->id ?? 0) ?: null,
            'source_type'          => 'student_step_request',
            'source_id'            => $studentId . ':' . now()->format('YmdHis'),
            'escalate_after_hours' => 24,
        ]);

        InternalNote::query()->create([
            'company_id'  => $companyId > 0 ? $companyId : 1,
            'student_id'  => $studentId,
            'content'     => "Student surec talebi | adim: " . trim((string) $data['current_step']) . (($data['note'] ?? '') ? ' | not: ' . trim((string) $data['note']) : ''),
            'category'    => 'general',
            'priority'    => 'normal',
            'is_pinned'   => false,
            'created_by'  => (string) ($user->email ?? 'student'),
            'created_role'=> 'student',
            'attachments' => [],
        ]);

        $this->eventLogService->log(
            eventType: 'student_step_requested',
            entityType: 'student',
            entityId: $studentId,
            message: "Student {$studentId} sonraki surec adimini talep etti.",
            meta: ['current_step' => trim((string) $data['current_step']), 'task_id' => (int) $task->id],
            actorEmail: (string) ($user->email ?? null),
            companyId: $companyId
        );

        return redirect('/student/dashboard')->with('status', 'Surec adimi talebiniz kaydedildi.');
    }

    public function autoSaveRegistration(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        // Student tarafı her zaman Level 2 (88 field tam form)
        $draftMap  = app(GuestRegistrationFieldSchemaService::class)->sanitizePayloadByLevel($request->all(), 2, $companyId);

        $guest->forceFill([
            'first_name'                       => trim((string) ($draftMap['first_name'] ?? '')) ?: $guest->first_name,
            'last_name'                        => trim((string) ($draftMap['last_name'] ?? '')) ?: $guest->last_name,
            'phone'                            => trim((string) ($draftMap['phone'] ?? '')) ?: $guest->phone,
            'gender'                           => trim((string) ($draftMap['gender'] ?? '')) ?: $guest->gender,
            'application_country'              => trim((string) ($draftMap['application_country'] ?? '')) ?: $guest->application_country,
            'application_type'                 => trim((string) ($draftMap['application_type'] ?? '')) ?: $guest->application_type,
            'target_city'                      => trim((string) ($draftMap['application_city'] ?? '')) ?: $guest->target_city,
            'target_term'                      => trim((string) ($draftMap['university_start_target_date'] ?? '')) ?: $guest->target_term,
            'language_level'                   => trim((string) ($draftMap['german_level'] ?? '')) ?: $guest->language_level,
            'notes'                            => trim((string) ($draftMap['additional_note'] ?? '')) ?: $guest->notes,
            'registration_form_draft'          => $draftMap,
            'registration_form_draft_saved_at' => now(),
            'status_message'                   => 'Student form taslagi kaydedildi.',
        ])->save();

        return redirect('/student/registration')->with('status', 'Taslak kaydedildi.');
    }

    public function submitRegistration(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(! $guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $companyId     = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $schemaService = app(GuestRegistrationFieldSchemaService::class);
        // Student tarafı her zaman Level 2
        $payload       = $schemaService->sanitizePayloadByLevel($request->all(), 2, $companyId);
        $skipKeys = array_merge(
            $schemaService->educationSkippedKeys($payload),
            $schemaService->spouseSkippedKeys($payload),
        );

        // Eski draft (Level 1'den kalan veriler) — validation HER İKİSİNİ birleşik
        // kontrol etmeli. Level 1'de doldurulmuş zorunlu alanlar (birth_date, vb.)
        // Level 2 form'unda görünmüyor ama hâlâ dolu olmalı.
        $existingDraft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        // ÖNEMLİ: payload'daki BOŞ değerler existingDraft'taki dolu değerleri
        // EZMEMELİ. Student form'unda gizli olan Level 1 alanları sanitize sonrası
        // '' ile gelir → array_merge ezer → Level 1'in dolu birth_date'i kaybolur.
        // Bu yüzden boş payload key'leri merge öncesi temizliyoruz.
        $payloadForMerge = collect($payload)->reject(function ($v) {
            if ($v === null) return true;
            if (is_array($v)) return empty($v);
            return trim((string) $v) === '';
        })->all();
        $mergedDraft = array_merge($existingDraft, $payloadForMerge);

        // Sadece OPSİYONEL Level 1 key'leri skip et — zorunlular merge edilmiş
        // veride hâlâ kontrol edilir. Aday öğrenciliğinde doldurmadıysa şimdi
        // hata göstererek kullanıcıya geri dönüp tamamlatırız.
        $level1OptionalKeys = collect(\App\Support\GuestRegistrationFormCatalog::flatFieldsByLevel(1))
            ->reject(fn ($f) => !empty($f['required']))
            ->pluck('key')->filter()->all();
        $skipKeys = array_merge($skipKeys, $level1OptionalKeys);

        // Field-specific hata mesajı için catalog'dan label + tip lookup
        $allFields = $schemaService->flatFieldsByLevel(2, $companyId);
        $fieldByKey = [];
        foreach ($allFields as $f) { $fieldByKey[$f['key'] ?? ''] = $f; }

        // Bir değerin "boş" olduğunu belirleyen helper —
        // null, '', '   ', boş array hepsi boş sayılır. ?? operatörü '' için
        // tetiklenmez, bu yüzden manuel fallback şart.
        $isBlank = function ($v): bool {
            if ($v === null) return true;
            if (is_array($v)) return empty($v);
            return trim((string) $v) === '';
        };

        $missingErrors = [];
        foreach ($schemaService->requiredKeysByLevel(2, $companyId) as $key) {
            if (in_array($key, $skipKeys, true)) {
                continue;
            }
            // L2 payload → L1 existingDraft → L0 guest model col sıralı, boş olmayan ilk değeri al
            $val = null;
            foreach ([$payload[$key] ?? null, $existingDraft[$key] ?? null, $guest->{$key} ?? null] as $candidate) {
                if (!$isBlank($candidate)) { $val = $candidate; break; }
            }
            $isEmpty = $isBlank($val);
            if ($isEmpty) {
                $field = $fieldByKey[$key] ?? null;
                $label = $field
                    ? trim(rtrim((string) ($field['label'] ?? $key), ' *'))
                    : $key;
                $verb = match ($field['type'] ?? '') {
                    'select', 'checkbox_group' => 'seçilmedi',
                    default                     => 'doldurulmadı',
                };
                $missingErrors[$key] = $label . ' ' . $verb;
            }
        }
        // B13/B15: eğitim tarih sırası + parent dob kontrolü (merged'da)
        foreach ($schemaService->educationDateOrderErrors($mergedDraft) as $f => $err) {
            $missingErrors[$f] = $err;
        }
        foreach ($this->conditionalRequiredErrors($mergedDraft) as $field => $msg) {
            $missingErrors[$field] = $msg;
        }
        if (! empty($missingErrors)) {
            return redirect('/student/registration')
                ->withErrors($missingErrors)
                ->withInput();
        }

        $guest->forceFill([
            'first_name'                         => trim((string) ($payload['first_name'] ?? '')) ?: $guest->first_name,
            'last_name'                          => trim((string) ($payload['last_name'] ?? '')) ?: $guest->last_name,
            'phone'                              => trim((string) ($payload['phone'] ?? '')) ?: $guest->phone,
            'gender'                             => trim((string) ($payload['gender'] ?? '')) ?: $guest->gender,
            'application_country'                => trim((string) ($payload['application_country'] ?? '')) ?: $guest->application_country,
            'application_type'                   => trim((string) ($payload['application_type'] ?? '')) ?: $guest->application_type,
            'target_city'                        => trim((string) ($payload['application_city'] ?? '')),
            'target_term'                        => trim((string) ($payload['university_start_target_date'] ?? '')),
            'language_level'                     => trim((string) ($payload['german_level'] ?? '')),
            'notes'                              => trim((string) ($payload['additional_note'] ?? '')) ?: $guest->notes,
            'registration_form_draft'            => $mergedDraft,
            'registration_form_submitted_at'     => now(),
            'registration_form_level'            => 'level_2_done',
            'status_message'                     => 'Tam kayıt formu gönderildi.',
        ])->save();

        $next = (int) GuestRegistrationSnapshot::query()
            ->where('guest_application_id', (int) $guest->id)
            ->max('snapshot_version');
        GuestRegistrationSnapshot::query()->create([
            'guest_application_id' => (int) $guest->id,
            'snapshot_version'     => max(1, $next + 1),
            'submitted_by_email'   => (string) optional($request->user())->email,
            'payload_json'         => $mergedDraft,
            'meta_json'            => ['submitted_via' => 'student_portal'],
            'submitted_at'         => now(),
        ]);

        $user = $request->user();
        if ($user && $user->email) {
            try {
                \Mail::to($user->email)->queue(new \App\Mail\WelcomeStudentMail($user));
            } catch (\Throwable) {}
        }

        // C5: submit sonrası belge sayfasına yönlendir (user tercihi)
        return redirect('/student/registration/documents')
            ->with('status', 'Tam kayıt formun gönderildi. Şimdi belgelerini yükleyebilirsin.');
    }

    public function registrationFormPdf(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(!$guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $schema = app(GuestRegistrationFieldSchemaService::class);
        $groups = $schema->groupsByLevel(2, $companyId);
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $formLevel = 2;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('guest.registration-form-pdf', compact('guest', 'groups', 'draft', 'formLevel'));
        $fileName = 'Kayit_Formu_L2_' . ($guest->first_name ?? '') . '_' . ($guest->last_name ?? '') . '_' . now()->format('Ymd') . '.pdf';

        if ($request->query('download')) {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }
}
