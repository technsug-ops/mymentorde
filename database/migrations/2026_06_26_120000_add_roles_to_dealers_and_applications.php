<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bayi "çoklu rol" kapasitesi: bir bayi hem lead-gen (referral) hem freelance
 * olabilir. dealer_type_code primary tier olarak kalır (izin/dashboard/komisyon
 * makinesi değişmez); roles sadece hangi iş modellerinde çalıştığını belirtir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            $table->json('roles')->nullable()->after('dealer_type_code');
        });

        Schema::table('dealer_applications', function (Blueprint $table): void {
            $table->json('roles')->nullable()->after('preferred_plan');
        });

        // ── Backfill: mevcut tip/plan → roles ─────────────────────────────────
        // dealer_type_code → role
        $typeToRoles = [
            'lead_generation'    => ['lead_generation'],
            'freelance_danisman' => ['freelance'],
            'b2b_partner'        => ['b2b_partner'],
        ];
        foreach ($typeToRoles as $type => $roles) {
            DB::table('dealers')
                ->where('dealer_type_code', $type)
                ->update(['roles' => json_encode($roles)]);
        }

        // preferred_plan → role (unsure → lead_generation varsayılan)
        $planToRoles = [
            'lead_generation' => ['lead_generation'],
            'freelance'       => ['freelance'],
            'unsure'          => ['lead_generation'],
        ];
        foreach ($planToRoles as $plan => $roles) {
            DB::table('dealer_applications')
                ->where('preferred_plan', $plan)
                ->update(['roles' => json_encode($roles)]);
        }
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            $table->dropColumn('roles');
        });
        Schema::table('dealer_applications', function (Blueprint $table): void {
            $table->dropColumn('roles');
        });
    }
};
