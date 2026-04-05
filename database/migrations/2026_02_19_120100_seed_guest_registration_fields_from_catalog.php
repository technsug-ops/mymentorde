<?php

use App\Support\GuestRegistrationFormCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }

        $exists = DB::table('guest_registration_fields')
            ->where('company_id', 0)
            ->exists();
        if ($exists) {
            return;
        }

        $now = now();
        $rows = [];

        foreach (GuestRegistrationFormCatalog::groups() as $sectionIndex => $group) {
            $sectionKey = (string) ($group['section_key'] ?? ('section_'.($sectionIndex + 1)));
            $sectionTitle = (string) ($group['title'] ?? ('Bolum '.($sectionIndex + 1)));
            $sectionOrder = (int) ($group['section_order'] ?? (($sectionIndex + 1) * 10));

            foreach ((array) ($group['fields'] ?? []) as $fieldIndex => $field) {
                $rows[] = [
                    'company_id' => 0,
                    'section_key' => $sectionKey,
                    'section_title' => $sectionTitle,
                    'section_order' => $sectionOrder,
                    'field_key' => (string) ($field['key'] ?? ''),
                    'label' => (string) ($field['label'] ?? ''),
                    'type' => (string) ($field['type'] ?? 'text'),
                    'is_required' => (bool) ($field['required'] ?? false),
                    'sort_order' => (int) ($field['sort_order'] ?? (($fieldIndex + 1) * 10)),
                    'max_length' => isset($field['max']) ? (int) $field['max'] : null,
                    'placeholder' => trim((string) ($field['placeholder'] ?? '')) ?: null,
                    'help_text' => null,
                    'options_json' => !empty($field['options']) ? json_encode($field['options'], JSON_UNESCAPED_UNICODE) : null,
                    'is_active' => true,
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('guest_registration_fields')->insert($rows);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }

        DB::table('guest_registration_fields')
            ->where('company_id', 0)
            ->where('is_system', true)
            ->delete();
    }
};

