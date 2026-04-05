<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('dealer_code');
            $table->string('bank_name');
            $table->string('iban', 50);
            $table->string('account_holder');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('dealer_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_payout_accounts');
    }
};
