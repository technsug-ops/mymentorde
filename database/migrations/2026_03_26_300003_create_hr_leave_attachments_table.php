<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('leave_request_id');
            $table->foreign('leave_request_id')->references('id')->on('hr_leave_requests')->onDelete('cascade');
            $table->enum('type', ['file', 'link']);
            $table->string('original_name')->nullable();
            $table->string('path')->nullable();
            $table->string('url', 1000)->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_attachments');
    }
};
