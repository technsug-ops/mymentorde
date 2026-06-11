<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Owner — Subscription Tier MVP (Faz 2).
 *
 * companies tablosuna SaaS abonelik plani + faturalama metadata kolonlari ekler:
 *   - subscription_tier  : trial | basic | gold | premium (default trial)
 *   - trial_ends_at      : trial bitis tarihi (NULL = trial degil)
 *   - subscription_renews_at : bir sonraki yenileme tarihi (NULL = paid degil)
 *   - billing_email      : faturalama icin ayri e-posta (manager e-postasindan ayri)
 *   - mrr_eur            : aylik tekrarlayan gelir (€) — analytics icin sabit
 *
 * Idempotent (her kolon ayri kontrol): tekrar calistirilirsa atlar.
 *
 * Geriye uyum: mevcut company'ler 'premium' olarak set edilir — modul toggle gore
 * mevcut haklarini kaybetmesinler. Yeni musteriler default 'trial' alir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            if (!Schema::hasColumn('companies', 'subscription_tier')) {
                // MySQL enum + Laravel: string + check default. SQLite uyumu icin string kullaniyoruz.
                $table->string('subscription_tier', 20)->default('trial')->after('is_active');
            }
            if (!Schema::hasColumn('companies', 'trial_ends_at')) {
                $table->date('trial_ends_at')->nullable()->after('subscription_tier');
            }
            if (!Schema::hasColumn('companies', 'subscription_renews_at')) {
                $table->date('subscription_renews_at')->nullable()->after('trial_ends_at');
            }
            if (!Schema::hasColumn('companies', 'billing_email')) {
                $table->string('billing_email', 190)->nullable()->after('subscription_renews_at');
            }
            if (!Schema::hasColumn('companies', 'mrr_eur')) {
                $table->decimal('mrr_eur', 8, 2)->default(0)->after('billing_email');
            }
        });

        // Geriye uyum: mevcut tum company'leri 'premium' yap — modul toggle uzerinden
        // tum yetkilerine sahiplerdi, tier introdusu bunu bozmasin.
        DB::table('companies')
            ->where(function ($q) {
                $q->whereNull('subscription_tier')->orWhere('subscription_tier', '')->orWhere('subscription_tier', 'trial');
            })
            ->update([
                'subscription_tier' => 'premium',
                'mrr_eur'           => 299.00, // premium tier MRR — config ile senkron
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            foreach (['mrr_eur', 'billing_email', 'subscription_renews_at', 'trial_ends_at', 'subscription_tier'] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
