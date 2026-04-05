<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->string('hold_reason', 255)->nullable()->after('status');
            $table->timestamp('review_requested_at')->nullable()->after('hold_reason');
            $table->timestamp('cancelled_at')->nullable()->after('review_requested_at');
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable()->after('cancelled_at');
            $table->foreign('cancelled_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropForeign(['cancelled_by_user_id']);
            $table->dropColumn(['hold_reason', 'review_requested_at', 'cancelled_at', 'cancelled_by_user_id']);
        });
    }
};
