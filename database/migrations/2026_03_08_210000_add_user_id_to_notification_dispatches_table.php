<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_dispatches', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            $table->unsignedBigInteger('company_id')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('notification_dispatches', function (Blueprint $table): void {
            $table->dropColumn(['user_id', 'company_id']);
        });
    }
};
