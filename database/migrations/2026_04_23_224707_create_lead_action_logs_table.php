<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Manager/senior'ın guest veya student için yaptığı manuel aksiyon logu.
        // Dashboard'tan her "Ara", "WhatsApp gönder", "Email at", "Not ekle", "Senior ata"
        // aksiyonu burada kaydedilir. Takip + PostHog event kaynağı.
        Schema::create('lead_action_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('actor_user_id')->index();
            $table->enum('target_type', ['guest', 'student'])->index();
            $table->unsignedBigInteger('target_id')->index();
            $table->enum('action_type', [
                'call', 'whatsapp', 'email', 'note', 'assign_senior',
                'payment_reminder', 'book_appointment', 'status_change', 'custom'
            ])->index();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('channel', 32)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('follow_up_at')->nullable()->index();
            $table->boolean('follow_up_sent')->default(false);
            $table->timestamps();

            $table->index(['target_type', 'target_id', 'created_at'], 'lal_target_time_idx');
            $table->index(['actor_user_id', 'created_at'], 'lal_actor_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_action_logs');
    }
};
