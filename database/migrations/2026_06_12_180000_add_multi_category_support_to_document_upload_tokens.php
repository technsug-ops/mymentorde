<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D5: Multi-doc per token — tek token, N belge.
 *
 * Manager bir aday için 5 belgeyi tek linkte ister:
 *   category_codes = ['passport', 'aps', 'transcript']
 *   max_uses = 3
 *
 * Public form'da kullanıcı her upload'da kalan listeden kategori seçer.
 * Yüklendikçe uploaded_category_codes JSON'una eklenir, used_count++.
 * max_uses'a ulaşınca token bitik (mevcut isExhausted() check'i çalışır).
 *
 * Legacy category_code (singular) kolonu KORUNUR — tek belge talepleri eski
 * akışla devam eder; yeni multi taleplerde category_code null, category_codes dolu.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_upload_tokens', function (Blueprint $table) {
            $table->json('category_codes')->nullable()->after('category_code');
            $table->json('uploaded_category_codes')->nullable()->after('category_codes');
        });
    }

    public function down(): void
    {
        Schema::table('document_upload_tokens', function (Blueprint $table) {
            $table->dropColumn(['category_codes', 'uploaded_category_codes']);
        });
    }
};
