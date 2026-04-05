<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketing_tasks', 'actual_hours')) {
                $table->decimal('actual_hours', 6, 2)->nullable()->after('estimated_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('marketing_tasks', 'actual_hours')) {
                $table->dropColumn('actual_hours');
            }
        });
    }
};
