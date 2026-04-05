<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_touchpoints', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guest_application_id')->index();
            $table->string('touchpoint_type', 64); // ad_click, organic_visit, email_click, social_click, dealer_referral, event_registration, direct_visit, content_view
            $table->string('channel', 64); // google_ads, meta_ads, tiktok_ads, instagram, linkedin, email, organic, referral, direct
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('utm_source', 128)->nullable();
            $table->string('utm_medium', 128)->nullable();
            $table->string('utm_campaign', 128)->nullable();
            $table->string('utm_content', 128)->nullable();
            $table->string('utm_term', 128)->nullable();
            $table->string('referrer_url', 512)->nullable();
            $table->string('landing_page', 512)->nullable();
            $table->string('device_type', 32)->nullable(); // desktop, mobile, tablet
            $table->boolean('is_converting_touch')->default(false);
            $table->timestamp('touched_at')->useCurrent();

            $table->foreign('guest_application_id')
                ->references('id')->on('guest_applications')
                ->cascadeOnDelete();
            $table->index(['guest_application_id', 'touched_at']);
            $table->index(['channel', 'touched_at']);
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_touchpoints');
    }
};
