<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_language_courses', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 64);
            $table->string('school_name');
            $table->string('city')->nullable();
            $table->string('country')->default('Germany');
            $table->enum('course_type', ['DSH', 'TestDaF', 'Goethe', 'other'])->default('other');
            $table->string('level_target')->nullable();   // B1, B2, C1, C2
            $table->string('level_achieved')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('certificate_status', ['none', 'pending', 'received', 'submitted'])->default('none');
            $table->text('notes')->nullable();
            $table->boolean('is_visible_to_student')->default(false);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('added_by')->nullable();
            $table->timestamps();

            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_language_courses');
    }
};
