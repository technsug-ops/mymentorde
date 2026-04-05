<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' && \Illuminate\Support\Facades\Schema::hasTable('conversations')) {
            DB::statement("ALTER TABLE conversations MODIFY COLUMN type ENUM('direct','group','room','announcement') NOT NULL DEFAULT 'group'");
        }
        // SQLite'da enum text olarak saklanır — değişiklik gerekmez
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE conversations MODIFY COLUMN type ENUM('direct','group','announcement') NOT NULL DEFAULT 'group'");
        }
    }
};
