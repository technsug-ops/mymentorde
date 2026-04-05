<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_onboarding_steps', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 20);
            $table->string('step_code', 50); // welcome, profile, meet_senior, first_docs, select_package
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['student_id', 'step_code'], 'idx_student_step');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_onboarding_steps');
    }
};
