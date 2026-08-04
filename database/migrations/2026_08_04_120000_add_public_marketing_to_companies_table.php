<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public sayfalarda (login, /apply) B2C kazanım içeriği gösterilsin mi?
 *
 * B2C tarafı (panel.mentorde.com) reklamın tamamını görmeye devam eder; partner
 * firmaların ve nötr portalın ziyaretçisi hiçbirini görmez. Kolon varsayılanı
 * TRUE — yani mevcut her şirket bugünkü davranışını aynen korur, davranış
 * değişikliği yalnızca açıkça kapatılan şirketlerde olur.
 *
 * Marka `App\Support\Brand` üzerinden config('brand.public_marketing')'e taşınır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'public_marketing')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->boolean('public_marketing')->default(true)->after('brand_overrides');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'public_marketing')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('public_marketing');
        });
    }
};
