<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_payment_settings', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->unique();

            // Ödeme modülü açık mı? (Muhasebeci onayıyla true olur)
            $t->boolean('is_payment_enabled')->default(false);

            // Payout ayarları
            $t->unsignedTinyInteger('payout_day_of_month')->default(5);    // Her ayın 5'i
            $t->unsignedInteger('payout_minimum_cents')->default(10000);   // €100 (cent)
            $t->boolean('allow_on_demand_payout')->default(true);

            // Default komisyon oranı (commission_rules yakalamazsa)
            $t->decimal('default_commission_pct', 5, 2)->default(20.00);

            // İade penceresi
            $t->unsignedSmallInteger('refund_window_hours')->default(24);

            // Stripe config (Phase 5'te dolacak)
            $t->string('stripe_mode', 16)->default('test');          // test / live
            $t->string('stripe_public_key', 255)->nullable();
            $t->string('stripe_secret_key', 255)->nullable();
            $t->string('stripe_webhook_secret', 255)->nullable();

            $t->timestamps();
        });

        $companies = DB::table('companies')->pluck('id');
        $now = now();
        foreach ($companies as $cid) {
            DB::table('company_payment_settings')->insert([
                'company_id'              => $cid,
                'is_payment_enabled'      => false,
                'payout_day_of_month'     => 5,
                'payout_minimum_cents'    => 10000,
                'allow_on_demand_payout'  => true,
                'default_commission_pct'  => 20.00,
                'refund_window_hours'     => 24,
                'stripe_mode'             => 'test',
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_payment_settings');
    }
};
