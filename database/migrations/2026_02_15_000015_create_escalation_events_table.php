<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('escalation_rule_id')->constrained('escalation_rules')->cascadeOnDelete();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedInteger('step_no');
            $table->string('action', 32); // remind | escalate
            $table->json('targets')->nullable();
            $table->json('channels')->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->string('status', 16)->default('queued');
            $table->timestamps();

            $table->unique(['escalation_rule_id', 'entity_type', 'entity_id', 'step_no'], 'esc_unique_step');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_events');
    }
};

