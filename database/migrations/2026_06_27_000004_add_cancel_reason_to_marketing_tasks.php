<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Task iptalinde iptal sebebi (dropdown) saklansın. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (!Schema::hasColumn('marketing_tasks', 'cancel_reason')) {
                $table->string('cancel_reason', 60)->nullable()->after('cancelled_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('marketing_tasks', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }
        });
    }
};
