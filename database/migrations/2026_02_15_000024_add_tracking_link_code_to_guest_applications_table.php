<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'tracking_link_code')) {
                $table->string('tracking_link_code', 64)->nullable()->after('campaign_code');
                $table->index('tracking_link_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_applications', 'tracking_link_code')) {
                $table->dropIndex(['tracking_link_code']);
                $table->dropColumn('tracking_link_code');
            }
        });
    }
};

