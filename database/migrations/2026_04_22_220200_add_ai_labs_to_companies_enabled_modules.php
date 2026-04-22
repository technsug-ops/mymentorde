<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'enabled_modules')) {
            return;
        }

        // Mevcut tüm company'lere ai_labs modülünü JSON listeye ekle.
        // DEFAULT_MODULES sadece enabled_modules boş olduğunda fallback.
        $rows = DB::table('companies')->select('id', 'enabled_modules')->get();
        foreach ($rows as $row) {
            $modules = [];
            if ($row->enabled_modules) {
                $decoded = json_decode((string) $row->enabled_modules, true);
                $modules = is_array($decoded) ? $decoded : [];
            }
            if (!in_array('ai_labs', $modules, true)) {
                $modules[] = 'ai_labs';
                DB::table('companies')
                    ->where('id', $row->id)
                    ->update(['enabled_modules' => json_encode($modules, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'enabled_modules')) {
            return;
        }

        $rows = DB::table('companies')->select('id', 'enabled_modules')->get();
        foreach ($rows as $row) {
            if (!$row->enabled_modules) {
                continue;
            }
            $modules = json_decode((string) $row->enabled_modules, true);
            if (!is_array($modules)) {
                continue;
            }
            $filtered = array_values(array_filter($modules, fn ($m) => $m !== 'ai_labs'));
            if (count($filtered) !== count($modules)) {
                DB::table('companies')
                    ->where('id', $row->id)
                    ->update(['enabled_modules' => json_encode($filtered, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }
};
