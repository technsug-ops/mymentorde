<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. Eksik izinleri ekle ──────────────────────────────────────────────
        $newPermissions = [
            ['code' => 'ticket.center.view',  'category' => 'tickets', 'description' => 'Ticket merkezi goruntuleme'],
            ['code' => 'ticket.center.route', 'category' => 'tickets', 'description' => 'Ticket yonlendirme islemi'],
        ];

        foreach ($newPermissions as $row) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['is_system' => true, 'updated_at' => $now, 'created_at' => $now])
            );
        }

        // ── 2. tpl_manager_core'a ticket izinlerini ekle ────────────────────────
        $managerTplId = DB::table('role_templates')->where('code', 'tpl_manager_core')->value('id');
        if ($managerTplId) {
            foreach (['ticket.center.view', 'ticket.center.route'] as $code) {
                $permId = DB::table('permissions')->where('code', $code)->value('id');
                if ($permId) {
                    DB::table('role_template_permissions')->updateOrInsert(
                        ['role_template_id' => $managerTplId, 'permission_id' => $permId],
                        ['updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }

        // tpl_system_admin_core'a da ticket.center.view ekle
        $sysAdminTplId = DB::table('role_templates')->where('code', 'tpl_system_admin_core')->value('id');
        if ($sysAdminTplId) {
            $permId = DB::table('permissions')->where('code', 'ticket.center.view')->value('id');
            if ($permId) {
                DB::table('role_template_permissions')->updateOrInsert(
                    ['role_template_id' => $sysAdminTplId, 'permission_id' => $permId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        // tpl_operations_admin_core'a ticket.center.view ekle
        $opsTplId = DB::table('role_templates')->where('code', 'tpl_operations_admin_core')->value('id');
        if ($opsTplId) {
            $permId = DB::table('permissions')->where('code', 'ticket.center.view')->value('id');
            if ($permId) {
                DB::table('role_template_permissions')->updateOrInsert(
                    ['role_template_id' => $opsTplId, 'permission_id' => $permId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        // ── 3. Staff-level düzenlenebilir şablonlar (is_system=false) ───────────
        $staffTemplates = [
            ['code' => 'tpl_system_staff_core',     'name' => 'System Staff — Temel',     'parent_role' => 'system_staff'],
            ['code' => 'tpl_operations_staff_core', 'name' => 'Operations Staff — Temel', 'parent_role' => 'operations_staff'],
            ['code' => 'tpl_finance_staff_core',    'name' => 'Finance Staff — Temel',    'parent_role' => 'finance_staff'],
            ['code' => 'tpl_marketing_staff_core',  'name' => 'Marketing Staff — Temel',  'parent_role' => 'marketing_staff'],
            ['code' => 'tpl_sales_staff_core',      'name' => 'Sales Staff — Temel',      'parent_role' => 'sales_staff'],
            ['code' => 'tpl_senior_core',           'name' => 'Senior — Temel',           'parent_role' => 'senior'],
            ['code' => 'tpl_mentor_core',           'name' => 'Mentor — Temel',           'parent_role' => 'mentor'],
        ];

        foreach ($staffTemplates as $tpl) {
            DB::table('role_templates')->updateOrInsert(
                ['code' => $tpl['code']],
                array_merge($tpl, [
                    'version'    => 1,
                    'is_system'  => false,   // Düzenlenebilir
                    'is_active'  => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        // Staff şablonlarına başlangıç izin atamaları
        $staffTemplateLinks = [
            'tpl_system_staff_core'     => ['config.view'],
            'tpl_operations_staff_core' => ['config.view', 'student.card.view'],
            'tpl_finance_staff_core'    => ['config.view'],
            'tpl_marketing_staff_core'  => ['marketing.dashboard.view'],
            'tpl_sales_staff_core'      => ['marketing.dashboard.view'],
            'tpl_senior_core'           => ['student.assignment.manage', 'student.card.view'],
            'tpl_mentor_core'           => ['student.card.view'],
        ];

        foreach ($staffTemplateLinks as $tplCode => $permCodes) {
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
        $staffCodes = [
            'tpl_system_staff_core', 'tpl_operations_staff_core', 'tpl_finance_staff_core',
            'tpl_marketing_staff_core', 'tpl_sales_staff_core', 'tpl_senior_core', 'tpl_mentor_core',
        ];

        $tplIds = DB::table('role_templates')->whereIn('code', $staffCodes)->pluck('id');
        if ($tplIds->isNotEmpty()) {
            DB::table('role_template_permissions')->whereIn('role_template_id', $tplIds)->delete();
            DB::table('role_templates')->whereIn('code', $staffCodes)->delete();
        }

        DB::table('permissions')->whereIn('code', ['ticket.center.view', 'ticket.center.route'])->delete();
    }
};
