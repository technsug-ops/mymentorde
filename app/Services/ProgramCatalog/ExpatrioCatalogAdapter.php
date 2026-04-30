<?php

namespace App\Services\ProgramCatalog;

use App\Contracts\ProgramCatalogContract;
use App\Models\Program;
use App\Models\ProgramSourceLink;
use App\Models\University;

/**
 * Expatrio Study Buddy → Canonical Program kataloğuna adapter.
 *
 * Refactor (Faz 1): Adapter artık ExpatrioProgram modelini değil,
 * canonical Program modelini sorgular. Source linkleri program_source_links
 * üzerinden bağlanır. Sync sırasında ChangeDetectionService devrede.
 *
 * Search/find dönen item formatı (ProgramCatalogContract'ı korur):
 *   ['id', 'source', 'label', 'university_name', 'course_name',
 *    'degree_specification', 'location', 'languages']
 *
 * NOT: Eski ExpatrioProgram model'i geriye uyumluluk için duruyor —
 * source raw data cache. Yeni implementation onu kullanmıyor;
 * canonical + program_source_links yeterli.
 */
class ExpatrioCatalogAdapter implements ProgramCatalogContract
{
    public const SOURCE = 'expatrio';

    public function source(): string
    {
        return self::SOURCE;
    }

    /** @return array<int, array<string, mixed>> */
    public function search(string $term, int $limit = 20): array
    {
        if (mb_strlen(trim($term)) < 2) return [];

        // Canonical Program'da search; sadece Expatrio source link'i olanları getir
        $programs = Program::query()
            ->active()
            ->search($term)
            ->whereHas('sourceLinks', fn ($q) => $q->where('source', self::SOURCE))
            ->limit($limit)
            ->get(['id', 'course_name', 'university_name_cached', 'degree_specification', 'languages_raw', 'location']);

        return $programs->map(fn (Program $p) => $this->programToArray($p))->all();
    }

    public function find(string $id): ?array
    {
        // Canonical UUID veya source external_id olabilir — ikisini de dene
        $program = Program::query()->find($id);
        if (! $program) {
            // External ID ile arıyor olabilir — source_links üzerinden bul
            $link = ProgramSourceLink::query()
                ->where('source', self::SOURCE)
                ->where('external_id', $id)
                ->first();
            if ($link) {
                $program = Program::query()->find($link->program_id);
            }
        }

        if (! $program) return null;
        return $this->programToArray($program, withDetails: true);
    }

    public function syncAll(): int
    {
        // Sync işi SyncExpatrioPrograms artisan command'ı tarafından yürütülür.
        // Bu adapter sadece kayıt sayısını döndürür (Expatrio source linkli programlar).
        return ProgramSourceLink::query()->where('source', self::SOURCE)->count();
    }

    public function isActive(): bool
    {
        return ProgramSourceLink::query()->where('source', self::SOURCE)->exists();
    }

    /**
     * Expatrio raw_data → canonical Program field'larına map.
     * Sync command bu method'u çağırır.
     *
     * @param  array  $raw  Expatrio search/detail response item
     * @return array  canonical fields hashmap
     */
    public function mapRawToCanonical(array $raw): array
    {
        return [
            'university_name'             => (string) ($raw['universityName'] ?? ''),
            'course_name'                 => (string) ($raw['courseName'] ?? ''),
            'degree_specification'        => $raw['degreeSpecification'] ?? null,
            'degree_type'                 => $this->resolveDegreeType($raw['degreeSpecification'] ?? ''),
            'language'                    => $this->resolveLanguage($raw['languages'] ?? []),
            'languages_raw'               => array_values((array) ($raw['languages'] ?? [])),
            'location'                    => $raw['location'] ?? null,
            'duration_semesters'          => isset($raw['semesterCount']) ? (int) $raw['semesterCount'] : null,
            'tuition_eur_per_semester'    => isset($raw['tuitionFeesPerSemester']) ? (int) $raw['tuitionFeesPerSemester'] : null,
            'study_fields'                => array_values((array) ($raw['studyFields'] ?? [])),
            'subjects'                    => array_values((array) ($raw['subjects'] ?? [])),
        ];
    }

    /** "Bachelor of Arts (B.A.)" → "bachelor" gibi normalize. */
    private function resolveDegreeType(?string $spec): ?string
    {
        if ($spec === null || trim($spec) === '') return null;
        $low = mb_strtolower($spec);
        if (str_contains($low, 'bachelor'))      return 'bachelor';
        if (str_contains($low, 'master'))        return 'master';
        if (str_contains($low, 'phd') || str_contains($low, 'doktor') || str_contains($low, 'promotion')) return 'phd';
        if (str_contains($low, 'studienkolleg')) return 'studienkolleg';
        if (str_contains($low, 'sprachkurs') || str_contains($low, 'language course')) return 'sprachkurs';
        if (str_contains($low, 'ausbildung'))    return 'ausbildung';
        return 'other';
    }

