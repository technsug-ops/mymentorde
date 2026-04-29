<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İndirim kodu paylaşım kartı (public landing /promo/{code}) için:
 *  - template_id: 5 görsel stilden hangisi (1=Classic, 2=Bold, 3=Premium, 4=Playful, 5=Urgency)
 *  - landing_*: kart üzerindeki düzenlenebilir metin alanları (boş = default)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->unsignedTinyInteger('template_id')->nullable()->after('description');
            $table->string('landing_title', 255)->nullable()->after('template_id');
            $table->string('landing_subtitle', 500)->nullable()->after('landing_title');
            $table->string('landing_cta_text', 120)->nullable()->after('landing_subtitle');
            $table->text('landing_disclaimer')->nullable()->after('landing_cta_text');
        });
    }

    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn([
                'template_id',
                'landing_title',
                'landing_subtitle',
                'landing_cta_text',
                'landing_disclaimer',
            ]);
        });
    }
};
