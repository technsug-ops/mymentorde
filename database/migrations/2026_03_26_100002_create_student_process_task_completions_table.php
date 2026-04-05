<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_process_task_completions', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 64);
            $table->unsignedBigInteger('task_id');
            $table->timestamp('completed_at')->nullable();
            $table->string('completed_by')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('task_id')
                  ->references('id')->on('process_step_tasks')
                  ->onDelete('cascade');

            $table->unique(['student_id', 'task_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_process_task_completions');
    }
};
