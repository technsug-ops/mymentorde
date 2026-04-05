<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guest_onboarding_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_application_id');
            $table->string('step_code', 50);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['guest_application_id', 'step_code'], 'idx_guest_step');
            $table->index('guest_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_onboarding_steps');
    }
};
