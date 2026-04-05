<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * K3 Notification — Zamanlanmış bildirim tablosu.
 * NotificationDispatch'ten farklı: belirli bir zamana ayarlanan, tekrarlanabilen kurallar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('channel', 32)->default('in_app');           // email|in_app|whatsapp
            $table->string('category', 64)->nullable();
            $table->string('subject', 191)->nullable();
            $table->text('body_template');                              // {name}, {date} gibi placeholder destekli
            $table->string('target_role', 64)->nullable();             // manager|senior|student|guest|all
            $table->string('target_email', 191)->nullable();           // belirli kişi
            $table->string('source_type', 80)->nullable();             // guest_application|marketing_task|…
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('schedule_type', 32)->default('once');      // once|daily|weekly|monthly
            $table->timestamp('send_at')->nullable();                   // once için
            $table->string('recurrence_time', 8)->nullable();          // HH:MM (daily/weekly/monthly)
            $table->unsignedTinyInteger('recurrence_day')->nullable(); // haftanın günü (1=Pazartesi) veya ayın günü
            $table->timestamp('recurrence_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->string('created_by_email', 191)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'schedule_type', 'send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};
