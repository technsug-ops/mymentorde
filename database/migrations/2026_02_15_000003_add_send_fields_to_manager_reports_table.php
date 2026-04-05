<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manager_reports', function (Blueprint $table): void {
            $table->string('send_status', 16)->default('draft')->after('sent_to');
            $table->timestamp('sent_at')->nullable()->after('send_status');
        });
    }

    public function down(): void
    {
        Schema::table('manager_reports', function (Blueprint $table): void {
            $table->dropColumn(['send_status', 'sent_at']);
        });
    }
};

