<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ROPA — Verarbeitungsverzeichnis (DSGVO Art. 30).
 * Şirketin tüm veri işleme süreçlerinin yazılı katalog.
 * Denetimde ilk istenen belge.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('processing_activities')) return;

        Schema::create('processing_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name', 190);                     // örn. "Newsletter gönderimi"
            $table->string('responsible', 190)->nullable();  // Verantwortlicher
            $table->text('purpose')->nullable();             // İşleme amacı
            $table->json('data_categories')->nullable();     // ["email","name"]
            $table->json('subject_categories')->nullable();  // ["customers","employees"]
            $table->json('recipients')->nullable();          // ["Brevo","DATEV"]
            $table->string('legal_basis', 100)->nullable();  // Art. 6(1)(a/b/c/f)
            $table->boolean('third_country_transfer')->default(false);
            $table->string('third_country_country', 100)->nullable();
            $table->unsignedInteger('retention_days')->nullable();
            $table->text('security_measures')->nullable();   // TOM özeti
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_activities');
    }
};
