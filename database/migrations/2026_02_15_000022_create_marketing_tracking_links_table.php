<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_tracking_links', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->string('code', 40)->unique();
            $table->string('destination_path', 255)->default('/apply');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('campaign_code', 191)->nullable();
            $table->string('dealer_code', 64)->nullable();
            $table->string('source_code', 64)->nullable();
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 191)->nullable();
            $table->string('utm_term', 191)->nullable();
            $table->string('utm_content', 191)->nullable();
            $table->string('status', 16)->default('active');
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'campaign_id']);
            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_tracking_links');
    }
};

