<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite FULLTEXT desteklemez — sadece MySQL/MariaDB
        if (config('database.default') === 'sqlite') {
            return;
        }

        if (!Schema::hasTable('guest_applications')) {
            return;
        }

        try {
            DB::statement('
                ALTER TABLE guest_applications
                ADD FULLTEXT INDEX ft_guest_search (first_name, last_name, email, phone)
            ');
        } catch (\Throwable $e) {
            // Index zaten varsa sessizce geç
            if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        try {
            DB::statement('ALTER TABLE guest_applications DROP INDEX ft_guest_search');
        } catch (\Throwable) {}
    }
};
