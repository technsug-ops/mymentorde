<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_appointments', function (Blueprint $table): void {
            if (!Schema::hasColumn('student_appointments', 'external_event_id')) {
                $table->string('external_event_id')->nullable()->after('meeting_url');
            }
            if (!Schema::hasColumn('student_appointments', 'calendar_provider')) {
                $table->string('calendar_provider', 32)->nullable()->after('external_event_id')
                      ->comment('google_calendar, cal_com, calendly');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_appointments', function (Blueprint $table): void {
            $table->dropColumn(['external_event_id', 'calendar_provider']);
        });
    }
};
