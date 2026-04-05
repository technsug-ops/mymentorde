<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_vaults', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id', 64);
            $table->string('service_name', 64);
            $table->string('service_label');
            $table->string('account_url')->nullable();
            $table->string('account_email');
            $table->string('account_username')->nullable();
            $table->longText('account_password_encrypted');
            $table->string('application_id', 64)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('active');
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'service_name']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_vaults');
    }
};