    /** ['English', 'German'] → 'both'; tek dil 'en'/'de'. */
    private function resolveLanguage(array $languages): ?string
    {
        if (empty($languages)) return null;
        $set = collect($languages)->map(fn ($l) => mb_strtolower(trim((string) $l)))->all();
        $hasEn = collect($set)->contains(fn ($l) => str_contains($l, 'english') || $l === 'en');
        $hasDe = collect($set)->contains(fn ($l) => str_contains($l, 'german') || $l === 'de' || str_contains($l, 'deutsch'));
        if ($hasEn && $hasDe) return 'both';
        if ($hasDe) return 'de';
        if ($hasEn) return 'en';
        return 'other';
    }

    /** Canonical Program → contract dönüş formatı. */
    private function programToArray(Program $p, bool $withDetails = false): array
    {
        $base = [
            'id'                   => $p->id,
            'source'               => self::SOURCE,
            'label'                => trim(($p->course_name ?? '') . ' — ' . ($p->university_name_cached ?? '')),
            'university_name'      => $p->university_name_cached,
            'course_name'          => $p->course_name,
            'degree_specification' => $p->degree_specification,
            'location'             => $p->location,
            'languages'            => (array) $p->languages_raw,
        ];

        if ($withDetails) {
            $base['semester_count']            = $p->duration_semesters;
            $base['tuition_fees_per_semester'] = $p->tuition_eur_per_semester;
            $base['study_fields']              = (array) $p->study_fields;
            $base['subjects']                  = (array) $p->subjects;
            $base['degree_type']               = $p->degree_type;
            $base['language']                  = $p->language;
            $base['quality_score']             = $p->quality_score;
        }

        return $base;
    }

    /**
     * Canonical Program upsert — sync command tarafından çağrılır.
     *
     * Manuel curation öncelikli: is_manually_curated=true ise canonical
     * field'lara dokunulmaz, sadece source_link ve change_log update.
     *
     * @return array{program: Program, was_created: bool, canonical_delta: array}
     */
    public function upsertCanonical(array $raw): array
    {
        $externalId = (string) ($raw['id'] ?? '');
        if ($externalId === '') {
            throw new \InvalidArgumentException('Expatrio raw missing id');
        }

        $canonical = $this->mapRawToCanonical($raw);
        $uniName = $canonical['university_name'] ?? null;
        if (! $uniName) {
            throw new \InvalidArgumentException("Expatrio raw missing universityName for {$externalId}");
        }

        // Üniversite resolve
        $university = University::findOrCreateByName($uniName);

        // Mevcut kanonik program?
        $existingLink = ProgramSourceLink::query()
            ->where('source', self::SOURCE)
            ->where('external_id', $externalId)
            ->first();

        $oldCanonicalSnapshot = [];
        if ($existingLink) {
            $program = Program::query()->find($existingLink->program_id);
            if ($program) {
                $oldCanonicalSnapshot = [
                    'university_name_cached'    => $program->university_name_cached,
                    'course_name'               => $program->course_name,
                    'degree_specification'      => $program->degree_specification,
                    'degree_type'               => $program->degree_type,
                    'language'                  => $program->language,
                    'location'                  => $program->location,
                    'duration_semesters'        => $program->duration_semesters,
                    'tuition_eur_per_semester'  => $program->tuition_eur_per_semester,
                ];
            }
        }

        // Canonical fields (university_name → university_name_cached + university_id)
        $canonicalFields = [
            'university_id'               => $university->id,
            'university_name_cached'      => $uniName,
            'course_name'                 => $canonical['course_name'],
            'degree_specification'        => $canonical['degree_specification'],
            'degree_type'                 => $canonical['degree_type'],
            'language'                    => $canonical['language'],
            'languages_raw'               => $canonical['languages_raw'],
            'location'                   => $canonical['location'],
            'duration_semesters'          => $canonical['duration_semesters'],
            'tuition_eur_per_semester'    => $canonical['tuition_eur_per_semester'],
            'study_fields'                => $canonical['study_fields'],
            'subjects'                    => $canonical['subjects'],
        ];

        $wasCreated = ! ($existingLink && Program::query()->whereKey($existingLink->program_id)->exists());

        if ($existingLink && ! $wasCreated) {
            $program = Program::query()->find($existingLink->program_id);

            // Manuel curation öncelikli — canonical'a dokunmaz
            if (! $program->is_manually_curated) {
                $program->fill($canonicalFields);
                $program->recomputeQualityScore();
                $program->save();
            }
        } else {
            $program = Program::query()->create(array_merge(
                $canonicalFields,
                ['is_active' => true]
            ));
            $program->recomputeQualityScore();
            $program->save();
        }

        // Diff hesapla
        $newSnapshot = [
            'university_name_cached'    => $program->university_name_cached,
            'course_name'               => $program->course_name,
            'degree_specification'      => $program->degree_specification,
            'degree_type'               => $program->degree_type,
            'language'                  => $program->language,
            'location'                  => $program->location,
            'duration_semesters'        => $program->duration_semesters,
            'tuition_eur_per_semester'  => $program->tuition_eur_per_semester,
        ];
        $detection = app(ChangeDetectionService::class);
        $delta = $wasCreated ? [] : $detection->diffCanonical($oldCanonicalSnapshot, $newSnapshot);

        // Source link kaydı + change log
        $detection->record(self::SOURCE, $externalId, $raw, $program, $delta);

        return [
            'program'         => $program,
            'was_created'     => $wasCreated,
            'canonical_delta' => $delta,
        ];
    }
}
