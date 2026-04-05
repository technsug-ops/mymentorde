<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_assignments') && !Schema::hasColumn('student_assignments', 'company_id')) {
            Schema::table('student_assignments', function (Blueprint $table) {
                $table->string('company_id', 64)->nullable()->after('id');
                $table->index('company_id', 'student_assignments_company_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_assignments') && Schema::hasColumn('student_assignments', 'company_id')) {
            Schema::table('student_assignments', function (Blueprint $table) {
                $table->dropIndex('student_assignments_company_id_index');
                $table->dropColumn('company_id');
            });
        }
    }
};
