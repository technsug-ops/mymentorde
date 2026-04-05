<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('slug')->unique();

            $table->string('title_tr');
            $table->string('title_de')->nullable();
            $table->string('title_en')->nullable();
            $table->text('summary_tr')->nullable();
            $table->text('summary_de')->nullable();
            $table->text('summary_en')->nullable();
            $table->longText('content_tr');
            $table->longText('content_de')->nullable();
            $table->longText('content_en')->nullable();

            $table->string('cover_image_url', 500)->nullable();
            $table->string('cover_image_alt')->nullable();
            $table->json('gallery_urls')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('video_thumbnail_url', 500)->nullable();

            $table->string('seo_meta_title_tr')->nullable();
            $table->string('seo_meta_title_de')->nullable();
            $table->string('seo_meta_title_en')->nullable();
            $table->string('seo_meta_description_tr', 300)->nullable();
            $table->string('seo_meta_description_de', 300)->nullable();
            $table->string('seo_meta_description_en', 300)->nullable();
            $table->json('seo_keywords')->nullable();
            $table->string('seo_canonical_url', 500)->nullable();
            $table->string('seo_og_image_url', 500)->nullable();

            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('featured_order')->nullable();

            $table->string('target_audience')->default('all');
            $table->json('target_student_types')->nullable();
            $table->foreignId('linked_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->string('category')->nullable();
            $table->json('tags')->nullable();

            $table->unsignedInteger('metric_total_views')->default(0);
            $table->unsignedInteger('metric_unique_views')->default(0);
            $table->unsignedInteger('metric_avg_read_time_seconds')->default(0);
            $table->decimal('metric_bounce_rate', 5, 2)->default(0);
            $table->unsignedInteger('metric_shares')->default(0);
            $table->unsignedInteger('metric_guest_conversions')->default(0);
            $table->unsignedInteger('current_revision')->default(1);

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('is_featured');
            $table->index('published_at');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_contents');
    }
};
