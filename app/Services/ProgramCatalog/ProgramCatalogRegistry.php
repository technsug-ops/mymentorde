<?php

namespace App\Services\ProgramCatalog;

use App\Contracts\ProgramCatalogContract;
use App\Models\Program;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Program kataloğu giriş noktası — canonical-first.
 *
 * Faz 1 refactor: Artık adapter'lar canonical Program tablosundan okur.
 * Registry source-bazlı search/find sağlar (filter), ama temel veri
 * her zaman canonical katmandan gelir.
 *
 * Genel pattern:
 *   $registry->findCanonical($uuid)              // source-bağımsız
 *   $registry->getAdapter('expatrio')->find($id) // source-spesifik
 *   $registry->searchAll('Informatik')           // tüm kaynaklar
 */
class ProgramCatalogRegistry
{
    /** @var array<string, ProgramCatalogContract> */
    private array $adapters = [];

    public function __construct()
    {
        $this->register(new ExpatrioCatalogAdapter());

        // ── İleride aktif edilecek (boş tablo, henüz adapter implementation yok) ──
        // $this->register(new HochschulkompassCatalogAdapter());
    }

    public function register(ProgramCatalogContract $adapter): void
    {
        $this->adapters[$adapter->source()] = $adapter;
    }

    public function getAdapter(string $source): ProgramCatalogContract
    {
        if (! isset($this->adapters[$source])) {
            return $this->adapters['expatrio'] ?? throw new RuntimeException('No catalog adapter registered.');
        }
        return $this->adapters[$source];
    }

    /**
     * Canonical UUID ile direkt program lookup (source-bağımsız).
     * Form'daki target_program_id artık canonical UUID saklıyor.
     *
     * @return array<string, mixed>|null
     */
    public function findCanonical(string $canonicalId): ?array
    {
        $program = Program::query()->with('university')->find($canonicalId);
        if (! $program) return null;

        return [
            'id'                   => $program->id,
            'source'               => 'canonical',
            'label'                => trim(($program->course_name ?? '') . ' — ' . ($program->university_name_cached ?? '')),
            'university_name'      => $program->university_name_cached,
            'course_name'          => $program->course_name,
            'degree_specification' => $program->degree_specification,
            'degree_type'          => $program->degree_type,
            'language'             => $program->language,
            'languages'            => (array) $program->languages_raw,
            'location'             => $program->location,
            'duration_semesters'   => $program->duration_semesters,
            'tuition_eur'          => $program->tuition_eur_per_semester,
            'quality_score'        => $program->quality_score,
            'is_manually_curated'  => $program->is_manually_curated,
        ];
    }

    /** Aktif tüm kaynaklarda search yap, sonuçları birleştir. */
    public function searchAll(string $term, int $limitPerSource = 20): array
    {
        $results = [];
        foreach ($this->activeAdapters() as $adapter) {
            foreach ($adapter->search($term, $limitPerSource) as $item) {
                $results[] = $item;
            }
        }
        return $results;
    }

    /**
     * Source-bağımsız canonical search — tüm aktif programlar arasından.
     * Form autocomplete bunu kullanır (kaynak farketmez).
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchCanonical(string $term, int $limit = 20): array
    {
        if (mb_strlen(trim($term)) < 2) return [];

        return Program::query()
            ->active()
            ->search($term)
            ->limit($limit)
            ->get(['id', 'course_name', 'university_name_cached', 'degree_specification', 'languages_raw', 'location', 'quality_score'])
            ->map(fn (Program $p) => [
                'id'                   => $p->id,
                'source'               => 'canonical',
                'label'                => trim(($p->course_name ?? '') . ' — ' . ($p->university_name_cached ?? '')),
                'university_name'      => $p->university_name_cached,
                'course_name'          => $p->course_name,
                'degree_specification' => $p->degree_specification,
                'location'             => $p->location,
                'languages'            => (array) $p->languages_raw,
                'quality_score'        => $p->quality_score,
            ])
            ->all();
    }

    /** Source bazlı find — adapter'a delege. */
    public function find(string $source, string $id): ?array
    {
        // Eğer source 'canonical' veya boşsa → canonical lookup
        if ($source === '' || $source === 'canonical') {
            return $this->findCanonical($id);
        }
        return $this->getAdapter($source)->find($id);
    }

    /** @return Collection<int, ProgramCatalogContract> */
    public function activeAdapters(): Collection
    {
        return collect($this->adapters)->filter(fn (ProgramCatalogContract $a) => $a->isActive())->values();
    }

    public function allSources(): array
    {
        return array_keys($this->adapters);
    }
}
