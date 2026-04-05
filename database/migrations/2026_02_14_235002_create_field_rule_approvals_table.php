<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_rule_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rule_id')->constrained('field_rules')->cascadeOnDelete();
            $table->string('student_id', 64)->nullable();
            $table->string('guest_id', 64)->nullable();
            $table->string('triggered_field', 255);
            $table->json('triggered_value')->nullable();
            $table->string('severity', 16);
            $table->string('status', 16)->default('pending');
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_rule_approvals');
    }
};
