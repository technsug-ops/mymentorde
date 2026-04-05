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
        Schema::create('dealer_student_revenues', function (Blueprint $table) {
            $table->id();
            $table->string('dealer_id');
            $table->string('student_id');
            $table->string('dealer_type');
            $table->json('milestone_progress')->nullable();
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_pending', 12, 2)->default(0);
            $table->unique(['dealer_id', 'student_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_student_revenues');
    }
};
