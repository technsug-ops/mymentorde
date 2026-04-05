<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'contract_template_id')) {
                $table->unsignedBigInteger('contract_template_id')->nullable()->after('contract_signed_file_path');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_template_code')) {
                $table->string('contract_template_code', 80)->nullable()->after('contract_template_id');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_snapshot_text')) {
                $table->longText('contract_snapshot_text')->nullable()->after('contract_template_code');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_annex_kvkk_text')) {
                $table->longText('contract_annex_kvkk_text')->nullable()->after('contract_snapshot_text');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_annex_commitment_text')) {
                $table->longText('contract_annex_commitment_text')->nullable()->after('contract_annex_kvkk_text');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_generated_at')) {
                $table->timestamp('contract_generated_at')->nullable()->after('contract_annex_commitment_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $cols = [
                'contract_generated_at',
                'contract_annex_commitment_text',
                'contract_annex_kvkk_text',
                'contract_snapshot_text',
                'contract_template_code',
                'contract_template_id',
            ];
            $exists = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('guest_applications', $c)));
            if ($exists !== []) {
                $table->dropColumn($exists);
            }
        });
    }
};

