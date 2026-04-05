<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // messages: edit_count + forwarded_from
        Schema::table('messages', function (Blueprint $table): void {
            if (!Schema::hasColumn('messages', 'edit_count')) {
                $table->unsignedSmallInteger('edit_count')->default(0)->after('is_edited');
            }
            if (!Schema::hasColumn('messages', 'forwarded_from')) {
                $table->unsignedBigInteger('forwarded_from')->nullable()->after('reply_to_message_id');
                $table->foreign('forwarded_from')->references('id')->on('messages')->nullOnDelete();
            }
        });

        // conversation_participants: pinned_at
        Schema::table('conversation_participants', function (Blueprint $table): void {
            if (!Schema::hasColumn('conversation_participants', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropForeign(['forwarded_from']);
            $table->dropColumn(['edit_count', 'forwarded_from']);
        });

        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->dropColumn('pinned_at');
        });
    }
};
