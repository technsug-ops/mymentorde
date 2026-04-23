<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email')->index();
            }
        });

        // Guest → Student dönüşümünde daha önce transfer edilmemiş kayıtlar için
        // GuestApplication.phone → User.phone backfill
        if (Schema::hasColumn('users', 'phone') && Schema::hasColumn('guest_applications', 'phone')) {
            \Illuminate\Support\Facades\DB::statement("
                UPDATE users u
                INNER JOIN guest_applications g ON g.converted_student_id = u.id
                SET u.phone = g.phone
                WHERE u.phone IS NULL AND g.phone IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropIndex(['phone']);
                $table->dropColumn('phone');
            }
        });
    }
};
