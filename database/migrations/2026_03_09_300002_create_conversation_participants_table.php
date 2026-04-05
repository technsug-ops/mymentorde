<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('conversation_id');
            $t->unsignedBigInteger('user_id');
            $t->enum('role', ['admin', 'member'])->default('member');
            $t->timestamp('joined_at')->useCurrent();
            $t->timestamp('last_read_at')->nullable();
            $t->boolean('is_muted')->default(false);
            $t->boolean('is_pinned')->default(false);

            $t->unique(['conversation_id', 'user_id']);
            $t->index(['user_id', 'is_pinned']);
            $t->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
