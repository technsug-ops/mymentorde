<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            if (!Schema::hasColumn('conversation_participants', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('last_read_at');
            }
            if (!Schema::hasColumn('conversation_participants', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }

            // İndeks yoksa ekle (SQLite ifExists desteği yok, try/catch ile)
            try {
                $table->index(['user_id', 'is_archived'], 'idx_cp_user_archived');
            } catch (\Exception) {
                // İndeks zaten var
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->dropIndex('idx_cp_user_archived');
            $table->dropColumn(['is_archived', 'archived_at']);
        });
    }
};
