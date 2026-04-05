<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_threads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(0)->index();
            $table->string('thread_type', 20)->index(); // guest|student
            $table->unsignedBigInteger('guest_application_id')->nullable()->index();
            $table->string('student_id', 64)->nullable()->index();
            $table->unsignedBigInteger('advisor_user_id')->nullable()->index();
            $table->unsignedBigInteger('initiated_by_user_id')->nullable()->index();
            $table->string('status', 20)->default('open')->index(); // open|closed
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_threads');
    }
};

