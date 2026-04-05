<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 190);
                $table->string('code', 40)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $defaultCompanyId = $this->ensureDefaultCompany();

        $tables = [
            'users',
            'guest_applications',
            'student_assignments',
            'lead_source_data',
            'marketing_campaigns',
            'marketing_reports',
            'marketing_tracking_links',
            'marketing_tracking_clicks',
            'marketing_external_metrics',
            'marketing_admin_settings',
            'marketing_teams',
            'marketing_budget',
        ];

        foreach ($tables as $table) {
            $this->addCompanyColumn($table);
        }

        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'company_id')) {
                continue;
            }
            DB::table($table)
                ->whereNull('company_id')
                ->update(['company_id' => $defaultCompanyId]);
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'guest_applications',
            'student_assignments',
            'lead_source_data',
            'marketing_campaigns',
            'marketing_reports',
            'marketing_tracking_links',
            'marketing_tracking_clicks',
            'marketing_external_metrics',
            'marketing_admin_settings',
            'marketing_teams',
            'marketing_budget',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropIndex(['company_id']);
                $blueprint->dropColumn('company_id');
            });
        }

        Schema::dropIfExists('companies');
    }

    private function addCompanyColumn(string $table): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, 'company_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('company_id')->nullable()->index();
        });
    }

    private function ensureDefaultCompany(): int
    {
        $existing = DB::table('companies')
            ->where('code', 'mentorde')
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $id = DB::table('companies')->insertGetId([
            'name' => 'MentorDE',
            'code' => 'mentorde',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $id;
    }
};

