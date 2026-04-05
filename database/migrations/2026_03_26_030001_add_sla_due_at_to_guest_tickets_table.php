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
            if (!Schema::hasColumn('guest_tickets', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('routed_at')->index();
            }
            if (!Schema::hasColumn('guest_tickets', 'sla_hours')) {
                $table->unsignedSmallInteger('sla_hours')->nullable()->after('sla_due_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_tickets')) {
            return;
        }
        Schema::table('guest_tickets', function (Blueprint $table): void {
            foreach (['sla_due_at', 'sla_hours'] as $col) {
                if (Schema::hasColumn('guest_tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
