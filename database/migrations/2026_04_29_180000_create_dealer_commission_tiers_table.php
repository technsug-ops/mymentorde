<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bayi (Dealer) kademe sistemi:
 *   - Lifetime kümülatif kayıt sayısına göre tier belirlenir
 *   - Her tier'da farklı komisyon oranı (€/kayıt veya %)
 *   - 5 Lead Generation kademe (Bronz → Elmas)
 *   - 3 Freelance Danışman kademe (Aktif → Elit)
 *
 * Pazarlama sayfasındaki (/satis-ortagi) iddia ile birebir eşleşir;
 * bayi panelinde progress bar gösterir, otomatik tier yükseltme yapar.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('dealer_commission_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('tier_code', 32);
            $table->string('tier_name', 64);
            $table->string('tier_emoji', 8)->nullable();

            // Hangi dealer type'a uygun: lead_generation / freelance_danisman / b2b_partner
            $table->string('dealer_type_code', 32);

            // Lifetime kümülatif eşik
            $table->unsignedInteger('min_count')->default(0);
            $table->unsignedInteger('max_count')->nullable(); // null = sınırsız (üst tier)

            // Komisyon yapısı
            // commission_rate_eur: sabit €/kayıt (örn. €200)
            // commission_rate_percent: yüzdeli (örn. %20)
            // İkisinden hangisi doluysa o kullanılır (öncelik: percent > eur ise percent kullan)
            $table->decimal('commission_rate_eur', 10, 2)->nullable();
            $table->decimal('commission_rate_percent', 5, 2)->nullable();

            $table->text('advantages_text')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'tier_code'], 'dct_company_code_uq');
            $table->index(['company_id', 'dealer_type_code', 'min_count'], 'dct_company_type_min_idx');
        });

        // Dealer tablosuna current tier cache + unlocked_at
        Schema::table('dealers', function (Blueprint $table) {
            if (! Schema::hasColumn('dealers', 'current_tier_code')) {
                $table->string('current_tier_code', 32)->nullable()->after('dealer_type_code');
            }
            if (! Schema::hasColumn('dealers', 'tier_unlocked_at')) {
                $table->timestamp('tier_unlocked_at')->nullable()->after('current_tier_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            if (Schema::hasColumn('dealers', 'current_tier_code')) $table->dropColumn('current_tier_code');
            if (Schema::hasColumn('dealers', 'tier_unlocked_at')) $table->dropColumn('tier_unlocked_at');
        });
        Schema::dropIfExists('dealer_commission_tiers');
    }
};
