<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('due_date');
            $table->json('mentioned_user_ids')->nullable()->after('column_order');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropColumn(['start_date', 'mentioned_user_ids']);
        });
    }
};
