<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_tickets', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('routed_at');
            $table->string('attachment_name', 255)->nullable()->after('attachment_path');
        });

        Schema::table('guest_ticket_replies', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('message');
            $table->string('attachment_name', 255)->nullable()->after('attachment_path');
        });
    }

    public function down(): void
    {
        Schema::table('guest_tickets', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
        Schema::table('guest_ticket_replies', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
