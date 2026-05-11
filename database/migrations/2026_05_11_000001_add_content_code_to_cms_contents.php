<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cms_contents.content_code — kategori bazlı insanlar-okur ID (UNI-001, BLOG-014, vb.)
 * Otomatik atama: CmsContent::creating event; mevcut satırlar için
 * cms:assign-content-codes artisan komutu.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->string('content_code', 20)->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dropColumn('content_code');
        });
    }
};
