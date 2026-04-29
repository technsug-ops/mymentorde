<?php

namespace App\Services\ProgramCatalog;

use App\Contracts\ProgramCatalogContract;
use App\Models\ExpatrioProgram;

/**
 * Expatrio Study Buddy → ProgramCatalogContract adapter.
 *
 * DB query'leri ExpatrioProgram modeli üzerinden yapılır. Sync işlemi
 * SyncExpatrioPrograms artisan command'ı tarafından yürütülür (bu adapter
 * çağrı yapmaz, sadece sayar).
 */
class ExpatrioCatalogAdapter implements ProgramCatalogContract
{
    public function source(): string
    {
        return 'expatrio';
    }

    /** @return array<int, array<string, mixed>> */
    public function search(string $term, int $limit = 20): array
    {
        if (mb_strlen(trim($term)) < 2) return [];

        return ExpatrioProgram::query()
            ->search($term)
            ->limit($limit)
            ->get(['id', 'course_name', 'university_name', 'degree_specification', 'languages', 'location'])
            ->map(fn (ExpatrioProgram $p) => [
                'id'                   => $p->id,
                'source'               => $this->source(),
                'label'                => trim(($p->course_name ?? '') . ' — ' . ($p->university_name ?? '')),
                'university_name'      => $p->university_name,
                'course_name'          => $p->course_name,
                'degree_specification' => $p->degree_specification,
                'location'             => $p->location,
                'languages'            => (array) $p->languages,
            ])
            ->all();
    }

    public function find(string $id): ?array
    {
        $p = ExpatrioProgram::query()->find($id);
        if (! $p) return null;

        return [
            'id'                   => $p->id,
            'source'               => $this->source(),
            'university_name'      => $p->university_name,
            'course_name'          => $p->course_name,
            'degree_specification' => $p->degree_specification,
            'location'             => $p->location,
            'languages'            => (array) $p->languages,
            'semester_count'       => $p->semester_count,
            'tuition_fees_per_semester' => $p->tuition_fees_per_semester,
        ];
    }

    public function syncAll(): int
    {
        // Sync işi SyncExpatrioPrograms artisan komutuna devredilir.
        // Bu adapter sadece mevcut kayıt sayısını döndürür.
        return ExpatrioProgram::query()->count();
    }

    public function isActive(): bool
    {
        return ExpatrioProgram::query()->exists();
    }
}
