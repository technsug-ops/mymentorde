<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner sitesi öğrenci yorumları (JSON: [{text, name, school}]).
 *
 * Şablonlarda örnek/uydurma yorum yazılmaz — bölüm yalnız partner kendi gerçek
 * yorumlarını girdiğinde görünür. Nullable: boş = bölüm gizli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'site_testimonials')) {
                $table->json('site_testimonials')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (Schema::hasColumn('dealers', 'site_testimonials')) {
                $table->dropColumn('site_testimonials');
            }
        });
    }
};
