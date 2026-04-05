<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('platform');
            $table->string('account_name');
            $table->string('account_url', 500);
            $table->string('profile_image_url', 500)->nullable();

            $table->unsignedInteger('followers')->default(0);
            $table->integer('followers_growth_this_month')->default(0);
            $table->unsignedInteger('total_posts')->default(0);
            $table->timestamp('metrics_last_updated_at')->nullable();

            $table->boolean('api_connected')->default(false);
            $table->text('api_access_token')->nullable();
            $table->timestamp('api_token_expires_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_accounts');
    }
};
