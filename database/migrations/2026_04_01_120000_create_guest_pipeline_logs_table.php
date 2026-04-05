<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_pipeline_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_application_id');
            $table->string('from_stage', 60)->nullable();
            $table->string('to_stage', 60);
            $table->string('moved_by_name')->nullable();
            $table->string('moved_by_email')->nullable();
            $table->string('contact_method', 40)->nullable();  // mail/phone/whatsapp/linkedin/digital/physical
            $table->string('contact_result', 60)->nullable();  // reached/not_available/callback/appointment/...
            $table->string('lost_reason', 60)->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('guest_application_id')->references('id')->on('guest_applications')->onDelete('cascade');
            $table->index(['guest_application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_pipeline_logs');
    }
};
