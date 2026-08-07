<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner firmanın yeni adaylarına atanacak VARSAYILAN danışman.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Otomatik atama en az yüklü danışmanı seçiyor. Yükler eşitken sıralama hep
 * aynı kişiyi öne çıkarıyor; pratikte her yeni aday aynı danışmana düşüyor.
 * Üst firma "bu partnerin işlerine şu danışman baksın" diyebilmeli.
 *
 * E-posta tutuluyor, id değil: sistemin geri kalanı danışmanı
 * `senior_email` üzerinden tanıyor (student_assignments, guest_applications).
 * Id tutmak burada tek başına farklı bir kimlik alanı yaratırdı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('default_advisor_email', 190)->nullable()->after('parent_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('default_advisor_email');
        });
    }
};
