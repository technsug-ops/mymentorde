<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sözleşme tutarının SABİTLENMESİ.
 *
 * ── NEDEN ───────────────────────────────────────────────────────────────
 * `contract_amount_eur` kolonu vardı ve finans ekranı onu topluyordu, ama
 * uygulamada onu YAZAN hiçbir kod yoktu — yani finans rakamları boş
 * geliyordu. Paket fiyatı ayrı bir alanda (`selected_package_price`) metin
 * olarak duruyor; pazarlıkla değişen gerçek tutarın yeri yoktu.
 *
 * Artık akış şu: paket fiyatı başlangıç değeri → sözleşme aşamasında elle
 * düzeltilebilir → SABİTLENİR → finans yalnızca sabitlenmiş tutarı sayar.
 *
 * Sabitleme kaydı tutuluyor çünkü bu bir PARA kararı: kimin, ne zaman
 * belirlediği sonradan sorulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->timestamp('contract_amount_locked_at')->nullable()->after('contract_amount_eur');
            $table->string('contract_amount_set_by', 190)->nullable()->after('contract_amount_locked_at');
            $table->text('contract_amount_note')->nullable()->after('contract_amount_set_by');
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->dropColumn(['contract_amount_locked_at', 'contract_amount_set_by', 'contract_amount_note']);
        });
    }
};
