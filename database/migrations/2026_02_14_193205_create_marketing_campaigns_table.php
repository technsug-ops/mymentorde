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
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel', 32);
            $table->decimal('budget', 12, 2)->default(0);
            $table->string('currency', 8)->default('EUR');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('target_audience')->nullable();
            $table->string('status', 32)->default('draft');
            $table->json('metrics')->nullable();
            $table->json('linked_cms_content_ids')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
