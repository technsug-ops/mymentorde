<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace Phase 4 — public /randevu directory için ek senior meta alanları.
 *
 * Tüm kolonlar nullable, eski booking akışını etkilemez (addon-independent).
 * - display_name zaten mevcut → eklenmez
 * - tagline, bio, languages, topics, avatar_url, directory_order yeni
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('senior_booking_settings', function (Blueprint $t): void {
            if (!Schema::hasColumn('senior_booking_settings', 'tagline')) {
                $t->string('tagline', 200)->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('senior_booking_settings', 'bio')) {
                $t->text('bio')->nullable()->after('tagline');
            }
            if (!Schema::hasColumn('senior_booking_settings', 'languages')) {
                // ["tr","en","de"] gibi ISO 639-1 kodları
                $t->json('languages')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('senior_booking_settings', 'topics')) {
                // ["uni-applications","visa","document","career"]
                $t->json('topics')->nullable()->after('languages');
            }
            if (!Schema::hasColumn('senior_booking_settings', 'avatar_url')) {
                $t->string('avatar_url', 300)->nullable()->after('topics');
            }
            if (!Schema::hasColumn('senior_booking_settings', 'directory_order')) {
                $t->unsignedInteger('directory_order')->default(100)->after('avatar_url');
            }
        });

        // Listeleme sıralaması için yardımcı index (is_public + is_active zaten var)
        Schema::table('senior_booking_settings', function (Blueprint $t): void {
            try {
                $t->index(['is_public', 'is_active', 'directory_order'], 'sbs_directory_idx');
            } catch (\Throwable $e) {
                // index zaten varsa sessizce geç
            }
        });
    }

    public function down(): void
    {
        Schema::table('senior_booking_settings', function (Blueprint $t): void {
            try { $t->dropIndex('sbs_directory_idx'); } catch (\Throwable $e) {}
            foreach (['directory_order', 'avatar_url', 'topics', 'languages', 'bio', 'tagline'] as $col) {
                if (Schema::hasColumn('senior_booking_settings', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
