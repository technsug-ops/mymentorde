<?php

namespace App\Console\Commands;

use App\Models\ExpatrioProgram;
use App\Models\ExpatrioUniversity;
use App\Services\ExpatrioStudyBuddyClient;
use Illuminate\Console\Command;

/**
 * Expatrio Study Buddy program kataloğunu DB'ye senkronize eder.
 *
 * Akış:
 *  1) /studybuddy/universities → ~500 üni
 *  2) /studybuddy/programs/search → 13K program (paginated, list view)
 *  3) Sadece YENİ veya değişen program ID'leri için /studybuddy/programs/{id} → tam detay
 *
 * Optimizasyon: detay endpoint'ini sadece eksik/güncellenmeyen kayıtlar için
 * çağırır. İlk full sync ~3-5 dakika sürer (rate limit: ~600 ms/req).
 *
 * Kullanım:
 *  php artisan expatrio:sync                  → tam sync
 *  php artisan expatrio:sync --limit=50       → ilk 50 program (test)
 *  php artisan expatrio:sync --details        → tüm programlar için detay endpoint çağır (yavaş)
 *  php artisan expatrio:sync --no-details     → sadece search endpoint, detay atla (hızlı)
 */
class SyncExpatrioPrograms extends Command
{
    protected $signature = 'expatrio:sync
        {--limit=0 : Sadece bu kadar program çek (0 = sınırsız)}
        {--details : Her programın detay endpoint\'ini çağır (yavaş ama tam veri)}
        {--no-details : Search verisi yeterli, detay atla (hızlı)}
        {--throttle=600 : İstekler arası bekleme (ms)}';

    protected $description = 'Expatrio Study Buddy program kataloğunu DB\'ye senkronize eder';

    public function handle(ExpatrioStudyBuddyClient $client): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $fetchDetails = $this->option('details') && ! $this->option('no-details');
        $throttleMs = max(0, (int) $this->option('throttle'));

        // Throttle override
        $client = new ExpatrioStudyBuddyClient($throttleMs);

        // ── 1) Üniversiteler ──
        $this->info('1/3 — Üniversiteler indiriliyor...');
        $unis = $client->listUniversities();
        $now = now();

        foreach ($unis as $u) {
            ExpatrioUniversity::query()->updateOrCreate(
                ['id' => $u['id']],
                ['name' => $u['name'], 'synced_at' => $now]
            );
        }
        $this->info('   ✓ ' . count($unis) . ' üniversite kaydedildi.');

        // ── 2) Tüm programlar (single-shot — limit yüksek tek POST) ──
        $this->info('2/3 — Program listesi indiriliyor (single-shot)...');
        $singleShotLimit = $limit > 0 ? $limit : 20000; // 13K aşan büyük limit → tüm liste
        $page = $client->searchPrograms($singleShotLimit, 0);
        $programs = $page['programs'];
        $totalRemote = $page['total'];
        $this->info("   Server toplam: {$totalRemote}, çekildi: " . count($programs));

        $totalFetched = 0;
        $allListIds = [];
        $skippedNoUni = 0;

        foreach ($programs as $p) {
            $uniId = $this->resolveUniversityId($p['universityName'] ?? null);
            if (! $uniId) {
                $skippedNoUni++;
                continue;
            }
            $allListIds[] = $p['id'];

            ExpatrioProgram::query()->updateOrCreate(
                ['id' => $p['id']],
                [
                    'university_id'             => $uniId,
                    'university_name'           => (string) ($p['universityName'] ?? ''),
                    'course_name'               => (string) ($p['courseName'] ?? ''),
                    'degree_specification'      => $p['degreeSpecification'] ?? null,
                    'location'                  => $p['location'] ?? null,
                    'languages'                 => (array) ($p['languages'] ?? []),
                    'tuition_fees_per_semester' => isset($p['tuitionFeesPerSemester']) ? (int) $p['tuitionFeesPerSemester'] : null,
                    'synced_at'                 => $now,
                ]
            );
            $totalFetched++;

            if ($totalFetched % 1000 === 0) {
                $this->info(sprintf('   ... %d / %d kaydedildi', $totalFetched, count($programs)));
            }
        }

        $this->info("   ✓ {$totalFetched} program kaydedildi (skip: {$skippedNoUni}).");

        // ── 3) Detay endpoint (opsiyonel — yavaş) ──
        if ($fetchDetails && ! empty($allListIds)) {
            $this->info('3/3 — Detay verileri indiriliyor (yavaş — rate limit ile)...');
            $bar = $this->output->createProgressBar(count($allListIds));
            $bar->start();

            foreach ($allListIds as $id) {
                $detail = $client->getProgram($id);
                if ($detail) {
                    ExpatrioProgram::query()->where('id', $id)->update([
                        'study_fields'    => array_values((array) ($detail['studyFields'] ?? [])),
                        'subjects'        => array_values((array) ($detail['subjects'] ?? [])),
                        'semester_count'  => isset($detail['semesterCount']) ? (int) $detail['semesterCount'] : null,
                        'data'            => $detail,
                        'synced_at'       => now(),
                    ]);
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
            $this->info("   ✓ Detaylar kaydedildi.");
        } else {
            $this->info('3/3 — Detay endpoint atlandı (--details flag\'i verilmedi).');
        }

        // ── Üni program sayılarını güncelle ──
        $this->info('Üniversite program sayıları hesaplanıyor...');
        ExpatrioUniversity::query()->each(function (ExpatrioUniversity $u) {
            $u->program_count = ExpatrioProgram::where('university_id', $u->id)->count();
            $u->save();
        });

        $this->info("\n✅ Senkronizasyon tamamlandı.");
        $this->info('   Üniversite: ' . ExpatrioUniversity::count());
        $this->info('   Program:    ' . ExpatrioProgram::count());

        return self::SUCCESS;
    }

    /**
     * Üniversite name'inden ID'yi resolve eder. Search response'unda sadece
     * universityName var; üni katalogunda name → id eşleştirmesi cache'lenir.
     */
    private array $uniNameToId = [];

    private function resolveUniversityId(?string $name): ?string
    {
        if ($name === null || $name === '') return null;

        if (empty($this->uniNameToId)) {
            $this->uniNameToId = ExpatrioUniversity::query()->pluck('id', 'name')->all();
        }
        return $this->uniNameToId[$name] ?? null;
    }
}
