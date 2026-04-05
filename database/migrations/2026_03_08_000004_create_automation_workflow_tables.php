<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_workflows', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'active', 'paused', 'archived'])->default('draft');
            $table->string('trigger_type', 64);
            $table->json('trigger_config')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->unsignedInteger('enrollment_limit')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('automation_workflow_nodes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->index();
            $table->string('node_type', 64);
            $table->json('node_config')->nullable();
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('connections')->nullable(); // [{target_node_id, condition}]
            $table->timestamps();

            $table->foreign('workflow_id')
                ->references('id')->on('automation_workflows')
                ->cascadeOnDelete();
        });

        Schema::create('automation_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->index();
            $table->unsignedBigInteger('guest_application_id')->index();
            $table->unsignedBigInteger('current_node_id')->nullable();
            $table->enum('status', ['active', 'waiting', 'completed', 'exited', 'errored'])->default('active');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('next_check_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->string('exit_reason', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('workflow_id')
                ->references('id')->on('automation_workflows')
                ->cascadeOnDelete();
            $table->foreign('guest_application_id')
                ->references('id')->on('guest_applications')
                ->cascadeOnDelete();
        });

        Schema::create('automation_enrollment_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('enrollment_id')->index();
            $table->unsignedBigInteger('node_id')->nullable();
            $table->string('action', 64); // entered, executed, waiting, condition_true, condition_false
            $table->json('result')->nullable();
            $table->timestamp('executed_at')->useCurrent();

            $table->foreign('enrollment_id')
                ->references('id')->on('automation_enrollments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_enrollment_logs');
        Schema::dropIfExists('automation_enrollments');
        Schema::dropIfExists('automation_workflow_nodes');
        Schema::dropIfExists('automation_workflows');
    }
};
