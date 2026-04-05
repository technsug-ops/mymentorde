<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('marketing_tasks')) {
            return;
        }

        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (!Schema::hasColumn('marketing_tasks', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false)->after('completed_at');
            }
            if (!Schema::hasColumn('marketing_tasks', 'recurrence_pattern')) {
                $table->string('recurrence_pattern', 24)->nullable()->after('is_recurring');
            }
            if (!Schema::hasColumn('marketing_tasks', 'recurrence_interval_days')) {
                $table->unsignedInteger('recurrence_interval_days')->nullable()->after('recurrence_pattern');
            }
            if (!Schema::hasColumn('marketing_tasks', 'next_run_at')) {
                $table->timestamp('next_run_at')->nullable()->index()->after('recurrence_interval_days');
            }
            if (!Schema::hasColumn('marketing_tasks', 'escalate_after_hours')) {
                $table->unsignedInteger('escalate_after_hours')->default(24)->after('next_run_at');
            }
            if (!Schema::hasColumn('marketing_tasks', 'last_escalated_at')) {
                $table->timestamp('last_escalated_at')->nullable()->after('escalate_after_hours');
            }
            if (!Schema::hasColumn('marketing_tasks', 'parent_task_id')) {
                $table->unsignedBigInteger('parent_task_id')->nullable()->index()->after('last_escalated_at');
            }
            if (!Schema::hasColumn('marketing_tasks', 'is_auto_generated')) {
                $table->boolean('is_auto_generated')->default(false)->after('parent_task_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('marketing_tasks')) {
            return;
        }

        Schema::table('marketing_tasks', function (Blueprint $table): void {
            foreach ([
                'is_auto_generated',
                'parent_task_id',
                'last_escalated_at',
                'escalate_after_hours',
                'next_run_at',
                'recurrence_interval_days',
                'recurrence_pattern',
                'is_recurring',
            ] as $column) {
                if (Schema::hasColumn('marketing_tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

