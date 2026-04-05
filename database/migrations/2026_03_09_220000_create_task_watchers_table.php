<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_watchers', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('task_id')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->timestamp('watched_at')->useCurrent();
            $t->unique(['task_id', 'user_id']);

            $t->foreign('task_id')->references('id')->on('marketing_tasks')->cascadeOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_watchers');
    }
};
