<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_appointments', function (Blueprint $table): void {
            if (!Schema::hasColumn('student_appointments', 'post_meeting_note')) {
                $table->text('post_meeting_note')->nullable();
            }
            if (!Schema::hasColumn('student_appointments', 'meeting_provider')) {
                $table->string('meeting_provider', 30)->nullable();
            }
            // meeting_provider: zoom, teams, google_meet, cal_com, calendly, other
        });
    }

    public function down(): void
    {
        Schema::table('student_appointments', function (Blueprint $table): void {
            $table->dropColumn(['post_meeting_note', 'meeting_provider']);
        });
    }
};
