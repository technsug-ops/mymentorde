<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dealer (satış ortağı) başvuru kayıtları — /satis-ortagi/basvuru formundan.
        // Manager panelden onay/red işlenir. Onay halinde dealers tablosuna yansır.
        Schema::create('dealer_applications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // Kişisel
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 180)->index();
            $table->string('phone', 50);
            $table->string('city', 100)->nullable();
            $table->string('country', 60)->default('TR');

            // Firma (opsiyonel — bireysel olarak da başvurulabilir)
            $table->string('company_name', 180)->nullable();
            $table->string('tax_number', 60)->nullable();
            $table->enum('business_type', ['individual', 'company', 'freelance'])->default('individual');

            // Plan tercihi
            $table->enum('preferred_plan', ['lead_generation', 'freelance', 'unsure'])->default('lead_generation');
            $table->unsignedInteger('expected_monthly_volume')->nullable(); // tahmini aylık aday sayısı
            $table->boolean('education_experience')->default(false);
            $table->text('experience_details')->nullable();

            // Pazarlama kaynakları
            $table->enum('heard_from', ['organic', 'social_media', 'referral', 'google', 'whatsapp', 'other'])->nullable();
            $table->string('referrer_email', 180)->nullable(); // kim yönlendirdi
            $table->text('motivation')->nullable(); // neden ortak olmak istiyor

            // UTM tracking (landing'den gelir)
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 120)->nullable();

            // Süreç
            $table->enum('status', ['pending', 'in_review', 'approved', 'rejected', 'waitlist'])->default('pending')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->text('rejected_reason')->nullable();

            // Onaylanınca oluşturulan dealer kaydı referansı
            $table->unsignedBigInteger('approved_dealer_id')->nullable()->index();
            $table->unsignedBigInteger('approved_user_id')->nullable()->index();

            // Forensic
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at'], 'da_status_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_applications');
    }
};
