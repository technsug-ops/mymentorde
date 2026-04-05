<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('channel', 32);
            $table->string('category', 64);
            $table->string('subject_tr')->nullable();
            $table->string('subject_de')->nullable();
            $table->string('subject_en')->nullable();
            $table->longText('body_tr');
            $table->longText('body_de')->nullable();
            $table->longText('body_en')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['channel', 'category']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
