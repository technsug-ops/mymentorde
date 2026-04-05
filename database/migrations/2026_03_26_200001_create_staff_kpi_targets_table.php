<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('period', 7);          // YYYY-MM
            $table->unsignedInteger('target_tasks_done')->default(0);
            $table->unsignedInteger('target_tickets_resolved')->default(0);
            $table->decimal('target_hours_logged', 8, 1)->default(0);
            $table->unsignedBigInteger('set_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period']);
            $table->index(['company_id', 'period']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_kpi_targets');
    }
};
