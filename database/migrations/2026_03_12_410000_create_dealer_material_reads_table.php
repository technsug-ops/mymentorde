<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_material_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dealer_user_id');
            $table->unsignedBigInteger('article_id');
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();
            $table->unique(['dealer_user_id', 'article_id']);
            $table->index('dealer_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_material_reads');
    }
};
