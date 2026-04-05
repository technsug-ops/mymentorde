<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name_tr');
            $table->string('name_de')->nullable();
            $table->string('name_en')->nullable();
            $table->string('target_field', 255);
            $table->string('target_form', 64);
            $table->json('condition');
            $table->json('exceptions')->nullable();
            $table->string('severity', 16)->default('warning');
            $table->text('warning_message_tr')->nullable();
            $table->text('warning_message_de')->nullable();
            $table->text('warning_message_en')->nullable();
            $table->text('block_message_tr')->nullable();
            $table->text('block_message_de')->nullable();
            $table->text('block_message_en')->nullable();
            $table->json('notify_roles')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->json('approval_roles')->nullable();
            $table->json('applicable_student_types')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['target_form', 'is_active']);
            $table->index(['severity', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_rules');
    }
};
