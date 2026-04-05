<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_utm_links', function (Blueprint $table) {
            $table->id();
            $table->string('dealer_code');
            $table->string('label'); // kullanici verilen isim
            $table->string('utm_campaign', 120);
            $table->string('utm_source', 120)->default('dealer');
            $table->string('utm_medium', 120)->default('referral');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('dealer_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_utm_links');
    }
};
