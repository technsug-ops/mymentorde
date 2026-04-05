<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('template_id')->constrained('email_templates');
            $table->json('segment_ids');
            $table->foreignId('linked_marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('draft');

            $table->unsignedInteger('total_recipients')->default(0);
            $table->json('recipient_snapshot')->nullable();

            $table->unsignedInteger('stat_sent')->default(0);
            $table->unsignedInteger('stat_delivered')->default(0);
            $table->unsignedInteger('stat_opened')->default(0);
            $table->decimal('stat_open_rate', 5, 2)->default(0);
            $table->unsignedInteger('stat_clicked')->default(0);
            $table->decimal('stat_click_rate', 5, 2)->default(0);
            $table->unsignedInteger('stat_bounced')->default(0);
            $table->unsignedInteger('stat_unsubscribed')->default(0);
            $table->unsignedInteger('stat_guest_registrations')->default(0);

            $table->string('zoho_campaign_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
