<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 2 — Komisyon override (bölge bayisine üst pay).
 *
 * - dealers: bölge bayisi bazlı override oranı (alt bayi getirisi üzerinden).
 * - dealer_student_revenues: override satırını işaretlemek için origin + is_override.
 *   Override satırı dealer_id = bölge bayisi code'u (mevcut sorgular değişmez),
 *   origin_dealer_id = getiriyi yapan alt bayinin code'u.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'override_rate_eur')) {
                $table->decimal('override_rate_eur', 10, 2)->nullable()->after('signup_bonus_unlocked_at');
            }
            if (!Schema::hasColumn('dealers', 'override_rate_percent')) {
                $table->decimal('override_rate_percent', 5, 2)->nullable()->after('override_rate_eur');
            }
            if (!Schema::hasColumn('dealers', 'override_basis')) {
                // 'percent_of_sub' (alt bayi hak edişinin %'si) | 'fixed_eur' (öğrenci başına sabit €)
                $table->string('override_basis', 32)->default('percent_of_sub')->after('override_rate_percent');
            }
        });

        Schema::table('dealer_student_revenues', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealer_student_revenues', 'origin_dealer_id')) {
                $table->string('origin_dealer_id')->nullable()->index()->after('dealer_id');
            }
            if (!Schema::hasColumn('dealer_student_revenues', 'is_override')) {
                $table->boolean('is_override')->default(false)->index()->after('origin_dealer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            foreach (['override_rate_eur', 'override_rate_percent', 'override_basis'] as $col) {
                if (Schema::hasColumn('dealers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('dealer_student_revenues', function (Blueprint $table): void {
            foreach (['origin_dealer_id', 'is_override'] as $col) {
                if (Schema::hasColumn('dealer_student_revenues', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
