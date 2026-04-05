<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketing_tasks', 'process_type')) {
                $table->string('process_type', 32)->nullable()->after('department')->index();
            }
            if (! Schema::hasColumn('marketing_tasks', 'workflow_stage')) {
                $table->string('workflow_stage', 64)->nullable()->after('process_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropColumn(['process_type', 'workflow_stage']);
        });
    }
};
