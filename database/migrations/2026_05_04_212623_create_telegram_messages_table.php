<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->string('source', 120)->index();
            $table->string('sender_hash', 32)->index();
            $table->dateTime('sent_at')->nullable()->index();
            $table->mediumText('text');
            $table->boolean('is_question')->default(false)->index();
            $table->boolean('is_short')->default(false);
            $table->unsignedSmallInteger('text_len')->default(0);
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->string('month', 7)->nullable()->index();
            $table->unsignedTinyInteger('dow')->nullable();
            $table->unsignedTinyInteger('hour')->nullable();
            $table->json('topics')->nullable();
            $table->string('imported_batch', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['source', 'sent_at']);
            $table->index(['is_question', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
