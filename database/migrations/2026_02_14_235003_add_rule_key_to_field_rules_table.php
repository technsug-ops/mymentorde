<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_rules', function (Blueprint $table): void {
            $table->string('rule_key', 64)->nullable()->after('created_by');
            $table->index('rule_key');
        });

        DB::table('field_rules')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $condition = json_decode((string) $row->condition, true);
                if (is_array($condition)) {
                    ksort($condition);
                }
                $base = sha1(implode('|', [
                    (string) $row->target_form,
                    (string) $row->target_field,
                    (string) $row->severity,
                    json_encode($condition, JSON_UNESCAPED_UNICODE),
                ]));

                $exists = DB::table('field_rules')
                    ->where('id', '!=', $row->id)
                    ->where('rule_key', $base)
                    ->exists();

                $ruleKey = $exists ? substr($base, 0, 56).sprintf('%08d', (int) $row->id) : $base;

                DB::table('field_rules')->where('id', $row->id)->update(['rule_key' => $ruleKey]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('field_rules', function (Blueprint $table): void {
            $table->dropIndex(['rule_key']);
            $table->dropColumn('rule_key');
        });
    }
};
