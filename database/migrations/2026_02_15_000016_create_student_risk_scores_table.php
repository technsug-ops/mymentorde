<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_risk_scores', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 64)->unique();
            $table->unsignedInteger('current_score')->default(0);
            $table->string('risk_level', 16)->default('low');
            $table->json('factors')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->json('history')->nullable();
            $table->timestamps();

            $table->index(['risk_level', 'current_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_risk_scores');
    }
};

