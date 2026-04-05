<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_builder_templates')) {
            return;
        }
        Schema::table('document_builder_templates', function (Blueprint $table): void {
            if (!Schema::hasColumn('document_builder_templates', 'name')) {
                $table->string('name', 150)->default('Varsayılan Şablon')->after('language');
            }
            if (!Schema::hasColumn('document_builder_templates', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
        });

        // Eski unique kısıtını kaldır (sadece MySQL'de)
        if (config('database.default') !== 'sqlite') {
            try {
                Schema::table('document_builder_templates', function (Blueprint $table): void {
                    $table->dropUnique('idx_company_type_lang');
                });
            } catch (\Throwable) {
                // Kısıt zaten kaldırılmış olabilir
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('document_builder_templates')) {
            return;
        }
        Schema::table('document_builder_templates', function (Blueprint $table): void {
            foreach (['name', 'is_default'] as $col) {
                if (Schema::hasColumn('document_builder_templates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
