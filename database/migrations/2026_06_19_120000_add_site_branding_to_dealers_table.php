<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 3 — Bayi white-label mini-site (path /p/{slug}) + custom domain alanları.
 * Hepsi nullable; mevcut bayiler etkilenmez (site_enabled default false).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            $cols = [
                'public_slug'              => fn () => $table->string('public_slug', 64)->nullable()->unique(),
                'site_enabled'            => fn () => $table->boolean('site_enabled')->default(false),
                'site_logo_path'          => fn () => $table->string('site_logo_path')->nullable(),
                'site_accent_color'       => fn () => $table->string('site_accent_color', 7)->nullable(),
                'site_hero_title'         => fn () => $table->string('site_hero_title', 160)->nullable(),
                'site_hero_subtitle'      => fn () => $table->string('site_hero_subtitle', 300)->nullable(),
                'site_hero_image_path'    => fn () => $table->string('site_hero_image_path')->nullable(),
                'site_about_text'         => fn () => $table->text('site_about_text')->nullable(),
                'site_phone'              => fn () => $table->string('site_phone', 50)->nullable(),
                'site_whatsapp'           => fn () => $table->string('site_whatsapp', 50)->nullable(),
                'site_instagram'          => fn () => $table->string('site_instagram', 100)->nullable(),
                'custom_domain'           => fn () => $table->string('custom_domain')->nullable()->unique(),
                'custom_domain_verified_at' => fn () => $table->timestamp('custom_domain_verified_at')->nullable(),
                'custom_domain_token'     => fn () => $table->string('custom_domain_token', 64)->nullable(),
            ];
            foreach ($cols as $name => $make) {
                if (!Schema::hasColumn('dealers', $name)) {
                    $make();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            foreach ([
                'public_slug', 'site_enabled', 'site_logo_path', 'site_accent_color',
                'site_hero_title', 'site_hero_subtitle', 'site_hero_image_path', 'site_about_text',
                'site_phone', 'site_whatsapp', 'site_instagram',
                'custom_domain', 'custom_domain_verified_at', 'custom_domain_token',
            ] as $col) {
                if (Schema::hasColumn('dealers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
