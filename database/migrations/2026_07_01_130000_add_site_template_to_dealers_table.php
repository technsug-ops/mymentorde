<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner Frontend — çok-template'li site. Partner ~N şablondan birini seçer.
 * Nullable; boşsa PartnerTemplates::DEFAULT ('aurora') kullanılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'site_template')) {
                $table->string('site_template', 40)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (Schema::hasColumn('dealers', 'site_template')) {
                $table->dropColumn('site_template');
            }
        });
    }
};
