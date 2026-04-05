<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guest_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_guest_id');
            $table->string('referral_code', 30)->unique();
            $table->unsignedBigInteger('referred_guest_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('reward_type', 30)->nullable();
            $table->timestamp('reward_applied_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('referrer_guest_id', 'idx_referrer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_referrals');
    }
};
