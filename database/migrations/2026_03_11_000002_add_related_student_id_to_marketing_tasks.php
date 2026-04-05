<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketing_tasks', 'related_student_id')) {
                $table->string('related_student_id', 64)->nullable()->after('workflow_stage')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropColumn('related_student_id');
        });
    }
};
