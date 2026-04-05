<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Mevcut tüm aktif kullanıcıların e-postasını doğrulanmış say.
        // Sistem başlatılmadan önce oluşturulan hesaplar için gerekli.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNull('deleted_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Geri alma: sadece bu migration'dan önce null olan kayıtlar için
        // güvenli geri alma mümkün değil — null'a döndürme yapılmıyor.
    }
};
