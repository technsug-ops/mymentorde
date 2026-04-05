<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id')->default(0)->index();
            $table->enum('contract_type', ['dealer', 'staff']);
            $table->string('template_code', 80)->unique();
            $table->string('name');
            $table->longText('body_text');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('business_contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id')->default(0)->index();
            $table->enum('contract_type', ['dealer', 'staff']);
            $table->unsignedBigInteger('dealer_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('contract_no', 60)->unique();
            $table->string('title');
            $table->longText('body_text');
            $table->json('meta')->nullable();
            $table->enum('status', ['draft', 'issued', 'signed_uploaded', 'approved', 'cancelled'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('signed_file_path')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['contract_type', 'status']);
            $table->index(['dealer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_contracts');
        Schema::dropIfExists('business_contract_templates');
    }
};
