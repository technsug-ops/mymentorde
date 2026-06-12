<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Owner — Customer Health Scores.
 *
 * customer_health_scores: Her company icin tek satir — 4 sub-score + overall + trend + risk_flags.
 *
 * Skorlama:
 *   - login_score   : son 30g aktif user / toplam aktif user oraninda 0-100
 *   - payment_score : odeme gecmisi (overdue/failed yok = 100, var = ceza)
 *   - usage_score   : feature kullanim (booking + doc_request + ai_query / baseline)
 *   - support_score : acik ticket sayisi -> ters orantili
 *   - overall       : login*0.30 + payment*0.30 + usage*0.30 + support*0.10
 *
 * Trend: onceki skora gore yon belirler — rising (+5+), declining (-5+), stable arası.
 * risk_flags: ["no_login_30d","payment_failed","trial_ending","feature_decline"]
 *
 * Idempotent — tabloya zaten varsa eksik kolon ekler.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_health_scores')) {
            Schema::create('customer_health_scores', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->unique();
                $table->unsignedTinyInteger('overall_score')->default(0);
                $table->unsignedTinyInteger('login_score')->default(0);
                $table->unsignedTinyInteger('payment_score')->default(0);
                $table->unsignedTinyInteger('usage_score')->default(0);
                $table->unsignedTinyInteger('support_score')->default(0);
                $table->enum('trend', ['rising', 'stable', 'declining'])->default('stable');
                $table->json('risk_flags')->nullable();
                $table->timestamp('computed_at')->nullable();
                $table->timestamp('last_risk_alert_sent_at')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->index('overall_score');
                $table->index('trend');
            });
        } else {
            Schema::table('customer_health_scores', function (Blueprint $table): void {
                foreach ([
                    'company_id'              => fn($t) => $t->unsignedBigInteger('company_id'),
                    'overall_score'           => fn($t) => $t->unsignedTinyInteger('overall_score')->default(0),
                    'login_score'             => fn($t) => $t->unsignedTinyInteger('login_score')->default(0),
                    'payment_score'           => fn($t) => $t->unsignedTinyInteger('payment_score')->default(0),
                    'usage_score'             => fn($t) => $t->unsignedTinyInteger('usage_score')->default(0),
                    'support_score'           => fn($t) => $t->unsignedTinyInteger('support_score')->default(0),
                    'trend'                   => fn($t) => $t->enum('trend', ['rising', 'stable', 'declining'])->default('stable'),
                    'risk_flags'              => fn($t) => $t->json('risk_flags')->nullable(),
                    'computed_at'             => fn($t) => $t->timestamp('computed_at')->nullable(),
                    'last_risk_alert_sent_at' => fn($t) => $t->timestamp('last_risk_alert_sent_at')->nullable(),
                ] as $col => $build) {
                    if (!Schema::hasColumn('customer_health_scores', $col)) {
                        $build($table);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_health_scores');
    }
};
