<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner sitesi bölüm kurgusu: sıra + aç/kapa.
 *
 * site_sections JSON: [{"key":"services","on":true}, {"key":"packages","on":false}, ...]
 * Boş/null = varsayılan sıra, hepsi açık (App\Support\PartnerSiteSections::resolve).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'site_sections')) {
                $table->json('site_sections')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (Schema::hasColumn('dealers', 'site_sections')) {
                $table->dropColumn('site_sections');
            }
        });
    }
};
