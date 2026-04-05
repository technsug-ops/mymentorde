<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_saved_contents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cms_content_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'cms_content_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('cms_content_id')->references('id')->on('cms_contents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_saved_contents');
    }
};
