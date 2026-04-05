<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_bulletins')) {
            return;
        }
        Schema::table('company_bulletins', function (Blueprint $table): void {
            if (!Schema::hasColumn('company_bulletins', 'target_roles')) {
                $table->json('target_roles')->nullable()->after('is_pinned')
                    ->comment('null = tüm roller; dizi = sadece belirtilen roller');
            }
            if (!Schema::hasColumn('company_bulletins', 'target_departments')) {
                $table->json('target_departments')->nullable()->after('target_roles')
                    ->comment('null = tüm departmanlar; dizi = sadece belirtilen departmanlar');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('company_bulletins')) {
            return;
        }
        Schema::table('company_bulletins', function (Blueprint $table): void {
            foreach (['target_roles', 'target_departments'] as $col) {
                if (Schema::hasColumn('company_bulletins', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
