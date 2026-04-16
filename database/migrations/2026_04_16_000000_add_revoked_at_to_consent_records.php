<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consent_records', function (Blueprint $table) {
            if (!Schema::hasColumn('consent_records', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('accepted_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('consent_records', function (Blueprint $table) {
            if (Schema::hasColumn('consent_records', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }
        });
    }
};
