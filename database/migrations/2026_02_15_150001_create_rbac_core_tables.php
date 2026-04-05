<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('category', 80)->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('role_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 120);
            $table->string('parent_role', 64)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('role_template_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_template_id')->constrained('role_templates')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_template_id', 'permission_id']);
        });

        Schema::create('user_role_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_template_id')->constrained('role_templates')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version_applied')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('role_change_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('target_type', 80);
            $table->string('target_id', 80)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('role_change_audits');
        Schema::dropIfExists('user_role_assignments');
        Schema::dropIfExists('role_template_permissions');
        Schema::dropIfExists('role_templates');
        Schema::dropIfExists('permissions');
    }

    private function seedDefaults(): void
    {
        $now = now();

        $permissions = [
            ['code' => 'config.view', 'category' => 'config', 'description' => 'Config paneli goruntuleme'],
            ['code' => 'config.manage', 'category' => 'config', 'description' => 'Config CRUD islemleri'],
            ['code' => 'student.assignment.manage', 'category' => 'student', 'description' => 'Ogrenci atama yonetimi'],
            ['code' => 'student.card.view', 'category' => 'student', 'description' => 'Student card goruntuleme'],
            ['code' => 'revenue.manage', 'category' => 'finance', 'description' => 'Revenue milestone yonetimi'],
            ['code' => 'approval.manage', 'category' => 'workflow', 'description' => 'Approval islemleri'],
            ['code' => 'notification.manage', 'category' => 'notification', 'description' => 'Bildirim islemleri'],
            ['code' => 'marketing.dashboard.view', 'category' => 'marketing', 'description' => 'Marketing panel dashboard'],
            ['code' => 'marketing.campaign.manage', 'category' => 'marketing', 'description' => 'Marketing campaign CRUD'],
            ['code' => 'role.template.manage', 'category' => 'security', 'description' => 'Rol template yonetimi'],
        ];

        foreach ($permissions as $row) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['is_system' => true, 'updated_at' => $now, 'created_at' => $now])
            );
        }

        $templates = [
            ['code' => 'tpl_manager_core', 'name' => 'Manager Core', 'parent_role' => 'manager'],
            ['code' => 'tpl_system_admin_core', 'name' => 'System Admin Core', 'parent_role' => 'system_admin'],
            ['code' => 'tpl_operations_admin_core', 'name' => 'Operations Admin Core', 'parent_role' => 'operations_admin'],
            ['code' => 'tpl_finance_admin_core', 'name' => 'Finance Admin Core', 'parent_role' => 'finance_admin'],
            ['code' => 'tpl_marketing_admin_core', 'name' => 'Marketing Admin Core', 'parent_role' => 'marketing_admin'],
            ['code' => 'tpl_sales_admin_core', 'name' => 'Sales Admin Core', 'parent_role' => 'sales_admin'],
        ];

        foreach ($templates as $tpl) {
            DB::table('role_templates')->updateOrInsert(
                ['code' => $tpl['code']],
                array_merge($tpl, ['version' => 1, 'is_system' => true, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now])
            );
        }

        $links = [
            'tpl_manager_core' => [
                'config.view', 'config.manage', 'student.assignment.manage', 'student.card.view',
                'revenue.manage', 'approval.manage', 'notification.manage', 'role.template.manage',
            ],
            'tpl_system_admin_core' => [
                'config.view', 'config.manage', 'role.template.manage', 'notification.manage',
            ],
            'tpl_operations_admin_core' => [
                'config.view', 'student.assignment.manage', 'approval.manage', 'notification.manage',
            ],
            'tpl_finance_admin_core' => [
                'config.view', 'revenue.manage', 'notification.manage',
            ],
            'tpl_marketing_admin_core' => [
                'marketing.dashboard.view', 'marketing.campaign.manage',
            ],
            'tpl_sales_admin_core' => [
                'marketing.dashboard.view',
            ],
        ];

        foreach ($links as $tplCode => $permissionCodes) {
            $tplId = DB::table('role_templates')->where('code', $tplCode)->value('id');
            if (!$tplId) {
                continue;
            }
            foreach ($permissionCodes as $permCode) {
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
};
