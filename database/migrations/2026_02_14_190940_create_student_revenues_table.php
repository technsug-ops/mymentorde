<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_revenues', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('package_id')->nullable();
            $table->decimal('package_total_price', 12, 2)->default(0);
            $table->string('package_currency', 8)->default('EUR');
            $table->json('milestone_progress')->nullable();
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_pending', 12, 2)->default(0);
            $table->decimal('total_remaining', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_revenues');
    }
};
