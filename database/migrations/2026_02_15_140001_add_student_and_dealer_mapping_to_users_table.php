<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'student_id')) {
                $table->string('student_id', 64)->nullable()->after('role');
                $table->index('student_id');
            }
            if (!Schema::hasColumn('users', 'dealer_code')) {
                $table->string('dealer_code', 64)->nullable()->after('student_id');
                $table->index('dealer_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'dealer_code')) {
                $table->dropIndex(['dealer_code']);
                $table->dropColumn('dealer_code');
            }
            if (Schema::hasColumn('users', 'student_id')) {
                $table->dropIndex(['student_id']);
                $table->dropColumn('student_id');
            }
        });
    }
};
