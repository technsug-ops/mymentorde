<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Çok dilli yasal/politika metinleri (KVKK, Datenschutzerklärung, GDPR,
 * Cookie Policy, Terms of Service vb.). Manager portal'dan editör ile
 * doğrudan düzenlenir, config dosyasına dokunmaya gerek yoktur.
 *
 * kind:   'privacy' | 'cookie' | 'terms' | 'kvkk' (alias) | 'imprint'
 * locale: 'tr' | 'de' | 'en'
 *
 * Aynı (company, kind, locale) için tek kayıt.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('policy_documents')) return;

        Schema::create('policy_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('kind', 40); // privacy/cookie/terms/imprint
            $table->string('locale', 5); // tr/de/en
            $table->string('title', 190)->nullable();
            $table->longText('body')->nullable(); // HTML veya Markdown
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'kind', 'locale'], 'pd_unique');
            $table->index(['kind', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_documents');
    }
};
