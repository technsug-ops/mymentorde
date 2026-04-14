<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Conversations.type ENUM'ına 'room' + 'announcement' değerlerinin garanti eder.
 *
 * Neden: Mevcut 2026_03_09_000001_add_room_type_to_conversations migration'ı
 * create_conversations_table'dan (2026_03_09_300001) DAHA ÖNCE çalışıyordu —
 * guard clause (`if (!Schema::hasTable(...)) return`) ile skip oluyordu.
 * Sonuç: Prod'da conversations.type ENUM('direct','group','announcement') kalmış,
 * 'room' yok. Oda/Room oluşturmak 500 veriyor.
 *
 * Bu migration idempotent — zaten doğru olan ENUM'u değiştirmez (MODIFY COLUMN
 * her durumda çalışır, ENUM zaten doğruysa no-op).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL + MariaDB için aynı syntax
            DB::statement("ALTER TABLE conversations MODIFY COLUMN type ENUM('direct','group','room','announcement') NOT NULL DEFAULT 'group'");
            return;
        }

        if ($driver === 'sqlite') {
            // SQLite için CHECK constraint kontrolü
            $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='conversations'");
            if ($row && str_contains((string) $row->sql, "'room'")) {
                return; // zaten var
            }

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
        // Geri dönüş yok — data kaybı riski.
    }
};
