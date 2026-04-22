<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AI Labs response cache — aynı soru + aynı kaynak havuzu kombinasyonu
     * 24 saat içinde tekrar sorulursa Gemini çağrısı yapmadan DB'den döner.
     *
     * cache_key = sha256(company_id | role | normalize(question) | sources_fingerprint)
     * sources_fingerprint = KnowledgeSource aktif/visible setinin hash'i
     *
     * Kaynaklar değişince fingerprint değişir → eski cache otomatik mismatched olur.
     */
    public function up(): void
    {
        Schema::create('ai_labs_response_cache', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('role', 32);
            $t->string('cache_key', 64)->unique();   // sha256

            $t->text('question');
            $t->longText('response_json');           // full ResponseRouter::ask output

            $t->unsignedInteger('hit_count')->default(1);
            $t->timestamp('last_hit_at')->nullable();
            $t->timestamp('expires_at')->index();

            $t->timestamps();

            $t->index(['company_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_labs_response_cache');
    }
};
