<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_university_applications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('student_id', 64)->index();

            // Üniversite (şimdilik serbest metin; sonraki versiyonda university_catalog.php ile standardize edilecek)
            $table->string('university_code', 64)->nullable()->index(); // gelecek katalog anahtarı
            $table->string('university_name');
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();                   // Federal eyalet

            // Bölüm
            $table->string('department_code', 64)->nullable();          // gelecek katalog anahtarı
            $table->string('department_name');
            $table->enum('degree_type', ['bachelor', 'master', 'phd', 'ausbildung', 'weiterbildung'])
                  ->default('master');

            // Başvuru detayları
            $table->string('semester', 20)->nullable();                 // WS2025/26, SS2026
            $table->enum('application_portal', ['uni_assist', 'hochschulstart', 'direct', 'other'])->nullable();
            $table->string('application_number', 100)->nullable();      // Referans numarası

            // Durum
            $table->enum('status', [
                'planned',              // Planlandı
                'submitted',            // Gönderildi
                'under_review',         // İnceleniyor
                'accepted',             // Kabul
                'conditional_accepted', // Şartlı kabul
                'rejected',             // Ret
                'withdrawn',            // Geri çekildi
            ])->default('planned');

            $table->unsignedTinyInteger('priority')->default(1);        // 1 = birinci tercih
            $table->date('deadline')->nullable();                       // Son başvuru tarihi
            $table->date('submitted_at')->nullable();                   // Gönderildiği tarih
            $table->date('result_at')->nullable();                      // Sonuç geldiği tarih

            $table->text('notes')->nullable();

            // Görünürlük
            $table->boolean('is_visible_to_student')->default(true);    // Öğrenci kendi başvurularını görür
            $table->boolean('is_visible_to_dealer')->default(false);

            $table->unsignedBigInteger('added_by');

            $table->softDeletes();
            $table->timestamps();

            // Composite indexes
            $table->index(['student_id', 'status']);
            $table->index(['student_id', 'priority']);
            $table->index(['student_id', 'is_visible_to_student'], 'sua_sid_visible_idx');
            $table->index(['student_id', 'university_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_university_applications');
    }
};
