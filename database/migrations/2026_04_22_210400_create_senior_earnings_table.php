<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_earnings', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('senior_user_id')->index();
            $t->unsignedBigInteger('public_booking_id')->nullable()->index();
            $t->unsignedBigInteger('student_appointment_id')->nullable()->index();

            // Tutarlar cent cinsinden (integer — float yuvarlama hatalarını engeller)
            $t->unsignedInteger('amount_net_cents')->default(0);         // KDV hariç
            $t->decimal('tax_rate_pct_applied', 5, 2)->default(0);
            $t->unsignedInteger('tax_amount_cents')->default(0);
            $t->unsignedInteger('amount_gross_cents')->default(0);       // net + tax

            $t->decimal('commission_pct_applied', 5, 2)->default(0);
            $t->unsignedInteger('commission_cents')->default(0);         // platform kazancı
            $t->unsignedInteger('senior_payout_cents')->default(0);      // senior'a kalan

            $t->string('currency', 8)->default('EUR');
            $t->string('status', 32)->default('recorded');
            // recorded / paid_out / refunded / voided

            $t->unsignedBigInteger('payout_id')->nullable()->index();    // ödendiyse hangi payout'ta
            $t->timestamp('recorded_at')->nullable();
            $t->timestamps();

            $t->index(['senior_user_id', 'status']);
            $t->index(['senior_user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_earnings');
    }
};
