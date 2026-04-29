<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Türkiye 81 il
        Schema::create('tr_provinces', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('plate_code')->unique()->comment('01-81 plaka kodu');
            $table->string('slug', 60)->unique()->comment('alfabetik slug (istanbul, ankara)');
            $table->string('name', 60)->comment('Türkçe il adı');
            $table->string('region', 60)->nullable()->comment('Coğrafi bölge (Marmara, Ege vb.)');
            $table->boolean('is_metropolitan')->default(false)->comment('Büyükşehir mi?');
            $table->timestamps();
        });

        // Türkiye ~973 ilçe
        Schema::create('tr_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('tr_provinces')->cascadeOnDelete();
            $table->string('slug', 80)->comment('alfabetik slug (kadikoy, atasehir)');
            $table->string('name', 80)->comment('Türkçe ilçe adı');
            $table->boolean('is_central')->default(false)->comment('Merkez ilçe mi (örn. Çankaya/Ankara)?');
            $table->timestamps();

            $table->unique(['province_id', 'slug'], 'tr_districts_province_slug_uq');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_districts');
        Schema::dropIfExists('tr_provinces');
    }
};
