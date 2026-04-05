<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('guest_application_id');
            $table->string('subject', 180);
            $table->text('message');
            $table->string('status', 24)->default('open');
            $table->string('priority', 24)->default('normal');
            $table->string('created_by_email', 190)->nullable();
            $table->timestamp('last_replied_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'guest_application_id']);
            $table->index(['status', 'priority']);
        });

        Schema::create('guest_ticket_replies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('guest_ticket_id');
            $table->string('author_role', 24)->default('guest');
            $table->string('author_email', 190)->nullable();
            $table->text('message');
            $table->timestamps();

            $table->index(['company_id', 'guest_ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_ticket_replies');
        Schema::dropIfExists('guest_tickets');
    }
};

