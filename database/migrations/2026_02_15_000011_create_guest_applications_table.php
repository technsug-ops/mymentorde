<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('tracking_token', 48)->unique();
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->string('email', 190);
            $table->string('phone', 60)->nullable();
            $table->string('application_type', 32);
            $table->string('target_term', 60)->nullable();
            $table->string('target_city', 100)->nullable();
            $table->string('language_level', 32)->nullable();
            $table->string('lead_source', 64)->default('organic');
            $table->string('dealer_code', 64)->nullable();
            $table->string('campaign_code', 64)->nullable();
            $table->string('branch', 64)->nullable();
            $table->string('priority', 16)->default('normal');
            $table->string('risk_level', 16)->default('normal');
            $table->string('lead_status', 32)->default('new');
            $table->text('notes')->nullable();
            $table->boolean('kvkk_consent')->default(false);
            $table->boolean('docs_ready')->default(false);
            $table->boolean('converted_to_student')->default(false);
            $table->text('status_message')->nullable();
            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['lead_status', 'converted_to_student']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_applications');
    }
};

