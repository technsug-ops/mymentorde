<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_ai_conversations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guest_application_id');
            $table->text('question');
            $table->text('answer');
            $table->json('context')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['guest_application_id', 'created_at'], 'idx_gac_guest_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_ai_conversations');
    }
};
