<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_appointments')) {
            Schema::create('student_appointments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->string('student_id', 64)->index();
                $table->string('student_email', 180)->nullable()->index();
                $table->string('senior_email', 180)->nullable()->index();
                $table->string('title', 190);
                $table->text('note')->nullable();
                $table->dateTime('requested_at')->nullable();
                $table->dateTime('scheduled_at')->nullable()->index();
                $table->integer('duration_minutes')->default(30);
                $table->string('channel', 32)->default('online');
                $table->string('meeting_url', 500)->nullable();
                $table->string('status', 32)->default('requested')->index();
                $table->dateTime('cancelled_at')->nullable();
                $table->string('cancel_reason', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_appointments');
    }
};

