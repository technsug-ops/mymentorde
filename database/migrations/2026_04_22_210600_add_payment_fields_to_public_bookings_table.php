<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_bookings', function (Blueprint $t): void {
            // Amount alanları (cent — integer)
            $t->unsignedInteger('amount_net_cents')->default(0)->after('senior_notes');
            $t->decimal('tax_rate_pct_applied', 5, 2)->default(0)->after('amount_net_cents');
            $t->unsignedInteger('tax_amount_cents')->default(0)->after('tax_rate_pct_applied');
            $t->unsignedInteger('amount_gross_cents')->default(0)->after('tax_amount_cents');
            $t->string('currency', 8)->default('EUR')->after('amount_gross_cents');

            // Ödeme durumu
            $t->string('payment_status', 32)->default('free')->after('currency');
            // free / pending_payment / paid / refunded / failed

            // Stripe alanları
            $t->string('stripe_session_id', 255)->nullable()->after('payment_status');
            $t->string('stripe_payment_intent_id', 255)->nullable()->after('stripe_session_id');
            $t->timestamp('paid_at')->nullable()->after('stripe_payment_intent_id');
            $t->timestamp('refunded_at')->nullable()->after('paid_at');

            // Müşteri bilgisi (KDV kuralı için)
            $t->string('customer_country_code', 2)->nullable()->after('invitee_phone');
            $t->string('customer_type', 16)->default('b2c')->after('customer_country_code');
            // b2c / b2b

            $t->boolean('is_contracted_user')->default(false)->after('customer_type');
            // Sözleşmeli kullanıcı → ödeme alınmadı bilgisi
        });
    }

    public function down(): void
    {
        Schema::table('public_bookings', function (Blueprint $t): void {
            $t->dropColumn([
                'amount_net_cents',
                'tax_rate_pct_applied',
                'tax_amount_cents',
                'amount_gross_cents',
                'currency',
                'payment_status',
                'stripe_session_id',
                'stripe_payment_intent_id',
                'paid_at',
                'refunded_at',
                'customer_country_code',
                'customer_type',
                'is_contracted_user',
            ]);
        });
    }
};
