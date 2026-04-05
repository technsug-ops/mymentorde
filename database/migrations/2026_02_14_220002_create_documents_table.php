<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->string('document_id', 64)->nullable()->unique();
            $table->string('student_id', 64);
            $table->foreignId('category_id')->constrained('document_categories')->cascadeOnDelete();
            $table->json('process_tags')->nullable();
            $table->string('original_file_name');
            $table->string('standard_file_name');
            $table->string('storage_path')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->string('status', 32)->default('uploaded');
            $table->string('uploaded_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
