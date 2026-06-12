<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Owner — Broadcast / Duyuru sistemi.
 *
 *  platform_broadcasts             : duyuru tanimi (draft / scheduled / sent ...)
 *  platform_broadcast_recipients   : her broadcast icin alici kullanici listesi
 *
 * Hedefleme: target_segment (all|trial|paid|specific) + opsiyonel target_tiers JSON
 * + opsiyonel target_company_ids JSON. Schedule, sent_at, sent_count, opened_count,
 * clicked_count ve CTA bilgileri ayni tabloda tutuluyor.
 *
 * Tracking: recipients tablosunda delivered_at / opened_at / clicked_at +
 * status enum (pending|sent|failed|opened|clicked) + error metni.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_broadcasts', function (Blueprint $table): void {
            $table->id();

            $table->string('title', 200);
            $table->text('body'); // Markdown

            $table->enum('channel', ['email', 'in_app', 'both'])->default('both');

            $table->enum('target_segment', ['all', 'trial', 'paid', 'specific'])->default('all');
            $table->json('target_tiers')->nullable();        // ["basic","gold"]
            $table->json('target_company_ids')->nullable();  // [1, 5, 12]

            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled'])->default('draft');

            $table->string('cta_label', 100)->nullable();
            $table->string('cta_url', 500)->nullable();

            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);

            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_for');
            $table->index('sent_at');
        });

        Schema::create('platform_broadcast_recipients', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('broadcast_id');
            $table->foreign('broadcast_id')
                ->references('id')->on('platform_broadcasts')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            $table->enum('status', ['pending', 'sent', 'failed', 'opened', 'clicked'])->default('pending');
            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['broadcast_id', 'status']);
            $table->index(['user_id', 'opened_at']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_broadcast_recipients');
        Schema::dropIfExists('platform_broadcasts');
    }
};
