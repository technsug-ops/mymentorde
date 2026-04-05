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
            if (!Schema::hasColumn('guest_tickets', 'first_response_at')) {
                $table->timestamp('first_response_at')->nullable()->after('last_replied_at');
            }
            if (!Schema::hasColumn('guest_tickets', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('first_response_at');
            }
            if (!Schema::hasColumn('guest_tickets', 'routed_at')) {
                $table->timestamp('routed_at')->nullable()->after('closed_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_tickets')) {
            return;
        }

        Schema::table('guest_tickets', function (Blueprint $table): void {
            foreach (['first_response_at', 'closed_at', 'routed_at'] as $col) {
                if (Schema::hasColumn('guest_tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

