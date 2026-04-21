<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies') || Schema::hasColumn('companies', 'enabled_modules')) {
            return;
        }

        Schema::table('companies', function (Blueprint $t): void {
            $t->json('enabled_modules')->nullable()->after('is_active');
        });

        // Mevcut tüm company'lere varsayılan olarak TÜM modüller açık —
        // geriye uyumluluk için (modül toggle'ı eklenmeden önce herşey açıktı).
        $defaultModules = [
            'core',
            'booking',
            'dam',
            'content_hub',
            'dealer',
            'marketing_admin',
            'analytics_hub',
            'doc_builder_ai',
            'contracts_hub',
            'multi_provider_ai',
        ];

        DB::table('companies')->update([
            'enabled_modules' => json_encode($defaultModules, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'enabled_modules')) {
            return;
        }

        Schema::table('companies', function (Blueprint $t): void {
            $t->dropColumn('enabled_modules');
        });
    }
};
