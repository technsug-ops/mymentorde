<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İndirim kodu sistemi (MVP):
 *  - discount_codes: kod tanımı (manager üretir)
 *  - discount_code_redemptions: kullanım kayıtları (polymorphic — ileride
 *    StudentPayment, BookingPayment vb. de aynı tabloyu kullanır)
 *
 * Future-proof kolonlar şemada var ama MVP UI'da gösterilmez:
 *  - applies_to_package_codes JSON — paket spesifik
 *  - min_purchase_amount_eur — minimum tutar şartı
 *  - dealer_id — bayi attribution / komisyon hook
 *  - metadata JSON — catch-all
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code', 64);
            $table->string('description', 255)->nullable();

            // Tip: 'percent' (0-100) veya 'fixed' (EUR sabit)
            $table->enum('discount_type', ['percent', 'fixed']);
            $table->decimal('discount_value', 10, 2);

            // Limitler
            $table->unsignedInteger('max_redemptions')->nullable(); // null = sınırsız
            $table->unsignedInteger('redemption_count')->default(0); // denormalize sayaç
            $table->unsignedInteger('max_per_user')->default(1);

            // Geçerlilik penceresi
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            // Future — UI'da MVP'de gösterilmez
            $table->json('applies_to_package_codes')->nullable();
            $table->decimal('min_purchase_amount_eur', 10, 2)->nullable();
            $table->unsignedBigInteger('dealer_id')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('discount_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('discount_code_id');

            // Polymorphic redeemable — MVP: 'guest_payment_request'
            // Future: 'student_payment', 'booking_payment', vs.
            $table->string('redeemable_type', 64);
            $table->unsignedBigInteger('redeemable_id');

            // Direct query'ler için (denormalize)
            $table->unsignedBigInteger('guest_application_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->decimal('original_amount_eur', 10, 2);
            $table->decimal('discount_amount_eur', 10, 2);
            $table->decimal('final_amount_eur', 10, 2);

            $table->timestamp('redeemed_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'discount_code_id']);
            $table->index(['redeemable_type', 'redeemable_id']);
            $table->index('guest_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_code_redemptions');
        Schema::dropIfExists('discount_codes');
    }
};
