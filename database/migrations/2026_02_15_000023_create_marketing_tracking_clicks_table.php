<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_tracking_clicks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tracking_link_id');
            $table->string('tracking_code', 40);
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->text('referrer_url')->nullable();
            $table->text('landing_url')->nullable();
            $table->json('query_params')->nullable();
            $table->timestamps();

            $table->index(['tracking_link_id', 'created_at']);
            $table->index('tracking_code');
            $table->foreign('tracking_link_id')->references('id')->on('marketing_tracking_links')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_tracking_clicks');
    }
};

