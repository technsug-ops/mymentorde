<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dm_threads', function (Blueprint $table): void {
            $table->string('department', 30)->default('advisory')->after('status')->index();
            $table->unsignedSmallInteger('sla_hours')->default(24)->after('department');
            $table->timestamp('next_response_due_at')->nullable()->after('sla_hours')->index();
            $table->timestamp('last_participant_message_at')->nullable()->after('next_response_due_at');
            $table->timestamp('last_advisor_reply_at')->nullable()->after('last_participant_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('dm_threads', function (Blueprint $table): void {
            $table->dropColumn([
                'department',
                'sla_hours',
                'next_response_due_at',
                'last_participant_message_at',
                'last_advisor_reply_at',
            ]);
        });
    }
};

