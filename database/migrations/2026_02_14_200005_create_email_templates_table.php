<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('category');

            $table->string('trigger_event')->nullable();
            $table->unsignedInteger('trigger_delay_minutes')->default(0);
            $table->json('trigger_conditions')->nullable();
            $table->boolean('trigger_is_active')->default(true);

            $table->string('subject_tr');
            $table->string('subject_de')->nullable();
            $table->string('subject_en')->nullable();
            $table->longText('body_tr');
            $table->longText('body_de')->nullable();
            $table->longText('body_en')->nullable();

            $table->json('available_placeholders')->nullable();
            $table->string('from_name')->default('MentorDE');
            $table->string('from_email')->default('noreply@mentorde.com');
            $table->string('reply_to')->nullable();

            $table->string('zoho_template_id')->nullable();
            $table->boolean('zoho_synced')->default(false);
            $table->timestamp('zoho_last_sync_at')->nullable();

            $table->unsignedInteger('stat_total_sent')->default(0);
            $table->unsignedInteger('stat_total_opened')->default(0);
            $table->decimal('stat_open_rate', 5, 2)->default(0);
            $table->unsignedInteger('stat_total_clicked')->default(0);
            $table->decimal('stat_click_rate', 5, 2)->default(0);
            $table->unsignedInteger('stat_total_bounced')->default(0);
            $table->unsignedInteger('stat_total_unsubscribed')->default(0);
            $table->timestamp('stat_last_sent_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('trigger_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
