<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesServicePackages;
use App\Http\Controllers\Student\Concerns\StudentPortalTrait;
use App\Models\AccountVault;
use App\Models\Document;
use App\Models\GuestApplication;
use App\Models\GuestRegistrationSnapshot;
use App\Models\GuestTicket;
use App\Models\StudentAppointment;
use App\Services\AccountVaultService;
use App\Services\EventLogService;
use App\Services\GuestRegistrationFieldSchemaService;
use App\Support\ApplicationCountryCatalog;
use App\Support\SchemaCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    use StudentPortalTrait, UsesServicePackages;

    // ── Kayıt Formu & Belgeler ───────────────────────────────────────────────

    public function registration(Request $request)
    {
        $base      = $this->baseData($request, 'registration', 'Kayit Sureci', 'Formu tamamlayin ve zorunlu alanlari bitirin.');
        $guest     = $this->resolveStudentGuest($request);
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $fieldGroups = app(GuestRegistrationFieldSchemaService::class)->groups($companyId);
        if ($guest) {
            $guest->registration_form_draft = $this->ensureRegistrationDraftHydrated($guest, $companyId);
        }

        return view('student.registration-form', array_merge($base, [
            'guestApplication'         => $guest,
            'registrationFieldGroups'  => $fieldGroups,
        ]));
    }

    public function registrationDocuments(Request $request)
    {
        $base        = $this->baseData($request, 'registration', 'Kayit Belgeleri', 'Zorunlu ve opsiyonel belgeleri yukleyin.');
        $guest       = $this->resolveStudentGuest($request);
        $builderOnly = (bool) $request->boolean('builder_only');

        if (!$guest) {
            return view('student.registration-documents', array_merge($base, [
                'guestApplication'           => null,
                'documents'                  => collect(),
                'requiredDocumentChecklist'  => [],
                'documentCategories'         => collect(),
                'documentTopCategoryLabels'  => \App\Models\DocumentCategory::topCategoryOptions(),
                'builderOnly'                => $builderOnly,
            ]));
        }

        $ownerIds    = $this->resolveDocumentOwnerIds($guest);
        $allDocuments = Document::query()
            ->whereIn('student_id', $ownerIds)
            ->with('category:id,code,name_tr')
            ->latest('id')
            ->limit(120)
            ->get(['id', 'student_id', 'category_id', 'document_id', 'original_file_name', 'status', 'review_note', 'updated_at', 'storage_path', 'process_tags']);

        $documents = $builderOnly
            ? $allDocuments->filter(function (Document $d): bool {
                $tags = collect(is_array($d->process_tags ?? null) ? $d->process_tags : [])
                    ->map(fn ($x) => strtolower(trim((string) $x)));
                return $tags->contains('student_document_builder')
                    || $tags->contains('builder_output')
                    || str_starts_with(strtolower((string) ($d->document_id ?? '')), 'doc-stb-');
            })->values()
            : $allDocuments;

        $uploadedCodes = $allDocuments->map(fn (Document $d) => (string) ($d->category->code ?? ''))->filter()->values()->all();
        $checklist     = $this->requiredDocumentsByApplicationType(
            (string) ($guest->application_type ?? ''),
            $uploadedCodes,
            'student'
        );

        return view('student.registration-documents', array_merge($base, [
            'guestApplication'          => $guest,
            'documents'                 => $documents,
            'requiredDocumentChecklist' => $checklist,
            'documentCategories'        => \App\Models\DocumentCategory::all(),
            'documentTopCategoryLabels' => \App\Models\DocumentCategory::topCategoryOptions(),
            'builderOnly'               => $builderOnly,
        ]));
    }

    // ── Document Builder ─────────────────────────────────────────────────────

    public function documentBuilder(Request $request)
    {
        $base     = $this->baseData($request, 'document_builder', 'Dokuman Olusturucu', 'CV, motivasyon ve referans metni olusturup belge merkezine gonderebilirsiniz.');
        $guest    = $this->resolveStudentGuest($request);
        $ownerId  = $guest ? $this->resolveDocumentOwnerId($guest) : '';
        $ownerIds = $guest ? $this->resolveDocumentOwnerIds($guest) : collect();

        $builderDocuments = collect();
        if ($ownerId !== '') {
            $builderDocuments = Document::query()
                ->whereIn('student_id', $ownerIds)
                ->with('category:id,code,name_tr')
                ->orderByDesc('id')
                ->limit(80)
                ->get(['id', 'student_id', 'category_id', 'process_tags', 'document_id', 'original_file_name', 'standard_file_name', 'storage_path', 'status', 'review_note', 'updated_at'])
                ->filter(function (Document $row): bool {
                    $tags = collect(is_array($row->process_tags) ? $row->process_tags : [])
                        ->map(fn ($v) => strtolower(trim((string) $v)));
                    return $tags->contains('student_document_builder')
                        || str_starts_with(strtolower((string) $row->document_id), 'DOC-STB-');
                })
                ->values();
        }

        $draft = is_array($guest?->registration_form_draft) ? $guest->registration_form_draft : [];

        return view('student.document-builder', array_merge($base, [
            'guestApplication'  => $guest,
            'builderDocuments'  => $builderDocuments,
            'builderDraft'      => [
                'motivation_text'              => (string) ($draft['motivation_text'] ?? ''),
                'target_program'               => (string) ($draft['target_program'] ?? ''),
                'reference_teacher_contact'    => (string) ($draft['reference_teacher_contact'] ?? ''),
                'other_language_level'         => (string) ($draft['other_language_level'] ?? ''),
                'cv_profile_summary_tr'        => (string) ($draft['cv_profile_summary_tr'] ?? ''),
                'cv_experience_tr'             => (string) ($draft['cv_experience_tr'] ?? ''),
                'cv_education_tr'              => (string) ($draft['cv_education_tr'] ?? ''),
                'cv_skills_tr'                 => (string) ($draft['cv_skills_tr'] ?? ''),
                'cv_languages_tr'              => (string) ($draft['cv_languages_tr'] ?? ''),
                'cv_certificates_tr'           => (string) ($draft['cv_certificates_tr'] ?? ''),
                'cv_projects_tr'               => (string) ($draft['cv_projects_tr'] ?? ''),
                'cv_references_tr'             => (string) ($draft['cv_references_tr'] ?? ''),
                'cv_computer_skills_tr'        => (string) ($draft['cv_computer_skills_tr'] ?? ''),
                'cv_hobbies_tr'                => (string) ($draft['cv_hobbies_tr'] ?? ''),
                'cv_city_signature_tr'         => (string) ($draft['cv_city_signature_tr'] ?? ''),
            ],
            'cvGuideSteps'      => [
                ['step' => 1, 'title' => 'Kisisel Veri Kontrolu',  'desc' => 'Ad, dogum, adres, iletisim alanlarini dogru doldur.'],
                ['step' => 2, 'title' => 'Egitim Satirlari',        'desc' => 'Ilkokul-Ortaokul-Lise-Universite bilgilerini tarihleriyle gir.'],
                ['step' => 3, 'title' => 'Dil + Bilgisayar',        'desc' => 'Almanca/Ingilizce seviye ve bilgisayar yetkinliklerini yaz.'],
                ['step' => 4, 'title' => 'Hobiler',                  'desc' => 'Kisa ve profesyonel hobiler ekle (muzik, teknoloji, spor vb.).'],
                ['step' => 5, 'title' => 'Almanca Cikti Uret',      'desc' => 'Sistem Lebenslauf cikisini ornek formatta olusturur.'],
            ],
            'cvGuideResources'  => [
                ['type' => 'video', 'title' => 'CV Hazirlama Video Linki',    'url' => 'https://example.com/cv-video'],
                ['type' => 'doc',   'title' => 'CV Checklist Dokumani (PDF)', 'url' => 'https://example.com/cv-checklist.pdf'],
            ],
            'documentBuilderBridge' => [
                'studentId'             => (string) ($base['studentId'] ?? ''),
                'defaultLanguage'       => 'tr',
                'allowLanguageSelector' => false,
                'generateUrl'           => route('student.document-builder.generate'),
                'documentCenterUrl'     => '/student/registration/documents?builder_only=1',
                'outputFormat'          => 'docx',
                'aiMode'                => 'template',
                'signatureCity'         => (string) ($draft['cv_city_signature_tr'] ?? $draft['application_city'] ?? ''),
                'prefill'               => [
                    'first_name'           => (string) ($guest->first_name ?? ''),
                    'last_name'            => (string) ($guest->last_name ?? ''),
                    'email'                => (string) ($guest->email ?? ''),
                    'phone'                => (string) ($guest->phone ?? ''),
                    'birth_date'           => (string) ($draft['birth_date'] ?? ''),
                    'birth_place'          => (string) ($draft['birth_place'] ?? ''),
                    'marital_status'       => (string) ($draft['marital_status'] ?? ''),
                    'marital_status_label' => (string) ($draft['marital_status'] ?? ''),
                    'nationality'          => (string) ($draft['nationality'] ?? ''),
                    'address_line'         => (string) ($draft['address_line'] ?? ''),
                    'district'             => (string) ($draft['district'] ?? ''),
                    'city'                 => (string) ($draft['application_city'] ?? $draft['city'] ?? ''),
                    'country'              => (string) ($draft['application_country'] ?? ''),
                    'postal_code'          => (string) ($draft['postal_code'] ?? ''),
                    'cv_computer_skills_tr'=> (string) ($draft['cv_computer_skills_tr'] ?? ''),
                    'cv_skills_tr'         => (string) ($draft['cv_skills_tr'] ?? ''),
                    'cv_hobbies_tr'        => (string) ($draft['cv_hobbies_tr'] ?? ''),
                ],
            ],
        ]));
    }

    // ── Randevular & Biletler ────────────────────────────────────────────────

    public function appointments(Request $request)
    {
        $base      = $this->baseData($request, 'appointments', 'Randevularim', 'Senior ile randevu talebi, onay ve gecmis kayitlari.');
        $studentId = (string) ($base['studentId'] ?? '');
        $appointments = collect();
        if ($studentId !== '') {
            $appointments = StudentAppointment::query()
                ->where('student_id', $studentId)
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        return view('student.appointments', array_merge($base, [
            'appointments' => $appointments,
        ]));
    }

    public function tickets(Request $request)
    {
        $base    = $this->baseData($request, 'tickets', 'Iletisim / Ticket', 'Surec bazli destek kayitlari ve yanitlar.');
        $guest   = $this->resolveStudentGuest($request);
        $tickets = collect();
        if ($guest) {
            $tickets = GuestTicket::query()
                ->where('guest_application_id', (int) $guest->id)
                ->with(['replies' => fn ($q) => $q->latest()->limit(15)])
                ->latest('id')
                ->limit(50)
                ->get();
        }

        return view('student.tickets', array_merge($base, [
            'guestApplication' => $guest,
            'tickets'          => $tickets,
            'ticketPrefill'    => [
                'subject'    => trim((string) $request->query('subject', '')),
                'message'    => trim((string) $request->query('message', '')),
                'priority'   => trim((string) $request->query('priority', 'normal')),
                'department' => trim((string) $request->query('department', 'auto')),
            ],
        ]));
    }

    // ── Sözleşme & Servisler ─────────────────────────────────────────────────

    public function contract(Request $request)
    {
        $base       = $this->baseData($request, 'contract', 'Sözleşme', 'Sözleşme görüntüleme, indirme ve durum takibi.');
        $guest      = $this->resolveStudentGuest($request);
        $packages   = collect($this->servicePackages());
        $extraServices = collect($this->extraServiceOptions());
        $readiness  = $this->contractPrerequisites($guest);
        $contractUi = $this->contractUiState($guest, $readiness);

        return view('student.contract', array_merge($base, [
            'guestApplication'          => $guest,
            'contractPackages'          => $packages,
            'contractExtraServices'     => $extraServices,
            'selectedPackageCode'       => (string) ($guest?->selected_package_code ?? ''),
            'selectedExtraServiceCodes' => collect(is_array($guest?->selected_extra_services) ? $guest->selected_extra_services : [])
                ->map(fn ($x) => trim((string) ($x['code'] ?? '')))->filter()->values()->all(),
            'formCompleted'             => (bool) ($readiness['formCompleted'] ?? false),
            'formDraftComplete'         => (bool) ($readiness['formDraftComplete'] ?? false),
            'formRequiredFilled'        => (int) ($readiness['formRequiredFilled'] ?? 0),
            'formRequiredTotal'         => (int) ($readiness['formRequiredTotal'] ?? 0),
            'docsCompleted'             => (bool) ($readiness['docsCompleted'] ?? false),
            'missingRequiredDocuments'  => (array) ($readiness['missingRequiredDocuments'] ?? []),
            'packageSelected'           => (bool) ($readiness['packageSelected'] ?? false),
            'contractPrereqSummary'     => (array) ($readiness['summary'] ?? []),
            'contractUi'                => $contractUi,
        ]));
    }

    public function services(Request $request)
    {
        $base      = $this->baseData($request, 'services', 'Servisler', 'Paket, ek servisler ve değişiklik talepleri.');
        $guest     = $this->resolveStudentGuest($request);
        $packages  = collect($this->servicePackages());
        $allExtras = collect(config('service_packages.extra_services', []))->where('is_active', true);

        $serviceCategories = collect(config('service_packages.service_categories', []))
            ->map(fn ($cat) => array_merge($cat, [
                'services' => $allExtras->where('category', $cat['key'])->sortBy('sort_order')->values()->all(),
            ]))
            ->filter(fn ($cat) => count($cat['services']) > 0)
            ->values()
            ->all();

        return view('student.services', array_merge($base, [
            'guestApplication'    => $guest,
            'packages'            => $packages,
            'extraServiceOptions' => collect($this->extraServiceOptions()),
            'serviceCategories'   => $serviceCategories,
            'selectedPackageCode' => (string) ($guest?->selected_package_code ?? ''),
            'selectedPackageTitle'=> (string) ($guest?->selected_package_title ?? ''),
            'selectedPackagePrice'=> (string) ($guest?->selected_package_price ?? ''),
            'selectedExtras'      => is_array($guest?->selected_extra_services) ? $guest->selected_extra_services : [],
        ]));
    }

    // ── Profil & Ayarlar ─────────────────────────────────────────────────────

    public function profile(Request $request)
    {
        $base                  = $this->baseData($request, 'profile', 'Profil', 'Kisisel bilgilerinizi ve iletisim verinizi guncelleyin.');
        $guest                 = $this->resolveStudentGuest($request);
        $registrationSnapshots = collect();
        if ($guest && SchemaCache::hasTable('guest_registration_snapshots')) {
            $registrationSnapshots = GuestRegistrationSnapshot::query()
                ->where('guest_application_id', (int) $guest->id)
                ->orderByDesc('snapshot_version')
                ->limit(10)
                ->get(['id', 'snapshot_version', 'submitted_by_email', 'meta_json', 'submitted_at']);
        }

        return view('student.profile', array_merge($base, [
            'guestApplication'      => $guest,
            'applicationCountries'  => ApplicationCountryCatalog::options(),
            'registrationSnapshots' => $registrationSnapshots,
        ]));
    }

    public function settings(Request $request)
    {
        $base     = $this->baseData($request, 'settings', 'Ayarlar', 'Dil, bildirim ve hesap guvenligi ayarlari.');
        $user     = $request->user();
        $pref     = $user
            ? \App\Models\UserPortalPreference::where('user_id', $user->id)->where('portal_key', 'student')->first()
            : null;
        $prefData = $pref?->preferences_json ?? [];

        return view('student.settings', array_merge($base, [
            'guestApplication'  => $this->resolveStudentGuest($request),
            'preferredTimezone' => $prefData['timezone'] ?? 'Europe/Berlin',
            'preferredDateFmt'  => $prefData['date_format'] ?? 'DD.MM.YYYY',
        ]));
    }

    // ── Hesap Kasası ─────────────────────────────────────────────────────────

    public function vault(Request $request)
    {
        $base      = $this->baseData($request, 'vault', 'Hesap Kasam', 'Danışmanınız tarafından oluşturulan portal hesap bilgileri.');
        $studentId = (string) ($base['studentId'] ?? '');

        $vaults = collect();
        if ($studentId !== '') {
            $vaults = AccountVault::query()
                ->where('student_id', $studentId)
                ->where('is_visible_to_student', true)
                ->where('status', 'active')
                ->latest()
                ->limit(50)
                ->get(['id', 'service_name', 'service_label', 'account_url', 'account_email', 'account_username', 'notes', 'application_id']);
        }

        return view('student.vault', array_merge($base, [
            'vaults' => $vaults,
        ]));
    }

    public function revealVault(Request $request, AccountVault $vault): JsonResponse
    {
        $studentId = (string) ($request->user()?->student_id ?? '');
        if ($studentId === '') {
            $guest     = $this->resolveStudentGuest($request);
            $studentId = (string) ($guest?->converted_student_id ?? '');
        }

        if ($studentId === '' || (string) $vault->student_id !== $studentId || !$vault->is_visible_to_student) {
            abort(403);
        }

        $password = app(AccountVaultService::class)->revealPassword($vault, $request);

        app(EventLogService::class)->log(
            'vault.revealed',
            'account_vault',
            (string) $vault->id,
            'Vault şifresi görüntülendi: ' . $vault->service_name,
            ['ip' => $request->ip(), 'student_id' => $studentId],
            $request->user()?->email,
        );

        return response()->json(['password' => $password]);
    }

    // ── Contract Private Helpers ─────────────────────────────────────────────

    private function contractPrerequisites(?GuestApplication $guest): array
    {
        if (!$guest) {
            return [
                'formCompleted'            => false,
                'formDraftComplete'        => false,
                'formRequiredFilled'       => 0,
                'formRequiredTotal'        => 0,
                'docsCompleted'            => false,
                'missingRequiredDocuments' => [],
                'packageSelected'          => false,
                'summary'                  => ['missing' => ['Bağlı guest başvurusu bulunamadı.'], 'allReady' => false],
            ];
        }

        $companyId         = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $formSubmitted     = !empty($guest->registration_form_submitted_at);
        $formDraft         = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $requiredKeys      = app(GuestRegistrationFieldSchemaService::class)->requiredKeys($companyId);
        $formRequiredTotal = count($requiredKeys);
        $formRequiredFilled = collect($requiredKeys)
            ->filter(fn (string $k) => trim((string) ($formDraft[$k] ?? '')) !== '')
            ->count();
        $formDraftComplete  = $formRequiredTotal > 0 && $formRequiredFilled >= $formRequiredTotal;
        $packageSelected    = trim((string) ($guest->selected_package_code ?? '')) !== '';

        $requiredChecklist        = collect($this->requiredDocumentsByApplicationType(
            (string) ($guest->application_type ?? ''),
            $this->uploadedCategoryCodes($guest),
            'student'
        ));
        $missingRequiredDocuments = $requiredChecklist
            ->filter(fn (array $i) => (bool) ($i['is_required'] ?? false) && !(bool) ($i['uploaded'] ?? false))
            ->values()->all();
        $docsCompleted = collect($missingRequiredDocuments)->isEmpty();

        $missing = [];
        if (!$formSubmitted) {
            $missing[] = $formDraftComplete
                ? 'Ön kayıt formu doldurulmuş ancak gönderilmemiş.'
                : 'Ön kayıt formu zorunlu alanları tamamlanmamış / gönderilmemiş.';
        }
        if (!$docsCompleted) {
            $codes     = collect($missingRequiredDocuments)->pluck('document_code')->filter()->implode(', ');
            $missing[] = 'Zorunlu belgeler eksik' . ($codes !== '' ? ": {$codes}" : '.');
        }
        if (!$packageSelected) {
            $missing[] = 'Hizmet paketi seçilmemiş.';
        }

        return [
            'formCompleted'            => $formSubmitted,
            'formDraftComplete'        => $formDraftComplete,
            'formRequiredFilled'       => $formRequiredFilled,
            'formRequiredTotal'        => $formRequiredTotal,
            'docsCompleted'            => $docsCompleted,
            'missingRequiredDocuments' => $missingRequiredDocuments,
            'packageSelected'          => $packageSelected,
            'summary'                  => ['missing' => $missing, 'allReady' => empty($missing)],
        ];
    }

    private function contractUiState(?GuestApplication $guest, array $readiness): array
    {
        $normalizedStatus   = $this->normalizeContractStatus((string) ($guest?->contract_status ?? 'not_requested'));
        $allowedForAddendum = ['requested', 'signed_uploaded', 'rejected'];
        $canRequestAddendum = in_array($normalizedStatus, $allowedForAddendum, true);
        $inconsistencies    = [];

        if ($guest) {
            $hasSnapshot    = trim((string) ($guest->contract_snapshot_text ?? '')) !== '';
            $hasTemplate    = trim((string) ($guest->contract_template_code ?? '')) !== '' || (int) ($guest->contract_template_id ?? 0) > 0;
            $hasSignedFile  = trim((string) ($guest->contract_signed_file_path ?? '')) !== '';
            $hasRequestedAt = !empty($guest->contract_requested_at);
            $hasSignedAt    = !empty($guest->contract_signed_at);
            $hasApprovedAt  = !empty($guest->contract_approved_at);

            if (in_array($normalizedStatus, ['requested', 'signed_uploaded', 'approved', 'rejected'], true) && !$hasSnapshot) {
                $inconsistencies[] = 'Sözleşme durumu aktif görünüyor ancak sözleşme snapshot metni bulunamadı.';
            }
            if (in_array($normalizedStatus, ['requested', 'signed_uploaded', 'approved', 'rejected'], true) && !$hasTemplate) {
                $inconsistencies[] = 'Sözleşme durumu aktif görünüyor ancak template kodu kaydı eksik.';
            }
            if (in_array($normalizedStatus, ['requested', 'signed_uploaded', 'approved', 'rejected'], true) && !$hasRequestedAt) {
                $inconsistencies[] = 'Sözleşme durumu aktif görünüyor ancak talep zamanı (contract_requested_at) kaydı eksik.';
            }
            if (in_array($normalizedStatus, ['signed_uploaded', 'approved'], true) && !$hasSignedFile) {
                $inconsistencies[] = 'İmzalı yükleme/onay durumu var ancak imzalı dosya kaydı bulunamadı.';
            }
            if (in_array($normalizedStatus, ['signed_uploaded', 'approved'], true) && !$hasSignedAt) {
                $inconsistencies[] = 'İmzalı yükleme/onay durumu var ancak imzalı yükleme zamanı (contract_signed_at) kaydı eksik.';
            }
            if ($normalizedStatus === 'approved' && !$hasApprovedAt) {
                $inconsistencies[] = 'Sözleşme onaylı görünüyor ancak onay zamanı (contract_approved_at) kaydı eksik.';
            }
            if ($normalizedStatus === 'not_requested' && ($hasSnapshot || $hasTemplate || $hasSignedFile || $hasRequestedAt || $hasSignedAt || $hasApprovedAt)) {
                $inconsistencies[] = 'Sözleşme statüsü not_requested ancak eski sözleşme kayıtları sistemde duruyor.';
            }
        }

        if (!empty($inconsistencies)) {
            $canRequestAddendum = false;
        }

        return [
            'status'                   => $normalizedStatus,
            'canRequestAddendum'       => $canRequestAddendum,
            'inconsistencies'          => $inconsistencies,
            'canOpenSignedFile'        => $guest && trim((string) ($guest->contract_signed_file_path ?? '')) !== '',
            'showCurrentContractPanel' => $guest && trim((string) ($guest->contract_signed_file_path ?? '')) !== '',
            'showSnapshotPanel'        => $guest && trim((string) ($guest->contract_snapshot_text ?? '')) !== '',
            'stepSummary'              => [
                'form'    => ['ok' => (bool) ($readiness['formCompleted'] ?? false), 'draft_complete' => (bool) ($readiness['formDraftComplete'] ?? false)],
                'docs'    => ['ok' => (bool) ($readiness['docsCompleted'] ?? false)],
                'package' => ['ok' => (bool) ($readiness['packageSelected'] ?? false)],
            ],
        ];
    }

    private function normalizeContractStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        return match ($normalized) {
            'not_requested', 'pending_manager', 'requested', 'signed_uploaded',
            'approved', 'rejected', 'cancelled', 'reopen_requested' => $normalized,
            default => 'not_requested',
        };
    }

    private function ensureRegistrationDraftHydrated(GuestApplication $guest, int $companyId): array
    {
        $draft      = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $schemaKeys = collect(app(GuestRegistrationFieldSchemaService::class)->groups($companyId))
            ->flatMap(fn ($g) => $g['fields'] ?? [])
            ->map(fn ($f) => (string) ($f['key'] ?? ''))
            ->filter()
            ->values()
            ->all();

        if (empty($schemaKeys)) {
            return $draft;
        }

        $fallbackMap = [
            'first_name'         => (string) ($guest->first_name ?? ''),
            'last_name'          => (string) ($guest->last_name ?? ''),
            'email'              => (string) ($guest->email ?? ''),
            'phone'              => (string) ($guest->phone ?? ''),
            'gender'             => (string) ($guest->gender ?? ''),
            'application_country'=> (string) ($guest->application_country ?? ''),
            'application_type'   => (string) ($guest->application_type ?? ''),
            'application_city'   => (string) ($guest->target_city ?? ''),
            'target_city'        => (string) ($guest->target_city ?? ''),
            'target_term'        => (string) ($guest->target_term ?? ''),
            'language_level'     => (string) ($guest->language_level ?? ''),
            'additional_note'    => (string) ($guest->notes ?? ''),
        ];

        $changed = false;
        foreach ($schemaKeys as $key) {
            if (array_key_exists($key, $draft) && trim((string) $draft[$key]) !== '') {
                continue;
            }
            $fallback = $fallbackMap[$key] ?? '';
            if ($fallback !== '') {
                $draft[$key] = $fallback;
                $changed     = true;
            }
        }

        if ($changed) {
            $guest->forceFill([
                'registration_form_draft'          => $draft,
                'registration_form_draft_saved_at' => now(),
            ])->save();
        }

        return $draft;
    }
}
