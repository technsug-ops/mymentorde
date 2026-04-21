<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_payouts', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('senior_user_id')->index();

            $t->unsignedInteger('amount_cents');
            $t->string('currency', 8)->default('EUR');

            $t->date('period_start');        // hangi ayın kazançları
            $t->date('period_end');

            $t->string('status', 32)->default('pending');
            // pending / queued / processing / paid / failed / voided

            $t->string('method', 32)->default('bank_transfer');   // bank_transfer / stripe / paypal / manual
            $t->string('stripe_transfer_id', 128)->nullable();
            $t->string('external_reference', 128)->nullable();    // banka ref, paypal id vb.

            $t->text('notes')->nullable();
            $t->text('failure_reason')->nullable();

            $t->timestamp('requested_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();

            $t->index(['senior_user_id', 'status']);
            $t->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_payouts');
    }
};
