<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UniMatch wizard mid-funnel lead capture.
 *
 * Step 12 sonrası soft gate: kullanıcı email VEYA WhatsApp bırakır.
 * Atlanabilir (skip butonu var) ama %40-50 conversion bekleniyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uni_match_responses')) return;

        Schema::table('uni_match_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('uni_match_responses', 'lead_email')) {
                $table->string('lead_email', 200)->nullable()->after('user_agent');
                $table->index('lead_email');
            }
            if (! Schema::hasColumn('uni_match_responses', 'lead_phone')) {
                $table->string('lead_phone', 30)->nullable()->after('lead_email');
            }
            if (! Schema::hasColumn('uni_match_responses', 'lead_first_name')) {
                $table->string('lead_first_name', 80)->nullable()->after('lead_phone');
            }
            if (! Schema::hasColumn('uni_match_responses', 'lead_consent_marketing')) {
                $table->boolean('lead_consent_marketing')->default(false)->after('lead_first_name');
            }
            if (! Schema::hasColumn('uni_match_responses', 'lead_captured_at')) {
                $table->timestamp('lead_captured_at')->nullable()->after('lead_consent_marketing');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('uni_match_responses')) return;

        Schema::table('uni_match_responses', function (Blueprint $table) {
            foreach (['lead_email', 'lead_phone', 'lead_first_name', 'lead_consent_marketing', 'lead_captured_at'] as $col) {
                if (Schema::hasColumn('uni_match_responses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
