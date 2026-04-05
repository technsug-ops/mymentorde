<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-configurable scoring rules
        Schema::create('lead_scoring_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('action_code', 64)->unique();
            $table->string('category', 32); // behavioral / demographic / decay
            $table->string('label');
            $table->integer('points');
            $table->unsignedSmallInteger('max_per_day')->nullable();
            $table->boolean('is_one_time')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Audit trail of every score change
        Schema::create('lead_score_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guest_application_id')->index();
            $table->string('action_code', 64);
            $table->integer('points');
            $table->integer('score_before');
            $table->integer('score_after');
            $table->string('tier_before', 20)->nullable();
            $table->string('tier_after', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('guest_application_id')
                ->references('id')->on('guest_applications')
                ->cascadeOnDelete();
            $table->index(['guest_application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_score_logs');
        Schema::dropIfExists('lead_scoring_rules');
    }
};
