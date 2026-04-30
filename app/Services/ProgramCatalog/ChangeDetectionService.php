<?php

namespace App\Services\ProgramCatalog;

use App\Models\Program;
use App\Models\ProgramChangeLog;
use App\Models\ProgramSourceLink;
use Illuminate\Support\Carbon;

/**
 * Source diff detection — adapter sync sırasında çağrılır.
 *
 * Akış:
 *  1. Adapter tek bir kaynaktan program detayını çeker (raw_data)
 *  2. ChangeDetectionService::record($source, $externalId, $rawData, $canonical)
 *     - Önce checksum eski ile farklı mı bak
 *     - Farklıysa: değişen field'ları diff et, change_log'a yaz
 *     - Severity belirle (kritik field mi?)
 *     - source_link kaydını güncelle
 *
 * Manuel curation öncelikli — Program::is_manually_curated=true ise
 * canonical güncellenmiyor; ama log düşüyor (manager'a bildiri için).
 */
class ChangeDetectionService
{
    /** Critical sayılan field'lar (severity=critical olur) */
    private const CRITICAL_FIELDS = [
        'university_name',
        'university_name_cached',
        'course_name',
        'is_active',
    ];

    /** Warning sayılan field'lar */
    private const WARNING_FIELDS = [
        'application_deadline_summer',
        'application_deadline_winter',
        'tuition_eur_per_semester',
        'language',
        'admission_type',
        'nc_value',
        'duration_semesters',
    ];

    /**
     * Source raw_data'sını canonical Program ile karşılaştır,
     * değişiklikleri log'la, source_link'i güncelle.
     *
     * @param  string  $source       'expatrio'|'hk'|'manual'
     * @param  string  $externalId   source'un kendi ID'si
     * @param  array   $rawData      sourceFromuçlanan raw payload
     * @param  Program $program      canonical program
     * @param  array   $canonicalDelta  canonical alanlardaki değişiklikler [field => [old, new]]
     * @return array{checksum_changed:bool, logs_created:int}
     */
    public function record(string $source, string $externalId, array $rawData, Program $program, array $canonicalDelta = []): array
    {
        $checksum = ProgramSourceLink::computeChecksum($rawData);
        $now = Carbon::now();

        $link = ProgramSourceLink::query()
            ->where('source', $source)
            ->where('external_id', $externalId)
            ->first();

        $logsCreated = 0;
        $checksumChanged = $link === null || $link->checksum !== $checksum;

        // Source link kaydı (yeni veya güncellenen checksum)
        if ($link === null) {
            ProgramSourceLink::query()->create([
                'program_id'      => $program->id,
                'source'          => $source,
                'external_id'     => $externalId,
                'raw_data'        => $rawData,
                'checksum'        => $checksum,
                'last_synced_at'  => $now,
                'is_primary'      => true, // ilk kaynak primary
            ]);
        } else {
            $link->update([
                'raw_data'        => $rawData,
                'checksum'        => $checksum,
                'last_synced_at'  => $now,
            ]);
        }

        // Canonical alanlardaki diff'leri log'la
        foreach ($canonicalDelta as $field => $values) {
            [$old, $new] = $values + [null, null];
            if ($this->isEqual($old, $new)) continue;

            $severity = $this->resolveSeverity($field);
            ProgramChangeLog::query()->create([
                'program_id'    => $program->id,
                'source'        => $source,
                'field_changed' => $field,
                'old_value'     => is_scalar($old) ? ['value' => $old] : (array) $old,
                'new_value'     => is_scalar($new) ? ['value' => $new] : (array) $new,
                'severity'      => $severity,
                'detected_at'   => $now,
            ]);
            $logsCreated++;
        }

        return [
            'checksum_changed' => $checksumChanged,
            'logs_created'     => $logsCreated,
        ];
    }

    /**
     * Yeni kaynak verisi (raw) → canonical alanlara map sonucu, hangi
     * canonical field'lar değişti? Adapter bu helper'ı kullanır.
     *
     * @param  array  $oldCanonical   mevcut Program field'ları (snake_case)
     * @param  array  $newCanonical   yeni canonical alanlar (adapter'ın hesapladığı)
     * @return array  delta — [field => [old, new]] sadece değişenler
     */
    public function diffCanonical(array $oldCanonical, array $newCanonical): array
    {
        $delta = [];
        foreach ($newCanonical as $field => $newVal) {
            $oldVal = $oldCanonical[$field] ?? null;
            if (! $this->isEqual($oldVal, $newVal)) {
                $delta[$field] = [$oldVal, $newVal];
            }
        }
        return $delta;
    }

    /** İki değer eşit mi? Array için recursive. */
    private function isEqual(mixed $a, mixed $b): bool
    {
        if (is_array($a) && is_array($b)) {
            return json_encode($a) === json_encode($b);
        }
        if ($a instanceof \DateTimeInterface) $a = $a->format('Y-m-d');
        if ($b instanceof \DateTimeInterface) $b = $b->format('Y-m-d');
        return $a == $b; // loose — null/0/'' farklılığı buralarda kritik değil
    }

    private function resolveSeverity(string $field): string
    {
        if (in_array($field, self::CRITICAL_FIELDS, true)) return ProgramChangeLog::SEV_CRITICAL;
        if (in_array($field, self::WARNING_FIELDS, true))  return ProgramChangeLog::SEV_WARNING;
        return ProgramChangeLog::SEV_INFO;
    }
}
