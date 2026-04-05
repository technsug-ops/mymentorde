<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('process_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('code')->unique();
            $table->string('name_tr');
            $table->string('name_de');
            $table->string('name_en');
            $table->text('description_tr')->nullable();
            $table->text('description_de')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mandatory')->default(true);
            $table->json('applicable_student_types')->nullable();
            $table->json('default_checklist')->nullable();
            $table->string('revenue_milestone_id')->nullable();
            $table->string('color', 32)->nullable();
            $table->string('icon', 64)->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_definitions');
    }
};
