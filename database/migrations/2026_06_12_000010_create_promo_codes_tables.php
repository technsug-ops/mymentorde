<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Owner — Promo Codes / Discount Yönetimi.
 *
 * promo_codes              : Platform Owner'in tanimladigi indirim kodlari.
 * promo_code_redemptions   : Bir company tarafindan kodun uygulanmasi (audit + analytics).
 *
 * Type ENUM:
 *   - percentage           : value = % indirim (ornek 20.00 = %20 off)
 *   - fixed_amount         : value = EUR cinsinden sabit indirim
 *   - first_n_months_free  : value = MRR'nin %100'u (duration_months kac ay)
 *
 * applies_to_tier NULL = tum tier'lar; aksi halde basic/gold/premium icin gecerli.
 * duration_months NULL = tek seferlik uygula (sadece kullanim aninda).
 *
 * Idempotent — her kolon tek tek kontrol, eksikse ekler.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──── promo_codes ──────────────────────────────────────────────────
        if (!Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('type', 30); // percentage|fixed_amount|first_n_months_free
                $table->decimal('value', 10, 2); // % veya EUR
                $table->unsignedInteger('duration_months')->nullable(); // NULL = tek seferlik
                $table->string('applies_to_tier', 20)->nullable(); // basic|gold|premium|NULL=hepsi
                $table->unsignedInteger('max_uses')->nullable(); // toplam max
                $table->unsignedInteger('current_uses')->default(0);
                $table->date('valid_from');
                $table->date('valid_until');
                $table->boolean('is_active')->default(true);
                $table->string('description', 300)->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'valid_until'], 'pc_active_valid_idx');
                $table->index('applies_to_tier', 'pc_tier_idx');
            });
        } else {
            Schema::table('promo_codes', function (Blueprint $table): void {
                foreach ([
                    'code'               => fn($t) => $t->string('code', 50)->unique(),
                    'type'               => fn($t) => $t->string('type', 30),
                    'value'              => fn($t) => $t->decimal('value', 10, 2),
                    'duration_months'    => fn($t) => $t->unsignedInteger('duration_months')->nullable(),
                    'applies_to_tier'    => fn($t) => $t->string('applies_to_tier', 20)->nullable(),
                    'max_uses'           => fn($t) => $t->unsignedInteger('max_uses')->nullable(),
                    'current_uses'       => fn($t) => $t->unsignedInteger('current_uses')->default(0),
                    'valid_from'         => fn($t) => $t->date('valid_from'),
                    'valid_until'        => fn($t) => $t->date('valid_until'),
                    'is_active'          => fn($t) => $t->boolean('is_active')->default(true),
                    'description'        => fn($t) => $t->string('description', 300)->nullable(),
                    'created_by_user_id' => fn($t) => $t->unsignedBigInteger('created_by_user_id')->nullable(),
                ] as $col => $build) {
                    if (!Schema::hasColumn('promo_codes', $col)) {
                        $build($table);
                    }
                }
            });
        }

        // ──── promo_code_redemptions ───────────────────────────────────────
        if (!Schema::hasTable('promo_code_redemptions')) {
            Schema::create('promo_code_redemptions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('promo_code_id');
                $table->unsignedBigInteger('company_id');
                $table->timestamp('applied_at')->useCurrent();
                $table->decimal('discount_applied_eur', 8, 2)->default(0);
                $table->json('invoice_ids')->nullable(); // etkiledigi platform_invoices id listesi
                $table->timestamps();

                $table->foreign('promo_code_id')->references('id')->on('promo_codes')->cascadeOnDelete();
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();

                $table->index(['promo_code_id', 'company_id'], 'pcr_code_company_idx');
                $table->index('applied_at', 'pcr_applied_idx');
            });
        } else {
            Schema::table('promo_code_redemptions', function (Blueprint $table): void {
                foreach ([
                    'promo_code_id'         => fn($t) => $t->unsignedBigInteger('promo_code_id'),
                    'company_id'            => fn($t) => $t->unsignedBigInteger('company_id'),
                    'applied_at'            => fn($t) => $t->timestamp('applied_at')->useCurrent(),
                    'discount_applied_eur'  => fn($t) => $t->decimal('discount_applied_eur', 8, 2)->default(0),
                    'invoice_ids'           => fn($t) => $t->json('invoice_ids')->nullable(),
                ] as $col => $build) {
                    if (!Schema::hasColumn('promo_code_redemptions', $col)) {
                        $build($table);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_redemptions');
        Schema::dropIfExists('promo_codes');
    }
};
