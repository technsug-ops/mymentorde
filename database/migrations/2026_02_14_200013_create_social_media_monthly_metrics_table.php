<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_monthly_metrics', function (Blueprint $table): void {
            $table->id();
            $table->string('period', 7);
            $table->foreignId('account_id')->constrained('social_media_accounts')->cascadeOnDelete();
            $table->string('platform');

            $table->unsignedInteger('followers_start');
            $table->unsignedInteger('followers_end');
            $table->integer('followers_growth');
            $table->decimal('followers_growth_rate', 5, 2);

            $table->unsignedInteger('total_posts');
            $table->unsignedBigInteger('total_views');
            $table->unsignedInteger('total_likes');
            $table->unsignedInteger('total_comments');
            $table->unsignedInteger('total_shares');
            $table->decimal('avg_engagement_rate', 5, 2);
            $table->unsignedInteger('total_click_through');
            $table->unsignedInteger('total_guest_registrations');

            $table->foreignId('top_post_id')->nullable()->constrained('social_media_posts')->nullOnDelete();
            $table->string('top_post_metric')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('created_at');

            $table->unique(['period', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_monthly_metrics');
    }
};
