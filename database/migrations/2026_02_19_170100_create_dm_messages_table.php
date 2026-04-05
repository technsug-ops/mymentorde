<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('thread_id')->index();
            $table->unsignedBigInteger('sender_user_id')->nullable()->index();
            $table->string('sender_role', 30)->index(); // guest|student|senior|mentor|manager|...
            $table->text('message')->nullable();
            $table->boolean('is_quick_request')->default(false)->index();
            $table->string('attachment_original_name', 255)->nullable();
            $table->string('attachment_storage_path', 500)->nullable();
            $table->string('attachment_mime', 120)->nullable();
            $table->unsignedInteger('attachment_size_kb')->nullable();
            $table->boolean('is_read_by_advisor')->default(false)->index();
            $table->boolean('is_read_by_participant')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_messages');
    }
};

