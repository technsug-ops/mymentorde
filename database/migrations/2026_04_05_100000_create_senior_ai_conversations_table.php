<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_ai_conversations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');            // senior'ın User.id
            $table->string('student_id', 64)->nullable();     // bağlam öğrencisi (opsiyonel)
            $table->text('question');
            $table->text('answer');
            $table->json('context')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at'], 'idx_sac_user_date');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_ai_conversations');
    }
};
