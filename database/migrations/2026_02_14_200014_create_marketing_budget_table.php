<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_budget', function (Blueprint $table): void {
            $table->id();
            $table->string('period', 7)->unique();
            $table->decimal('total_budget', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->json('allocations');
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->decimal('total_remaining', 10, 2)->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_budget');
    }
};
