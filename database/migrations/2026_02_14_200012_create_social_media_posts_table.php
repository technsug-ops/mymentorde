<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('social_media_accounts')->cascadeOnDelete();
            $table->string('platform');

            $table->text('caption')->nullable();
            $table->json('media_urls')->nullable();
            $table->string('post_type');
            $table->string('post_url', 500)->nullable();

            $table->string('status')->default('idea');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->unsignedInteger('metric_views')->default(0);
            $table->unsignedInteger('metric_likes')->default(0);
            $table->unsignedInteger('metric_comments')->default(0);
            $table->unsignedInteger('metric_shares')->default(0);
            $table->unsignedInteger('metric_saves')->default(0);
            $table->unsignedInteger('metric_reach')->default(0);
            $table->unsignedInteger('metric_impressions')->default(0);
            $table->decimal('metric_engagement_rate', 5, 2)->default(0);
            $table->unsignedInteger('metric_click_through')->default(0);
            $table->unsignedInteger('metric_guest_registrations')->default(0);

            $table->json('tags')->nullable();
            $table->foreignId('linked_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('linked_content_id')->nullable()->constrained('cms_contents')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['platform', 'status']);
            $table->index('scheduled_at');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_posts');
    }
};
