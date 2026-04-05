<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) birth_date → users
        if (!Schema::hasColumn('users', 'birth_date')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('birth_date')->nullable()->after('email');
            });
        }

        // 2) Yeni kategoriler için company_bulletins.category enum güncelle (sadece MySQL)
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE company_bulletins MODIFY COLUMN category ENUM('genel','duyuru','acil','ik','kutlama','motivasyon') NOT NULL DEFAULT 'genel'");
        }

        // 3) bulletin_reactions tablosu
        Schema::create('bulletin_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->constrained('company_bulletins')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 10); // 🎉 👍 ❤️ 🙌 🏆
            $table->timestamps();
            $table->unique(['bulletin_id', 'user_id']); // 1 kullanıcı 1 reaksiyon
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_reactions');
        Schema::table('users', fn(Blueprint $t) => $t->dropColumn('birth_date'));
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE company_bulletins MODIFY COLUMN category ENUM('genel','duyuru','acil','ik') NOT NULL DEFAULT 'genel'");
        }
    }
};
