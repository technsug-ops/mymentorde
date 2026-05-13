<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Models\University;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Partner için tek-seferlik veri snapshot'ı üretir.
 *
 * Kullanım:
 *   php artisan partner:snapshot                     # JSON, pretty
 *   php artisan partner:snapshot --minified          # JSON, minified (boyut küçük)
 *   php artisan partner:snapshot --out=/custom/path  # custom dizin
 *
 * Çıktı:
 *   storage/app/partner-snapshot/<timestamp>/
 *     ├── universities.json
 *     ├── programs.json
 *     ├── states.json
 *     ├── study-fields.json
 *     └── manifest.json
 */
class PartnerSnapshotCommand extends Command
{
    protected $signature = 'partner:snapshot
        {--minified : JSON\'u minified (tek satır) yaz, dosya boyutu küçük}
        {--out= : Custom output dizini (default: storage/app/partner-snapshot/<timestamp>/)}';

    protected $description = 'Partner için bir seferlik üniversite + program kataloğu JSON snapshot\'ı üretir';

    public function handle(): int
    {
        // 15K+ program JSON'a serialize edince ek bellek lazım
        ini_set('memory_limit', '512M');

        $timestamp = now()->format('Y-m-d_His');
        $relPath = "partner-snapshot/{$timestamp}";
        $outDir = $this->option('out') ?: storage_path("app/{$relPath}");
        @mkdir($outDir, 0755, true);

        $flags = $this->option('minified') ? 0 : (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $flags |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        $this->info("=== Snapshot başlıyor: {$outDir} ===");

        // ── Universities ──────────────────────────────────────────
        $this->line('• Universities…');
        $unis = University::query()->where('is_active', true)->get()->map(fn ($u) => [
            'id'                   => $u->id,
            'name'                 => $u->name,
            'city'                 => $u->city,
            'state'                => $u->state,
            'type'                 => $u->type,
            'is_public'            => (bool) $u->is_public,
            'is_uni_assist_member' => (bool) $u->is_uni_assist_member,
            'uni_assist_id'        => $u->uni_assist_id,
            'image_url'            => $u->image_path ? url($u->image_path) : null,
            'video_url'            => $u->video_url,
            'video_caption'        => $u->video_caption,
        ])->all();
        file_put_contents("{$outDir}/universities.json", json_encode($unis, $flags));
        $this->info('  → ' . count($unis) . ' üniversite');

        // ── Programs ──────────────────────────────────────────────
        // Streaming write — 15K+ program tek array'de tutarsa belleği yer.
        // JSON Array elle yazılır: `[` + her record + `,` + ... + `]`
        $this->line('• Programs (streaming write)…');
        $fp = fopen("{$outDir}/programs.json", 'w');
        fwrite($fp, '[');
        $programCount = 0;
        $first = true;
        // Minified hep — streaming pretty zor + dosya 3-5x büyür
        $itemFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        Program::query()->active()->chunk(500, function ($chunk) use ($fp, &$programCount, &$first, $itemFlags) {
            foreach ($chunk as $p) {
                if (! $first) fwrite($fp, ',');
                $first = false;
                fwrite($fp, json_encode([
                    'id'                       => $p->id,
                    'university_id'            => $p->university_id,
                    'university_name'          => $p->university_name_cached,
                    'course_name'              => $p->course_name,
                    'degree_type'              => $p->degree_type,
                    'degree_specification'     => $p->degree_specification,
                    'language'                 => $p->language,
                    'languages_raw'            => $p->languages_raw ?: [],
                    'location'                 => $p->location,
                    'duration_semesters'       => $p->duration_semesters,
                    'tuition_eur_per_semester' => $p->tuition_eur_per_semester,
                    'application_fee_eur'      => $p->application_fee_eur,
                    'cost_per_semester_eur'    => $p->cost_per_semester_eur,
                    'application_deadline_summer' => optional($p->application_deadline_summer)->toDateString(),
                    'application_deadline_winter' => optional($p->application_deadline_winter)->toDateString(),
                    'admission_type'           => $p->admission_type,
                    'nc_value'                 => $p->nc_value,
                    'study_fields'             => $p->study_fields ?: [],
                    'subjects'                 => $p->subjects ?: [],
                    'description_tr'           => $p->description_tr,
                    'description_en'           => $p->description,
                    'qualification_requirements_tr' => $p->qualification_requirements_tr,
                    'language_requirements_tr' => $p->language_requirements_tr,
                    'required_documents_tr'    => $p->required_documents_tr,
                ], $itemFlags));
                $programCount++;
            }
            $this->output->write('.');
        });
        fwrite($fp, ']');
        fclose($fp);
        $this->newLine();
        $this->info('  → ' . $programCount . ' program');

        // ── States (16 Bundesländer + program counts) ─────────────
        $this->line('• States…');
        $cityToState = (array) config('germany_geo.city_to_state', []);
        $statesData = [];
        foreach ((array) config('germany_geo.states', []) as $key => $name) {
            $cities = array_keys(array_filter($cityToState, fn ($s) => $s === $key));
            $statesData[] = [
                'key'           => $key,
                'name'          => $name,
                'cities'        => $cities,
                'program_count' => $cities ? Program::query()->active()->whereIn('location', $cities)->count() : 0,
            ];
        }
        file_put_contents("{$outDir}/states.json", json_encode($statesData, $flags));
        $this->info('  → ' . count($statesData) . ' eyalet');

        // ── Study fields ──────────────────────────────────────────
        $this->line('• Study fields…');
        $fieldCounts = [];
        foreach (Program::query()->active()->whereNotNull('study_fields')->pluck('study_fields') as $fields) {
            foreach ((array) $fields as $f) {
                $f = trim((string) $f);
                if ($f === '') continue;
                $fieldCounts[$f] = ($fieldCounts[$f] ?? 0) + 1;
            }
        }
        $fieldCounts = array_filter($fieldCounts, fn ($c) => $c >= 2);
        arsort($fieldCounts);
        $studyFields = [];
        foreach ($fieldCounts as $name => $count) {
            $studyFields[] = ['name' => $name, 'program_count' => $count];
        }
        file_put_contents("{$outDir}/study-fields.json", json_encode($studyFields, $flags));
        $this->info('  → ' . count($studyFields) . ' kategori');

        // ── Manifest ──────────────────────────────────────────────
        $manifest = [
            'snapshot_version' => 'v1',
            'snapshot_taken_at' => now()->toIso8601String(),
            'api_base_url' => url('/api/v1/partner'),
            'note' => 'Tek-seferlik snapshot. Güncel veriler için REST API kullanın (referral_url her programda mevcut).',
            'counts' => [
                'universities' => count($unis),
                'programs'     => $programCount,
                'states'       => count($statesData),
                'study_fields' => count($studyFields),
            ],
            'files' => [
                'universities.json' => filesize("{$outDir}/universities.json"),
                'programs.json'     => filesize("{$outDir}/programs.json"),
                'states.json'       => filesize("{$outDir}/states.json"),
                'study-fields.json' => filesize("{$outDir}/study-fields.json"),
            ],
            'schema_docs' => url('/docs/PARTNER_API.md'), // Github linki ileride
        ];
        file_put_contents("{$outDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // ── Özet ──────────────────────────────────────────────────
        $this->newLine();
        $this->info('=== Snapshot tamamlandı ===');
        $this->table(
            ['Dosya', 'Boyut'],
            collect($manifest['files'])->map(fn ($size, $name) => [
                $name,
                $size > 1024*1024
                    ? round($size / 1024 / 1024, 2) . ' MB'
                    : round($size / 1024, 1) . ' KB',
            ])->values()->all(),
        );
        $this->info("📂 Dizin: {$outDir}");
        $this->line('💡 Tek dosya olarak göndermek için ZIP\'le:');
        $this->line("   tar -czf snapshot-{$timestamp}.tar.gz -C " . dirname($outDir) . ' ' . basename($outDir));

        return self::SUCCESS;
    }
}
