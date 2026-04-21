<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('google_email', 190);
            $table->string('google_user_id', 64)->nullable();

            // OAuth tokens (access refreshlenir, refresh long-lived)
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('scope', 500)->nullable();

            // Hangi takvime yazacak — default: primary
            $table->string('calendar_id', 190)->default('primary');
            $table->string('calendar_summary', 190)->nullable();

            // Senkronizasyon kontrolü
            $table->boolean('sync_push')->default(true);   // portal → Google
            $table->boolean('sync_pull')->default(false);  // Google → portal (sonra)
            $table->string('last_sync_status', 32)->default('pending'); // pending|ok|failed
            $table->text('last_sync_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->index(['last_sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_connections');
    }
};
