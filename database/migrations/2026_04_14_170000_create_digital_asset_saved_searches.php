<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAM5 — Saved searches (bookmarks): kullanıcının sık kullandığı
 * filter kombinasyonlarını tek tıkla uygulama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_asset_saved_searches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 150);
            $table->json('query_params'); // {q, tag, category, uploader, size_min, size_max, from, to}
            $table->timestamps();

            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_asset_saved_searches');
    }
};
