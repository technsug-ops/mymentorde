<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026_06_09_000002 sadece guest_ + staff_ ai_conversations tablolarına
 * response_time_ms ekledi — senior_ai_conversations atlandı. Bu migration
 * onu tamamlar; AnalyticsService::conversationMetrics() ortalama yanıt
 * süresine senior konuşmalarını da dahil edebilsin.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('senior_ai_conversations')) {
            return;
        }

        Schema::table('senior_ai_conversations', function (Blueprint $t) {
            if (! Schema::hasColumn('senior_ai_conversations', 'response_time_ms')) {
                $t->unsignedInteger('response_time_ms')->nullable()->after('tokens_output');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('senior_ai_conversations')
            && Schema::hasColumn('senior_ai_conversations', 'response_time_ms')) {
            Schema::table('senior_ai_conversations', function (Blueprint $t) {
                $t->dropColumn('response_time_ms');
            });
        }
    }
};
