<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_event_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('event_type', 80);
            $table->string('entity_type', 80)->nullable();
            $table->string('entity_id', 120)->nullable();
            $table->string('message', 400);
            $table->json('meta')->nullable();
            $table->string('actor_email', 190)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'event_type']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_event_logs');
    }
};

