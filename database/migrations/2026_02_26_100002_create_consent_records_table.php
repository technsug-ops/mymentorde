<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('application_id')->nullable()->index();
            // Onay tipi: kvkk | gdpr | marketing | terms
            $table->string('consent_type', 64)->default('kvkk');
            // Metnin versiyonu: "2026-01", "2026-06" gibi
            $table->string('version', 64)->default('2026-01');
            $table->string('ip_address', 100)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->index(['application_id', 'consent_type']);
            $table->index(['user_id', 'consent_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
