<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('marketing_tasks')) {
            return;
        }

        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (!Schema::hasColumn('marketing_tasks', 'department')) {
                $table->string('department', 32)->default('operations')->after('priority')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('marketing_tasks')) {
            return;
        }

        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('marketing_tasks', 'department')) {
                $table->dropColumn('department');
            }
        });
    }
};

