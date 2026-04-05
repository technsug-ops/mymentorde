<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title_tr');
            $table->string('title_de')->nullable();
            $table->string('title_en')->nullable();
            $table->longText('body_tr');
            $table->longText('body_de')->nullable();
            $table->longText('body_en')->nullable();
            $table->string('category', 64)->default('faq');
            $table->json('tags')->nullable();
            $table->json('target_roles')->nullable();
            $table->boolean('is_published')->default(false);
            $table->string('author_id')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_articles');
    }
};

