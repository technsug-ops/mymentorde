<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('category')->unique();
            $table->string('active_provider')->nullable();
            $table->json('providers')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('status', 32)->default('disconnected');
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_configs');
    }
};
