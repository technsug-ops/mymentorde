<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->string('pipeline_moved_by')->nullable()->after('lead_status');
            $table->timestamp('pipeline_moved_at')->nullable()->after('pipeline_moved_by');
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->dropColumn(['pipeline_moved_by', 'pipeline_moved_at']);
        });
    }
};
