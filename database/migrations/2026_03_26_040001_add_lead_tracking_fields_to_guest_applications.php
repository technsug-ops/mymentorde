<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guest_applications')) {
            return;
        }
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'qualification_status')) {
                $table->enum('qualification_status', ['unqualified', 'warm', 'hot', 'qualified'])
                    ->nullable()->after('lead_status')->index();
            }
            if (!Schema::hasColumn('guest_applications', 'lost_reason')) {
                $table->enum('lost_reason', ['no_response', 'chose_competitor', 'budget', 'not_interested', 'timing', 'other'])
                    ->nullable()->after('qualification_status');
            }
            if (!Schema::hasColumn('guest_applications', 'lost_note')) {
                $table->string('lost_note', 300)->nullable()->after('lost_reason');
            }
            if (!Schema::hasColumn('guest_applications', 'follow_up_date')) {
                $table->date('follow_up_date')->nullable()->after('lost_note')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_applications')) {
            return;
        }
        Schema::table('guest_applications', function (Blueprint $table): void {
            foreach (['qualification_status', 'lost_reason', 'lost_note', 'follow_up_date'] as $col) {
                if (Schema::hasColumn('guest_applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
