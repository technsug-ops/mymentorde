<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['documents', 'guest_tickets'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t): void {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['documents', 'guest_tickets'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t): void {
                    $t->dropSoftDeletes();
                });
            }
        }
    }
};
