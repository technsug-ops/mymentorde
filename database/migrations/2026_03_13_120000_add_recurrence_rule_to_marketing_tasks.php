<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (!Schema::hasColumn('marketing_tasks', 'recurrence_rule')) {
                $table->json('recurrence_rule')->nullable()->after('source_id');
                // Örnek: {"frequency": "weekly", "day": "monday", "end_date": "2026-06-30"}
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropColumn('recurrence_rule');
        });
    }
};
