<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->string('dealer_code');
            $table->unsignedBigInteger('payout_account_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('EUR');
            $table->string('status', 32)->default('requested'); // requested|approved|paid|rejected
            $table->string('requested_by_email')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('dealer_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_payout_requests');
    }
};
