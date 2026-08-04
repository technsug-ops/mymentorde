<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // GuestApplication.phone → User.phone backfill.
        //
        // NOT: Burada MySQL'e özgü "UPDATE ... INNER JOIN" kullanılmıyor — SQLite
        // (test DB'si) o sözdizimini desteklemiyor ve RefreshDatabase kullanan TÜM
        // testleri düşürüyordu. Chunk'lı döngü her sürücüde çalışır; bu bir tek
        // seferlik backfill olduğu için performans kritik değil.
        if (Schema::hasColumn('users', 'phone') && Schema::hasColumn('guest_applications', 'phone')) {
            DB::table('guest_applications')
                ->whereNotNull('converted_student_id')
                ->whereNotNull('phone')
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        // ⚠ converted_student_id HER ZAMAN sayısal user id değil:
                        // bazı kayıtlarda öğrenci numarası formatında metin tutuluyor
                        // ("BCS-26-03-SMQQ"). Bunu doğrudan users.id ile eşleştirmek
                        // MySQL'de "Truncated incorrect INTEGER value" ile PATLAR.
                        // (Eski INNER JOIN sürümü sessizce eşleşme bulamıyordu.)
                        $studentRef = $row->converted_student_id;

                        $query = DB::table('users')->whereNull('phone');

                        if (is_numeric($studentRef)) {
                            $query->where('id', (int) $studentRef);
                        } elseif (Schema::hasColumn('users', 'student_id')) {
                            $query->where('student_id', (string) $studentRef);
                        } else {
                            continue;
                        }

                        $query->update(['phone' => $row->phone]);
                    }
                });
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
