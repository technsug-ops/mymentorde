<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_feedback', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guest_application_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('feedback_type', 30);
            $table->string('process_step', 50)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->unsignedTinyInteger('nps_score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['guest_application_id', 'feedback_type'], 'idx_guest_fb_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_feedback');
    }
};
