<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Models\ProgramSourceLink;
use App\Models\University;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * DAAD International Programmes (myGUIDE) → Canonical Program kataloğu sync.
 *
 * Public Solr endpoint:
 *   https://www2.daad.de/deutschland/studienangebote/international-programmes/api/solr/{lang}/search.json
 *
 * Veri zenginliği (Expatrio'dan farkı):
 *  - DAAD: ~2.5K uluslararası program, daha fazla EN ağırlıklı
 *  - DAAD: registrationDeadline (kesin), date[] periodlar ile
 *  - DAAD: courseType (PhD/Master/Bachelor/Summer/Lang/Prep)
 *  - DAAD: link (resmi DAAD detay sayfası)
 *
 * Çakışma stratejisi (deduplication):
 *  - Aynı university + course_name bizde varsa: source link ekle, canonical'a dokunma
 *  - Yoksa: yeni canonical Program + source link
 *  - Manuel curation aktifse override etme
 *
 * Çalıştırma:
 *   php artisan programs:sync-daad
 *   php artisan programs:sync-daad --lang=de --dry-run
 */
class SyncDaadPrograms extends Command
{
    protected $signature = 'programs:sync-daad
        {--lang=en : DAAD locale (en/de). Çoğu uluslararası program EN}
        {--dry-run : Sadece raporla, DB değiştirme}
        {--limit=0 : Sınırla (0=hepsi). Test için 50 kullan.}';

    protected $description = 'DAAD International Programmes Solr API → canonical Program tablosu sync';

    private const SOURCE = 'daad';

    public function handle(): int
    {
        $lang = $this->option('lang') === 'de' ? 'de' : 'en';
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $url = "https://www2.daad.de/deutschland/studienangebote/international-programmes/api/solr/{$lang}/search.json";

        $this->info("DAAD JSON indiriliyor: {$url}");
        $resp = Http::timeout(60)->get($url);
        if (! $resp->successful()) {
            $this->error("HTTP " . $resp->status() . " — sync iptal.");
            return self::FAILURE;
        }

        $data = $resp->json();
        $courses = $data['courses'] ?? [];
        $this->info("Toplam DAAD program: " . count($courses));

        if ($limit > 0) {
            $courses = array_slice($courses, 0, $limit);
            $this->info("--limit={$limit} ile sınırlı.");
        }

        if ($dryRun) {
            $this->warn('--dry-run: DB değişmeyecek, sadece sample mapping göster.');
            $sample = $this->mapToCanonical($courses[0] ?? []);
            $this->table(['Field', 'Value'], collect($sample)->map(fn ($v, $k) => [
                $k,
                is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v,
            ])->values()->all());
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($courses));
        $bar->start();

        $created = 0;
        $linked = 0;     // mevcut canonical'a source link eklendi
        $unchanged = 0;  // bizde zaten var, link de var
        $manualSkip = 0;
        $errors = 0;

        DB::transaction(function () use ($courses, $bar, &$created, &$linked, &$unchanged, &$manualSkip, &$errors, $lang) {
            foreach ($courses as $course) {
                try {
                    $result = $this->upsertCourse($course, $lang);
                    match ($result) {
                        'created'      => $created++,
                        'linked'       => $linked++,
                        'unchanged'    => $unchanged++,
                        'manual_skip'  => $manualSkip++,
                    };
                } catch (\Throwable $e) {
                    $errors++;
                    if ($errors < 5) {
                        $this->newLine();
                        $this->warn("⚠ Hata #{$errors} (id={$course['id']}, course={$course['courseName']}): " . substr($e->getMessage(), 0, 200));
                    }
                    \Log::warning('SyncDaad.row_failed', [
                        'id' => $course['id'] ?? null,
                        'course_name' => $course['courseName'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ DAAD sync tamamlandı:');
        $this->table(['Metric', 'Count'], [
            ['Yeni canonical program', $created],
            ['Mevcut programa source link eklendi', $linked],
            ['Değişiklik yok (zaten linkli)', $unchanged],
            ['Manuel curation skip', $manualSkip],
            ['Hata', $errors],
            ['Toplam', count($courses)],
        ]);

        return self::SUCCESS;
    }

    /**
     * DAAD course → canonical Program upsert.
     * Return: 'created' | 'linked' | 'unchanged' | 'manual_skip'
     */
    private function upsertCourse(array $course, string $lang): string
    {
        $canonical = $this->mapToCanonical($course);
        $externalId = (string) $course['id'];

        if (empty($canonical['university_name']) || empty($canonical['course_name'])) {
            throw new \InvalidArgumentException("DAAD #{$externalId}: missing university or course name");
        }

        // 1) Bu source ID için zaten link var mı?
        $existingLink = ProgramSourceLink::query()
            ->where('source', self::SOURCE)
            ->where('external_id', $externalId)
            ->first();

        if ($existingLink) {
            // Daha önce linklenmiş — sadece raw_data güncelle (canonical'a dokunma)
            $existingLink->update([
                'raw_data'       => $course,
                'last_synced_at' => now(),
            ]);
            return 'unchanged';
        }

        // 2) Aynı üniversitede aynı course_name var mı? (Expatrio'dan gelmiş olabilir)
        $university = University::findOrCreateByName($canonical['university_name']);
        $existingProgram = Program::query()
            ->where('university_id', $university->id)
            ->where('course_name', $canonical['course_name'])
            ->where('is_active', true)
            ->first();

        if ($existingProgram) {
            // Mevcut canonical → DAAD source link ekle, canonical'a dokunma (manuel curation respect)
            ProgramSourceLink::create([
                'program_id'     => $existingProgram->id,
                'source'         => self::SOURCE,
                'external_id'    => $externalId,
                'source_url'     => 'https://www2.daad.de' . ($course['link'] ?? ''),
                'raw_data'       => $course,
                'last_synced_at' => now(),
            ]);

            // Eğer manuel curation aktif değilse, sadece BOŞ olan canonical alanları doldur (enrich)
            if (! $existingProgram->is_manually_curated) {
                $enrich = $this->buildEnrichUpdate($existingProgram, $canonical);
                if (! empty($enrich)) {
                    DB::table('programs')->where('id', $existingProgram->id)->update($enrich);
                }
            }

            return 'linked';
        }

        // 3) Yeni canonical Program — DAAD'dan oluştur
        $program = Program::create([
            'university_id'                => $university->id,
            'university_name_cached'       => $canonical['university_name'],
            'course_name'                  => $canonical['course_name'],
            'degree_specification'         => $canonical['degree_specification'],
            'degree_type'                  => $canonical['degree_type'],
            'language'                     => $canonical['language'],
            'languages_raw'                => $canonical['languages_raw'],
            'location'                     => $canonical['location'],
            'duration_semesters'           => $canonical['duration_semesters'],
            'tuition_eur_per_semester'     => $canonical['tuition_eur_per_semester'],
            'application_deadline_winter'  => $canonical['application_deadline'] ?? null,
            'subjects'                     => $canonical['subjects'],
            'study_fields'                 => $canonical['study_fields'],
            'is_active'                    => true,
            'is_manually_curated'          => false,
        ]);
        $program->recomputeQualityScore();
        $program->save();

        ProgramSourceLink::create([
            'program_id'     => $program->id,
            'source'         => self::SOURCE,
            'external_id'    => $externalId,
            'source_url'     => 'https://www2.daad.de' . ($course['link'] ?? ''),
            'raw_data'       => $course,
            'last_synced_at' => now(),
        ]);

        return 'created';
    }

    /** DAAD course → canonical schema mapping. */
    private function mapToCanonical(array $c): array
    {
        // courseType: 1=PhD, 2=Master, 3=Bachelor, 4=Pre-Bachelor, 5=Summer, 6=Language, 7=other, 56=Studienkolleg
        $degreeMap = [
            1  => ['phd', 'Doctorate'],
            2  => ['master', 'Master'],
            3  => ['bachelor', 'Bachelor'],
            4  => ['other', 'Pre-Bachelor'],
            5  => ['other', 'Summer School'],
            6  => ['sprachkurs', 'Language Course'],
            7  => ['other', 'Other'],
            56 => ['studienkolleg', 'Studienkolleg'],
        ];
        $courseType = (int) ($c['courseType'] ?? 0);
        [$degreeType, $degreeSpec] = $degreeMap[$courseType] ?? ['other', null];

        // preparationForDegree daha güvenilir bilgi olabilir (Bachelor/Master/State examination)
        // Bazı kayıtlarda array olabiliyor (Studienkolleg: ["Bachelor", "Master"])
        $prepRaw = $c['preparationForDegree'] ?? null;
        $prepStr = is_array($prepRaw) ? implode(', ', array_filter($prepRaw)) : (string) ($prepRaw ?? '');
        if ($prepStr !== '') {
            $prep = mb_strtolower($prepStr);
            if (str_contains($prep, 'bachelor')) [$degreeType, $degreeSpec] = ['bachelor', $prepStr];
            if (str_contains($prep, 'master'))   [$degreeType, $degreeSpec] = ['master', $prepStr];
        }

        // Languages
        $langs = (array) ($c['languages'] ?? []);
        $langCode = $this->resolveLanguage($langs);

        // Tuition: date[].costs → ortalama veya null
        $tuition = null;
        if (! empty($c['date']) && is_array($c['date'])) {
            $costs = array_filter(array_column($c['date'], 'costs'));
            if (! empty($costs)) $tuition = (int) array_sum($costs) / count($costs);
        }
        if (is_numeric($c['tuitionFees'] ?? null)) $tuition = (int) $c['tuitionFees'];

        // Duration semester parse (e.g., "4 semesters", "2 years")
        $duration = $this->parseDuration($c['programmeDuration'] ?? null);

        // Application deadline — date[0].registrationDeadline
        $deadline = null;
        if (! empty($c['date'][0]['registrationDeadline'])) {
            try {
                $deadline = Carbon::parse($c['date'][0]['registrationDeadline'])->format('Y-m-d');
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Subject + study fields
        $subjects = ! empty($c['subject']) ? [$c['subject']] : [];
        $studyFields = (array) ($c['preparationForSubjectGroups'] ?? []);
        if (empty($studyFields) && ! empty($c['subject'])) {
            // Subject'ten study_field guess
            $studyFields = [$this->guessStudyField($c['subject'])];
        }

        return [
            'university_name'      => trim((string) ($c['academy'] ?? '')),
            'course_name'          => trim((string) ($c['courseName'] ?? '')),
            'degree_specification' => $degreeSpec,
            'degree_type'          => $degreeType,
            'language'             => $langCode,
            'languages_raw'        => $langs,
            'location'             => trim((string) ($c['city'] ?? '')) ?: null,
            'duration_semesters'   => $duration,
            'tuition_eur_per_semester' => $tuition !== null ? (int) $tuition : null,
            'application_deadline' => $deadline,
            'subjects'             => $subjects,
            'study_fields'         => $studyFields,
        ];
    }

    /** "4 semesters" / "2 years" / "12 months" → semester sayısı. */
    private function parseDuration(?string $s): ?int
    {
        if (! $s) return null;
        $s = mb_strtolower($s);
        if (preg_match('/(\d+)\s*semester/u', $s, $m))      return (int) $m[1];
        if (preg_match('/(\d+)\s*(year|yıl|jahr)/u', $s, $m))  return (int) $m[1] * 2;
        if (preg_match('/(\d+)\s*(month|monat|ay)/u', $s, $m)) return (int) ceil($m[1] / 6);
        return null;
    }

    /** ['English','German'] → 'both'/'en'/'de'. */
    private function resolveLanguage(array $langs): ?string
    {
        if (empty($langs)) return null;
        $norm = array_map(fn ($l) => mb_strtolower(trim((string) $l)), $langs);
        $hasEn = collect($norm)->contains(fn ($l) => str_contains($l, 'english') || $l === 'en');
        $hasDe = collect($norm)->contains(fn ($l) => str_contains($l, 'german') || $l === 'de' || str_contains($l, 'deutsch'));
        if ($hasEn && $hasDe) return 'both';
        if ($hasEn) return 'en';
        if ($hasDe) return 'de';
        return 'other';
    }

    /** Subject → study_field guess (Expatrio kategori sistemi ile uyum). */
    private function guessStudyField(string $subject): string
    {
        $low = mb_strtolower($subject);
        if (str_contains($low, 'computer') || str_contains($low, 'informatik') || str_contains($low, 'data sci')) return 'Computer Science and IT';
        if (str_contains($low, 'engineer')) return 'Engineering';
        if (str_contains($low, 'business') || str_contains($low, 'management') || str_contains($low, 'economics') || str_contains($low, 'finance')) return 'Business Management and Economics';
        if (str_contains($low, 'medicine') || str_contains($low, 'health') || str_contains($low, 'nurs')) return 'Medicine and Health';
        if (str_contains($low, 'biology') || str_contains($low, 'chem') || str_contains($low, 'physic') || str_contains($low, 'math')) return 'Natural Sciences and Mathematics';
        if (str_contains($low, 'art') || str_contains($low, 'music') || str_contains($low, 'design') || str_contains($low, 'archit')) return 'Arts, Design and Architecture';
        if (str_contains($low, 'law')) return 'Law';
        return 'Social Sciences, Humanities and Linguistics';
    }

    /** Mevcut programda boş kalmış alanları DAAD'tan doldur (enrich). */
    private function buildEnrichUpdate(Program $existing, array $canonical): array
    {
        $update = [];
        $candidates = [
            'duration_semesters'       => $canonical['duration_semesters'],
            'language'                 => $canonical['language'],
            'languages_raw'            => $canonical['languages_raw'],
            'tuition_eur_per_semester' => $canonical['tuition_eur_per_semester'],
        ];
        foreach ($candidates as $field => $newVal) {
            if ($newVal === null || $newVal === [] || $newVal === '') continue;
            $oldVal = $existing->{$field};
            if ($oldVal === null || $oldVal === [] || $oldVal === '') {
                $update[$field] = is_array($newVal) ? json_encode($newVal) : $newVal;
            }
        }
        if (! empty($update)) {
            $update['updated_at'] = now();
        }
        return $update;
    }
}
