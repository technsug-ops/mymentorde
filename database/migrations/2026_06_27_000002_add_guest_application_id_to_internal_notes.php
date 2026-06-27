<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * InternalNote aday öğrenciye (GuestApplication) de bağlanabilsin — aktivite
 * günlüğü hem dönüşmüş öğrenci (student_id) hem aday öğrenci için çalışsın.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_notes', function (Blueprint $table): void {
            if (!Schema::hasColumn('internal_notes', 'guest_application_id')) {
                $table->unsignedBigInteger('guest_application_id')->nullable()->after('student_id');
                $table->index('guest_application_id');
            }
        });

        // student_id nullable olsun — not artık öğrenci VEYA aday öğrenciye bağlı olabilir.
        Schema::table('internal_notes', function (Blueprint $table): void {
            $table->string('student_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('internal_notes', function (Blueprint $table): void {
            if (Schema::hasColumn('internal_notes', 'guest_application_id')) {
                $table->dropIndex(['guest_application_id']);
                $table->dropColumn('guest_application_id');
            }
        });
    }
};
