<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_type_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
            $table->string('dealer_code', 64);
            $table->string('old_type_code', 64)->nullable();
            $table->string('new_type_code', 64)->nullable();
            $table->string('changed_by', 255)->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['dealer_code', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_type_histories');
    }
};
