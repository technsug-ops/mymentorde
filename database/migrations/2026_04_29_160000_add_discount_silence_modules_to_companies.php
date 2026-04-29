<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mevcut tüm company'lere iki yeni modülü ekle:
 *   - discount_codes  → manager indirim kodu üretip aday services'da uygular
 *   - silence_checkin → aday/öğrenci timeline sessizliğinde otomatik touchpoint
 *
 * Yeni feature'lar SİSTEMDEN BAĞIMSIZ — devre dışı bırakılırsa
 * mevcut akış (ödeme talebi, dashboard) etkilenmez.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'enabled_modules')) {
            return;
        }

        $newModules = ['discount_codes', 'silence_checkin'];

        $rows = DB::table('companies')->select('id', 'enabled_modules')->get();
        foreach ($rows as $row) {
            $modules = [];
            if ($row->enabled_modules) {
                $decoded = json_decode((string) $row->enabled_modules, true);
                $modules = is_array($decoded) ? $decoded : [];
            }
            $changed = false;
            foreach ($newModules as $mod) {
                if (!in_array($mod, $modules, true)) {
                    $modules[] = $mod;
                    $changed = true;
                }
            }
            if ($changed) {
                DB::table('companies')
                    ->where('id', $row->id)
                    ->update(['enabled_modules' => json_encode($modules, JSON_UNESCAPED_UNICODE)]);
                // Cache'i invalidate et — admin değişiminden sonra anında yansısın
                Cache::forget("company:{$row->id}:enabled_modules");
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'enabled_modules')) {
            return;
        }

        $removeModules = ['discount_codes', 'silence_checkin'];

        $rows = DB::table('companies')->select('id', 'enabled_modules')->get();
        foreach ($rows as $row) {
            if (!$row->enabled_modules) continue;
            $modules = json_decode((string) $row->enabled_modules, true);
            if (!is_array($modules)) continue;
            $filtered = array_values(array_filter($modules, fn ($m) => !in_array($m, $removeModules, true)));
            if (count($filtered) !== count($modules)) {
                DB::table('companies')
                    ->where('id', $row->id)
                    ->update(['enabled_modules' => json_encode($filtered, JSON_UNESCAPED_UNICODE)]);
                Cache::forget("company:{$row->id}:enabled_modules");
            }
        }
    }
};
