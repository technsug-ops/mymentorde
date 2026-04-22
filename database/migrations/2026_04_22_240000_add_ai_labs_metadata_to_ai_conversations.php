<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * guest_ai_conversations + senior_ai_conversations tablolarına AI Labs
     * response_mode + cited_sources + provider/model metadata kolonları ekler.
     *
     * Eski kayıtlar response_mode=null kalır (eski flow göstergesi).
     */
    public function up(): void
    {
        foreach (['guest_ai_conversations', 'senior_ai_conversations'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table): void {
                if (!Schema::hasColumn($table, 'response_mode')) {
                    $t->string('response_mode', 20)->nullable()->after('tokens_used'); // source|external|refused
                }
                if (!Schema::hasColumn($table, 'cited_sources')) {
                    $t->json('cited_sources')->nullable()->after('response_mode'); // [2, 7]
                }
                if (!Schema::hasColumn($table, 'tokens_input')) {
                    $t->integer('tokens_input')->default(0)->after('cited_sources');
                }
                if (!Schema::hasColumn($table, 'tokens_output')) {
                    $t->integer('tokens_output')->default(0)->after('tokens_input');
                }
                if (!Schema::hasColumn($table, 'provider')) {
                    $t->string('provider', 32)->nullable()->after('tokens_output'); // gemini|claude|openai
                }
                if (!Schema::hasColumn($table, 'model')) {
                    $t->string('model', 64)->nullable()->after('provider');
                }
                if (!Schema::hasColumn($table, 'role')) {
                    $t->string('role', 32)->nullable()->after('model'); // guest|student|senior|manager|admin_staff
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['guest_ai_conversations', 'senior_ai_conversations'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table): void {
                foreach (['response_mode', 'cited_sources', 'tokens_input', 'tokens_output', 'provider', 'model', 'role'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
