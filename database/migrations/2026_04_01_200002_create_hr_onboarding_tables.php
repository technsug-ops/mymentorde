<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Onboarding şablonları (görev listeleri)
        Schema::create('hr_onboarding_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();    // onboarding yapılan çalışan
            $table->string('week');                            // '1', '2', '3', '4'
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_done')->default(false);
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Bordro profili (F2.2 hazırlığı)
        Schema::create('hr_salary_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('gross_salary', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->unsignedTinyInteger('payment_day')->default(1); // Ayın kaçında ödenir
            $table->string('bank_name')->nullable();
            $table->string('iban')->nullable();
            $table->date('valid_from');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_salary_profiles');
        Schema::dropIfExists('hr_onboarding_tasks');
    }
};
