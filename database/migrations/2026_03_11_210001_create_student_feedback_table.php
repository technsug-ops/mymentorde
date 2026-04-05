<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_feedback', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 20);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('feedback_type', 30);        // process_step, general, nps
            $table->string('process_step', 50)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();    // 1-5
            $table->unsignedTinyInteger('nps_score')->nullable(); // 0-10
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['student_id', 'feedback_type'], 'idx_student_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_feedback');
    }
};
