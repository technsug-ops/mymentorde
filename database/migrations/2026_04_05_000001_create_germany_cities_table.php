<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('germany_cities', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 128);
            $table->string('state', 128)->nullable();
            $table->string('emoji', 8)->nullable();
            $table->unsignedTinyInteger('cost_index')->default(3); // 1-5
            $table->json('data');  // tüm içerik (location, culture, universities, costs, vb.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('germany_cities');
    }
};
