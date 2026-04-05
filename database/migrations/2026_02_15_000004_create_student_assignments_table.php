<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 64)->unique();
            $table->string('senior_email')->nullable();
            $table->string('branch', 64)->nullable();
            $table->string('risk_level', 16)->default('normal');
            $table->string('payment_status', 32)->default('ok');
            $table->string('dealer_id', 64)->nullable();
            $table->string('student_type', 32)->nullable();
            $table->boolean('is_archived')->default(false);
            $table->string('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['senior_email', 'is_archived']);
            $table->index(['risk_level', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_assignments');
    }
};

