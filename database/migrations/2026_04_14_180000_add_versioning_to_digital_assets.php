<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAM6 — Lightweight asset versioning.
 *
 * Model:
 *   - Her asset bir "version group"a ait (version_group_id — UUID-style string
 *     veya ilk version'un id'si)
 *   - version_number: 1, 2, 3, ...
 *   - is_current_version: aktif/gösterilen version
 *   - version_note: upload notları
 *
 * Bir asset'in yeni versiyonu yüklendiğinde:
 *   1. Yeni DigitalAsset row oluşur (aynı version_group_id)
 *   2. Eski version'un is_current_version=false olur
 *   3. Yeni version is_current_version=true
 *
 * Listeleme varsayılan olarak sadece is_current_version=true gösterir.
 * "Geçmiş versiyonlar" butonu ile version_group_id üzerinden geçmiş çekilir.
 *
 * Önceki version kalıp için restore edilebilir: current flag'i swap edilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('digital_assets')) {
            return;
        }

        Schema::table('digital_assets', function (Blueprint $table): void {
            if (!Schema::hasColumn('digital_assets', 'version_group_id')) {
                $table->string('version_group_id', 64)->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('digital_assets', 'version_number')) {
                $table->unsignedSmallInteger('version_number')->default(1)->after('version_group_id');
            }
            if (!Schema::hasColumn('digital_assets', 'is_current_version')) {
                $table->boolean('is_current_version')->default(true)->after('version_number')->index();
            }
            if (!Schema::hasColumn('digital_assets', 'version_note')) {
                $table->string('version_note', 500)->nullable()->after('is_current_version');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('digital_assets')) {
            return;
        }

        Schema::table('digital_assets', function (Blueprint $table): void {
            if (Schema::hasColumn('digital_assets', 'version_note')) {
                $table->dropColumn('version_note');
            }
            if (Schema::hasColumn('digital_assets', 'is_current_version')) {
                $table->dropColumn('is_current_version');
            }
            if (Schema::hasColumn('digital_assets', 'version_number')) {
                $table->dropColumn('version_number');
            }
            if (Schema::hasColumn('digital_assets', 'version_group_id')) {
                $table->dropColumn('version_group_id');
            }
        });
    }
};
