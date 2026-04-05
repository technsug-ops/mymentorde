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
     * @return array<int,array<string,mixed>>
     */
    public function groups(int $companyId = 0): array
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return GuestRegistrationFormCatalog::groups();
        }

        $this->ensureDefaults($companyId);

        $rows = GuestRegistrationField::query()
            ->where('company_id', $companyId > 0 ? $companyId : 0)
            ->where('is_active', true)
            ->orderBy('section_order')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty() && $companyId > 0) {
            $rows = GuestRegistrationField::query()
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
        return collect($this->groups($companyId))
            ->flatMap(fn (array $g) => (array) ($g['fields'] ?? []))
            ->values()
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    public function sanitizePayload(array $input, int $companyId = 0): array
    {
        return GuestRegistrationFormCatalog::sanitizePayloadByFields($input, $this->flatFields($companyId));
    }

    /**
     * @return array<int,string>
     */
    public function requiredKeys(int $companyId = 0): array
    {
        $keys = GuestRegistrationFormCatalog::requiredKeysByFields($this->flatFields($companyId));
        $conditional = [
            'passport_number',
            'german_course_name',
            'teacher_reference_contact',
            'germany_stay_date_range',
            'germany_last_residences',
            'other_language_level',
        ];
        return array_values(array_filter($keys, static fn (string $k) => !in_array($k, $conditional, true)));
    }

    /**
     * DB kaydındaki type'ı döndür; application_country için 'select' olarak zorla.
     */
    private function resolveFieldType(GuestRegistrationField $row): string
    {
        if ($row->field_key === 'application_country') {
            return 'select';
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

    public function ensureDefaults(int $companyId = 0): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }
        $cid = $companyId > 0 ? $companyId : 0;
        $hasAny = GuestRegistrationField::query()->where('company_id', $cid)->exists();
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
                if (!in_array($type, ['text', 'email', 'date', 'select', 'textarea'], true)) {
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
