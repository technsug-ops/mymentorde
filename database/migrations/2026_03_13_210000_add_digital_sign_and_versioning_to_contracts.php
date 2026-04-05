<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // K2.1 — Dijital imza alanları
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'contract_digital_signature_data')) {
                $table->text('contract_digital_signature_data')->nullable()->after('contract_cancelled_by');
                // base64 PNG imza görseli
            }
            if (!Schema::hasColumn('guest_applications', 'contract_digital_signed_at')) {
                $table->timestamp('contract_digital_signed_at')->nullable()->after('contract_digital_signature_data');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_digital_sign_ip')) {
                $table->string('contract_digital_sign_ip', 45)->nullable()->after('contract_digital_signed_at');
            }
        });

        // K2.2 — Şablon versiyonlama alanları
        Schema::table('contract_templates', function (Blueprint $table): void {
            if (!Schema::hasColumn('contract_templates', 'parent_version_id')) {
                $table->unsignedBigInteger('parent_version_id')->nullable()->after('version');
            }
            if (!Schema::hasColumn('contract_templates', 'change_log')) {
                $table->text('change_log')->nullable()->after('parent_version_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'contract_digital_signature_data',
                'contract_digital_signed_at',
                'contract_digital_sign_ip',
            ]);
        });

        Schema::table('contract_templates', function (Blueprint $table): void {
            $table->dropColumn(['parent_version_id', 'change_log']);
        });
    }
};
