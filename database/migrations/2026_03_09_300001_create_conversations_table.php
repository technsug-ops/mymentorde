<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->enum('type', ['direct', 'group', 'announcement']);
            $t->string('title', 190)->nullable();
            $t->unsignedBigInteger('created_by_user_id')->nullable();
            $t->string('context_type', 64)->nullable();
            $t->string('context_id', 64)->nullable();
            $t->boolean('is_archived')->default(false);
            $t->timestamp('last_message_at')->nullable();
            $t->string('last_message_preview', 100)->nullable();
            $t->timestamps();

            $t->index(['company_id', 'type']);
            $t->index(['last_message_at']);
            $t->index(['context_type', 'context_id']);
            $t->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
