<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'converted_student_id')) {
                $table->string('converted_student_id', 64)->nullable()->after('converted_to_student');
                $table->index('converted_student_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_applications', 'converted_student_id')) {
                $table->dropIndex(['converted_student_id']);
                $table->dropColumn('converted_student_id');
            }
        });
    }
};

