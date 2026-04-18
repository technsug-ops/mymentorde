<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dealer signup bonus
        Schema::table('dealers', function (Blueprint $table) {
            $table->decimal('signup_bonus_amount', 10, 2)->default(100.00)->after('is_archived');
            $table->string('signup_bonus_status', 20)->default('locked')->after('signup_bonus_amount'); // locked, pending, unlocked
            $table->timestamp('signup_bonus_unlocked_at')->nullable()->after('signup_bonus_status');
        });

        // Lead referral type
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->string('referral_type', 30)->nullable()->after('dealer_code'); // recommendation, confirmed_referral
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn(['signup_bonus_amount', 'signup_bonus_status', 'signup_bonus_unlocked_at']);
        });

        Schema::table('guest_applications', function (Blueprint $table) {
            $table->dropColumn('referral_type');
        });
    }
};
