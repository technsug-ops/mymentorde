<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_booking_settings', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->unsignedBigInteger('senior_user_id')->unique();

            // Slot kuralları
            $t->unsignedSmallInteger('slot_duration')->default(30);         // dakika, min 15
            $t->unsignedSmallInteger('buffer_minutes')->default(5);         // randevular arası mola
            $t->unsignedSmallInteger('min_notice_hours')->default(6);       // en az kaç saat öncesi
            $t->unsignedSmallInteger('max_future_days')->default(90);       // max ileriye booking

            // Timezone
            $t->string('timezone', 64)->default('Europe/Berlin');

            // Public toggle — senior kendi "link paylaşılabilir" yapabilir
            $t->boolean('is_public')->default(false);
            $t->string('public_slug', 64)->nullable()->unique();            // /book/{slug}

            // Meta
            $t->string('display_name', 120)->nullable();                    // "Ayşe ile 30 dk danışma"
            $t->text('welcome_message')->nullable();                        // public sayfa üstü metin
            $t->boolean('is_active')->default(true);                        // senior kapatabilir

            $t->timestamps();

            $t->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_booking_settings');
    }
};
