<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UniMatch lead'leri için drip framework'üne enrollment desteği:
     * - uni_match_response_id (nullable FK) — guest_application_id yoksa kullan
     * - guest_application_id'yi nullable yap — UniMatch lead'inde GuestApplication yok
     */
    public function up(): void
    {
        // 1. guest_application_id nullable yap — bu doctrine/dbal gerekebilir,
        //    onun yerine ham SQL kullanıyorum (Laravel default'ta change() için DBAL gerek)
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE email_drip_enrollments MODIFY guest_application_id BIGINT UNSIGNED NULL'
        );

        // 2. uni_match_response_id ekle (zaten yoksa)
        Schema::table('email_drip_enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('email_drip_enrollments', 'uni_match_response_id')) {
                $table->unsignedBigInteger('uni_match_response_id')->nullable()->after('guest_application_id');
                $table->index('uni_match_response_id', 'edre_unimatch_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_drip_enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('email_drip_enrollments', 'uni_match_response_id')) {
                $table->dropIndex('edre_unimatch_idx');
                $table->dropColumn('uni_match_response_id');
            }
            // guest_application_id NOT NULL'a geri çevirme — risk: NULL row varsa fail eder.
        });
    }
};
