<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversations tablosuna Slack-tarzı yönetim kolonları ekler:
 * - archived_at / archived_by_user_id: archive metadata (kim, ne zaman)
 * - deleted_at: soft delete (permanent destroy için manager tarafından)
 *
 * Mevcut is_archived boolean kolonu geriye dönük uyumluluk için kalıyor;
 * ama yeni kod archived_at NULL kontrolünü tercih edecek.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        Schema::table('conversations', function (Blueprint $table): void {
            if (!Schema::hasColumn('conversations', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }
            if (!Schema::hasColumn('conversations', 'archived_by_user_id')) {
                $table->unsignedBigInteger('archived_by_user_id')->nullable()->after('archived_at');
            }
            if (!Schema::hasColumn('conversations', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        Schema::table('conversations', function (Blueprint $table): void {
            if (Schema::hasColumn('conversations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('conversations', 'archived_by_user_id')) {
                $table->dropColumn('archived_by_user_id');
            }
            if (Schema::hasColumn('conversations', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
