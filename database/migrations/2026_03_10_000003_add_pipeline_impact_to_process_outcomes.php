<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_outcomes', function (Blueprint $table): void {
            $table->string('pipeline_impact', 64)->nullable()->after('is_visible_to_student');
            // 'score_boost' | 'status_advance' | 'score_penalty' | 'none'
            $table->integer('lead_score_delta')->default(0)->after('pipeline_impact');
        });
    }

    public function down(): void
    {
        Schema::table('process_outcomes', function (Blueprint $table): void {
            $table->dropColumn(['pipeline_impact', 'lead_score_delta']);
        });
    }
};
