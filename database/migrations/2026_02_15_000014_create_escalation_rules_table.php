<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalation_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('entity_type', 64); // field_rule_approval | process_outcome
            $table->unsignedInteger('duration_hours')->default(24);
            $table->json('escalation_steps'); // [{step,after_hours,action,target_roles,channels}]
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_rules');
    }
};

