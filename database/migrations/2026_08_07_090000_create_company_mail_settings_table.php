<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firma başına giden mail taşıyıcısı.
 *
 * ── NEDEN AYRI TABLO ────────────────────────────────────────────────────
 * Kimlik bilgileri `companies` satırına konmadı. O satır her istekte
 * okunuyor, hiyerarşi haritalarında ve marka önbelleğinde dolaşıyor;
 * şifreleri oraya koymak onları gereksiz yere ortalıkta gezdirmek olurdu.
 * Burası yalnızca mail gönderilirken okunuyor.
 *
 * ── NEDEN GEREKLİ ───────────────────────────────────────────────────────
 * White-label platformda gönderim kimliği firmaya ait olmalı. "Kendi mail
 * sunucumu / kendi Resend hesabımı kullanın" diyen firmaya verilecek cevap
 * bu tablo. Vermeyen firma bağlı olduğu portalın taşıyıcısını kullanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_mail_settings', function (Blueprint $table) {
            $table->id();

            // Bir firmanın tek taşıyıcısı olur.
            $table->unsignedBigInteger('company_id')->unique();

            // smtp → evrensel · resend → firmanın kendi Resend hesabı
            $table->string('driver', 20);

            // ── SMTP ──
            $table->string('host', 190)->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('username', 190)->nullable();
            $table->string('encryption', 10)->nullable();   // tls | ssl | null

            // ⚠ Şifreli saklanır (model cast'i). Panelde bir daha GÖSTERİLMEZ.
            $table->text('password')->nullable();
            $table->text('api_key')->nullable();

            // Gönderen adresi — markadan farklıysa. Boşsa marka katmanı karar verir.
            $table->string('from_address', 190)->nullable();

            // Test edilmeden aktifleşmemeli: yanlış kimlik bilgisi o firmanın
            // TÜM mailini sessizce keser.
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_test_error')->nullable();

            $table->string('updated_by', 190)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_mail_settings');
    }
};
