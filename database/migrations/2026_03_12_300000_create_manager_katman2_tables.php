<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 2.2 Zamanlanmış Raporlar ──────────────────────────────────────────
        Schema::create('manager_scheduled_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(0);
            $table->string('report_type', 30);           // weekly_summary, monthly_summary, senior_performance
            $table->string('frequency', 15);             // weekly, monthly
            $table->unsignedTinyInteger('day_of_week')->default(1);   // 1=Monday
            $table->unsignedTinyInteger('day_of_month')->default(1);  // 1-28
            $table->json('send_to');                     // ["manager@firma.com"]
            $table->string('senior_filter')->nullable(); // belirli senior veya null
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active'], 'idx_msr_company_active');
        });

        // ── 2.3 Performans Hedefleri ──────────────────────────────────────────
        Schema::create('manager_performance_targets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(0);
            $table->string('period', 7);                 // '2026-03'
            $table->string('target_type', 30);           // company_wide, senior_specific
            $table->string('senior_email')->nullable();
            $table->decimal('target_revenue', 12, 2)->default(0);
            $table->integer('target_conversions')->default(0);
            $table->integer('target_new_guests')->default(0);
            $table->integer('target_doc_reviews')->default(0);
            $table->integer('target_contracts_signed')->default(0);
            $table->unsignedBigInteger('set_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'period', 'target_type', 'senior_email'], 'idx_mpt_unique');
        });

        // ── 2.4 Alert Kuralları ───────────────────────────────────────────────
        Schema::create('manager_alert_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(0);
            $table->string('name', 180);
            $table->string('condition_type', 50);        // risk_score_above, revenue_below, inactive_students, pending_docs_above, overdue_outcomes
            $table->decimal('threshold_value', 12, 2);
            $table->string('check_frequency', 15)->default('daily'); // hourly, daily, weekly
            // MySQL 8.x JSON kolona default için expression syntax gerekir; nullable yapıp
            // model tarafında default'u set etmek cross-version (MySQL 8 / MariaDB / SQLite) en güvenlisi.
            $table->json('notify_channels')->nullable();
            $table->json('notify_emails')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active'], 'idx_mar_company_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_alert_rules');
        Schema::dropIfExists('manager_performance_targets');
        Schema::dropIfExists('manager_scheduled_reports');
    }
};
