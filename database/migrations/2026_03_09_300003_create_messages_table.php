<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('conversation_id');
            $t->unsignedBigInteger('sender_id')->nullable();
            $t->text('body');
            $t->unsignedBigInteger('reply_to_message_id')->nullable();
            $t->string('attachment_path', 500)->nullable();
            $t->string('attachment_name', 255)->nullable();
            $t->unsignedInteger('attachment_size')->nullable();
            $t->string('attachment_mime', 100)->nullable();
            $t->boolean('is_system')->default(false);
            $t->boolean('is_edited')->default(false);
            $t->timestamp('edited_at')->nullable();
            $t->softDeletes();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['conversation_id', 'created_at']);
            $t->index(['sender_id']);
            $t->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $t->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('reply_to_message_id')->references('id')->on('messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
