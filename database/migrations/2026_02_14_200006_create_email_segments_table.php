<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_segments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->json('rules')->nullable();
            $table->json('member_user_ids')->nullable();
            $table->unsignedInteger('estimated_size')->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->string('zoho_list_id')->nullable();
            $table->boolean('zoho_synced')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_segments');
    }
};
