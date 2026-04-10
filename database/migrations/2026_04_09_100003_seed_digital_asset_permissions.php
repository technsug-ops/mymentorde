<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. DAM izinleri ─────────────────────────────────────────────────
        $permissions = [
            ['code' => 'dam.view',          'category' => 'dam', 'description' => 'Dijital varlıkları görüntüle'],
            ['code' => 'dam.download',      'category' => 'dam', 'description' => 'Dijital varlık indir'],
            ['code' => 'dam.upload',        'category' => 'dam', 'description' => 'Dijital varlık yükle'],
            ['code' => 'dam.update',        'category' => 'dam', 'description' => 'Dijital varlık düzenle'],
            ['code' => 'dam.delete',        'category' => 'dam', 'description' => 'Dijital varlık sil'],
            ['code' => 'dam.folder.manage', 'category' => 'dam', 'description' => 'Klasör oluştur/düzenle/sil'],
            ['code' => 'dam.admin',         'category' => 'dam', 'description' => 'Tüm DAM yönetimi'],
        ];

        foreach ($permissions as $row) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['is_system' => true, 'updated_at' => $now, 'created_at' => $now])
            );
        }

        // ── 2. Role template atamaları ─────────────────────────────────────
        $allDam     = ['dam.view', 'dam.download', 'dam.upload', 'dam.update', 'dam.delete', 'dam.folder.manage', 'dam.admin'];
        $staffWrite = ['dam.view', 'dam.download', 'dam.upload', 'dam.update', 'dam.folder.manage'];
        $seniorSet  = ['dam.view', 'dam.download', 'dam.upload', 'dam.update'];
        $readOnly   = ['dam.view', 'dam.download'];

        $links = [
            'tpl_manager_core'           => $allDam,
            'tpl_marketing_admin_core'   => $allDam,
            'tpl_marketing_staff_core'   => $staffWrite,
            'tpl_senior_core'            => $seniorSet,
            'tpl_operations_admin_core'  => $readOnly,
            'tpl_operations_staff_core'  => $readOnly,
            'tpl_system_admin_core'      => $readOnly,
        ];

        foreach ($links as $tplCode => $permCodes) {
            $tplId = DB::table('role_templates')->where('code', $tplCode)->value('id');
            if (!$tplId) {
                continue;
            }
            foreach ($permCodes as $permCode) {
                $permId = DB::table('permissions')->where('code', $permCode)->value('id');
                if (!$permId) {
                    continue;
                }
                DB::table('role_template_permissions')->updateOrInsert(
                    ['role_template_id' => $tplId, 'permission_id' => $permId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $codes = ['dam.view', 'dam.download', 'dam.upload', 'dam.update', 'dam.delete', 'dam.folder.manage', 'dam.admin'];

        $permIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        if ($permIds->isNotEmpty()) {
            DB::table('role_template_permissions')->whereIn('permission_id', $permIds)->delete();
            DB::table('permissions')->whereIn('id', $permIds)->delete();
        }
    }
};
