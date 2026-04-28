<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Belge Talep Linki — premium özellik.
 *
 * Manager/danışman aday öğrenciden belirli bir belgeyi tek-kullanımlık
 * imzalı link ile talep eder. Aday linke tıklar, telefonla fotoğraf çeker
 * veya dosya yükler, belge sisteme düşer. Login gerektirmez (token yetki).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('document_upload_tokens')) return;

        Schema::create('document_upload_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('token', 64)->unique(); // 32+ char random
            $table->unsignedBigInteger('guest_application_id')->nullable()->index();
            $table->string('category_code', 64); // hangi belge talep edildi (DOC-PASS, DOC-CV__ vb.)
            $table->string('category_name', 190)->nullable(); // snapshot (TR adı)
            $table->string('document_name_de', 190)->nullable(); // snapshot (DE adı, popup için)
            $table->text('custom_message')->nullable(); // manager'ın eklediği özel mesaj
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
            $table->unsignedSmallInteger('max_uses')->default(1);
            $table->unsignedSmallInteger('used_count')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->string('last_used_user_agent', 255)->nullable();
            $table->unsignedBigInteger('document_id')->nullable()->comment('İlgili Document kaydı (yükleme sonrası)');
            $table->timestamps();

            $table->index(['company_id', 'guest_application_id']);
            $table->index(['expires_at', 'used_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_upload_tokens');
    }
};
