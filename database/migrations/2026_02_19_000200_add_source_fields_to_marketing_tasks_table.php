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
            if (!Schema::hasColumn('marketing_tasks', 'source_type')) {
                $table->string('source_type', 64)->nullable()->after('is_auto_generated');
            }
            if (!Schema::hasColumn('marketing_tasks', 'source_id')) {
                $table->string('source_id', 96)->nullable()->after('source_type');
            }
            $table->index(['company_id', 'source_type', 'source_id'], 'mkt_tasks_source_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('marketing_tasks')) {
            return;
        }

        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropIndex('mkt_tasks_source_idx');
            foreach (['source_id', 'source_type'] as $col) {
                if (Schema::hasColumn('marketing_tasks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

