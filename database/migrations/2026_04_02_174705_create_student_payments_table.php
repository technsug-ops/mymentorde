<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('student_id', 64)->index();
            $table->string('invoice_number', 32)->unique(); // INV-2026-0001
            $table->string('description', 255);
            $table->decimal('amount_eur', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->enum('payment_method', ['bank_transfer', 'credit_card', 'cash', 'other'])->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
