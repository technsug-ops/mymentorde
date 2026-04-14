<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * marketing_tracking_links.category_code/platform_code/placement_code
 * başlangıçta VARCHAR(2) ve (1) olarak tanımlanmıştı — ama seeder'lar ve
 * runtime kod "social", "instagram", "story" gibi uzun değerler kullanıyor.
 * Bu yüzden 64 karaktere çıkarılıyor (diğer category_code alanlarıyla tutarlı).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tracking_links', function (Blueprint $table): void {
            if (Schema::hasColumn('marketing_tracking_links', 'category_code')) {
                $table->string('category_code', 64)->nullable()->change();
            }
            if (Schema::hasColumn('marketing_tracking_links', 'platform_code')) {
                $table->string('platform_code', 64)->nullable()->change();
            }
            if (Schema::hasColumn('marketing_tracking_links', 'placement_code')) {
                $table->string('placement_code', 64)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Geri dönüş yapılmaz — veri kaybı riski (64 → 2 truncation)
    }
};
