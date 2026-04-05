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
        Schema::create('student_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_tr');
            $table->string('name_de');
            $table->string('name_en');
            $table->string('code')->unique();
            $table->string('id_prefix', 3)->unique();
            $table->text('description_tr')->nullable();
            $table->text('description_de')->nullable();
            $table->text('description_en')->nullable();
            $table->json('applicable_processes')->nullable();
            $table->json('required_document_categories')->nullable();
            $table->string('default_checklist_template_id')->nullable();
            $table->json('field_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_types');
    }
};
