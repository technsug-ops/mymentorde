<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AVV Registry — DSGVO Art. 28.
 * Üçüncü taraf veri işleyici sözleşmelerinin (Auftragsverarbeitungsverträge)
 * arşivi. Hosting, email, CRM, analytics, payment provider'lar için.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('data_processing_agreements')) return;

        Schema::create('data_processing_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('provider_name', 190);
            $table->string('provider_url', 255)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->string('avv_pdf_path', 255)->nullable(); // storage/local
            $table->date('signed_date')->nullable();
            $table->date('expires_date')->nullable();
            $table->string('country', 100)->nullable();      // sağlayıcının veri merkezi ülkesi
            $table->boolean('eu_based')->default(true);
            $table->json('processed_categories')->nullable();// hangi veri kategorileri (email,name,...)
            $table->string('purpose_summary', 500)->nullable();// kısa açıklama
            $table->enum('status', ['active','pending','expired','terminated'])->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index('expires_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_processing_agreements');
    }
};
