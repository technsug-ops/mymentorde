<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_requirement_maps', function (Blueprint $table): void {
            $table->id();
            $table->string('university_code', 32)->index();
            $table->string('department_code', 64)->nullable()->index(); // null = üniversite geneli
            $table->string('degree_type', 32); // master|bachelor|phd|ausbildung
            $table->string('semester', 8)->default('both'); // WS|SS|both
            $table->string('portal_name', 32)->default('uni_assist'); // uni_assist|direct|other
            $table->tinyInteger('deadline_month_ws')->nullable(); // 1-12
            $table->tinyInteger('deadline_day_ws')->nullable();   // 1-31
            $table->tinyInteger('deadline_month_ss')->nullable();
            $table->tinyInteger('deadline_day_ss')->nullable();
            $table->json('required_document_codes');               // ['APP-CV','APP-APS',...]
            $table->json('recommended_document_codes')->nullable();
            $table->string('language_requirement', 128)->nullable(); // 'DSH-2 oder TestDaF 4x4'
            $table->decimal('min_gpa', 3, 2)->nullable();            // ör. 2.50 (Alman skalası)
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['university_code', 'department_code', 'degree_type', 'semester'], 'uni_req_map_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_requirement_maps');
    }
};
