<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-brand Faz 2 — şirket başına marka ve domain.
 *
 * Bugüne kadar marka tamamen .env tabanlıydı (config/brand.php), yani bir kurulum =
 * bir marka. YourGermanUni altında her partner firma kendi markasını göreceği için
 * marka bilgisi artık şirket kaydından gelmeli.
 *
 * Sıcak yol için 3 düz kolon (isim/logo/renk), uzun kuyruk (banka, hukuki, sosyal,
 * mail kimliği, ödeme hatırlatma günleri...) için tek JSON: brand_overrides —
 * config/brand.php'nin yapısını birebir taklit eder, array_replace_recursive ile
 * üzerine biner.
 *
 * primary_domain BİLEREK boş bırakılıyor: dolduran olmadıkça host eşleşmesi
 * çalışmaz ve sistem bugünkü gibi varsayılan şirkete düşer (davranış değişmez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (!Schema::hasColumn('companies', 'slug')) {
                $table->string('slug', 60)->nullable()->after('code');
            }
            if (!Schema::hasColumn('companies', 'primary_domain')) {
                // yourgermanuni.com / a.yourgermanuni.com / panel.mentorde.com
                $table->string('primary_domain', 190)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('companies', 'domain_aliases')) {
                // ["www.yourgermanuni.com", "firmaA.com"]
                $table->json('domain_aliases')->nullable()->after('primary_domain');
            }
            if (!Schema::hasColumn('companies', 'brand_name')) {
                $table->string('brand_name', 120)->nullable()->after('domain_aliases');
            }
            if (!Schema::hasColumn('companies', 'brand_logo_url')) {
                $table->string('brand_logo_url', 500)->nullable()->after('brand_name');
            }
            if (!Schema::hasColumn('companies', 'brand_primary_color')) {
                $table->string('brand_primary_color', 7)->nullable()->after('brand_logo_url');
            }
            if (!Schema::hasColumn('companies', 'brand_overrides')) {
                $table->json('brand_overrides')->nullable()->after('brand_primary_color');
            }
        });

        // Index'ler ayrı: hasColumn kontrolünden sonra güvenle eklenir.
        Schema::table('companies', function (Blueprint $table): void {
            $table->index('primary_domain', 'companies_primary_domain_idx');
            $table->index('slug', 'companies_slug_idx');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex('companies_primary_domain_idx');
            $table->dropIndex('companies_slug_idx');
        });

        Schema::table('companies', function (Blueprint $table): void {
            foreach (['brand_overrides', 'brand_primary_color', 'brand_logo_url', 'brand_name', 'domain_aliases', 'primary_domain', 'slug'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
