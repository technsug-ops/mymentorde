<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AI Labs içerik üretici — her oluşturulan draft burada saklanır.
     * Template bazlı (motivation, sperrkonto, vize, uni-rec, blog, faq, custom).
     */
    public function up(): void
    {
        Schema::create('ai_labs_content_drafts', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('user_id')->index();       // oluşturan kullanıcı
            $t->unsignedBigInteger('target_user_id')->nullable()->index(); // öğrenci için üretildiyse

            $t->string('template_code', 40);                  // motivation_letter|sperrkonto|visa_call|uni_recommendation|blog_post|faq|custom
            $t->string('title', 300);                         // manager girer

            $t->json('variables')->nullable();                // form girdisi (hedef uni, CV özeti vs.)
            $t->longText('content');                          // AI'ın ürettiği + kullanıcının edit ettiği
            $t->json('metadata')->nullable();                 // blog SEO: slug, keywords, meta_description / FAQ: [{q,a}] array

            $t->enum('status', ['draft', 'published', 'archived'])->default('draft');

            $t->integer('tokens_input')->default(0);
            $t->integer('tokens_output')->default(0);
            $t->string('provider', 32)->nullable();
            $t->string('model', 64)->nullable();

            $t->timestamps();

            $t->index(['company_id', 'template_code']);
            $t->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_labs_content_drafts');
    }
};
