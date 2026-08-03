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
        // 1. guest_application_id nullable yap.
        //    Laravel 11+ change() icin artik doctrine/dbal gerekmiyor; ham "MODIFY" SQL'i
        //    MySQL'e ozgu oldugu icin SQLite (test DB) uzerinde tum migration zincirini
        //    dusuruyordu. change() her iki surucude de calisir.
        Schema::table('email_drip_enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('guest_application_id')->nullable()->change();
        });

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
