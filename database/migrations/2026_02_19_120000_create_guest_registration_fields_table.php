<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_registration_fields', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(0)->index();
            $table->string('section_key', 80)->index();
            $table->string('section_title', 140);
            $table->unsignedInteger('section_order')->default(100);
            $table->string('field_key', 100);
            $table->string('label', 190);
            $table->string('type', 32)->default('text');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(100);
            $table->unsignedInteger('max_length')->nullable();
            $table->string('placeholder', 255)->nullable();
            $table->string('help_text', 500)->nullable();
            $table->json('options_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'field_key']);
            $table->index(['company_id', 'section_order', 'sort_order'], 'grf_company_section_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_registration_fields');
    }
};

