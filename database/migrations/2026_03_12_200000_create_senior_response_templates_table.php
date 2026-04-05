<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_response_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable(); // NULL = company-wide
            $table->string('category', 50); // document, visa, language, housing, payment, general
            $table->string('title', 180);
            $table->text('body'); // supports {{student_name}}, {{university}}, {{deadline}}
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'category'], 'idx_company_category');
            $table->index(['owner_user_id'], 'idx_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_response_templates');
    }
};
