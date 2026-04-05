<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token', 512)->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'push_enabled')) {
                $table->boolean('push_enabled')->default(false)->after('fcm_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('fcm_token');
            $table->dropColumnIfExists('push_enabled');
        });
    }
};
