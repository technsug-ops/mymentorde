<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_popups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('title', 150);
            $table->string('video_url', 500)->nullable();
            $table->string('video_type', 20)->default('youtube');
            $table->text('description')->nullable();
            $table->json('target_pages');
            $table->json('target_roles');
            $table->unsignedSmallInteger('delay_seconds')->default(3);
            $table->string('frequency', 20)->default('first_login');
            $table->unsignedSmallInteger('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('created_by', 180)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_popups');
    }
};
