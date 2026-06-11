<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace Phase 5 — Stripe Checkout entegrasyonu
 *
 * Mevcut altyapı (Phase 4):
 *   - public_bookings: amount_*_cents, currency, payment_status, stripe_session_id,
 *     stripe_payment_intent_id, paid_at, refunded_at zaten var.
 *   - senior_earnings: amount_*_cents + commission + senior_payout_cents zaten var.
 *
 * Phase 5'in eklediği alanlar:
 *   - public_bookings.refund_id            — Stripe Refund::id
 *   - public_bookings.stripe_expires_at    — checkout.session.expires_at (15dk hold)
 *   - senior_earnings.available_at         — 24 saat sonra ödenebilir hale gelir
 *   - senior_earnings.reversed_at          — refund sonrası earning kapanma timestamp'i
 *
 * Status enum (string) genişletildi: 'recorded' | 'available' | 'paid_out' | 'refunded' | 'voided'
 * (DB tarafında string olduğu için ALTER gerekmiyor — uygulama kodu yeni değerleri tanıyor)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_bookings', function (Blueprint $t): void {
            if (!Schema::hasColumn('public_bookings', 'refund_id')) {
                $t->string('refund_id', 200)->nullable()->after('refunded_at');
            }
            if (!Schema::hasColumn('public_bookings', 'stripe_expires_at')) {
                // checkout.session 15dk hold suresi — webhook expire eventi geldiginde
                // bookingu cancel etmek icin kullanilir
                $t->timestamp('stripe_expires_at')->nullable()->after('stripe_payment_intent_id');
            }
            if (!Schema::hasColumn('public_bookings', 'payment_failure_reason')) {
                // Stripe payment_intent.payment_failed → kullaniciya gosterilecek mesaj
                $t->string('payment_failure_reason', 255)->nullable()->after('refund_id');
            }
        });

        Schema::table('senior_earnings', function (Blueprint $t): void {
            if (!Schema::hasColumn('senior_earnings', 'available_at')) {
                // recorded → available transition timestamp (24 saat sonra settle command set eder)
                $t->timestamp('available_at')->nullable()->after('recorded_at');
            }
            if (!Schema::hasColumn('senior_earnings', 'reversed_at')) {
                // refund sonrasi reversed transition
                $t->timestamp('reversed_at')->nullable()->after('available_at');
            }
            if (!Schema::hasColumn('senior_earnings', 'stripe_charge_id')) {
                // hangi Stripe charge'a bagli — refund icin gerekli
                $t->string('stripe_charge_id', 200)->nullable()->after('reversed_at');
            }
        });

        // Available earnings'i settle command'inin hizli bulmasi icin index — idempotent guard
        try {
            Schema::table('senior_earnings', function (Blueprint $t): void {
                $t->index(['status', 'available_at'], 'se_status_available_idx');
            });
        } catch (\Throwable $e) {
            // Index zaten varsa MySQL "Duplicate key name" hatasi atar — idempotent guard
            if (!str_contains(strtolower($e->getMessage()), 'duplicate')) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        Schema::table('public_bookings', function (Blueprint $t): void {
            foreach (['refund_id', 'stripe_expires_at', 'payment_failure_reason'] as $col) {
                if (Schema::hasColumn('public_bookings', $col)) {
                    $t->dropColumn($col);
                }
            }
        });

        Schema::table('senior_earnings', function (Blueprint $t): void {
            try { $t->dropIndex('se_status_available_idx'); } catch (\Throwable) {}
            foreach (['available_at', 'reversed_at', 'stripe_charge_id'] as $col) {
                if (Schema::hasColumn('senior_earnings', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
