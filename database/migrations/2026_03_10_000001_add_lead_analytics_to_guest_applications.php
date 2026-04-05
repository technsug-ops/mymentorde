<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'lead_score')) {
                $table->integer('lead_score')->default(0)->after('risk_level');
            }
            if (!Schema::hasColumn('guest_applications', 'lead_score_tier')) {
                $table->string('lead_score_tier', 32)->nullable()->after('lead_score');
            }
            if (!Schema::hasColumn('guest_applications', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('converted_to_student');
            }
            if (!Schema::hasColumn('guest_applications', 'last_senior_action_at')) {
                $table->timestamp('last_senior_action_at')->nullable()->after('converted_at');
            }
        });

        // Backfill converted_at from contract_approved_at for existing converted records
        DB::statement("
            UPDATE guest_applications
            SET converted_at = COALESCE(contract_approved_at, contract_signed_at, updated_at)
            WHERE converted_to_student = 1 AND converted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->dropColumn(['lead_score', 'lead_score_tier', 'converted_at', 'last_senior_action_at']);
        });
    }
};
