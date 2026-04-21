<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_bookings', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->unsignedBigInteger('senior_user_id')->index();

            // Kim booking yaptı (login olmuş user varsa, yoksa null)
            $t->unsignedBigInteger('booked_by_user_id')->nullable()->index();

            // Booking kimin randevusu (login student/guest → user_id, public ise null)
            $t->unsignedBigInteger('student_user_id')->nullable()->index();
            $t->unsignedBigInteger('guest_application_id')->nullable()->index();

            // Invitee serbest metin (public booking durumunda)
            $t->string('invitee_name', 180)->nullable();
            $t->string('invitee_email', 180)->nullable();
            $t->string('invitee_phone', 64)->nullable();

            // Zaman bilgisi — UTC olarak sakla, render'da senior.timezone ile göster
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');

            // Durum
            $t->string('status', 32)->default('pending_confirm');
            // pending_confirm | confirmed | canceled_by_invitee | canceled_by_senior | completed | no_show

            $t->text('notes')->nullable();                    // invitee açıklaması
            $t->text('senior_notes')->nullable();             // senior iç not

            // Cancel/reschedule link token
            $t->string('booking_token', 64)->unique();

            // Mevcut student_appointments tablosuna yansıyan row (Google Calendar sync için)
            $t->unsignedBigInteger('student_appointment_id')->nullable()->index();

            $t->timestamp('canceled_at')->nullable();
            $t->timestamps();

            $t->index(['senior_user_id', 'starts_at'], 'pb_senior_time_idx');
            $t->index(['status', 'starts_at'], 'pb_status_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_bookings');
    }
};
