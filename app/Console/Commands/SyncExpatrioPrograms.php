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
        // Detail mode aktifse her program için detail endpoint çağrılır + raw'a merge.
        // Detail çağrısı throttle ile rate-limit'lidir (~400 ms/req).
        // 13K × 400ms = ~1.5 saat. Background'da çalıştırılmalı.
        $this->info($fetchDetails
            ? '2/2 — Canonical layer\'a yazılıyor (DETAIL endpoint dahil, yavaş, ~1.5 saat)...'
            : '2/2 — Canonical layer\'a yazılıyor (search-only)...');

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
        $detailFetched = 0;
        $detailFailed = 0;
        $totalDeltas = 0;
        $criticalChanges = 0;
        $progressInterval = $fetchDetails ? 100 : 1000;

        foreach ($programs as $i => $raw) {
            // Detail mode: önce detay çek, raw'a merge — search alanlarını override etmez
            if ($fetchDetails && isset($raw['id'])) {
                try {
                    $detail = $client->getProgram((string) $raw['id']);
                    if ($detail) {
                        $raw = array_merge($raw, $detail);
                        $detailFetched++;
                    } else {
                        $detailFailed++;
                    }
                } catch (\Throwable $e) {
                    $detailFailed++;
                }
            }

            try {
                $result = $adapter->upsertCanonical($raw);

                if ($result['was_created']) {
                    $created++;
                } elseif (! empty($result['canonical_delta'])) {
                    $updated++;
                    $totalDeltas += count($result['canonical_delta']);
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
                if ($skipped < 5) $this->warn("   ⚠ Skip: " . substr($e->getMessage(), 0, 100));
            }

            if (($i + 1) % $progressInterval === 0) {
                $detailMsg = $fetchDetails ? " detail:{$detailFetched}" : '';
                $this->info(sprintf('   ... %d / %d (c:%d u:%d nc:%d sk:%d%s)',
                    $i + 1, count($programs), $created, $updated, $unchanged, $skipped, $detailMsg));
            }

            if ($limit > 0 && ($i + 1) >= $limit) break;
        }

        if ($fetchDetails) {
            $this->info("   ✓ Detail: {$detailFetched} başarılı, {$detailFailed} başarısız");
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
