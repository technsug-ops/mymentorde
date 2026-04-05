<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('marketing_events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->string('mentorde_id')->nullable();

            $table->string('status')->default('registered');
            $table->timestamp('attended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->boolean('survey_completed')->default(false);
            $table->unsignedTinyInteger('survey_score')->nullable();
            $table->text('survey_feedback')->nullable();

            $table->boolean('converted_to_guest_after')->default(false);
            $table->string('converted_guest_id')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('registered_at');
            $table->timestamp('created_at');

            $table->index(['event_id', 'status']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
