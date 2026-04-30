<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 2 — Program detay alanlarının Türkçe çeviri cache'i.
 *
 * Lazy on-demand pattern:
 *  - description / qualification_requirements / vb. orijinal İngilizce kalır
 *  - *_tr kolonu boşsa → kullanıcı "Türkçeye çevir" butonu görür
 *  - Çeviri yapılınca DB'ye yazılır → sonraki ziyarette anında gelir
 *  - Manager UI'da is_manually_curated=true ile manuel düzeltme korunur
 *
 * AI provider: Gemini Flash (mevcut AI Labs altyapısı).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->text('description_tr')->nullable()->after('description')
                ->comment('description alanının Türkçe çevirisi (Gemini lazy translate)');
            $table->text('qualification_requirements_tr')->nullable()->after('qualification_requirements')
                ->comment('qualification_requirements alanının Türkçe çevirisi');
            $table->text('language_requirements_tr')->nullable()->after('language_requirements')
                ->comment('language_requirements alanının Türkçe çevirisi (gelecekte)');
            $table->text('required_documents_tr')->nullable()->after('required_documents')
                ->comment('required_documents alanının Türkçe çevirisi (gelecekte)');
            $table->timestamp('translated_at')->nullable()
                ->comment('En son çeviri zamanı — orijinal değişirse stale flag için');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn([
                'description_tr', 'qualification_requirements_tr',
                'language_requirements_tr', 'required_documents_tr',
                'translated_at',
            ]);
        });
    }
};
