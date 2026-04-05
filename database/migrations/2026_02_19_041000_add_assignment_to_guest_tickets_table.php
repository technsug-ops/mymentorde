<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guest_tickets')) {
            return;
        }

        Schema::table('guest_tickets', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_tickets', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('department')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_tickets')) {
            return;
        }

        Schema::table('guest_tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_tickets', 'assigned_user_id')) {
                $table->dropColumn('assigned_user_id');
            }
        });
    }
};

