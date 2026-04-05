<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_step_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('process_definition_id');
            $table->string('label_tr');
            $table->string('label_de')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->string('added_by')->nullable();
            $table->timestamps();

            $table->foreign('process_definition_id')
                  ->references('id')->on('process_definitions')
                  ->onDelete('cascade');

            $table->index('process_definition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_step_tasks');
    }
};
