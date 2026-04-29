<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessizlik check-in akışı:
 *  - aday/öğrencinin timeline'ında N gündür hareket yoksa sistem otomatik
 *    "süreç aktif" tipi touchpoint düşürür.
 *  - kadans hiyerarşi: kişi override > şirket override > config default.
 *  - dedup: last_silence_checkin_at — üst üste tetiklemesin.
 *  - manager kişi bazında pause edebilir (banka sorunu, mücbir sebep değil
 *    burada — örn. müşteri "şu an arama" dediyse).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->unsignedSmallInteger('silence_checkin_days_override')->nullable()->after('last_senior_action_at');
            $table->timestamp('silence_checkin_paused_at')->nullable()->after('silence_checkin_days_override');
            $table->timestamp('last_silence_checkin_at')->nullable()->after('silence_checkin_paused_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('silence_checkin_days_override')->nullable();
            $table->timestamp('silence_checkin_paused_at')->nullable();
            $table->timestamp('last_silence_checkin_at')->nullable();
        });

        Schema::table('companies', function (Blueprint $table) {
            // Stage başına gün override: {"application": 7, "uni_assist": 7, "visa": 14}
            $table->json('silence_checkin_overrides')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->dropColumn([
                'silence_checkin_days_override',
                'silence_checkin_paused_at',
                'last_silence_checkin_at',
            ]);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'silence_checkin_days_override',
                'silence_checkin_paused_at',
                'last_silence_checkin_at',
            ]);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('silence_checkin_overrides');
        });
    }
};
