<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            $table->timestamp('last_downloaded_at')->nullable()->after('contract_change_log');
            $table->unsignedSmallInteger('download_count')->default(0)->after('last_downloaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            $table->dropColumn(['last_downloaded_at', 'download_count']);
        });
    }
};
