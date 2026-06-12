<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Owner — Trial Yonetimi (Faz 8).
 *
 * 1) trial_extensions — Platform Owner'in manuel trial uzatma kayitlari (audit)
 * 2) companies tablosuna trial lifecycle metadata:
 *    - trial_started_at      : trial'in basladigi an
 *    - converted_to_paid_at  : trial -> paid donusumu (LTV/conversion icin)
 *    - nurture_emails_sent   : gonderilmis nurture mail listesi (JSON)
 *
 * Tum eklemeler Schema::hasColumn idempotent — tekrar calistirilirsa atlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1) trial_extensions tablosu ───────────────────────────────────────
        if (!Schema::hasTable('trial_extensions')) {
            Schema::create('trial_extensions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedInteger('extension_days');
                $table->string('reason', 500)->nullable();
                $table->unsignedBigInteger('granted_by_user_id')->nullable();
                $table->date('previous_trial_ends_at')->nullable();
                $table->date('new_trial_ends_at');
                $table->timestamps();

                $table->index('company_id');
                $table->index('granted_by_user_id');
                $table->index('created_at');

                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->cascadeOnDelete();
            });
        }

        // ── 2) companies tablosuna trial lifecycle alanlari ──────────────────
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            if (!Schema::hasColumn('companies', 'trial_started_at')) {
                $table->timestamp('trial_started_at')->nullable()->after('trial_ends_at');
            }
            if (!Schema::hasColumn('companies', 'converted_to_paid_at')) {
                $table->timestamp('converted_to_paid_at')->nullable()->after('trial_started_at');
            }
            if (!Schema::hasColumn('companies', 'nurture_emails_sent')) {
                $table->json('nurture_emails_sent')->nullable()->after('converted_to_paid_at');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table): void {
                foreach (['nurture_emails_sent', 'converted_to_paid_at', 'trial_started_at'] as $col) {
                    if (Schema::hasColumn('companies', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('trial_extensions')) {
            Schema::drop('trial_extensions');
        }
    }
};
