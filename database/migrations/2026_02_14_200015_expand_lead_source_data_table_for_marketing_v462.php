<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_source_data', function (Blueprint $table): void {
            if (!Schema::hasColumn('lead_source_data', 'initial_source_detail')) {
                $table->string('initial_source_detail')->nullable()->after('initial_source');
            }
            if (!Schema::hasColumn('lead_source_data', 'initial_source_platform')) {
                $table->string('initial_source_platform')->nullable()->after('initial_source_detail');
            }
            if (!Schema::hasColumn('lead_source_data', 'verified_source_detail')) {
                $table->string('verified_source_detail')->nullable()->after('verified_source');
            }
            if (!Schema::hasColumn('lead_source_data', 'source_match')) {
                $table->boolean('source_match')->nullable()->after('verified_source_detail');
            }

            if (!Schema::hasColumn('lead_source_data', 'utm_source')) {
                $table->string('utm_source')->nullable()->after('source_match');
                $table->string('utm_medium')->nullable()->after('utm_source');
                $table->string('utm_campaign')->nullable()->after('utm_medium');
                $table->string('utm_term')->nullable()->after('utm_campaign');
                $table->string('utm_content')->nullable()->after('utm_term');
            }

            if (!Schema::hasColumn('lead_source_data', 'dealer_id')) {
                $table->string('dealer_id')->nullable()->after('campaign_id');
            }
            if (!Schema::hasColumn('lead_source_data', 'referral_link_id')) {
                $table->string('referral_link_id')->nullable()->after('dealer_id');
            }
            if (!Schema::hasColumn('lead_source_data', 'event_id')) {
                $table->unsignedBigInteger('event_id')->nullable()->after('referral_link_id');
            }
            if (!Schema::hasColumn('lead_source_data', 'cms_content_id')) {
                $table->unsignedBigInteger('cms_content_id')->nullable()->after('event_id');
            }

            if (!Schema::hasColumn('lead_source_data', 'funnel_registered')) {
                $table->boolean('funnel_registered')->default(false)->after('cms_content_id');
                $table->timestamp('funnel_registered_at')->nullable()->after('funnel_registered');
                $table->boolean('funnel_form_completed')->default(false)->after('funnel_registered_at');
                $table->timestamp('funnel_form_completed_at')->nullable()->after('funnel_form_completed');
                $table->boolean('funnel_documents_uploaded')->default(false)->after('funnel_form_completed_at');
                $table->timestamp('funnel_documents_uploaded_at')->nullable()->after('funnel_documents_uploaded');
                $table->boolean('funnel_package_selected')->default(false)->after('funnel_documents_uploaded_at');
                $table->timestamp('funnel_package_selected_at')->nullable()->after('funnel_package_selected');
                $table->boolean('funnel_contract_signed')->default(false)->after('funnel_package_selected_at');
                $table->timestamp('funnel_contract_signed_at')->nullable()->after('funnel_contract_signed');
                $table->boolean('funnel_converted')->default(false)->after('funnel_contract_signed_at');
                $table->timestamp('funnel_converted_at')->nullable()->after('funnel_converted');
                $table->string('funnel_dropped_at_stage')->nullable()->after('funnel_converted_at');
            }

            if (!Schema::hasColumn('lead_source_data', 'content_interactions')) {
                $table->json('content_interactions')->nullable()->after('funnel_dropped_at_stage');
            }
        });

        Schema::table('lead_source_data', function (Blueprint $table): void {
            $table->index('initial_source');
            $table->index('utm_campaign');
            $table->index('campaign_id');
            $table->index('funnel_converted');
        });
    }

    public function down(): void
    {
        // Intentionally left empty to avoid dropping existing production data columns.
    }
};
