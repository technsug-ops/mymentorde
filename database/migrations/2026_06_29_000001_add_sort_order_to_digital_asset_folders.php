<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAM klasörlerine manuel sıralama (sort_order). Varsayılan 0 → eski davranış
 * (isme göre) korunur; reorder edilince kardeşlere sıralı değer atanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_asset_folders', function (Blueprint $table): void {
            if (!Schema::hasColumn('digital_asset_folders', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('icon');
                $table->index(['parent_id', 'sort_order']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('digital_asset_folders', function (Blueprint $table): void {
            if (Schema::hasColumn('digital_asset_folders', 'sort_order')) {
                $table->dropIndex(['parent_id', 'sort_order']);
                $table->dropColumn('sort_order');
            }
        });
    }
};
