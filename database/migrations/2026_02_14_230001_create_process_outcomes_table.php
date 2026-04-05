<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_outcomes', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 64);
            $table->string('application_id', 64)->nullable();
            $table->string('process_step', 64);
            $table->string('outcome_type', 64);
            $table->string('university')->nullable();
            $table->string('program')->nullable();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->text('details_tr');
            $table->text('details_de')->nullable();
            $table->text('details_en')->nullable();
            $table->text('conditions')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->boolean('is_visible_to_student')->default(false);
            $table->timestamp('made_visible_at')->nullable();
            $table->string('made_visible_by')->nullable();
            $table->boolean('student_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->string('added_by')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'process_step']);
            $table->index(['outcome_type', 'is_visible_to_student']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_outcomes');
    }
};
