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
        Schema::create('lead_source_data', function (Blueprint $table) {
            $table->id();
            $table->string('guest_id')->unique();
            $table->string('initial_source');
            $table->string('verified_source')->nullable();
            $table->string('source_detail')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->json('utm_params')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_source_data');
    }
};
