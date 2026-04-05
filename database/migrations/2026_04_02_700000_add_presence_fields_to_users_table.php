<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('presence_status', ['online', 'away', 'busy', 'offline'])
                  ->default('offline')->after('remember_token')->index();
            $table->timestamp('last_activity_at')->nullable()->after('presence_status')->index();
        });

        Schema::create('user_availability_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedTinyInteger('day_of_week'); // 0=Pazar … 6=Cumartesi
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 60)->default('Europe/Berlin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'day_of_week']);
        });

        Schema::create('user_away_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->dateTime('away_from');
            $table->dateTime('away_until');
            $table->string('away_message', 300)->nullable(); // "Tatildeyim, Pazartesi dönerim"
            $table->boolean('auto_reply_enabled')->default(true);
            $table->string('auto_reply_message', 500)->nullable();
            $table->string('timezone', 60)->default('Europe/Berlin');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'away_from', 'away_until']);
        });

        // Auto-reply tekrar gönderimi önlemek için
        Schema::table('dm_threads', function (Blueprint $table) {
            $table->timestamp('auto_reply_sent_at')->nullable()->after('last_advisor_reply_at');
            $table->string('auto_reply_away_period_id', 20)->nullable()->after('auto_reply_sent_at');
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->timestamp('auto_reply_sent_at')->nullable()->after('is_pinned');
            $table->string('auto_reply_away_period_id', 20)->nullable()->after('auto_reply_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn(['auto_reply_sent_at', 'auto_reply_away_period_id']);
        });

        Schema::table('dm_threads', function (Blueprint $table) {
            $table->dropColumn(['auto_reply_sent_at', 'auto_reply_away_period_id']);
        });

        Schema::dropIfExists('user_away_periods');
        Schema::dropIfExists('user_availability_schedules');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['presence_status']);
            $table->dropIndex(['last_activity_at']);
            $table->dropColumn(['presence_status', 'last_activity_at']);
        });
    }
};
