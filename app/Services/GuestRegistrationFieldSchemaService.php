<?php

namespace App\Services;

use App\Models\GuestRegistrationField;
use App\Support\ApplicationCountryCatalog;
use App\Support\GuestRegistrationFormCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class GuestRegistrationFieldSchemaService
{
    /**
     * Level filtreli grup listesi.
     *
     * Level 1 form'u sadece Catalog'tan gelir — guest_registration_fields DB
     * tablosunda level metadatası yok, custom field'lar Level 2 için.
     * Level 2 davranışı değişmez (DB → fallback Catalog).
     *
     * @return array<int,array<string,mixed>>
     */
    public function groupsByLevel(int $level, int $companyId = 0): array
    {
        if ($level <= 1) {
            return GuestRegistrationFormCatalog::groupsByLevel(1);
        }
        return $this->groups($companyId);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function groups(int $companyId = 0): array
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return GuestRegistrationFormCatalog::groups();
        }

        $this->ensureDefaults($companyId);

        // ⚠ KAPSAM DIŞI OKUNUYOR. `GuestRegistrationField` firma kapsamlı;
        // kapsam açıkken `where('company_id', 0)` ortak şablonu HİÇ bulamaz
        // (kapsam "yalnızca kendi firman" diye ekliyor). O hâlde firma sabit
        // PHP kataloğuna düşer ve merkezden yapılan form değişikliği ona
        // ULAŞMAZ — düzeltmek istediğimiz sorunun ta kendisi.
        //
        // Hangi satırların okunacağı zaten aşağıdaki açık `company_id`
        // koşuluyla belirleniyor; kapsam burada koruma sağlamıyor, engel
        // oluyor.
        $rows = GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId > 0 ? $companyId : 0)
            ->where('is_active', true)
            ->orderBy('section_order')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty() && $companyId > 0) {
            $rows = GuestRegistrationField::query()
                ->withoutGlobalScope('company')
                ->where('company_id', 0)
                ->where('is_active', true)
                ->orderBy('section_order')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }
        if ($rows->isEmpty()) {
            return GuestRegistrationFormCatalog::groups();
        }

        return $rows
            ->groupBy('section_key')
            ->map(function (Collection $sectionRows): array {
                $first = $sectionRows->first();
                return [
                    'section_key' => (string) ($first->section_key ?? ''),
                    'title' => (string) ($first->section_title ?? 'Bolum'),
                    'section_order' => (int) ($first->section_order ?? 100),
                    'fields' => $sectionRows->map(fn (GuestRegistrationField $row) => [
                        'key' => (string) $row->field_key,
                        'label' => (string) $row->label,
                        'type' => $this->resolveFieldType($row),
                        'required' => (bool) $row->is_required,
                        'max' => $row->max_length ?: 255,
                        'placeholder' => (string) ($row->placeholder ?? ''),
                        'help_text' => (string) ($row->help_text ?? ''),
                        'sort_order' => (int) ($row->sort_order ?? 100),
                        'options' => $this->resolveFieldOptions($row),
                    ])->values()->all(),
                ];
            })
            ->sortBy('section_order')
            ->values()
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function flatFields(int $companyId = 0): array
    {
        return $this->flatFieldsByLevel(2, $companyId);
    }

    /**
     * Level filtreli düz field listesi.
     * @return array<int,array<string,mixed>>
     */
    public function flatFieldsByLevel(int $level, int $companyId = 0): array
    {
        return collect($this->groupsByLevel($level, $companyId))
            ->flatMap(fn (array $g) => (array) ($g['fields'] ?? []))
            ->values()
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    public function sanitizePayload(array $input, int $companyId = 0): array
    {
        return $this->sanitizePayloadByLevel($input, 2, $companyId);
    }

    /**
     * Level filtreli sanitize. Level 1'de sadece Level 1 field'ları kabul edilir.
     * @return array<string,mixed>
     */
    public function sanitizePayloadByLevel(array $input, int $level, int $companyId = 0): array
    {
        return GuestRegistrationFormCatalog::sanitizePayloadByFields($input, $this->flatFieldsByLevel($level, $companyId));
    }

    /**
     * @return array<int,string>
     */
    public function requiredKeys(int $companyId = 0): array
    {
        return $this->requiredKeysByLevel(2, $companyId);
    }

    /**
     * Level filtreli zorunlu key listesi.
     * Conditional field'lar (passport_number, german_course_name vb.) çıkarılır —
     * bunlar başka koşullu kontrollerde tetiklenir.
     * Level 1 için ek koşullu çıkarımlar: university_*, guarantor_*,
     * accommodation_contact_city, german_certificate_*.
     * @return array<int,string>
     */
    public function requiredKeysByLevel(int $level, int $companyId = 0): array
    {
        $keys = GuestRegistrationFormCatalog::requiredKeysByFields($this->flatFieldsByLevel($level, $companyId));

        // Tüm seviyelerde koşullu olduğu için her zaman çıkarılan field'lar
        $conditional = [
            'passport_number',
            'german_course_name',
            'teacher_reference_contact',
            'germany_stay_date_range',
            'germany_stay_from',
            'germany_stay_to',
            'germany_last_residences',
            'germany_references',
            'other_language_level',
            // Level 2 conditional detail field'ları (yes/no sorularına bağlı)
            'visa_history_details',
            'abroad_experience_details',
            'health_condition_details',
            'other_country_details',
        ];

        // Level 1'e özel koşullu (UI'da JS ile gizlenebiliyor — required kontrolünde çıkar)
        if ($level === 1) {
            $conditional = array_merge($conditional, [
                'university_name',
                'university_department',
                'university_year',
                'guarantor_relation',
                'guarantor_name',
                'accommodation_contact_city',
                'german_certificate_type',
                'german_certificate_score',
                'german_certificate_held',
                'english_certificate_held',
                'english_certificate_type',
                'english_certificate_score',
                // Pasaport — has_passport='no' iken bunlar gizli
                'passport_number',
                'passport_issue_date',
                'passport_expiry_date',
                'passport_issue_place',
            ]);
        }

        return array_values(array_filter($keys, static fn (string $k) => !in_array($k, $conditional, true)));
    }

    /**
     * B12: Kullanıcının education_level seçimine göre üst kademe alanlarını
     * required listesinden atla. "middle_school" seçildiyse lise ve üniversite
     * alanları gereksizdir; "high_school" seçildiyse üniversite alanı gereksizdir.
     *
     * @param  array<string,mixed> $payload
     * @return array<int,string>
     */
    public function educationSkippedKeys(array $payload): array
    {
        $level = strtolower(trim((string) ($payload['education_level'] ?? '')));

        $highKeys = [
            'high_start_date', 'high_end_date', 'high_school_name',
            'high_school_type', 'high_school_grade',
        ];
        $universityKeys = [
            'university_name', 'university_department',
        ];

        return match ($level) {
            'middle_school' => array_merge($highKeys, $universityKeys),
            'high_school'   => $universityKeys,
            default         => [],
        };
    }

    /**
     * Spouse alanları yalnızca marital_status === 'married' ise required sayılır.
     * children_count ayrıca has_children === 'yes' olduğunda zorunlu
     * (conditionalRequiredErrors içinde handle edilir — buradaki skipKeys sadece
     * "required değil" demek; conditional rule ayrı bir kontrol).
     *
     * @param  array<string,mixed> $payload
     * @return array<int,string>
     */
    public function spouseSkippedKeys(array $payload): array
    {
        $maritalStatus = strtolower(trim((string) ($payload['marital_status'] ?? '')));
        if ($maritalStatus !== 'married') {
            // Evli değil → tüm spouse + children_count alanları atlanır
            return [
                'spouse_full_name',
                'spouse_birth_date',
                'spouse_nationality',
                'spouse_occupation',
                'marriage_date',
                'marriage_place',
                'spouse_currently_in_germany',
                'has_children',
                'children_count',
            ];
        }
        // Evli → children_count'u default skip (zorunlu değil),
        // sadece has_children === 'yes' ise required sayılacak (conditional rule).
        return ['children_count'];
    }

    /**
     * B13: Eğitim tarihleri — her kademenin minimum süresini ve kademeler arası
     * sıralamayı doğrular.
     *   İlkokul ≥ 4 yıl | Ortaokul ≥ 3 yıl | Lise ≥ 3 yıl
     *
     * @param  array<string,mixed> $payload
     * @return array<string,string>
     */
    public function educationDateOrderErrors(array $payload): array
    {
        // [start_key, end_key, min_years, label]
        $durationRules = [
            ['primary_start_date', 'primary_end_date', 4, 'İlkokul'],
            ['middle_start_date',  'middle_end_date',  3, 'Ortaokul'],
            ['high_start_date',    'high_end_date',    3, 'Lise'],
        ];
        $orderChain = [
            ['primary_end_date', 'middle_start_date', 'Ortaokul başlama tarihi ilkokul bitiş tarihinden önce olamaz.'],
            ['middle_end_date', 'high_start_date', 'Lise başlama tarihi ortaokul bitiş tarihinden önce olamaz.'],
        ];
        $skipKeys = $this->educationSkippedKeys($payload);
        $errors = [];

        foreach ($durationRules as [$sKey, $eKey, $minYears, $label]) {
            if (in_array($sKey, $skipKeys, true) || in_array($eKey, $skipKeys, true)) {
                continue;
            }
            $s = trim((string) ($payload[$sKey] ?? ''));
            $e = trim((string) ($payload[$eKey] ?? ''));
            if ($s === '' || $e === '') {
                continue;
            }
            if ($e < $s) {
                $errors[$eKey] = $label . ' bitiş tarihi başlama tarihinden önce olamaz.';
                continue;
            }
            try {
                $minEnd = (new \DateTimeImmutable($s))->modify('+' . $minYears . ' years')->format('Y-m-d');
                if ($e < $minEnd) {
                    $errors[$eKey] = $label . ' bitiş tarihi başlamadan en az ' . $minYears . ' yıl sonra olmalı.';
                }
            } catch (\Throwable) {}
        }

        foreach ($orderChain as [$aKey, $bKey, $msg]) {
            if (in_array($aKey, $skipKeys, true) || in_array($bKey, $skipKeys, true)) {
                continue;
            }
            if (isset($errors[$bKey])) {
                continue;
            }
            $a = trim((string) ($payload[$aKey] ?? ''));
            $b = trim((string) ($payload[$bKey] ?? ''));
            if ($a === '' || $b === '') {
                continue;
            }
            if ($b < $a) {
                $errors[$bKey] = $msg;
            }
        }

        // B15: parent dob vs child dob
        $childDob = trim((string) ($payload['birth_date'] ?? ''));
        if ($childDob !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $childDob)) {
            try {
                $maxParentDob = (new \DateTimeImmutable($childDob))->modify('-12 years')->format('Y-m-d');
                foreach (['father_birth_date', 'mother_birth_date'] as $pKey) {
                    $pVal = trim((string) ($payload[$pKey] ?? ''));
                    if ($pVal !== '' && $pVal > $maxParentDob) {
                        $errors[$pKey] = 'Doğum tarihi çocuğun doğum tarihinden en az 12 yıl önce olmalı.';
                    }
                }
            } catch (\Throwable) {}
        }

        return $errors;
    }

    /**
     * DB kaydındaki type'ı döndür; application_country için 'select' olarak zorla.
     */
    private function resolveFieldType(GuestRegistrationField $row): string
    {
        if ($row->field_key === 'application_country') {
            return 'select';
        }
        // Eski DB kayıtlarında 'month' tipi 'text'e çevrilmişti; katalog 'month' diyorsa onu kullan.
        $monthFields = [
            'primary_start_date', 'primary_end_date',
            'middle_start_date', 'middle_end_date',
            'high_start_date', 'high_end_date',
            'germany_stay_from', 'germany_stay_to',
        ];
        if (in_array($row->field_key, $monthFields, true)) {
            return 'month';
        }
        return (string) $row->type;
    }

    /**
     * DB kaydındaki options_json'u döndür; application_country için katalog inject et.
     *
     * @return array<int,array<string,mixed>>
     */
    private function resolveFieldOptions(GuestRegistrationField $row): array
    {
        $options = is_array($row->options_json) ? $row->options_json : [];

        if ($row->field_key === 'application_country' && empty($options)) {
            return GuestRegistrationFormCatalog::applicationCountryOptions();
        }

        return $options;
    }

    /**
     * Catalog'taki güncel type+options'ı DB'deki mevcut field rows'una
     * push eder. Yeni alan eklendiğinde de DB'ye seed yapar.
     *
     * - is_system=true rows için type/options/label/help_text güncellenir
     * - is_system=false (manager elle eklemiş) rows korunur
     * - Catalog'da olmayan eski rows soft-deactivate edilmez (manuel temizlik)
     *
     * Çalıştır: php artisan tinker → app(GuestRegistrationFieldSchemaService::class)->syncFromCatalog(0)
     */
    public function syncFromCatalog(int $companyId = 0): array
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return ['updated' => 0, 'inserted' => 0, 'skipped' => 0];
        }
        $cid = $companyId > 0 ? $companyId : 0;
        $now = CarbonImmutable::now();
        $updated = 0;
        $inserted = 0;
        $skipped = 0;

        // Kapsam dışı: hedef şirket açıkça $cid ile belirtiliyor. Kapsam
        // açık kalırsa ortak şablon (company_id=0) senkronu boş küme görür
        // ve var olan alanları yeniden eklemeye çalışır.
        $existing = GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $cid)
            ->get()
            ->keyBy('field_key');

        foreach (GuestRegistrationFormCatalog::groups() as $sectionIndex => $group) {
            $sectionKey = $this->safeCode($group['section_key'] ?? ('section_' . ($sectionIndex + 1)), 'section_' . ($sectionIndex + 1));
            $sectionTitle = $this->safeText($group['title'] ?? ('Bolum ' . ($sectionIndex + 1)), 'Bolum ' . ($sectionIndex + 1));
            $sectionOrder = (int) ($group['section_order'] ?? (($sectionIndex + 1) * 10));

            foreach ((array) ($group['fields'] ?? []) as $fieldIndex => $field) {
                $fieldKey = $this->safeCode($field['key'] ?? null, 'field_' . ($fieldIndex + 1));
                $type = $this->safeText($field['type'] ?? 'text', 'text');
                if (!in_array($type, ['text', 'email', 'date', 'month', 'select', 'textarea', 'phone', 'checkbox_group', 'canonical_program', 'expatrio_program', 'hidden'], true)) {
                    $type = 'text';
                }

                $payload = [
                    'section_key'   => $sectionKey,
                    'section_title' => $sectionTitle,
                    'section_order' => $sectionOrder,
                    'label'         => $this->safeText($field['label'] ?? null, $fieldKey),
                    'type'          => $type,
                    'is_required'   => (bool) ($field['required'] ?? false),
                    'sort_order'    => (int) ($field['sort_order'] ?? (($fieldIndex + 1) * 10)),
                    'max_length'    => isset($field['max']) ? (int) $field['max'] : null,
                    'placeholder'   => $this->safeNullableText($field['placeholder'] ?? null, 255),
                    'help_text'     => $this->safeNullableText($field['help_text'] ?? null, 500),
                    'options_json'  => $this->normalizeOptionsJson($field['options'] ?? null),
                    'updated_at'    => $now,
                ];

                $existingRow = $existing->get($fieldKey);

                if ($existingRow) {
                    // Manager elle eklediyse (is_system=false) sadece options sync yap, label/type değiştirme
                    if (! $existingRow->is_system) {
                        $skipped++;
                        continue;
                    }
                    // RAW UPDATE — Eloquent array cast bypass (lesson #2 double-encode)
                    GuestRegistrationField::query()->where('id', $existingRow->id)->update($payload);
                    $updated++;
                } else {
                    GuestRegistrationField::query()->insert(array_merge($payload, [
                        'company_id' => $cid,
                        'field_key'  => $fieldKey,
                        'is_active'  => true,
                        'is_system'  => true,
                        'created_at' => $now,
                    ]));
                    $inserted++;
                }
            }
        }

        return ['updated' => $updated, 'inserted' => $inserted, 'skipped' => $skipped];
    }

    /**
     * Varsayılan form tanımını hazırla — YALNIZCA ORTAK ŞABLONA.
     *
     * ── NEDEN FİRMAYA KOPYALANMIYOR ──────────────────────────────────────
     * Eskiden bir firma formu ilk kez açtığında katalogun TAMAMI o firmaya
     * kopyalanıyordu (100+ satır). Kopya oluştuğu an firma kalıcı olarak
     * ayrışıyor: merkezden yapılan form değişikliği ona ULAŞMIYOR ve
     * `groups()` içindeki "ortak şablona düş" yedeği bir daha çalışmıyor.
     *
     * Sonuç, fark edilmesi zor bir sessiz sapma: form güncellenir, bazı
     * firmalar eski formda kalır, kimse anlamaz. Alt firma sayısı arttıkça
     * hata olasılığı artar.
     *
     * Artık tohumlama her zaman `company_id = 0`'a yapılıyor. Firma kendi
     * satırlarını ancak BİLEREK özelleştirdiğinde (config panelinden alan
     * ekleyip düzenlediğinde) ediniyor; o zaman da ayrışma kasıtlı oluyor.
     *
     * @param  int  $companyId  Geriye uyum için duruyor; tohumlamayı etkilemez.
     */
    public function ensureDefaults(int $companyId = 0): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }

        $cid = 0;

        // ⚠ Kapsam dışı: kapsam açıkken ortak şablon görünmez, "yok" sanılır
        // ve her istekte yeniden tohumlanmaya çalışılır — tekil anahtar
        // ihlaliyle sayfa 500 verir.
        $hasAny = GuestRegistrationField::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $cid)
            ->exists();

        if ($hasAny) {
            return;
        }

        $rows = [];
        $now = CarbonImmutable::now();
        foreach (GuestRegistrationFormCatalog::groups() as $sectionIndex => $group) {
            $sectionKey = $this->safeCode($group['section_key'] ?? ('section_'.($sectionIndex + 1)), 'section_'.($sectionIndex + 1));
            $sectionTitle = $this->safeText($group['title'] ?? ('Bolum '.($sectionIndex + 1)), 'Bolum '.($sectionIndex + 1));
            $sectionOrder = (int) ($group['section_order'] ?? (($sectionIndex + 1) * 10));
            foreach ((array) ($group['fields'] ?? []) as $fieldIndex => $field) {
                $fieldKey = $this->safeCode($field['key'] ?? null, 'field_'.($fieldIndex + 1));
                $label = $this->safeText($field['label'] ?? null, $fieldKey);
                $type = $this->safeText($field['type'] ?? 'text', 'text');
                if (!in_array($type, ['text', 'email', 'date', 'month', 'select', 'textarea', 'phone', 'checkbox_group', 'canonical_program', 'expatrio_program', 'hidden'], true)) {
                    $type = 'text';
                }
                $placeholder = $this->safeNullableText($field['placeholder'] ?? null, 255);
                $helpText = $this->safeNullableText($field['help_text'] ?? null, 500);
                $rows[] = [
                    'company_id' => $cid,
                    'section_key' => $sectionKey,
                    'section_title' => $sectionTitle,
                    'section_order' => $sectionOrder,
                    'field_key' => $fieldKey,
                    'label' => $label,
                    'type' => $type,
                    'is_required' => (bool) ($field['required'] ?? false),
                    'sort_order' => (int) ($field['sort_order'] ?? (($fieldIndex + 1) * 10)),
                    'max_length' => isset($field['max']) ? (int) $field['max'] : null,
                    'placeholder' => $placeholder,
                    'help_text' => $helpText,
                    'options_json' => $this->normalizeOptionsJson($field['options'] ?? null),
                    'is_active' => true,
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if (!empty($rows)) {
            GuestRegistrationField::query()->insert($rows);
        }
    }

    private function safeText(mixed $value, string $fallback = ''): string
    {
        if (is_array($value)) {
            $txt = trim((string) json_encode($value, JSON_UNESCAPED_UNICODE));
            return $txt !== '' ? $txt : $fallback;
        }
        if (is_object($value)) {
            $txt = trim((string) json_encode($value, JSON_UNESCAPED_UNICODE));
            return $txt !== '' ? $txt : $fallback;
        }
        $txt = trim((string) ($value ?? ''));
        return $txt !== '' ? $txt : $fallback;
    }

    private function safeNullableText(mixed $value, int $maxLen = 255): ?string
    {
        $txt = $this->safeText($value, '');
        if ($txt === '') {
            return null;
        }
        return mb_substr($txt, 0, max(1, $maxLen));
    }

    private function safeCode(mixed $value, string $fallback): string
    {
        $txt = strtolower($this->safeText($value, $fallback));
        $txt = preg_replace('/[^a-z0-9_]/', '_', $txt) ?: $fallback;
        $txt = preg_replace('/_+/', '_', $txt) ?: $fallback;
        $txt = trim($txt, '_');
        return $txt !== '' ? mb_substr($txt, 0, 100) : $fallback;
    }

    private function normalizeOptionsJson(mixed $options): ?string
    {
        if ($options === null || $options === '' || $options === []) {
            return null;
        }
        if (is_string($options)) {
            $trim = trim($options);
            return $trim !== '' ? $trim : null;
        }
        if (is_array($options) || is_object($options)) {
            return json_encode($options, JSON_UNESCAPED_UNICODE);
        }
        return null;
    }
}
