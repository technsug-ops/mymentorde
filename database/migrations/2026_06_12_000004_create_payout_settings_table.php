<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace Phase 6 — payout_settings
 *
 * Şirket bazlı ödeme akışı ayarları:
 *  - payout_day: Aylık otomatik ödeme günü (1-28)
 *  - payout_minimum_eur: Minimum bakiye eşiği
 *  - allow_on_demand: Senior'lar on-demand çekim talep edebilir mi
 *  - currency: Ödeme para birimi
 *  - notification_email: Ödeme/uyarı bildirim adresi
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_settings', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->unique();
            $t->unsignedTinyInteger('payout_day')->default(5);             // ayın hangi günü
            $t->decimal('payout_minimum_eur', 8, 2)->default(100.00);      // €100 default eşik
            $t->boolean('allow_on_demand')->default(true);
            $t->string('currency', 8)->default('EUR');
            $t->string('notification_email', 200)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_settings');
    }
};
