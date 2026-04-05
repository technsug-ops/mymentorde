<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_finance_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->date('entry_date');
            $table->enum('type', ['income', 'expense']);
            $table->string('category', 60); // salary, rent, software, marketing, travel, tax, student_fee, commission_paid, service, other
            $table->string('title', 200);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('reference_no', 100)->nullable(); // fatura/makbuz no
            $table->text('notes')->nullable();
            $table->enum('source', ['manual', 'bank_import', 'api'])->default('manual');
            $table->string('bank_transaction_id', 100)->nullable()->index(); // banka entegrasyonu için
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'type', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_finance_entries');
    }
};
