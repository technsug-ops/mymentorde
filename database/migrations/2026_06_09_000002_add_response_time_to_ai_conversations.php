<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AssistantService::ask() response_time_ms ölçüp model'e geçiyor ama kolon
 * yok → fillable da yok → sessizce kayboluyor. Bu migration kolonu ekler,
 * analytics dashboard'da "ortalama yanıt süresi" gösterilebilsin.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['guest_ai_conversations', 'staff_ai_conversations'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'response_time_ms')) {
                    $t->unsignedInteger('response_time_ms')->nullable()->after('tokens_output');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['guest_ai_conversations', 'staff_ai_conversations'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'response_time_ms')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('response_time_ms');
                });
            }
        }
    }
};
