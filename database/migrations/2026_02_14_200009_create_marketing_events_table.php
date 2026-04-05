<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_events', function (Blueprint $table): void {
            $table->id();

            $table->string('title_tr');
            $table->string('title_de')->nullable();
            $table->string('title_en')->nullable();
            $table->longText('description_tr');
            $table->longText('description_de')->nullable();
            $table->longText('description_en')->nullable();

            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->string('timezone')->default('Europe/Berlin');

            $table->string('type');
            $table->string('format');

            $table->string('online_platform')->nullable();
            $table->string('online_meeting_url', 500)->nullable();
            $table->string('online_meeting_id')->nullable();
            $table->string('online_meeting_password')->nullable();
            $table->string('online_recording_url', 500)->nullable();

            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_city')->nullable();
            $table->string('venue_country')->nullable();
            $table->string('venue_map_url', 500)->nullable();

            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('current_registrations')->default(0);
            $table->boolean('waitlist_enabled')->default(false);

            $table->string('target_audience')->default('all');
            $table->json('target_student_types')->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->json('gallery_urls')->nullable();

            $table->foreignId('linked_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('cms_content_id')->nullable()->constrained('cms_contents')->nullOnDelete();
            $table->json('reminders')->nullable();
            $table->boolean('post_event_survey_enabled')->default(false);
            $table->string('post_event_survey_url', 500)->nullable();

            $table->unsignedInteger('metric_total_registrations')->default(0);
            $table->unsignedInteger('metric_total_attendees')->default(0);
            $table->decimal('metric_attendance_rate', 5, 2)->default(0);
            $table->unsignedInteger('metric_avg_duration_minutes')->nullable();
            $table->unsignedInteger('metric_guest_conversions')->default(0);
            $table->decimal('metric_satisfaction_score', 3, 1)->nullable();

            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_events');
    }
};
