<?php

use App\Support\ModuleAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mevcut şirketlere 'doc_request' premium modülünü otomatik ekle.
 *
 * `companies.enabled_modules` JSON kolonuna doc_request ekler. Yeni
 * deploy'larda eski kayıtların DEFAULT_MODULES ile senkron olmasını sağlar.
 * Idempotent: zaten varsa eklemez.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'enabled_modules')) return;

        $rows = DB::table('companies')->select('id', 'enabled_modules')->get();
        foreach ($rows as $row) {
            $raw = $row->enabled_modules;
            $list = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
            if (!is_array($list) || empty($list)) continue;
            if (in_array('doc_request', $list, true)) continue;

            $list[] = 'doc_request';
            DB::table('companies')->where('id', $row->id)->update([
                'enabled_modules' => json_encode(array_values($list)),
                'updated_at'      => now(),
            ]);
            ModuleAccess::flushCache((int) $row->id);
        }
    }

    public function down(): void
    {
        // 'doc_request'i geri kaldır — migration revert isteğe bağlı temizlik
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'enabled_modules')) return;

        $rows = DB::table('companies')->select('id', 'enabled_modules')->get();
        foreach ($rows as $row) {
            $raw = $row->enabled_modules;
            $list = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
            if (!is_array($list)) continue;
            $new = array_values(array_filter($list, fn ($v) => $v !== 'doc_request'));
            if (count($new) === count($list)) continue;
            DB::table('companies')->where('id', $row->id)->update([
                'enabled_modules' => json_encode($new),
                'updated_at'      => now(),
            ]);
            ModuleAccess::flushCache((int) $row->id);
        }
    }
};
