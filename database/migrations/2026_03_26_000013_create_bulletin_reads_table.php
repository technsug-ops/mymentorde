<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_reads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id');
            $table->foreign('bulletin_id')->references('id')->on('company_bulletins')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['bulletin_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_reads');
    }
};
