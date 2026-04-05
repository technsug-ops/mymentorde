<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('guest_application_id')->index();
            $table->string('package_code', 64)->nullable();
            $table->string('package_title')->nullable();
            $table->decimal('amount_eur', 10, 2)->default(0);
            $table->enum('payment_method', ['bank_transfer', 'credit_card', 'other'])->default('bank_transfer');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['guest_application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_payment_requests');
    }
};
