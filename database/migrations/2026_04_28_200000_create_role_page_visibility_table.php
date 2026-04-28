<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Premium "page_visibility" modülü — manager rol-bazlı sayfa görünürlüğünü
 * kontrol eder.
 *
 * Tablo şeması: company × role × page_key → on/off
 *
 * Yokluk durumunda PageAccess default'a düşer (genelde TRUE — tüm sayfalar
 * açık). Manager bir kez setting yazınca artık kayıt o değeri tutar.
 *
 * page_key: PageAccess::PAGES'da tanımlı (örn. 'discover', 'marketplace')
 * role: User rolü ('guest', 'student', 'dealer', 'senior', ileride staff_*)
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('role_page_visibility')) return;

        Schema::create('role_page_visibility', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('role', 60);
            $table->string('page_key', 80);
            $table->boolean('is_visible')->default(true);
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'role', 'page_key'], 'rpv_unique');
            $table->index(['role', 'page_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_page_visibility');
    }
};
