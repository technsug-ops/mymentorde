<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_timeline_milestones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guest_application_id');
            $table->string('milestone_code', 50);
            $table->string('label', 180);
            $table->string('category', 30)->default('general');
            $table->date('target_date');
            $table->timestamp('completed_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['guest_application_id', 'milestone_code'], 'idx_gtm_guest_milestone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_timeline_milestones');
    }
};
