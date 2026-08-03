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
                        DB::table('users')
                            ->where('id', $row->converted_student_id)
                            ->whereNull('phone')
                            ->update(['phone' => $row->phone]);
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
