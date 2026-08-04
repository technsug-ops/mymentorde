<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Çok-şirketli personel erişimi (Faz 3).
 *
 * AYRIM:
 *   users.company_id  → AİDİYET  (kullanıcı kime ait; tek değer)
 *   company_user      → ERİŞİM   (nereye girebilir; çok değer)
 *
 * Neden gerekli: MentorDE ekibi partner firmaların (YourGermanUni altındaki
 * A/B/C) öğrenci işlemlerini yapacak. Bir senior'ın 3 firmanın öğrencilerini
 * tek listede görmesi gerekiyor — ama firma kullanıcıları YALNIZCA kendi
 * şirketlerini görmeli.
 *
 * Firma kullanıcılarının bu tabloda satırı OLMAZ → görünür kümesi tek elemanlı
 * kalır (users.company_id). Yani mevcut davranış değişmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_user')) {
            return;
        }

        Schema::create('company_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');

            // operator = kayıt oluşturup düzenleyebilir, viewer = sadece okur.
            $table->string('role_in_company', 32)->default('operator');

            // Kullanıcının varsayılan çalışma şirketi (şirket seçicide ön seçili gelir).
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'company_id'], 'company_user_unique');
            $table->index('company_id', 'company_user_company_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
