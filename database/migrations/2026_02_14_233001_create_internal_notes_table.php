<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 64);
            $table->text('content');
            $table->string('category', 32)->default('general');
            $table->string('priority', 16)->default('normal');
            $table->boolean('is_pinned')->default(false);
            $table->json('attachments')->nullable();
            $table->string('created_by')->nullable();
            $table->string('created_by_role', 32)->nullable();
            $table->timestamps();

            $table->index(['student_id', 'is_pinned']);
            $table->index(['category', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_notes');
    }
};
