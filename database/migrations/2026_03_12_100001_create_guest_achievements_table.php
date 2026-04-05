<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guest_achievements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_application_id');
            $table->string('achievement_code', 50);
            $table->timestamp('earned_at')->useCurrent();
            $table->unique(['guest_application_id', 'achievement_code'], 'idx_guest_achievement');
            $table->index('guest_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_achievements');
    }
};
