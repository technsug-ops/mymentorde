<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite'ta enum → string migration (yeni kategorileri desteklemek için).
 * MySQL'de migration 000015 zaten MODIFY COLUMN ile güncelledi; bu sadece SQLite için gerekli.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            return; // MySQL zaten 000015'te güncellendi
        }

        // 1) Mevcut veriyi al
        $rows = DB::table('company_bulletins')->get()->toArray();

        // 2) Yeni tablo oluştur (string category — CHECK kısıtı yok)
        Schema::create('company_bulletins_new', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('author_id');
            $table->foreign('author_id')->references('id')->on('users');
            $table->string('title', 200);
            $table->text('body');
            $table->string('category', 20)->default('genel'); // enum yerine string
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'published_at']);
            $table->index(['company_id', 'category']);
        });

        // 3) Veriyi kopyala
        foreach ($rows as $row) {
            DB::table('company_bulletins_new')->insert((array) $row);
        }

        // 4) bulletin_reads foreign key geçici olarak bırak (SQLite'ta drop/rename chain)
        // bulletin_reads → bulletin_id: SQLite FK enforcement varsayılan kapalı
        Schema::drop('company_bulletins');
        Schema::rename('company_bulletins_new', 'company_bulletins');
    }

    public function down(): void
    {
        // Geri alma: gerekli değil (schema aynı, sadece constraint farkı)
    }
};
