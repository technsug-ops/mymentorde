<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_send_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_campaign_id')->nullable()->constrained('email_campaigns')->nullOnDelete();
            $table->foreignId('template_id')->constrained('email_templates');
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email');
            $table->string('subject');
            $table->string('language', 5);
            $table->string('trigger_event')->nullable();
            $table->string('status');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->json('clicked_links')->nullable();
            $table->string('bounce_reason')->nullable();
            $table->timestamp('sent_at');
            $table->timestamp('created_at');

            $table->index(['recipient_user_id', 'sent_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_send_log');
    }
};
