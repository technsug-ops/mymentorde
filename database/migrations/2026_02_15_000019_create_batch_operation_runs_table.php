<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_operation_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('operation_type', 64); // notification_broadcast, status_update, etc.
            $table->json('filters')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('target_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 16)->default('done');
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['operation_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_operation_runs');
    }
};

