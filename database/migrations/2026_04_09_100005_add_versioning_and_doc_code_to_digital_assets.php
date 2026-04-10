<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_assets', function (Blueprint $table): void {
            // ── Versiyon yönetimi ────────────────────────────────────────
            // version=1 ilk yükleme; üstüne yüklenirse parent_asset_id ile zincir.
            $table->unsignedSmallInteger('version')->default(1)->after('is_pinned');
            $table->unsignedBigInteger('parent_asset_id')->nullable()->after('version');

            // ── Stable doc kodu (DocumentNamingService ile uyumlu) ──────
            // Format: DOC-{YEAR}-{ID 6 digit}, örn DOC-2026-000123
            $table->string('doc_code', 32)->nullable()->after('uuid');

            $table->index(['company_id', 'parent_asset_id']);
            $table->index('doc_code');
        });
    }

    public function down(): void
    {
        Schema::table('digital_assets', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'parent_asset_id']);
            $table->dropIndex(['doc_code']);
            $table->dropColumn(['version', 'parent_asset_id', 'doc_code']);
        });
    }
};
