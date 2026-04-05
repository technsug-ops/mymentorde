<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\InternalNote;
use App\Models\StudentAssignment;
use App\Services\AiWritingService;
use App\Services\ContractTemplateService;
use App\Services\CvTemplateService;
use App\Services\DocumentBuilderService;
use App\Services\EventLogService;
use App\Services\StudentGuestResolver;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentDocumentBuilderController extends Controller
{
    public function __construct(
        private readonly DocumentBuilderService $documentBuilderService,
        private readonly EventLogService $eventLogService,
        private readonly TaskAutomationService $taskAutomationService,
        private readonly ContractTemplateService $contractTemplateService,
        private readonly CvTemplateService $cvTemplateService,
        private readonly AiWritingService $aiWritingService
    ) {
    }

    private function resolveStudentGuest(Request $request): ?GuestApplication
    {
        return app(StudentGuestResolver::class)->resolveForUser($request->user());
    }

    private function resolveDocumentOwnerId(GuestApplication $guest): string
    {
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '') {
            return $studentId;
        }
        return 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }

    public function generateDocumentBuilderFile(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(!$guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $data = $request->validate([
            'document_type' => ['required', 'in:cv,motivation,reference,cover_letter,sperrkonto,housing'],
            'language' => ['required', 'in:tr,de,en'],
            'output_format' => ['nullable', 'in:docx,pdf,md'],
            'title' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'ai_mode' => ['nullable', 'in:template,ai_assist,final_text'],
            'final_text_content' => ['nullable', 'string', 'max:20000'],
            'motivation_text' => ['nullable', 'string', 'max:10000'],
            'target_program' => ['nullable', 'string', 'max:255'],
            'reference_teacher_contact' => ['nullable', 'string', 'max:1000'],
            'cv_profile_summary_tr' => ['nullable', 'string', 'max:2000'],
            'cv_experience_tr' => ['nullable', 'string', 'max:4000'],
            'cv_education_tr' => ['nullable', 'string', 'max:4000'],
            'cv_skills_tr' => ['nullable', 'string', 'max:2000'],
            'cv_languages_tr' => ['nullable', 'string', 'max:2000'],
            'cv_certificates_tr' => ['nullable', 'string', 'max:2000'],
            'cv_projects_tr' => ['nullable', 'string', 'max:3000'],
            'cv_references_tr' => ['nullable', 'string', 'max:2000'],
            'cv_computer_skills_tr' => ['nullable', 'string', 'max:2000'],
            'cv_hobbies_tr' => ['nullable', 'string', 'max:2000'],
            'cv_city_signature_tr' => ['nullable', 'string', 'max:120'],
        ]);

        $ownerId = $this->resolveDocumentOwnerId($guest);
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $cvDraftPatch = [
            'motivation_text' => trim((string) ($data['motivation_text'] ?? ($draft['motivation_text'] ?? ''))),
            'target_program' => trim((string) ($data['target_program'] ?? ($draft['target_program'] ?? ''))),
            'reference_teacher_contact' => trim((string) ($data['reference_teacher_contact'] ?? ($draft['reference_teacher_contact'] ?? ''))),
            'cv_profile_summary_tr' => trim((string) ($data['cv_profile_summary_tr'] ?? '')),
            'cv_experience_tr' => trim((string) ($data['cv_experience_tr'] ?? '')),
            'cv_education_tr' => trim((string) ($data['cv_education_tr'] ?? '')),
            'cv_skills_tr' => trim((string) ($data['cv_skills_tr'] ?? '')),
            'cv_languages_tr' => trim((string) ($data['cv_languages_tr'] ?? '')),
            'cv_certificates_tr' => trim((string) ($data['cv_certificates_tr'] ?? '')),
            'cv_projects_tr' => trim((string) ($data['cv_projects_tr'] ?? '')),
            'cv_references_tr' => trim((string) ($data['cv_references_tr'] ?? '')),
            'cv_computer_skills_tr' => trim((string) ($data['cv_computer_skills_tr'] ?? '')),
            'cv_hobbies_tr' => trim((string) ($data['cv_hobbies_tr'] ?? '')),
            'cv_city_signature_tr' => trim((string) ($data['cv_city_signature_tr'] ?? '')),
        ];
        $draft = array_merge($draft, $cvDraftPatch);
        $guest->registration_form_draft = $draft;
        $guest->registration_form_draft_saved_at = now();
        $guest->save();

        $docType = (string) $data['document_type'];
        // Tüm belge tipleri Almanca üretilir (config'de force_lang=de).
        $lang = 'de';
        $outputFormat = (string) ($data['output_format'] ?? 'docx');
        $aiMode = (string) ($data['ai_mode'] ?? 'template');
        $finalTextContent = trim((string) ($data['final_text_content'] ?? ''));
        if ($aiMode === 'final_text' && $finalTextContent !== '' && in_array($docType, ['motivation', 'reference'], true)) {
            $titleMap = [
                'motivation'   => 'Motivationsschreiben',
                'reference'    => 'Empfehlungsschreiben',
                'cover_letter' => 'Anschreiben',
                'sperrkonto'   => 'Sperrkonto-Antrag',
                'housing'      => 'Wohnheimsantrag',
            ];
            $built = ['title' => $titleMap[$docType] ?? $docType, 'content' => $finalTextContent];
        } else {
            $built = $this->documentBuilderService->buildDocumentText(
                $guest,
                $draft,
                $docType,
                $lang,
                (string) ($data['notes'] ?? ''),
                $aiMode
            );
        }
        $aiAssistResult = null;
        if ($aiMode === 'ai_assist' && in_array($docType, ['motivation', 'reference'], true)) {
            $aiAssistResult = $this->documentBuilderService->applyAiAssist(
                $docType,
                $built,
                $guest,
                $draft,
                (string) ($data['notes'] ?? '')
            );
            $built = $aiAssistResult['built'];
            if (!empty($aiAssistResult['effective_mode'])) {
                $aiMode = (string) $aiAssistResult['effective_mode'];
            }
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = $built['title'];
        }

        $slugType = Str::slug($docType);
        $slugLang = Str::slug($lang);
        $extension = match($outputFormat) { 'pdf' => 'pdf', 'md' => 'md', default => 'docx' };
        $fileName = "{$ownerId}_{$slugType}_{$slugLang}_" . now()->format('Ymd_His') . '.' . $extension;
        $path = "student-builder/{$guest->id}/{$fileName}";
        if ($outputFormat === 'pdf') {
            $binary = $this->cvTemplateService->buildPdfFromText((string) $built['content']);
            Storage::disk('public')->put($path, $binary);
        } elseif ($outputFormat === 'docx') {
            $binary = $this->cvTemplateService->buildDocxFromText((string) $built['content']);
            Storage::disk('public')->put($path, $binary);
        } else {
            Storage::disk('public')->put($path, (string) $built['content']);
        }

        $category = $this->documentBuilderService->resolveBuilderCategory($docType);
        $builderTags = [
            'student_document_builder',
            'builder_output',
            $docType,
            'doc_type:' . $docType,
            $lang,
            'lang:' . $lang,
            $aiMode,
            'ai:' . $aiMode,
        ];

        $row = Document::query()->create([
            'student_id' => $ownerId,
            'category_id' => (int) $category->id,
            'process_tags' => collect($builderTags)->filter()->unique()->values()->all(),
            'original_file_name' => $title . '.' . $extension,
            'standard_file_name' => $fileName,
            'storage_path' => $path,
            'mime_type' => match($outputFormat) {
                'pdf'  => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'text/markdown',
            },
            'status' => 'generated',
            'uploaded_by' => (string) optional($request->user())->email,
            'review_note' => $this->documentBuilderService->composeReviewNote((string) ($data['notes'] ?? ''), $aiAssistResult),
        ]);
        $row->forceFill([
            'document_id' => 'DOC-STB-' . str_pad((string) $row->id, 7, '0', STR_PAD_LEFT),
        ])->save();

        InternalNote::query()->create([
            'company_id' => (int) ($guest->company_id ?: 1),
            'student_id' => $ownerId,
            'content' => "Student document builder uretimi: {$docType}/{$lang} | {$row->document_id}",
            'category' => 'document',
            'priority' => 'normal',
            'is_pinned' => false,
            'created_by' => (string) optional($request->user())->email,
            'created_role' => 'student',
            'attachments' => [['document_id' => (string) $row->document_id, 'path' => (string) $row->storage_path]],
        ]);

        $this->eventLogService->log(
            eventType: 'student_document_generated',
            entityType: 'document',
            entityId: (string) $row->id,
            message: "Student {$ownerId} belge uretti: {$docType}/{$lang}.",
            meta: [
                'document_id' => (string) $row->document_id,
                'document_type' => $docType,
                'language' => $lang,
                'output_format' => $outputFormat,
                'ai_mode' => $aiMode,
                'ai_assist' => $aiAssistResult ? [
                    'used' => (bool) ($aiAssistResult['used'] ?? false),
                    'provider' => (string) ($aiAssistResult['provider'] ?? ''),
                    'model' => (string) ($aiAssistResult['model'] ?? ''),
                    'fallback_reason' => (string) ($aiAssistResult['error'] ?? ''),
                ] : null,
            ],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 1)
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Dokuman olusturuldu ve belge merkezine eklendi.',
                'ai' => $aiAssistResult ? [
                    'used' => (bool) ($aiAssistResult['used'] ?? false),
                    'fallback_reason' => (string) ($aiAssistResult['error'] ?? ''),
                ] : null,
                'document' => [
                    'id' => (int) $row->id,
                    'document_id' => (string) $row->document_id,
                    'storage_path' => (string) $row->storage_path,
                    'status' => (string) $row->status,
                ],
                'redirect' => '/student/registration/documents',
            ]);
        }

        $statusMessage = 'Dokuman olusturuldu ve belge merkezine eklendi.';
        if ($aiAssistResult && empty($aiAssistResult['used']) && !empty($aiAssistResult['error'])) {
            $statusMessage .= ' (AI destek kullanilamadi; template mod ile devam edildi.)';
        } elseif ($aiAssistResult && !empty($aiAssistResult['used'])) {
            $statusMessage .= ' (AI destek uygulandi.)';
        }

        return redirect('/student/document-builder')->with('status', $statusMessage);
    }

    public function aiDraftPreview(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveStudentGuest($request);
        if (!$guest) {
            return response()->json(['ok' => false, 'error' => 'no_student'], 403);
        }

        $docType = (string) $request->input('document_type', '');
        if (!in_array($docType, ['motivation', 'reference'], true)) {
            return response()->json(['ok' => false, 'error' => 'invalid_type'], 422);
        }

        $assembledDraft = $this->documentBuilderService->assembleStructuredDraftForAi($docType, $request->all());
        if (trim($assembledDraft) === '') {
            return response()->json(['ok' => false, 'error' => 'empty_input'], 422);
        }

        $context = [
            'target_program' => $request->input('mot_program', ''),
            'candidate_name'  => $request->input('mot_name', ''),
        ];

        if ($this->aiWritingService->isAvailable()) {
            $result = $this->aiWritingService->improveGermanDocument($docType, $assembledDraft, $context);
            if ($result['ok']) {
                return response()->json([
                    'ok'       => true,
                    'content'  => $result['content'],
                    'mode'     => 'ai',
                    'provider' => $result['provider'],
                    'model'    => $result['model'],
                ]);
            }
        }

        // AI kullanılamıyorsa: birleştirilmiş ham metni döndür
        return response()->json([
            'ok'      => true,
            'content' => $assembledDraft,
            'mode'    => 'template',
        ]);
    }

    public function previewDocumentBuilder(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $user  = Auth::user();
        /** @var \App\Models\User $user */
        $guest = GuestApplication::query()
            ->whereNotNull('converted_student_id')
            ->where('converted_student_id', (string) ($user->student_id ?? ''))
            ->first();

        if (!$guest) {
            return response()->json(['error' => 'Öğrenci profili bulunamadı.'], 404);
        }

        $draft   = (array) $request->input('draft', []);
        $docType = trim((string) $request->input('doc_type', 'motivation'));
        $lang    = trim((string) $request->input('language', 'de'));
        $notes   = trim((string) $request->input('extra_notes', ''));

        $svc     = app(\App\Services\DocumentBuilderService::class);
        $result  = $svc->preview($guest, $draft, $docType, $lang, $notes);
        $quality = $svc->qualityScore((string) ($result['content'] ?? ''), $docType);

        return response()->json(['ok' => true, 'preview' => $result, 'quality' => $quality]);
    }
}
