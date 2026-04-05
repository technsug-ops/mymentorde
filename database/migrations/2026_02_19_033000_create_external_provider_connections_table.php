<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_provider_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('provider', 40); // meta_ads, ga4, google_ads, ...
            $table->string('account_label', 120)->nullable();
            $table->string('status', 24)->default('draft'); // draft, connected, error, paused
            $table->string('oauth_client_id', 255)->nullable();
            $table->text('scopes')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_error', 400)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'provider']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_provider_connections');
    }
};

