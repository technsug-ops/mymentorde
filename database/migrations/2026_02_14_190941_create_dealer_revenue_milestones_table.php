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
        Schema::create('dealer_revenue_milestones', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 32)->unique();
            $table->string('name_tr');
            $table->string('name_de');
            $table->string('name_en');
            $table->string('trigger_type', 32);
            $table->json('trigger_condition')->nullable();
            $table->string('revenue_type', 32);
            $table->decimal('percentage', 8, 2)->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->string('fixed_currency', 8)->nullable();
            $table->json('applicable_dealer_types')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_revenue_milestones');
    }
};
