<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('manager_requests')) {
            return;
        }

        Schema::create('manager_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('requester_user_id')->nullable()->index();
            $table->unsignedBigInteger('target_manager_user_id')->nullable()->index();
            $table->string('request_type', 64)->default('general')->index();
            $table->string('subject', 180);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 32)->default('normal')->index();
            $table->date('due_date')->nullable()->index();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->string('source_type', 64)->nullable()->index();
            $table->string('source_id', 120)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_requests');
    }
};

