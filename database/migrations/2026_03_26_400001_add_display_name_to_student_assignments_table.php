<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * student_assignments tablosuna display_name kolonu ekler.
 * Pipeline, öğrenci listesi ve benzeri yerlerde GuestApplication
 * join yapmadan isme erişilebilmesi için.
 *
 * Backfill: mevcut kayıtlar için GuestApplication.converted_student_id
 * üzerinden first_name + last_name birleştirilerek doldurulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_assignments', 'display_name')) {
                $table->string('display_name', 200)->nullable()->after('student_id');
            }
        });

        // Backfill: GuestApplication'dan isim çek
        $isSqlite = config('database.default') === 'sqlite';

        if ($isSqlite) {
            $rows = DB::table('student_assignments as sa')
                ->join('guest_applications as ga', 'ga.converted_student_id', '=', 'sa.student_id')
                ->whereNull('sa.display_name')
                ->whereNotNull('ga.first_name')
                ->whereNull('ga.deleted_at')
                ->select('sa.student_id', 'ga.first_name', 'ga.last_name')
                ->get();

            foreach ($rows as $row) {
                $name = trim($row->first_name . ' ' . ($row->last_name ?? ''));
                if ($name !== '') {
                    DB::table('student_assignments')
                        ->where('student_id', $row->student_id)
                        ->whereNull('display_name')
                        ->update(['display_name' => $name]);
                }
            }
        } else {
            DB::statement("
                UPDATE student_assignments sa
                JOIN guest_applications ga ON ga.converted_student_id = sa.student_id
                SET sa.display_name = TRIM(CONCAT(COALESCE(ga.first_name,''), ' ', COALESCE(ga.last_name,'')))
                WHERE sa.display_name IS NULL
                  AND ga.deleted_at IS NULL
                  AND (ga.first_name IS NOT NULL OR ga.last_name IS NOT NULL)
            ");
        }

        // Backfill 2: User tablosundan (student_id eşleşen kullanıcılar)
        $userRows = DB::table('student_assignments as sa')
            ->join('users as u', 'u.student_id', '=', 'sa.student_id')
            ->whereNull('sa.display_name')
            ->whereNotNull('u.name')
            ->select('sa.student_id', 'u.name')
            ->get();

        foreach ($userRows as $row) {
            DB::table('student_assignments')
                ->where('student_id', $row->student_id)
                ->whereNull('display_name')
                ->update(['display_name' => $row->name]);
        }
    }

    public function down(): void
    {
        Schema::table('student_assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('student_assignments', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
};
