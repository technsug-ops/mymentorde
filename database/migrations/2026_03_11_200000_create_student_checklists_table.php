<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_checklists', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 20)->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general'); // registration, document, visa, housing, language, general
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('created_by_email')->nullable(); // senior email
            $table->timestamps();

            $table->index(['student_id', 'is_done'], 'idx_student_done');
            $table->index(['student_id', 'category'], 'idx_student_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_checklists');
    }
};
