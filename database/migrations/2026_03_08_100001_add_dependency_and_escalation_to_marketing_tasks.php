<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->unsignedBigInteger('depends_on_task_id')->nullable()->after('parent_task_id');
            $table->unsignedInteger('escalation_level')->default(0)->after('last_escalated_at');
            $table->softDeletes()->after('updated_at');

            $table->foreign('depends_on_task_id')
                ->references('id')->on('marketing_tasks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropForeign(['depends_on_task_id']);
            $table->dropColumn(['depends_on_task_id', 'escalation_level', 'deleted_at']);
        });
    }
};
