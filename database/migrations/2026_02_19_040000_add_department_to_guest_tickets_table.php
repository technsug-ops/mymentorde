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
            if (!Schema::hasColumn('guest_tickets', 'department')) {
                $table->string('department', 32)->default('operations')->after('priority')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_tickets')) {
            return;
        }

        Schema::table('guest_tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_tickets', 'department')) {
                $table->dropColumn('department');
            }
        });
    }
};

