<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_performance_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('senior_email', 191);
            $table->string('period', 7);     // '2026-03'
            $table->unsignedInteger('target_conversions')->default(0);
            $table->unsignedInteger('target_outcomes')->default(0);
            $table->unsignedInteger('target_doc_reviews')->default(0);
            $table->unsignedInteger('target_appointments')->default(0);
            $table->unsignedBigInteger('set_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['senior_email', 'period'], 'idx_senior_period');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_performance_targets');
    }
};
