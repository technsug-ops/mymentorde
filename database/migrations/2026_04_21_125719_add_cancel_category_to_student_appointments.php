<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_appointments', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_appointments', 'cancel_category')) {
                $table->string('cancel_category', 48)->nullable()->after('cancel_reason')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_appointments', function (Blueprint $table): void {
            if (Schema::hasColumn('student_appointments', 'cancel_category')) {
                $table->dropIndex(['cancel_category']);
                $table->dropColumn('cancel_category');
            }
        });
    }
};
