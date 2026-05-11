<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cms_contents.author_name + author_role — partner/dış yazar içeriği için.
 *
 * created_by zaten sistem user'ını tutuyor; author_name dış yazar görünür adı
 * (örn "Dr. Selen Yılmaz (TUM Mezunu)"), author_role rol tanımı (örn "Partner",
 * "Misafir Yazar", "Editör Ekibi"). Boş bırakılırsa kart önyüzünde gösterilmez.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->string('author_name', 120)->nullable()->after('approved_by');
            $table->string('author_role', 80)->nullable()->after('author_name');
            $table->index('author_name');
        });
    }

    public function down(): void
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dropIndex(['author_name']);
            $table->dropColumn(['author_name', 'author_role']);
        });
    }
};
