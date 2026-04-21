<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_booking_pricing', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->unique();

            $t->boolean('is_free')->default(true);              // default: ücretsiz
            $t->string('currency', 8)->default('EUR');
            $t->unsignedSmallInteger('cancellation_window_hours')->default(24);

            // pricing_rules JSON: [{duration:15, price_net:20.00, enabled:false}, ...]
            $t->json('pricing_rules')->nullable();

            $t->timestamps();
        });

        // Mevcut her company için default satır (is_free=true, fiyatlar 0)
        $companies = DB::table('companies')->pluck('id');
        $defaultRules = [
            ['duration' => 15, 'price_net' => 0, 'enabled' => false],
            ['duration' => 30, 'price_net' => 0, 'enabled' => true],
            ['duration' => 45, 'price_net' => 0, 'enabled' => true],
            ['duration' => 60, 'price_net' => 0, 'enabled' => true],
            ['duration' => 90, 'price_net' => 0, 'enabled' => false],
            ['duration' => 120, 'price_net' => 0, 'enabled' => false],
        ];
        $now = now();
        foreach ($companies as $cid) {
            DB::table('company_booking_pricing')->insert([
                'company_id'                => $cid,
                'is_free'                   => true,
                'currency'                  => 'EUR',
                'cancellation_window_hours' => 24,
                'pricing_rules'             => json_encode($defaultRules, JSON_UNESCAPED_UNICODE),
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_booking_pricing');
    }
};
