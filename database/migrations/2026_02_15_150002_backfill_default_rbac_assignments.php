<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('role_templates') || !Schema::hasTable('user_role_assignments')) {
            return;
        }

        $templateByRole = [
            'manager' => 'tpl_manager_core',
            'system_admin' => 'tpl_system_admin_core',
            'operations_admin' => 'tpl_operations_admin_core',
            'finance_admin' => 'tpl_finance_admin_core',
            'marketing_admin' => 'tpl_marketing_admin_core',
            'sales_admin' => 'tpl_sales_admin_core',
        ];

        $templates = DB::table('role_templates')
            ->whereIn('code', array_values($templateByRole))
            ->get(['id', 'code', 'version'])
            ->keyBy('code');

        $users = DB::table('users')
            ->whereIn('role', array_keys($templateByRole))
            ->get(['id', 'role']);

        foreach ($users as $user) {
            $templateCode = $templateByRole[(string) $user->role] ?? null;
            if (!$templateCode || !isset($templates[$templateCode])) {
                continue;
            }
            $tpl = $templates[$templateCode];

            $exists = DB::table('user_role_assignments')
                ->where('user_id', (int) $user->id)
                ->where('role_template_id', (int) $tpl->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('user_role_assignments')->insert([
                'user_id' => (int) $user->id,
                'role_template_id' => (int) $tpl->id,
                'assigned_by_user_id' => null,
                'version_applied' => (int) ($tpl->version ?? 1),
                'is_active' => true,
                'assigned_at' => now(),
                'revoked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // no-op: intentionally keeps created assignments
    }
};
