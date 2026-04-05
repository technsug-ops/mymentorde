<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('account_vaults')->cascadeOnDelete();
            $table->string('student_id', 64);
            $table->string('accessed_by')->nullable();
            $table->string('access_type', 16);
            $table->string('ip_address', 64)->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();

            $table->index(['student_id', 'accessed_at']);
            $table->index(['access_type', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_access_logs');
    }
};
