<?php

use App\Support\GuestRegistrationFormCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-seeds guest_registration_fields for company_id=0 with corrected Turkish labels.
 * Previous seed had ASCII-only labels (missing ş, ı, ğ, ü, ö, ç, İ etc.).
 * The catalog (GuestRegistrationFormCatalog) is now corrected; this migration
 * replaces the old rows with fresh data from the catalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }

        // Remove old system default rows (company_id=0) so we can re-insert fresh.
        DB::table('guest_registration_fields')
            ->where('company_id', 0)
            ->where('is_system', true)
            ->delete();

        $now = now();
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
                    'company_id'    => 0,
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

    public function down(): void
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            return;
        }

        // On rollback simply remove what we inserted; the previous migration will
        // re-seed from whatever catalog state was active at that point.
        DB::table('guest_registration_fields')
            ->where('company_id', 0)
            ->where('is_system', true)
            ->delete();
    }
};
