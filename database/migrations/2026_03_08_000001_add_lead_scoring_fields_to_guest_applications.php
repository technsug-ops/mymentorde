<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->integer('lead_score')->default(0)->after('lead_status');
            $table->string('lead_score_tier', 20)->default('cold')->after('lead_score'); // cold/warm/hot/sales_ready/champion
            $table->timestamp('lead_score_updated_at')->nullable()->after('lead_score_tier');
            $table->timestamp('lead_score_decayed_at')->nullable()->after('lead_score_updated_at');
            $table->index('lead_score_tier');
            $table->index('lead_score');
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->dropIndex(['lead_score_tier']);
            $table->dropIndex(['lead_score']);
            $table->dropColumn(['lead_score', 'lead_score_tier', 'lead_score_updated_at', 'lead_score_decayed_at']);
        });
    }
};
