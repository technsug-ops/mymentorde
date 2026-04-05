<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('task_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('attachment_type', 16);   // image | video | pdf | file | link
            $table->string('file_path')->nullable();  // storage/app/public relative path
            $table->string('original_name')->nullable();
            $table->string('mime_type', 127)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('url', 2048)->nullable();  // for link type OR public URL of uploaded file
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('marketing_tasks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};
