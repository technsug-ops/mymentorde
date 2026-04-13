<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE conversations MODIFY COLUMN type ENUM('direct','group','room','announcement') NOT NULL DEFAULT 'group'");
            return;
        }

        if ($driver === 'sqlite') {
            // SQLite enum'u CHECK constraint olarak enforce ediyor — modify in-place mümkün değil.
            // Çözüm: tabloyu yeniden oluştur, veriyi taşı.
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('
                CREATE TABLE conversations_new (
                    "id" integer primary key autoincrement not null,
                    "company_id" integer,
                    "type" varchar check ("type" in (\'direct\', \'group\', \'room\', \'announcement\')) not null default \'group\',
                    "title" varchar,
                    "created_by_user_id" integer,
                    "context_type" varchar,
                    "context_id" varchar,
                    "is_archived" tinyint(1) not null default \'0\',
                    "last_message_at" datetime,
                    "last_message_preview" varchar,
                    "created_at" datetime,
                    "updated_at" datetime,
                    foreign key("created_by_user_id") references "users"("id") on delete set null
                )
            ');
            DB::statement('
                INSERT INTO conversations_new
                  (id, company_id, type, title, created_by_user_id, context_type, context_id, is_archived, last_message_at, last_message_preview, created_at, updated_at)
                SELECT id, company_id, type, title, created_by_user_id, context_type, context_id, is_archived, last_message_at, last_message_preview, created_at, updated_at
                FROM conversations
            ');
            DB::statement('DROP TABLE conversations');
            DB::statement('ALTER TABLE conversations_new RENAME TO conversations');
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE conversations MODIFY COLUMN type ENUM('direct','group','announcement') NOT NULL DEFAULT 'group'");
        }
        // SQLite down: revert için aynı table recreation pattern gerekirdi, atlandı.
    }
};
