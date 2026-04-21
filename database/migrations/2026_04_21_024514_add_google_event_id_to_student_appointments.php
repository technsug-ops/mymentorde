<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_appointments', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_appointments', 'google_event_id')) {
                $table->string('google_event_id', 128)->nullable()->after('meeting_url');
                $table->timestamp('google_synced_at')->nullable()->after('google_event_id');
                $table->index('google_event_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_appointments', function (Blueprint $table): void {
            if (Schema::hasColumn('student_appointments', 'google_event_id')) {
                $table->dropIndex(['google_event_id']);
                $table->dropColumn(['google_event_id', 'google_synced_at']);
            }
        });
    }
};
