<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Belge Talep Linki için yeni izin: doc_request.use
 *
 * - Manager template'e otomatik atanır (her zaman kullanabilir)
 * - Senior template'e atanmaz; manager bireysel senior user'lara verir
 *   (manager senior detay sayfasından "Belge Talep Et yetkisi" toggle'ı ile)
 */
return new class extends Migration {
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['code' => 'doc_request.use'],
            [
                'code'        => 'doc_request.use',
                'category'    => 'doc_request',
                'description' => 'Aday öğrenciye tek-kullanımlık belge yükleme linki gönder',
                'is_system'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        // Manager template'e otomatik bağla — manager her zaman kullanabilir
        $managerTplId = DB::table('role_templates')->where('code', 'tpl_manager_core')->value('id');
        $permId       = DB::table('permissions')->where('code', 'doc_request.use')->value('id');
        if ($managerTplId && $permId) {
            DB::table('role_template_permissions')->updateOrInsert(
                ['role_template_id' => $managerTplId, 'permission_id' => $permId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('code', 'doc_request.use')->value('id');
        if (!$permId) return;
        DB::table('role_template_permissions')->where('permission_id', $permId)->delete();
        DB::table('permissions')->where('id', $permId)->delete();
    }
};
