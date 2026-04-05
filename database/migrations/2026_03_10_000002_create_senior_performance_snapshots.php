<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_performance_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('senior_email', 190)->index();
            $table->string('period', 7)->index(); // '2026-03'
            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('active_count')->default(0);
            $table->unsignedInteger('converted_count')->default(0);
            $table->unsignedInteger('university_accepted_count')->default(0);
            $table->unsignedInteger('university_rejected_count')->default(0);
            $table->unsignedInteger('visa_approved_count')->default(0);
            $table->float('avg_process_days')->nullable();
            $table->decimal('revenue_generated', 12, 2)->default(0);
            $table->timestamp('snapshotted_at')->nullable();
            $table->timestamps();

            $table->unique(['senior_email', 'period']);
            $table->index(['company_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_performance_snapshots');
    }
};
