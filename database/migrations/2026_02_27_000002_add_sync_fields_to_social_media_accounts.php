<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media_accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('social_media_accounts', 'external_account_id')) {
                $table->string('external_account_id', 100)->nullable()->after('api_access_token');
            }
            if (! Schema::hasColumn('social_media_accounts', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('external_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('social_media_accounts', function (Blueprint $table): void {
            $cols = ['external_account_id', 'last_synced_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('social_media_accounts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
