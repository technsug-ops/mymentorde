<?php

use App\Support\GuestRegistrationFormCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes guest_registration_fields for ALL company IDs.
 * The previous migration only fixed company_id=0, but the app resolves
 * a real company from the companies table (e.g. company_id=1) and
 * that was still seeded with ASCII-only Turkish labels.
 * This migration deletes ALL is_system=true rows for every company_id
 * and re-seeds them from the corrected catalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }

        // Get all company_ids that have system records.
        $companyIds = DB::table('guest_registration_fields')
            ->where('is_system', true)
            ->distinct()
            ->orderBy('company_id')
            ->pluck('company_id')
            ->toArray();

        if (empty($companyIds)) {
            return;
        }

        // Delete old system records for every company.
        DB::table('guest_registration_fields')
            ->where('is_system', true)
            ->delete();

        $now = now();

        foreach ($companyIds as $cid) {
            $rows = [];

            foreach (GuestRegistrationFormCatalog::groups() as $sectionIndex => $group) {
                $sectionKey   = (string) ($group['section_key'] ?? ('section_'.($sectionIndex + 1)));
                $sectionTitle = (string) ($group['title'] ?? ('Bölüm '.($sectionIndex + 1)));
                $sectionOrder = (int)    ($group['section_order'] ?? (($sectionIndex + 1) * 10));

                foreach ((array) ($group['fields'] ?? []) as $fieldIndex => $field) {
                    $type = (string) ($field['type'] ?? 'text');
                    if (!in_array($type, ['text', 'email', 'date', 'select', 'textarea'], true)) {
                        $type = 'text';
                    }

                    $rows[] = [
                        'company_id'    => (int) $cid,
                        'section_key'   => $sectionKey,
                        'section_title' => $sectionTitle,
                        'section_order' => $sectionOrder,
                        'field_key'     => (string) ($field['key'] ?? ''),
                        'label'         => (string) ($field['label'] ?? ''),
                        'type'          => $type,
                        'is_required'   => (bool) ($field['required'] ?? false),
                        'sort_order'    => (int) ($field['sort_order'] ?? (($fieldIndex + 1) * 10)),
                        'max_length'    => isset($field['max']) ? (int) $field['max'] : null,
                        'placeholder'   => isset($field['placeholder']) && trim((string) $field['placeholder']) !== ''
                            ? trim((string) $field['placeholder'])
                            : null,
                        'help_text'     => null,
                        'options_json'  => !empty($field['options'])
                            ? json_encode($field['options'], JSON_UNESCAPED_UNICODE)
                            : null,
                        'is_active'     => true,
                        'is_system'     => true,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('guest_registration_fields')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        // Not easily reversible; no-op on rollback.
    }
};
