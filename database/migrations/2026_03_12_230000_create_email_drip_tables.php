<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_drip_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('trigger_event', 50);  // guest_registered, contract_signed, package_selected
            $table->unsignedBigInteger('segment_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 191)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('email_drip_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drip_sequence_id');
            $table->unsignedInteger('step_order');
            $table->unsignedInteger('delay_hours')->default(24);
            $table->unsignedBigInteger('template_id');
            $table->string('subject_override', 191)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('drip_sequence_id')->references('id')->on('email_drip_sequences')->onDelete('cascade');
            $table->index(['drip_sequence_id', 'step_order'], 'idx_sequence_order');
        });

        Schema::create('email_drip_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drip_sequence_id');
            $table->unsignedBigInteger('guest_application_id');
            $table->unsignedInteger('current_step')->default(0);
            $table->string('status', 20)->default('active'); // active, completed, unsubscribed, paused
            $table->timestamp('next_send_at')->nullable();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->unique(['drip_sequence_id', 'guest_application_id'], 'idx_sequence_guest');
            $table->index('next_send_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_drip_enrollments');
        Schema::dropIfExists('email_drip_steps');
        Schema::dropIfExists('email_drip_sequences');
    }
};
