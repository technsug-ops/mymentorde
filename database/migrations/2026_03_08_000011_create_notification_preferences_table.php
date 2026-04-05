<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_id', 64)->nullable();
            $table->string('student_id', 64)->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('channel', 32);
            $table->string('category', 64)->default('*');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Unique constraint: aynı kullanıcı için aynı kanal+kategori kombinasyonu
            $table->unique(['user_id', 'guest_id', 'student_id', 'channel', 'category'], 'notif_pref_unique');

            $table->index(['user_id', 'channel', 'category']);
            $table->index(['guest_id', 'channel', 'category']);
            $table->index(['student_id', 'channel', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
