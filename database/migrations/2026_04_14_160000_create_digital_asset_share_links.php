<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAM4 — Share links: süreli + opsiyonel şifreli paylaşım linkleri.
 *
 * Harici kişilere asset gönderme akışı:
 *   1. Admin "Paylaş" butonu → link oluşturur (token, expires_at, password?)
 *   2. Oluşan URL'i harici kişiye iletir
 *   3. Harici kişi URL'i açar → password varsa girer → asset'i indirir/izler
 *   4. Download counter artar; link expired/revoked ise 410 Gone
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_asset_share_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('token', 64)->unique();
            $table->string('password_hash', 255)->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('max_downloads')->nullable(); // null = sınırsız
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('last_accessed_ip', 45)->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();

            $table->index(['asset_id']);
            $table->index(['expires_at']);
            $table->foreign('asset_id')->references('id')->on('digital_assets')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_asset_share_links');
    }
};
