<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_achievements', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 20);
            $table->string('achievement_code', 50);
            $table->timestamp('earned_at')->useCurrent();

            $table->unique(['student_id', 'achievement_code'], 'idx_student_achievement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};
