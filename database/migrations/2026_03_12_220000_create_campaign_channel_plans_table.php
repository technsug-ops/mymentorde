<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_channel_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('channel', 30);         // email, social_facebook, social_instagram, whatsapp, event, sms
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->string('content_type', 50)->nullable();  // cms_content, email_campaign, social_post, marketing_event
            $table->string('status', 20)->default('planned'); // planned, scheduled, sent, completed, cancelled
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('campaign_id', 'idx_ccp_campaign');
            $table->index('status', 'idx_ccp_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_channel_plans');
    }
};
