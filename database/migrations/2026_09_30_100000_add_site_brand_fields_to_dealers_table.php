<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner sitesinde görünen marka adı + etiket (slogan) — partner kendisi düzenler.
 *
 * `site_brand_name` bilerek `name`'den ayrı: `name` raporlarda, komisyon ve
 * sözleşmelerde geçen kayıt adıdır; partner sitesinde farklı bir ticari ad
 * kullanmak isteyebilir. null = kayıt adı / varsayılan etiket gösterilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'site_brand_name')) {
                $table->string('site_brand_name', 120)->nullable();
            }
            if (!Schema::hasColumn('dealers', 'site_tagline')) {
                $table->string('site_tagline', 120)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            foreach (['site_brand_name', 'site_tagline'] as $col) {
                if (Schema::hasColumn('dealers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
