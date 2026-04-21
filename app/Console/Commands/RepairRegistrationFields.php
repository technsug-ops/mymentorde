<?php

namespace App\Console\Commands;

use App\Models\GuestRegistrationField;
use App\Support\GuestRegistrationFormCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RepairRegistrationFields extends Command
{
    protected $signature = 'system:repair-registration-fields
                            {--company= : Hedef company_id (0 = default shared catalog). Verilmezse tüm bilinen company_id\'ler için çalışır.}';

    protected $description = 'guest_registration_fields tablosundaki eksik section/field\'ları default catalog\'dan ekler (insertOrIgnore — mevcutlara dokunmaz).';

    public function handle(): int
    {
        if (!Schema::hasTable('guest_registration_fields')) {
            $this->error('guest_registration_fields tablosu yok.');
            return self::FAILURE;
        }

        $companyArg = $this->option('company');
        if ($companyArg !== null) {
            $companyIds = [(int) $companyArg];
        } else {
            $companyIds = GuestRegistrationField::query()
                ->distinct()
                ->pluck('company_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();
            if (!in_array(0, $companyIds, true)) {
                $companyIds[] = 0;
            }
            if (empty($companyIds)) {
                $companyIds = [0, 1];
            }
        }

        $totalInserted = 0;
        foreach ($companyIds as $cid) {
            $inserted = $this->repairFor($cid);
            $this->line("company_id={$cid}: {$inserted} yeni satır eklendi.");
            $totalInserted += $inserted;
        }

        $this->newLine();
        $this->info("Toplam eklenen: {$totalInserted} satır.");
        return self::SUCCESS;
    }

    private function repairFor(int $companyId): int
    {
        $existing = GuestRegistrationField::query()
            ->where('company_id', $companyId)
            ->get(['section_key', 'field_key'])
            ->map(fn ($r) => $r->section_key . '|' . $r->field_key)
            ->values()
            ->all();
        $existingSet = array_flip($existing);

        $rows = [];
        $now = CarbonImmutable::now();

        foreach (GuestRegistrationFormCatalog::groups() as $sectionIndex => $group) {
            $sectionKey   = (string) ($group['section_key'] ?? ('section_' . ($sectionIndex + 1)));
            $sectionTitle = (string) ($group['title'] ?? ('Bölüm ' . ($sectionIndex + 1)));
            $sectionOrder = (int) ($group['section_order'] ?? (($sectionIndex + 1) * 10));

            foreach ((array) ($group['fields'] ?? []) as $fieldIndex => $field) {
                $fieldKey = (string) ($field['key'] ?? ('field_' . ($fieldIndex + 1)));
                if ($sectionKey === '' || $fieldKey === '') {
                    continue;
                }
                if (isset($existingSet[$sectionKey . '|' . $fieldKey])) {
                    continue;
                }

                $type = (string) ($field['type'] ?? 'text');
                if (!in_array($type, ['text', 'email', 'date', 'select', 'textarea'], true)) {
                    $type = 'text';
                }

                $rows[] = [
                    'company_id'    => $companyId,
                    'section_key'   => $sectionKey,
                    'section_title' => $sectionTitle,
                    'section_order' => $sectionOrder,
                    'field_key'     => $fieldKey,
                    'label'         => (string) ($field['label'] ?? $fieldKey),
                    'type'          => $type,
                    'is_required'   => (bool) ($field['required'] ?? false),
                    'sort_order'    => (int) ($field['sort_order'] ?? (($fieldIndex + 1) * 10)),
                    'max_length'    => isset($field['max']) ? (int) $field['max'] : null,
                    'placeholder'   => isset($field['placeholder']) ? (string) $field['placeholder'] : null,
                    'help_text'     => isset($field['help_text']) ? (string) $field['help_text'] : null,
                    'options_json'  => $this->encodeOptions($field['options'] ?? null),
                    'is_active'     => true,
                    'is_system'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        if (empty($rows)) {
            return 0;
        }

        GuestRegistrationField::query()->insert($rows);
        return count($rows);
    }

    private function encodeOptions(mixed $options): ?string
    {
        if ($options === null || $options === '' || $options === []) {
            return null;
        }
        if (is_string($options)) {
            $t = trim($options);
            return $t === '' ? null : $t;
        }
        if (is_array($options) || is_object($options)) {
            return json_encode($options, JSON_UNESCAPED_UNICODE);
        }
        return null;
    }
}
