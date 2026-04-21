<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('rule_name', 120);

            // Match filtreleri — hepsi nullable, null = "wildcard match"
            $t->string('match_country_code', 2)->nullable()->index();   // DE, TR, FR, ... NULL=hepsi
            $t->string('match_customer_type', 16)->nullable();          // b2c / b2b / NULL=hepsi

            $t->decimal('tax_rate_pct', 5, 2)->default(0);              // 0.00 / 19.00 / 8.00
            $t->string('tax_code', 32)->default('standard');            // standard/reduced/exempt/reverse_charge
            $t->text('invoice_note')->nullable();                       // fatura altı metin

            $t->unsignedSmallInteger('priority')->default(10);          // yüksek öncelik önce denenir
            $t->boolean('is_active')->default(true);

            $t->timestamps();

            $t->index(['company_id', 'is_active', 'priority']);
        });

        // Default satırlar — her company için muhafazakar 3 kural:
        // 1. Default fallback (priority 1): herkes %0 (export varsayımı)
        // 2. DE müşteri (priority 5): %19 ama is_active=false (muhasebeci onayıyla açılır)
        // 3. AB-dışı (TR dahil) muaf (priority 3): açık, %0
        $companies = DB::table('companies')->pluck('id');
        $now = now();
        foreach ($companies as $cid) {
            DB::table('tax_rules')->insert([
                [
                    'company_id'          => $cid,
                    'rule_name'           => 'Default — muaf (%0)',
                    'match_country_code'  => null,
                    'match_customer_type' => null,
                    'tax_rate_pct'        => 0,
                    'tax_code'            => 'exempt',
                    'invoice_note'        => 'Tax exempt — no VAT applicable.',
                    'priority'            => 1,
                    'is_active'           => true,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ],
                [
                    'company_id'          => $cid,
                    'rule_name'           => 'AB-dışı muafiyet',
                    'match_country_code'  => null,      // liste eklenebilir; şimdilik default yakalıyor
                    'match_customer_type' => null,
                    'tax_rate_pct'        => 0,
                    'tax_code'            => 'exempt',
                    'invoice_note'        => 'Export — reverse charge or VAT-exempt.',
                    'priority'            => 3,
                    'is_active'           => false,     // şimdilik kapalı; default yakalıyor
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ],
                [
                    'company_id'          => $cid,
                    'rule_name'           => 'Almanya içi standart KDV (%19)',
                    'match_country_code'  => 'DE',
                    'match_customer_type' => null,
                    'tax_rate_pct'        => 19,
                    'tax_code'            => 'standard',
                    'invoice_note'        => 'Gesetzliche Umsatzsteuer 19% DE.',
                    'priority'            => 10,
                    'is_active'           => false,     // muhasebeci onayıyla aktif edilir
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
