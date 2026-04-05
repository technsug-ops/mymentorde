<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // marketing_tasks'a checklist sayaçları
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->unsignedSmallInteger('checklist_total')->default(0)->after('cancelled_by_user_id');
            $table->unsignedSmallInteger('checklist_done')->default(0)->after('checklist_total');
        });

        // task_checklists tablosu
        Schema::create('task_checklists', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('task_id')->index();
            $table->string('title', 255);
            $table->boolean('is_done')->default(false);
            $table->unsignedBigInteger('done_by_user_id')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('marketing_tasks')->cascadeOnDelete();
            $table->foreign('done_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['task_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_checklists');
        Schema::table('marketing_tasks', function (Blueprint $table): void {
            $table->dropColumn(['checklist_total', 'checklist_done']);
        });
    }
};
