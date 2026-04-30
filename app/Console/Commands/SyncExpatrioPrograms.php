<?php

namespace App\Console\Commands;

use App\Models\ExpatrioUniversity;
use App\Models\Program;
use App\Models\ProgramChangeLog;
use App\Models\ProgramSourceLink;
use App\Services\ExpatrioStudyBuddyClient;
use App\Services\ProgramCatalog\ExpatrioCatalogAdapter;
use Illuminate\Console\Command;

/**
 * Expatrio Study Buddy → Canonical Program kataloğu senkronize.
 *
 * Faz 1 refactor: Adapter canonical layer'a yazar; mevcut ExpatrioProgram
 * eski cache hâlâ duruyor ama kullanılmıyor (legacy, ileride drop).
 *
 * Akış:
 *  1. Expatrio /programs/search → 13K liste tek POST
 *  2. Her item için ExpatrioCatalogAdapter::upsertCanonical()
 *     → University findOrCreate
 *     → Canonical Program upsert (manuel curation öncelikli)
 *     → ChangeDetectionService.record (diff log)
 *  3. Detail endpoint opsiyonel (--details) — extra alanlar
 *
 * Çıktı: yeni eklenen / güncellenen / değişiklik tespit edilen sayılar
 * + critical change'leri terminal'de göster.
 */
class SyncExpatrioPrograms extends Command
{
    protected $signature = 'expatrio:sync
        {--limit=0 : Sadece bu kadar program çek (0 = sınırsız)}
        {--details : Her program için detay endpoint çağır (yavaş)}
        {--throttle=400 : İstekler arası bekleme (ms)}';

    protected $description = 'Expatrio Study Buddy → Canonical Program kataloğunu senkronize eder (change detection ile)';

    public function handle(): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $fetchDetails = (bool) $this->option('details');
        $throttleMs = max(0, (int) $this->option('throttle'));

        $client = new ExpatrioStudyBuddyClient($throttleMs);
        $adapter = app(ExpatrioCatalogAdapter::class);

        // ── 1) Liste indir (single-shot, max 20K limit) ─────────────
        $this->info('1/2 — Expatrio program listesi indiriliyor...');
        $singleShotLimit = $limit > 0 ? $limit : 20000;
        $page = $client->searchPrograms($singleShotLimit, 0);
        $programs = $page['programs'];
        $totalRemote = $page['total'];
        $this->info("   Server toplam: {$totalRemote}, çekildi: " . count($programs));

        // ── 2) Canonical layer'a upsert + change detection ──────────
        $this->info('2/2 — Canonical layer\'a yazılıyor...');
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
        $totalDeltas = 0;
        $criticalChanges = 0;

        foreach ($programs as $i => $raw) {
            try {
                $result = $adapter->upsertCanonical($raw);

                if ($result['was_created']) {
                    $created++;
                } elseif (! empty($result['canonical_delta'])) {
                    $updated++;
                    $totalDeltas += count($result['canonical_delta']);
                    // Critical alanlar var mı bu delta'da?
                    foreach (array_keys($result['canonical_delta']) as $field) {
                        if (in_array($field, ['university_name_cached', 'course_name', 'is_active'], true)) {
                            $criticalChanges++;
                        }
                    }
                } else {
                    $unchanged++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                $this->warn("   ⚠ Skip: " . substr($e->getMessage(), 0, 100));
            }

            // Detay endpoint opsiyonel
            if ($fetchDetails && isset($raw['id'])) {
                try {
                    $detail = $client->getProgram($raw['id']);
                    if ($detail) {
                        $adapter->upsertCanonical(array_merge($raw, $detail));
                    }
                } catch (\Throwable $e) {
                    // detay yoksa atla
                }
            }

            if (($i + 1) % 1000 === 0) {
                $this->info(sprintf('   ... %d / %d (created:%d updated:%d unchanged:%d)', $i + 1, count($programs), $created, $updated, $unchanged));
            }

            if ($limit > 0 && ($i + 1) >= $limit) break;
        }

        $this->newLine();
        $this->info('✅ Sync tamamlandı.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created (yeni canonical)',   $created],
                ['Updated (değişiklik tespit)', $updated],
                ['Unchanged',                   $unchanged],
                ['Skipped (hata)',              $skipped],
                ['Total field deltas',          $totalDeltas],
                ['CRITICAL changes',            $criticalChanges],
            ]
        );

        $this->info('   Canonical Program: ' . Program::count());
        $this->info('   Source links (Expatrio): ' . ProgramSourceLink::where('source', 'expatrio')->count());
        $this->info('   Total change logs: ' . ProgramChangeLog::count());

        return self::SUCCESS;
    }
}
