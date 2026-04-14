<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E4 — Klasör yıldızlama: user ↔ folder favorites junction table.
 * Unique (user_id, folder_id) ile duplicate engellenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_asset_folder_favorites', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('folder_id');
            $table->timestamps();

            $table->unique(['user_id', 'folder_id']);
            $table->index(['folder_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('folder_id')->references('id')->on('digital_asset_folders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_asset_folder_favorites');
    }
};
