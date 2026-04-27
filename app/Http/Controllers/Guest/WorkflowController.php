<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\DmMessage;
use App\Models\DmThread;
use App\Models\GuestApplication;
use App\Models\GuestFeedback;
use App\Models\GuestPaymentRequest;
use App\Models\GuestRegistrationSnapshot;
use App\Models\GuestOnboardingStep;
use App\Models\GuestReferral;
use App\Models\MessageTemplate;
use App\Services\FieldRuleEngine;
use App\Services\TaskAutomationService;
use App\Services\EventLogService;
use App\Services\GuestRegistrationFieldSchemaService;
use App\Services\ContractTemplateService;
use App\Services\GuestResolverService;
use App\Services\NotificationService;
use App\Rules\ValidFileMagicBytes;
use App\Http\Controllers\Concerns\UsesServicePackages;
use App\Http\Controllers\Guest\Concerns\GuestDocumentTrait;
use App\Http\Controllers\Guest\Concerns\GuestTicketTrait;
use App\Http\Controllers\Guest\Concerns\GuestContractTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class WorkflowController extends Controller
{
    use UsesServicePackages, GuestDocumentTrait, GuestTicketTrait, GuestContractTrait;

    public function __construct(
        private readonly FieldRuleEngine $fieldRuleEngine,
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly GuestRegistrationFieldSchemaService $registrationFieldSchemaService,
        private readonly ContractTemplateService $contractTemplateService,
        private readonly NotificationService $notificationService,
        private readonly GuestResolverService $guestResolver,
    ) {
    }

    /**
     * 3-Level form yönlendirme: guest'in registration_form_level kolonu Level 1
     * ya da Level 2 olduğunu belirler. Aday seviyesinde Level 1 catalog (27 field)
     * kullanılır; öğrenciye geçtiyse Level 2 (88 field) tam form.
     */
    private function resolveFormLevel(\App\Models\GuestApplication $guest): int
    {
        $status = (string) ($guest->registration_form_level ?? 'level_1_pending');
        return in_array($status, ['level_2_pending', 'level_2_done'], true) ? 2 : 1;
    }

    public function autoSaveRegistration(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');
        if (!$this->ensureDraftNotStale($request, $guest)) {
            return redirect()
                ->route('guest.registration.form')
                ->withErrors(['draft_conflict' => 'Form baska bir sekmede guncellenmis. Sayfayi yenileyip tekrar deneyin.'])
                ->withInput();
        }
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $formLevel = $this->resolveFormLevel($guest);
        $draftMap = $this->registrationFieldSchemaService->sanitizePayloadByLevel($request->all(), $formLevel, $companyId);

        // Kullanıcı girdisi → fill() ile $fillable koruması altında yazar.
        $guest->fill([
            'first_name'                      => trim((string) ($draftMap['first_name'] ?? '')) ?: $guest->first_name,
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
        ]);
        // @internal: Sunucu tarafından üretilen durum mesajı — kullanıcı girdisi değil.
        $guest->status_message = 'On kayit formu taslak olarak kaydedildi.';
        $guest->save();

        return redirect()->route('guest.registration.form')->with('status', 'Taslak kaydedildi.');
    }

    public function ajaxSaveRegistration(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return response()->json(['ok' => false, 'error' => 'Guest bulunamadi'], 404);
        }
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $formLevel = $this->resolveFormLevel($guest);
        $draftMap = $this->registrationFieldSchemaService->sanitizePayloadByLevel($request->all(), $formLevel, $companyId);

        // Kullanıcı girdisi → fill() ile $fillable koruması altında yazar.
        $guest->fill([
            'first_name'                      => trim((string) ($draftMap['first_name'] ?? '')) ?: $guest->first_name,
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
        ]);
        // @internal: Sunucu tarafından üretilen durum mesajı — kullanıcı girdisi değil.
        $guest->status_message = 'On kayit formu taslak olarak kaydedildi.';
        $guest->save();

        $guest->refresh();
        return response()->json([
            'ok' => true,
            'saved_at' => optional($guest->registration_form_draft_saved_at)->toIso8601String(),
        ]);
    }

    public function submitRegistration(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');
        if (!$this->ensureDraftNotStale($request, $guest)) {
            return redirect()
                ->route('guest.registration.form')
                ->withErrors(['draft_conflict' => 'Form baska bir sekmede guncellenmis. Sayfayi yenileyip tekrar deneyin.'])
                ->withInput();
        }
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $formLevel = $this->resolveFormLevel($guest);
        $payload = $this->registrationFieldSchemaService->sanitizePayloadByLevel($request->all(), $formLevel, $companyId);

        // skipKeys yalnızca Level 2'de anlamlı — Level 1'de education_level sorulmuyor
        $skipKeys = $formLevel === 2
            ? array_merge(
                $this->registrationFieldSchemaService->educationSkippedKeys($payload),
                $this->registrationFieldSchemaService->spouseSkippedKeys($payload),
            )
            : [];

        // Level 1'de higher_education_status='not_started' iken üniversite alanları gizli
        if ($formLevel === 1) {
            $heStatus = strtolower(trim((string) ($payload['higher_education_status'] ?? '')));
            if ($heStatus !== 'enrolled') {
                $skipKeys = array_merge($skipKeys, ['university_year']);
            }
        }

        // Field-specific hata mesajı için catalog'dan label + tip lookup
        $allFields = $this->registrationFieldSchemaService->flatFieldsByLevel($formLevel, $companyId);
        $fieldByKey = [];
        foreach ($allFields as $f) {
            $fieldByKey[$f['key'] ?? ''] = $f;
        }

        $missingErrors = [];
        foreach ($this->registrationFieldSchemaService->requiredKeysByLevel($formLevel, $companyId) as $key) {
            if (in_array($key, $skipKeys, true)) {
                continue;
            }
            $val = $payload[$key] ?? null;
            $isEmpty = is_array($val) ? empty($val) : ($val === null || trim((string) $val) === '');
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
        // B13: eğitim tarihleri mantıklı sırada mı + B15 parent dob kontrolü
        foreach ($this->registrationFieldSchemaService->educationDateOrderErrors($payload) as $f => $err) {
            $missingErrors[$f] = $err;
        }
        if (!empty($missingErrors)) {
            return redirect()
                ->route('guest.registration.form')
                ->withErrors($missingErrors)
                ->withInput();
        }

        $existingDraft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $mergedDraft = array_merge($existingDraft, $payload);
        foreach ($this->conditionalRequiredErrors($mergedDraft) as $f => $err) {
            $missingErrors[$f] = $err;
        }
        if (!empty($missingErrors)) {
            return redirect()
                ->route('guest.registration.form')
                ->withErrors($missingErrors)
                ->withInput();
        }

        $ruleResults = $this->fieldRuleEngine->evaluate(
            targetForm: 'guest_registration',
            formData: $mergedDraft,
            studentType: (string) ($guest->application_type ?? ''),
            studentId: null,
            guestId: (string) $guest->id,
            actor: (string) optional($request->user())->email
        );
        $blocked = collect($ruleResults)->where('severity', 'block')->values();
        if ($blocked->isNotEmpty()) {
            $first = (array) $blocked->first();
            $msg = (string) ($first['message'] ?? 'Bu kayit icin yetkili onayi gerekiyor.');
            return redirect()
                ->route('guest.registration.form')
                ->withErrors(['rule_engine' => $msg]);
        }

        $warnings = collect($ruleResults)->where('severity', 'warning')->values();

        // Kullanıcı girdisi → fill() ile $fillable koruması altında yazar.
        $guest->fill([
            'first_name'                     => trim((string) ($payload['first_name'] ?? '')) ?: $guest->first_name,
            'last_name'                      => trim((string) ($payload['last_name'] ?? '')) ?: $guest->last_name,
            'phone'                          => trim((string) ($payload['phone'] ?? '')) ?: $guest->phone,
            'gender'                         => trim((string) ($payload['gender'] ?? '')) ?: $guest->gender,
            'application_country'            => trim((string) ($payload['application_country'] ?? '')) ?: $guest->application_country,
            'application_type'               => trim((string) ($payload['application_type'] ?? '')) ?: $guest->application_type,
            'target_city'                    => trim((string) ($payload['application_city'] ?? '')),
            'target_term'                    => trim((string) ($payload['university_start_target_date'] ?? '')),
            'language_level'                 => trim((string) ($payload['german_level'] ?? '')),
            'notes'                          => trim((string) ($payload['additional_note'] ?? '')) ?: $guest->notes,
            'registration_form_draft'        => $mergedDraft,
            'registration_form_submitted_at' => now(),
        ]);
        // @internal: Sunucu state machine değerleri — kullanıcı girdisi değil, $fillable dışındadır.
        $guest->lead_status    = 'meeting_scheduled';
        $guest->status_message = $warnings->isNotEmpty()
            ? 'Form gonderildi (uyari ile). Danisman incelemesi bekleniyor.'
            : 'On kayit formu gonderildi. Danisman incelemesi bekleniyor.';

        // 3-Level state transition: Level 1 submit → 'level_1_done', sözleşme akışı tetiklenebilir
        if ($formLevel === 1) {
            $guest->registration_form_level = 'level_1_done';
        }

        $guest->save();
        $this->createRegistrationSnapshot($guest, $request, $mergedDraft, $warnings->count());
        $this->taskAutomationService->ensureGuestRegistrationReviewTask($guest);

        // Timeline: "Kayıt Formu Tamamla" milestone'unu otomatik işaretle
        app(\App\Services\GuestTimelineService::class)->complete($guest, 'form_complete');

        $status = $warnings->isNotEmpty()
            ? 'Form gonderildi. Kural uyari sayisi: '.$warnings->count()
            : 'Form gonderildi.';
        return redirect()->route('guest.registration.documents')->with('status', $status);
    }

    public function registrationFormPdf(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        // 3-Level form yönlendirme: aday seviyesindeyken (level_1_*) Level 1
        // groupları gösterilir; student'a geçtiyse (level_2_*) tam Level 2.
        // PortalController::registrationForm ile aynı mantık.
        $formLevelStatus = (string) ($guest->registration_form_level ?? 'level_1_pending');
        $isLevel2 = in_array($formLevelStatus, ['level_2_pending', 'level_2_done'], true);
        $formLevel = $isLevel2 ? 2 : 1;

        $groups = $this->registrationFieldSchemaService->groupsByLevel($formLevel, $companyId);
        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('guest.registration-form-pdf', compact('guest', 'groups', 'draft', 'formLevel'));
        $fileName = 'Kayit_Formu_L' . $formLevel . '_' . ($guest->first_name ?? '') . '_' . ($guest->last_name ?? '') . '_' . now()->format('Ymd') . '.pdf';

        if ($request->query('download')) {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }

    public function selectPackage(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $data = $request->validate([
            'package_code' => ['required', 'string', 'in:pkg_basic,pkg_plus,pkg_premium'],
        ]);

        $packages = collect($this->servicePackages())->keyBy('code');
        $pkg = (array) $packages->get(trim((string) $data['package_code']));

        // Yeni pakette dahil olan ek hizmetleri, seçili ekstralardan kaldır
        $includedExtras = is_array($pkg['included_extras'] ?? null) ? $pkg['included_extras'] : [];
        $currentExtras = is_array($guest->selected_extra_services) ? $guest->selected_extra_services : [];
        if (!empty($includedExtras) && !empty($currentExtras)) {
            $currentExtras = collect($currentExtras)
                ->reject(fn ($x) => in_array((string) ($x['code'] ?? ''), $includedExtras, true))
                ->values()
                ->all();
        }

        $guest->fill([
            'selected_package_code'   => (string) ($pkg['code'] ?? ''),
            'selected_package_title'  => (string) ($pkg['title'] ?? ''),
            'selected_package_price'  => (string) ($pkg['price'] ?? ''),
            'selected_extra_services' => $currentExtras,
            'package_selected_at'     => now(),
        ]);
        $guest->status_message = 'Hizmet paketi seçildi. Sözleşme aşaması bekleniyor.'; // @internal
        $guest->save();

        // Timeline: "Paket Seç" milestone'unu otomatik işaretle
        app(\App\Services\GuestTimelineService::class)->complete($guest, 'package_select');

        return redirect()->route('guest.services')->with('status', 'Paket secimi kaydedildi.');
    }

    public function confirmPackage(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');
        abort_if(trim((string) ($guest->selected_package_code ?? '')) === '', 422, 'Oncelikle bir paket secmelisiniz.');

        $guest->forceFill([
            'package_selected_at' => now(),
            'status_message'      => 'Paket secimi kesinlestirildi. Sozlesme asamasi bekleniyor.',
        ])->save();

        return redirect()->route('guest.services')->with('package_confirmed', true);
    }

    public function addExtraService(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $data = $request->validate([
            'extra_code' => ['required', 'string', 'in:vip_meeting,blocked_account_support,visa_file_review,airport_pickup,accommodation_support,uni_dept_selection,uni_assist_apply,uni_application_tracking,visa_consulate_appointment,visa_file_preparation,visa_intent_letter,visa_interview_orient,finance_blocked_account,finance_health_insurance,accom_arrangement,accom_dorm_apply,accom_info,abroad_deutschlandticket,abroad_phone_line,abroad_airport_pickup,abroad_bank_account,abroad_residence_reg,abroad_foreigners_office,abroad_health_activate,abroad_life_seminar'],
        ]);

        $extraOptions = collect($this->extraServiceOptions())->keyBy('code');
        $extras = is_array($guest->selected_extra_services) ? $guest->selected_extra_services : [];
        $code = trim((string) $data['extra_code']);

        // Seçili pakette dahil olan hizmeti tekrar eklemeyi engelle
        $selectedPkg = collect(config('service_packages.packages', []))->firstWhere('code', $guest->selected_package_code);
        $includedExtras = is_array($selectedPkg['included_extras'] ?? null) ? $selectedPkg['included_extras'] : [];
        if (in_array($code, $includedExtras, true)) {
            return redirect()->route('guest.services')->with('status', 'Bu hizmet sectiginiz pakete zaten dahil.');
        }

        $meta = (array) $extraOptions->get($code);
        $exists = collect($extras)->contains(fn ($x) => (string) ($x['code'] ?? '') === $code);
        if (!$exists) {
            $extras[] = [
                'code' => (string) ($meta['code'] ?? $code),
                'title' => (string) ($meta['title'] ?? $code),
                'added_at' => now()->toDateTimeString(),
            ];
        }

        // Güvenlik: kod başına tekil olduğundan emin ol
        $extras = collect($extras)->unique('code')->values()->all();

        $guest->fill(['selected_extra_services' => $extras]);
        $guest->status_message = 'Ek servis guncellendi.'; // @internal
        $guest->save();

        return redirect()->route('guest.services')->with('status', 'Ek servis eklendi.');
    }

    public function removeExtraService(Request $request, string $extraCode)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $code = trim((string) $extraCode);
        $extras = is_array($guest->selected_extra_services) ? $guest->selected_extra_services : [];
        $filtered = collect($extras)
            ->reject(fn ($x) => (string) ($x['code'] ?? '') === $code)
            ->values()
            ->all();

        $guest->fill(['selected_extra_services' => $filtered]);
        $guest->status_message = 'Ek servis listesi guncellendi.'; // @internal
        $guest->save();

        return redirect()->route('guest.services')->with('status', 'Ek servis kaldirildi.');
    }

    public function updateSettings(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $data = $request->validate([
            'preferred_locale' => ['required', 'in:tr,de,en'],
            'notifications_enabled' => ['nullable', 'boolean'],
        ]);

        $guest->fill([
            'preferred_locale'       => (string) $data['preferred_locale'],
            'communication_language' => (string) $data['preferred_locale'],
            'notifications_enabled'  => (bool) ($data['notifications_enabled'] ?? false),
        ]);
        $guest->status_message = 'Ayarlar guncellendi.'; // @internal
        $guest->save();

        return redirect()->route('guest.settings')->with('status', 'Ayarlar kaydedildi.');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Oturum bulunamadi.');

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->max(128)],
        ]);

        if (!Hash::check((string) $data['current_password'], (string) $user->password)) {
            return redirect()->route('guest.settings')->withErrors(['current_password' => 'Mevcut sifre hatali.']);
        }

        $user->password = Hash::make((string) $data['new_password']);
        $user->save();

        // Şifre değişince tüm diğer aktif oturumları (cookie/token) geçersiz kıl.
        Auth::logoutOtherDevices((string) $data['new_password']);

        return redirect()->route('guest.settings')->with('status', 'Sifre guncellendi.');
    }

    public function logoutAllDevices(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check((string) $data['password'], (string) $user->password)) {
            return redirect()->route('guest.settings')->withErrors(['password' => 'Şifre hatalı.']);
        }

        Auth::logoutOtherDevices((string) $data['password']);

        return redirect()->route('guest.settings')->with('success', 'Diğer tüm oturumlar kapatıldı.');
    }

    public function uploadProfilePhoto(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $data = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', new ValidFileMagicBytes()],
        ]);

        $file    = $data['profile_photo'];
        $baseName = 'profile_' . now()->format('Ymd_His');

        $oldPath = trim((string) ($guest->profile_photo_path ?? ''));
        if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = app(\App\Services\ImageOptimizationService::class)
            ->optimizeProfilePhoto($file, "guest-profile/{$guest->id}", $baseName);

        $guest->fill(['profile_photo_path' => $path]);
        $guest->status_message = 'Profil fotografi guncellendi.'; // @internal
        $guest->save();

        return redirect()->route('guest.profile')->with('status', 'Profil fotografi yuklendi.');
    }

    public function updateProfile(Request $request)
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydi bulunamadi.');

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'gender' => ['nullable', 'in:kadin,erkek,belirtmek_istemiyorum'],
            'application_country' => ['nullable', 'string', 'max:80'],
            'communication_language' => ['nullable', 'string', 'max:16'],
            'target_city' => ['nullable', 'string', 'max:100'],
            'target_term' => ['nullable', 'string', 'max:60'],
            'language_level' => ['nullable', 'string', 'max:32'],
            'language_skills' => ['nullable', 'array', 'max:10'],
            'language_skills.*.lang' => ['required', 'string', 'max:20'],
            'language_skills.*.level' => ['required', 'string', 'max:20'],
            'language_skills.*.custom' => ['nullable', 'string', 'max:60'],
        ]);

        // Temizle: boş veya geçersiz satırları filtrele
        $validLangs = ['tr', 'de', 'en', 'fr', 'es', 'it', 'ar', 'other'];
        $validLevels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'native'];
        $languageSkills = collect($data['language_skills'] ?? [])
            ->filter(fn ($row) => in_array($row['lang'] ?? '', $validLangs, true)
                               && in_array($row['level'] ?? '', $validLevels, true))
            ->map(fn ($row) => [
                'lang'   => (string) $row['lang'],
                'level'  => (string) $row['level'],
                'custom' => ($row['lang'] === 'other') ? trim((string) ($row['custom'] ?? '')) : null,
            ])
            ->values()
            ->all();

        $guest->fill([
            'first_name'             => trim((string) $data['first_name']),
            'last_name'              => trim((string) $data['last_name']),
            'phone'                  => trim((string) ($data['phone'] ?? '')) ?: null,
            'gender'                 => trim((string) ($data['gender'] ?? '')) ?: null,
            'application_country'    => trim((string) ($data['application_country'] ?? '')) ?: null,
            'communication_language' => trim((string) ($data['communication_language'] ?? '')) ?: null,
            'target_city'            => trim((string) ($data['target_city'] ?? '')) ?: null,
            'target_term'            => trim((string) ($data['target_term'] ?? '')) ?: null,
            'language_level'         => trim((string) ($data['language_level'] ?? '')) ?: null,
            'language_skills'        => !empty($languageSkills) ? $languageSkills : null,
        ]);
        $guest->status_message = 'Profil bilgileri guncellendi.'; // @internal
        $guest->save();

        $user = $request->user();
        if ($user) {
            $user->name = trim((string) $data['first_name'] . ' ' . (string) $data['last_name']);
            $user->save();
        }

        return redirect()->route('guest.profile')->with('status', 'Profil bilgileri kaydedildi.');
    }

    private function resolveGuest(Request $request): ?GuestApplication
    {
        return $this->guestResolver->resolve($request);
    }

    /**
     * @param array<string,mixed> $vars
     */
    private function queueTemplateNotification(
        GuestApplication $guest,
        string $category,
        string $sourceType,
        string $sourceId,
        array $vars = []
    ): void {
        $template = MessageTemplate::query()
            ->where('category', $category)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        if (!$template) {
            return;
        }

        $name = trim((string) ($guest->first_name ?? '') . ' ' . (string) ($guest->last_name ?? ''));
        $payloadVars = array_merge([
            'guest_name' => $name,
            'guest_email' => (string) ($guest->email ?? ''),
        ], $vars);

        $this->notificationService->send([
            'template_id'     => (int) $template->id,
            'channel'         => (string) $template->channel,
            'category'        => (string) $template->category,
            'student_id'      => (string) ($guest->converted_student_id ?? ''),
            'company_id'      => (int) ($guest->company_id ?: 0),
            'recipient_email' => (string) ($guest->email ?? ''),
            'recipient_phone' => (string) ($guest->phone ?? ''),
            'recipient_name'  => $name,
            'body'            => '',
            'variables'       => $payloadVars,
            'source_type'     => $sourceType,
            'source_id'       => $sourceId,
            'triggered_by'    => 'system:guest_workflow',
        ]);
    }

    private function ensureDraftNotStale(Request $request, GuestApplication $guest): bool
    {
        $client = trim((string) $request->input('draft_saved_at', ''));
        if ($client === '') {
            return true;
        }
        $server = $guest->registration_form_draft_saved_at;
        if (!$server) {
            return true;
        }

        // strtotime() "next Tuesday" gibi keyfi giriş kabul eder — güvenlik açığı.
        // Yalnızca ISO 8601 formatını (örn: "2026-03-21T14:05:00Z") kabul et.
        try {
            $clientTs = Carbon::createFromFormat(DATE_ATOM, $client)->getTimestamp();
        } catch (InvalidFormatException) {
            return true; // Geçersiz format → çakışma yok kabul et
        }

        return $server->getTimestamp() <= ($clientTs + 2);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,string>
     */
    private function conditionalRequiredErrors(array $payload): array
    {
        $rules = [
            'passport_number' => [
                'when_key' => 'has_passport',
                'when_values' => ['yes'],
                'message' => 'Pasaportunuz var ise seri numarasi zorunludur.',
            ],
            'german_course_name' => [
                'when_key' => 'is_enrolled_german_course',
                'when_values' => ['yes'],
                'message' => 'Almanca kursuna kayitliysaniz kurs adi zorunludur.',
            ],
            'teacher_reference_contact' => [
                'when_key' => 'has_teacher_reference',
                'when_values' => ['yes'],
                'message' => 'Referans secildi ise referans iletisim bilgisi zorunludur.',
            ],
            'germany_stay_date_range' => [
                'when_key' => 'lived_in_germany_before',
                'when_values' => ['yes'],
                'message' => 'Almanya gecmisi secildi ise tarih araligi zorunludur.',
            ],
            'germany_last_residences' => [
                'when_key' => 'lived_in_germany_before',
                'when_values' => ['yes'],
                'message' => 'Almanya gecmisi secildi ise son ikamet bilgileri zorunludur.',
            ],
            'children_count' => [
                'when_key' => 'has_children',
                'when_values' => ['yes'],
                'message' => 'Çocuğunuz varsa kaç çocuğunuz olduğunu belirtin.',
            ],
        ];

        $errors = [];
        // children_count yalnızca evli + çocuk var ise zorunlu — marital_status kontrolü
        $isMarried = strtolower(trim((string) ($payload['marital_status'] ?? ''))) === 'married';
        foreach ($rules as $field => $cfg) {
            // children_count için marital_status !== married ise atla
            if ($field === 'children_count' && !$isMarried) {
                continue;
            }
            $when = strtolower(trim((string) ($payload[$cfg['when_key']] ?? '')));
            $expects = array_map(static fn ($v) => strtolower(trim((string) $v)), (array) ($cfg['when_values'] ?? []));
            if (!in_array($when, $expects, true)) {
                continue;
            }
            $val = trim((string) ($payload[$field] ?? ''));
            if ($val === '') {
                $errors[$field] = (string) ($cfg['message'] ?? 'Bu alan zorunludur.');
            }
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function createRegistrationSnapshot(
        GuestApplication $guest,
        Request $request,
        array $payload,
        int $warningCount = 0
    ): void {
        $next = (int) GuestRegistrationSnapshot::query()
            ->where('guest_application_id', (int) $guest->id)
            ->max('snapshot_version');
        $version = max(1, $next + 1);

        GuestRegistrationSnapshot::query()->create([
            'guest_application_id' => (int) $guest->id,
            'snapshot_version' => $version,
            'submitted_by_email' => (string) optional($request->user())->email,
            'payload_json' => $payload,
            'meta_json' => [
                'application_type' => (string) ($guest->application_type ?? ''),
                'warnings_count' => $warningCount,
                'submitted_via' => 'guest_portal',
            ],
            'submitted_at' => now(),
        ]);
    }

    // ── 2.1 Onboarding ───────────────────────────────────────────────────────

    public function completeOnboardingStep(Request $request, string $stepCode): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest || !in_array($stepCode, GuestOnboardingStep::STEPS, true)) {
            return response()->json(['ok' => false], 400);
        }
        GuestOnboardingStep::updateOrCreate(
            ['guest_application_id' => $guest->id, 'step_code' => $stepCode],
            ['completed_at' => now(), 'skipped_at' => null]
        );
        $done = GuestOnboardingStep::where('guest_application_id', $guest->id)
            ->where(fn ($q) => $q->whereNotNull('completed_at')->orWhereNotNull('skipped_at'))
            ->count();
        return response()->json(['ok' => true, 'done' => $done, 'total' => count(GuestOnboardingStep::STEPS)]);
    }

    public function skipOnboardingStep(Request $request, string $stepCode): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest || !in_array($stepCode, GuestOnboardingStep::STEPS, true)) {
            return response()->json(['ok' => false], 400);
        }
        GuestOnboardingStep::updateOrCreate(
            ['guest_application_id' => $guest->id, 'step_code' => $stepCode],
            ['skipped_at' => now(), 'completed_at' => null]
        );
        $done = GuestOnboardingStep::where('guest_application_id', $guest->id)
            ->where(fn ($q) => $q->whereNotNull('completed_at')->orWhereNotNull('skipped_at'))
            ->count();
        return response()->json(['ok' => true, 'done' => $done, 'total' => count(GuestOnboardingStep::STEPS)]);
    }

    // ── 2.5 Referral ─────────────────────────────────────────────────────────

    public function createReferralLink(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return response()->json(['ok' => false], 404);
        }
        $existing = GuestReferral::where('referrer_guest_id', $guest->id)->first();
        if ($existing) {
            return response()->json(['ok' => true, 'code' => $existing->referral_code, 'url' => url("/apply?ref={$existing->referral_code}")]);
        }
        $code = 'REF-' . $guest->id . '-' . strtoupper(Str::random(6));
        GuestReferral::create(['referrer_guest_id' => $guest->id, 'referral_code' => $code]);
        return response()->json(['ok' => true, 'code' => $code, 'url' => url("/apply?ref={$code}")]);
    }

    public function referralStats(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return response()->json(['ok' => false], 404);
        }
        return response()->json([
            'ok'          => true,
            'total_sent'  => GuestReferral::where('referrer_guest_id', $guest->id)->count(),
            'registered'  => GuestReferral::where('referrer_guest_id', $guest->id)->where('status', 'registered')->count(),
            'converted'   => GuestReferral::where('referrer_guest_id', $guest->id)->where('status', 'converted')->count(),
            'referral_code' => GuestReferral::where('referrer_guest_id', $guest->id)->value('referral_code'),
        ]);
    }

    // ── 2.3 Chat Poll ─────────────────────────────────────────────────────────

    public function pollMessages(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return response()->json(['messages' => [], 'unread' => 0]);
        }
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $thread = DmThread::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('thread_type', 'guest')
            ->where('guest_application_id', (int) $guest->id)
            ->first(['id']);

        if (!$thread) {
            return response()->json(['messages' => [], 'unread' => 0]);
        }
        $after = (int) ($request->query('after') ?? $request->query('after_id', 0));
        $msgs = DmMessage::query()
            ->where('thread_id', $thread->id)
            ->when($after > 0, fn ($q) => $q->where('id', '>', $after))
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'sender_role', 'sender_user_id', 'message', 'is_read_by_participant', 'created_at'])
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'id' => $m->id,
                'sender_role' => $m->sender_role,
                'sender_name' => '',
                'message' => $m->message,
                'is_read' => (bool) $m->is_read_by_participant,
                'created_at' => $m->created_at,
            ]);

        $unread = DmMessage::where('thread_id', $thread->id)
            ->where('sender_role', '!=', 'guest')
            ->where('is_read_by_participant', false)
            ->count();

        $isAdvisorTyping = Cache::has("dm_typing_{$thread->id}_advisor");

        return response()->json(['messages' => $msgs, 'unread' => $unread, 'is_advisor_typing' => $isAdvisorTyping]);
    }

    // ── 2.3a Typing indicator ──────────────────────────────────────────────────

    public function markTyping(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveGuest($request);
        if (!$guest) {
            return response()->json(['ok' => false]);
        }

        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $thread = DmThread::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('thread_type', 'guest')
            ->where('guest_application_id', (int) $guest->id)
            ->first(['id']);

        if ($thread) {
            Cache::put("dm_typing_{$thread->id}_guest", true, 8);
        }

        return response()->json(['ok' => true]);
    }

    // ── Ödeme Talebi ──────────────────────────────────────────────────────────

    public function requestPayment(Request $request): \Illuminate\Http\RedirectResponse
    {
        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 404, 'Guest kaydı bulunamadı.');

        $data = $request->validate([
            'payment_method' => ['required', 'in:bank_transfer,credit_card'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $pkgConfig = config('guest_registration_form.packages', []);
        $selCode   = trim((string) ($guest->selected_package_code ?? ''));
        abort_if($selCode === '', 422, 'Önce bir paket seçin.');

        $pkg = collect($pkgConfig)->firstWhere('code', $selCode);
        if (!$pkg) {
            return redirect()->route('guest.services')->withErrors(['payment' => 'Seçili paket bulunamadı.']);
        }

        $priceStr  = preg_replace('/[^0-9.]/', '', str_replace(['.', ','], ['', '.'], (string) ($pkg['price'] ?? '0')));
        $amountEur = (float) $priceStr;

        GuestPaymentRequest::create([
            'company_id'           => $guest->company_id,
            'guest_application_id' => $guest->id,
            'package_code'         => $selCode,
            'package_title'        => (string) ($pkg['title'] ?? $selCode),
            'amount_eur'           => $amountEur,
            'payment_method'       => $data['payment_method'],
            'status'               => 'pending',
            'notes'                => $data['notes'] ?? null,
        ]);

        $this->eventLogService->log(
            eventType: 'guest_payment_requested',
            entityType: 'guest_payment_request',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} ödeme talebi oluşturdu.",
            meta: ['package' => $selCode, 'method' => $data['payment_method']],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        return redirect()->route('guest.services')->with('status', 'Ödeme talebiniz alındı. Danışmanınız en kısa sürede size dönecek.');
    }

    public function storeFeedback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'feedback_type' => ['required', 'string', 'in:general,process,advisor,portal'],
            'process_step'  => ['nullable', 'string', 'max:80'],
            'rating'        => ['required', 'integer', 'min:1', 'max:5'],
            'comment'       => ['nullable', 'string', 'max:2000'],
        ]);

        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 422, 'Başvuru kaydı bulunamadı.');

        GuestFeedback::create([
            'guest_application_id' => $guest->id,
            'company_id'           => (int) ($guest->company_id ?: 0),
            'feedback_type'        => $data['feedback_type'],
            'process_step'         => $data['process_step'] ?? null,
            'rating'               => (int) $data['rating'],
            'comment'              => $data['comment'] ?? null,
        ]);

        return redirect()->route('guest.feedback')->with('success', 'Geri bildiriminiz alındı. Teşekkürler!');
    }

    public function storeNps(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'nps_score' => ['required', 'integer', 'min:0', 'max:10'],
            'comment'   => ['nullable', 'string', 'max:1000'],
        ]);

        $guest = $this->resolveGuest($request);
        abort_if(!$guest, 422);

        GuestFeedback::create([
            'guest_application_id' => $guest->id,
            'company_id'           => (int) ($guest->company_id ?: 0),
            'feedback_type'        => 'nps',
            'nps_score'            => (int) $data['nps_score'],
            'comment'              => $data['comment'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }
}
