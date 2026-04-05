<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ip_access_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('rule_type', 20);        // whitelist, blacklist
            $table->string('ip_range', 45);         // tek IP veya CIDR
            $table->string('description', 180)->nullable();
            $table->json('applies_to_roles')->nullable(); // null = tüm roller
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 191)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['company_id', 'is_active'], 'idx_company_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_access_rules');
    }
};
