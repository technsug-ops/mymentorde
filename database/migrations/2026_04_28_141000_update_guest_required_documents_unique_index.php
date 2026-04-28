<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unique index'i (company_id, application_type, document_code)'dan
 * (company_id, application_type, stage, top_category_code, document_code)'a
 * genişletir — aynı belge birden fazla top kategoride etiketlenebilsin.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('guest_required_documents')) return;

        Schema::table('guest_required_documents', function (Blueprint $table) {
            $table->dropUnique('grd_company_type_doc_unique');
        });
        Schema::table('guest_required_documents', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'application_type', 'stage', 'top_category_code', 'document_code'],
                'grd_company_type_stage_top_doc_unique'
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guest_required_documents')) return;

        Schema::table('guest_required_documents', function (Blueprint $table) {
            $table->dropUnique('grd_company_type_stage_top_doc_unique');
        });
        Schema::table('guest_required_documents', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'application_type', 'document_code'],
                'grd_company_type_doc_unique'
            );
        });
    }
};
