<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * /randevu sayfasında bir kategori (Bachelor / Master / Diğer) için müsait
 * danışman yoksa, ziyaretçi bekleme listesi formunu doldurur. Manager bu
 * kayıtları görüp uygun danışman atadığında dönüş yapar.
 *
 * WhatsApp doğrudan iletişim yerine bu form: lead kalitesi yüksek
 * (ad+email+telefon+track), spam zor, manager kontrol eder.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_waitlist_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->string('name', 120);
            $table->string('email', 180);
            $table->string('phone', 32)->nullable();

            // Hangi kategoride randevu istiyor: bachelor / master / other
            $table->string('track', 16)->default('other');

            $table->text('message')->nullable();

            // Durum: new (henüz dokunulmadı) / contacted (manager iletişime geçti) / converted (randevu alındı) / dismissed
            $table->string('status', 16)->default('new');

            $table->unsignedBigInteger('contacted_by')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->text('contact_notes')->nullable();

            // Marketing context
            $table->string('utm_source', 64)->nullable();
            $table->string('utm_medium', 64)->nullable();
            $table->string('utm_campaign', 64)->nullable();
            $table->string('referrer_url', 500)->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'track']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_waitlist_requests');
    }
};
