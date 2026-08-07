<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firma başına hizmet kataloğu — paketler ve ek hizmetler.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * Paketler `config/service_packages.php` içinde koda gömülüydü: tüm firmalar
 * için aynı ve değiştirmek deploy gerektiriyordu. Firmalar kendi
 * hizmetlerini detaylandırıp fiyatlandırabilmeli.
 *
 * ── MİRAS ───────────────────────────────────────────────────────────────
 * Çözüm sırası, sistemdeki diğer miraslarla aynı desende:
 *   firmanın kendi kataloğu → üst firmalar → config (fabrika kataloğu)
 * Firma kendi satırlarını edinmedikçe üstündekini kullanır.
 *
 * ── ALAN ŞEKLİ ──────────────────────────────────────────────────────────
 * Kolonlar config'teki dizinin BİREBİR karşılığı. Çözümleyici DB satırını
 * aynı diziye çeviriyor; böylece 35 okuma noktasının hiçbiri veri şeklini
 * bilmek zorunda kalmıyor.
 *
 * `price` (metin, "2.000 EUR") ve `price_amount` (sayı) ikisi de duruyor:
 * mevcut kod ikisini de okuyor, biri gösterim biri hesap için.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_service_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            $table->string('code', 64);
            $table->string('title', 190);
            $table->string('price', 64)->nullable();          // gosterim: "2.000 EUR"
            $table->decimal('price_amount', 10, 2)->default(0); // hesap
            $table->string('currency', 8)->default('EUR');
            $table->string('includes', 255)->nullable();

            // Icerik — firma degistirebiliyor.
            $table->json('features')->nullable();
            $table->json('included_categories')->nullable();
            $table->json('included_extras')->nullable();

            $table->unsignedInteger('max_universities')->nullable();
            $table->boolean('includes_visa')->default(false);
            $table->boolean('includes_housing')->default(false);
            $table->string('support_level', 32)->nullable();
            $table->unsignedInteger('validity_months')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('updated_by', 190)->nullable();
            $table->timestamps();

            // Bir firmada aynı kod iki kez olamaz.
            $table->unique(['company_id', 'code']);
        });

        Schema::create('company_service_extras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            $table->string('code', 64);
            $table->string('category', 64)->nullable();
            $table->string('title', 190);
            $table->string('price', 64)->nullable();
            $table->decimal('price_amount', 10, 2)->default(0);
            $table->string('currency', 8)->default('EUR');
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('updated_by', 190)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_service_extras');
        Schema::dropIfExists('company_service_packages');
    }
};
