<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_visa_applications')) {
            return;
        }
        Schema::create('student_visa_applications', function (Blueprint $table) {
            $table->id();
            $table->string('company_id', 32)->index();
            $table->string('student_id', 64)->index();

            // Vize bilgileri
            $table->enum('visa_type', ['national_d', 'student_visa', 'language_course', 'other'])->default('national_d');
            $table->enum('status', ['not_started', 'preparing', 'submitted', 'in_review', 'approved', 'rejected', 'expired'])->default('not_started');

            // Tarihler
            $table->date('application_date')->nullable();
            $table->date('appointment_date')->nullable(); // Konsolosluk randevusu
            $table->date('decision_date')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            // Belge bilgileri
            $table->string('visa_number', 64)->nullable();
            $table->string('consulate_city', 100)->nullable(); // İstanbul, Ankara, İzmir
            $table->json('submitted_documents')->nullable(); // ['passport','insurance',...]

            // Notlar (senior tarafından görülebilir)
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Görünürlük
            $table->boolean('is_visible_to_student')->default(true);
            $table->unsignedBigInteger('added_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_visa_applications');
    }
};
