<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('manager_reports', 'company_id')) {
            Schema::table('manager_reports', function (Blueprint $table): void {
                $table->unsignedBigInteger('company_id')->nullable()->index()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('manager_reports', 'company_id')) {
            Schema::table('manager_reports', function (Blueprint $table): void {
                $table->dropColumn('company_id');
            });
        }
    }
};
