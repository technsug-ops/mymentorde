<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // field_rule_approvals: öğrenci+status filtreli sorgular
        if (!$this->indexExists('field_rule_approvals', ['student_id', 'status'])) {
            Schema::table('field_rule_approvals', function (Blueprint $table): void {
                $table->index(['student_id', 'status'], 'fra_student_status_idx');
            });
        }

        // dm_threads: danışman + durum + SLA deadline sorgular
        if (!$this->indexExists('dm_threads', ['advisor_user_id', 'status'])) {
            Schema::table('dm_threads', function (Blueprint $table): void {
                $table->index(['advisor_user_id', 'status', 'next_response_due_at'], 'dmt_advisor_status_due_idx');
            });
        }

        // student_revenues: tarih bazlı raporlama sorgular
        if (!$this->indexExists('student_revenues', ['created_at'])) {
            Schema::table('student_revenues', function (Blueprint $table): void {
                $table->index('created_at', 'str_created_at_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('field_rule_approvals', function (Blueprint $table): void {
            $table->dropIndexIfExists('fra_student_status_idx');
        });

        Schema::table('dm_threads', function (Blueprint $table): void {
            $table->dropIndexIfExists('dmt_advisor_status_due_idx');
        });

        Schema::table('student_revenues', function (Blueprint $table): void {
            $table->dropIndexIfExists('str_created_at_idx');
        });
    }

    /**
     * Verilen sütunları kapsayan bir index mevcut mu?
     * SQLite ve MySQL için çalışır.
     */
    private function indexExists(string $table, array $columns): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                $idxCols = array_map('strtolower', $index['columns']);
                if ($idxCols === array_map('strtolower', $columns)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // getIndexes() desteklenmiyorsa devam et
        }
        return false;
    }
};
