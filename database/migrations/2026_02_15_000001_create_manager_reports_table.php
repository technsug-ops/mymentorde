<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('report_type', 32)->default('manual');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('senior_email')->nullable();
            $table->json('stats');
            $table->json('funnel')->nullable();
            $table->json('trend')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_reports');
    }
};

