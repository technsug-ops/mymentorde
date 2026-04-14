<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DAM7 — Full-text search: MySQL FULLTEXT index on searchable columns.
 *
 * LIKE %term% yerine FULLTEXT MATCH() AGAINST() kullanıldığında:
 * - Büyük tablolarda 10-100x hızlanma
 * - Natural language mode + boolean mode destekler
 * - Stop words handling
 *
 * SQLite FULLTEXT index desteklemez (FTS virtual table var ama karmaşık);
 * SQLite'ta LIKE fallback kullanılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('digital_assets') || DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE digital_assets ADD FULLTEXT INDEX ft_dam_search (name, original_filename, description, doc_code)');
        } catch (\Throwable $e) {
            // InnoDB FULLTEXT destekliyor (MySQL 5.6+), hata olursa sessizce geç
            // (MyISAM olsaydı da çalışır; dev/test için daha önemli)
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('digital_assets') || DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE digital_assets DROP INDEX ft_dam_search');
        } catch (\Throwable $e) {
            // Index yoksa hata verir, ignore
        }
    }
};
