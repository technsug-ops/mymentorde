<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Owner audit log — Mentorde SaaS sahibi tarafindan yapilan
 * kritik aksiyonlarin denetim kaydi. audit_trails'ten farkli olarak:
 *
 *   - audit_trails: customer manager seviyesinde, company_id'ye bagli, CRUD odakli
 *   - platform_audit_logs: platform owner seviyesinde, cross-company, EVENT odakli
 *     (impersonate, billing, settings, security update, vb.)
 *
 * Mevcut audit_trails'i bozmadan paralel calisir. PlatformController & friends
 * iki tabloya da yazar (geciste audit_trails'i tetkike acik tutalim).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('platform_audit_logs')) {
            return;
        }

        Schema::create('platform_audit_logs', function (Blueprint $table): void {
            $table->id();

            // Event icerigi (ornek: 'platform.impersonate.start', 'platform.billing.send')
            $table->string('event', 100)->index();

            // Aktor — eylem sahibi (platform owner, manager, vb.)
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('actor_email', 200)->nullable();
            $table->string('actor_role', 50)->nullable();
            $table->string('actor_ip', 45)->nullable();

            // Hedef — etkilenen entity (company, invoice, user, etc.)
            $table->string('target_type', 50)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();

            // Ek metadata — old/new values, context, vb.
            $table->json('context')->nullable();

            // Onem — info|warning|critical (filtre/alert icin)
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');

            $table->timestamp('created_at')->useCurrent();

            // Kompozit + bireysel indeksler
            $table->index(['target_type', 'target_id'], 'pal_target_idx');
            $table->index(['severity', 'created_at'], 'pal_severity_date_idx');
            $table->index('created_at', 'pal_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_audit_logs');
    }
};
