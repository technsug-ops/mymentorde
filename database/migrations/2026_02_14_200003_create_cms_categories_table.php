<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name_tr');
            $table->string('name_de')->nullable();
            $table->string('name_en')->nullable();
            $table->text('description_tr')->nullable();
            $table->foreignId('parent_category_id')->nullable()->constrained('cms_categories')->nullOnDelete();
            $table->string('icon_url', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_categories');
    }
};
