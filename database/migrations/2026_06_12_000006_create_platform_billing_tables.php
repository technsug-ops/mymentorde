<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Owner — Billing tables (Faz 7).
 *
 * platform_invoices       : Mentorde'nin musteri company'lerine kestigi faturalar.
 * platform_payment_methods: Stripe iliskili odeme aracilari (kart/SEPA/wire).
 *
 * Idempotent — her kolon ayri kontrol, tabloya zaten varsa atlar.
 *
 * NOT: 'platform_*' prefix'i ile kararli — Mentorde platformunun
 * kendi gelirini takip eder (Stripe Connect / payout altyapisindan ayri).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──── platform_invoices ────────────────────────────────────────────
        if (!Schema::hasTable('platform_invoices')) {
            Schema::create('platform_invoices', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('invoice_number', 50)->unique();
                $table->date('period_start');
                $table->date('period_end');
                $table->string('tier', 20);
                $table->decimal('amount_eur', 10, 2);
                $table->decimal('tax_rate_pct', 5, 2)->default(19.00);
                $table->decimal('tax_amount_eur', 10, 2);
                $table->decimal('total_eur', 10, 2);
                $table->string('status', 20)->default('draft'); // draft|sent|paid|overdue|cancelled
                $table->string('stripe_invoice_id', 200)->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('pdf_path', 300)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->index(['company_id', 'status']);
                $table->index(['status', 'period_end']);
            });
        } else {
            // Idempotent: eksik kolonlar varsa ekle
            Schema::table('platform_invoices', function (Blueprint $table): void {
                foreach ([
                    'company_id'         => fn($t) => $t->unsignedBigInteger('company_id'),
                    'invoice_number'     => fn($t) => $t->string('invoice_number', 50),
                    'period_start'       => fn($t) => $t->date('period_start'),
                    'period_end'         => fn($t) => $t->date('period_end'),
                    'tier'               => fn($t) => $t->string('tier', 20),
                    'amount_eur'         => fn($t) => $t->decimal('amount_eur', 10, 2),
                    'tax_rate_pct'       => fn($t) => $t->decimal('tax_rate_pct', 5, 2)->default(19.00),
                    'tax_amount_eur'     => fn($t) => $t->decimal('tax_amount_eur', 10, 2),
                    'total_eur'          => fn($t) => $t->decimal('total_eur', 10, 2),
                    'status'             => fn($t) => $t->string('status', 20)->default('draft'),
                    'stripe_invoice_id'  => fn($t) => $t->string('stripe_invoice_id', 200)->nullable(),
                    'sent_at'            => fn($t) => $t->timestamp('sent_at')->nullable(),
                    'paid_at'            => fn($t) => $t->timestamp('paid_at')->nullable(),
                    'pdf_path'           => fn($t) => $t->string('pdf_path', 300)->nullable(),
                    'notes'              => fn($t) => $t->text('notes')->nullable(),
                ] as $col => $build) {
                    if (!Schema::hasColumn('platform_invoices', $col)) {
                        $build($table);
                    }
                }
            });
        }

        // ──── platform_payment_methods ─────────────────────────────────────
        if (!Schema::hasTable('platform_payment_methods')) {
            Schema::create('platform_payment_methods', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('stripe_payment_method_id', 200);
                $table->string('type', 50); // card|sepa|wire
                $table->string('brand', 20)->nullable(); // visa|mastercard|amex
                $table->string('last4', 4)->nullable();
                $table->unsignedTinyInteger('exp_month')->nullable();
                $table->unsignedSmallInteger('exp_year')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->index(['company_id', 'is_default']);
            });
        } else {
            Schema::table('platform_payment_methods', function (Blueprint $table): void {
                foreach ([
                    'company_id'                => fn($t) => $t->unsignedBigInteger('company_id'),
                    'stripe_payment_method_id'  => fn($t) => $t->string('stripe_payment_method_id', 200),
                    'type'                      => fn($t) => $t->string('type', 50),
                    'brand'                     => fn($t) => $t->string('brand', 20)->nullable(),
                    'last4'                     => fn($t) => $t->string('last4', 4)->nullable(),
                    'exp_month'                 => fn($t) => $t->unsignedTinyInteger('exp_month')->nullable(),
                    'exp_year'                  => fn($t) => $t->unsignedSmallInteger('exp_year')->nullable(),
                    'is_default'                => fn($t) => $t->boolean('is_default')->default(false),
                ] as $col => $build) {
                    if (!Schema::hasColumn('platform_payment_methods', $col)) {
                        $build($table);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payment_methods');
        Schema::dropIfExists('platform_invoices');
    }
};
