<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versiyonlama altyapısı (version + parent_asset_id) UI'da hiç kullanılmadığı
     * için kod karmaşıklığını ve bakım yükünü azaltmak amacıyla kaldırılıyor.
     * İhtiyaç doğarsa 2026_04_09_100005 migration'ı referans alınarak geri eklenir.
     *
     * doc_code kolonu tutuluyor — aktif olarak kullanılıyor (UI'da ve aramada görünür).
     */
    public function up(): void
    {
        Schema::table('digital_assets', function (Blueprint $table): void {
            // SQLite indeks silme davranışı farklı — önce index, sonra kolonlar.
            try {
                $table->dropIndex(['company_id', 'parent_asset_id']);
            } catch (\Throwable $e) {
                // İndeks yoksa sorun değil
            }
        });

        Schema::table('digital_assets', function (Blueprint $table): void {
            if (Schema::hasColumn('digital_assets', 'version')) {
                $table->dropColumn('version');
            }
            if (Schema::hasColumn('digital_assets', 'parent_asset_id')) {
                $table->dropColumn('parent_asset_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('digital_assets', function (Blueprint $table): void {
            $table->unsignedSmallInteger('version')->default(1)->after('is_pinned');
            $table->unsignedBigInteger('parent_asset_id')->nullable()->after('version');
            $table->index(['company_id', 'parent_asset_id']);
        });
    }
};
