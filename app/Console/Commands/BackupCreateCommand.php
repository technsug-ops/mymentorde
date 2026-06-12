<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Otomatik DB backup — pure PHP mysqldump alternatifi.
 *
 * KAS shared host'ta `mysqldump` binary olmadığı için (SSH yok) tablo'ları
 * SELECT * ile okur, CREATE TABLE + INSERT statement'larını üretip gzip ile sıkıştırır.
 * Sonuç: storage/app/backups/db_YYYY-MM-DD_HHMMSS.sql.gz
 *
 * Çağrı:
 *   php artisan backup:create
 *   php artisan backup:create --keep=14   # default 14 gün retention
 *
 * Schedule (routes/console.php):
 *   Schedule::command('backup:create')->dailyAt('03:00')->withoutOverlapping();
 */
class BackupCreateCommand extends Command
{
    protected $signature = 'backup:create {--keep=14 : Retention gün sayısı (eski yedekler silinir)}';

    protected $description = 'Veritabanı yedeği oluştur (gzip\'li SQL dump) + eski yedekleri temizle.';

    /** Yedek almama gereken tablolar (büyük + reproducible). */
    private const SKIP_TABLES = [
        'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches',
        'failed_jobs', 'telescope_entries', 'telescope_entries_tags',
        'telescope_monitoring', 'pulse_*',
    ];

    public function handle(): int
    {
        $start = microtime(true);
        $now = Carbon::now();
        $name = 'db_' . $now->format('Y-m-d_His') . '.sql.gz';
        $tmpPath = sys_get_temp_dir() . '/' . 'mentorde_backup_' . uniqid() . '.sql';

        $this->info("📦 Backup başlatılıyor — {$name}");

        try {
            $bytesWritten = $this->writeDump($tmpPath);
        } catch (\Throwable $e) {
            $this->error('Dump oluşturulamadı: ' . $e->getMessage());
            Log::error('backup:create dump failed', ['error' => $e->getMessage()]);
            @unlink($tmpPath);
            return self::FAILURE;
        }

        // Gzip ile sıkıştırıp Storage'a yaz
        try {
            $contents = (string) file_get_contents($tmpPath);
            $gz = gzencode($contents, 6); // dengeli sıkıştırma
            @unlink($tmpPath);

            if ($gz === false) {
                throw new \RuntimeException('gzencode başarısız');
            }

            Storage::disk('local')->put("backups/{$name}", $gz);
            $size = strlen($gz);

            $this->info(sprintf(
                '✓ Yedek tamam: %s (%.2f MB, %.2fs)',
                $name,
                $size / 1024 / 1024,
                microtime(true) - $start
            ));
        } catch (\Throwable $e) {
            $this->error('Sıkıştırma/depolama başarısız: ' . $e->getMessage());
            Log::error('backup:create save failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        // Eski yedekleri temizle
        $keep = max(1, (int) $this->option('keep'));
        $this->purgeOld($keep);

        Log::info('backup:create success', [
            'file'     => $name,
            'size_kb'  => (int) ($size / 1024),
            'duration' => round(microtime(true) - $start, 2),
        ]);

        return self::SUCCESS;
    }

    private function writeDump(string $path): int
    {
        $fp = fopen($path, 'w');
        if (!$fp) {
            throw new \RuntimeException("Tmp dosya açılamadı: {$path}");
        }

        $dbName = config('database.connections.' . config('database.default') . '.database');

        $header = "-- MentorDE DB Backup\n";
        $header .= "-- Generated: " . Carbon::now()->toIso8601String() . "\n";
        $header .= "-- Database: {$dbName}\n";
        $header .= "-- Charset: utf8mb4\n\n";
        $header .= "SET NAMES utf8mb4;\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        $bytes = fwrite($fp, $header) ?: 0;

        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->filter(function ($table) {
                foreach (self::SKIP_TABLES as $skip) {
                    if (str_ends_with($skip, '*')) {
                        $prefix = rtrim($skip, '*');
                        if (str_starts_with($table, $prefix)) return false;
                    } elseif ($table === $skip) {
                        return false;
                    }
                }
                return true;
            })
            ->values();

        foreach ($tables as $table) {
            $bytes += $this->dumpTable($fp, $table);
        }

        $footer = "\nSET FOREIGN_KEY_CHECKS=1;\n";
        $bytes += fwrite($fp, $footer) ?: 0;
        fclose($fp);

        return $bytes;
    }

    private function dumpTable($fp, string $table): int
    {
        $bytes = 0;
        $bytes += fwrite($fp, "\n-- ─────── Table: {$table} ───────\n") ?: 0;
        $bytes += fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n") ?: 0;

        // CREATE TABLE
        $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
        $createSql = $create['Create Table'] ?? $create['Create View'] ?? '';
        if (!empty($createSql)) {
            $bytes += fwrite($fp, $createSql . ";\n\n") ?: 0;
        }

        // INSERT — 500'lük chunk'larla
        $columns = collect(DB::select("SHOW COLUMNS FROM `{$table}`"))
            ->map(fn ($c) => '`' . $c->Field . '`')
            ->implode(', ');

        $offset = 0;
        $chunkSize = 500;
        $pdo = DB::connection()->getPdo();

        do {
            $rows = DB::select("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}");
            if (empty($rows)) break;

            $values = [];
            foreach ($rows as $row) {
                $arr = (array) $row;
                $escaped = array_map(function ($v) use ($pdo) {
                    if (is_null($v)) return 'NULL';
                    if (is_numeric($v) && !is_string($v)) return (string) $v;
                    return $pdo->quote((string) $v);
                }, $arr);
                $values[] = '(' . implode(', ', $escaped) . ')';
            }

            $insert = "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $values) . ";\n";
            $bytes += fwrite($fp, $insert) ?: 0;

            $offset += $chunkSize;
        } while (count($rows) === $chunkSize);

        return $bytes;
    }

    private function purgeOld(int $keepDays): void
    {
        $cutoff = Carbon::now()->subDays($keepDays);
        $disk = Storage::disk('local');
        $files = $disk->files('backups');
        $deleted = 0;

        foreach ($files as $file) {
            if (!preg_match('/db_(\d{4}-\d{2}-\d{2})_/', basename($file), $m)) {
                continue;
            }
            $fileDate = Carbon::createFromFormat('Y-m-d', $m[1]);
            if ($fileDate && $fileDate->lt($cutoff)) {
                $disk->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("🗑  {$deleted} eski yedek silindi (> {$keepDays} gün)");
        }
    }
}
