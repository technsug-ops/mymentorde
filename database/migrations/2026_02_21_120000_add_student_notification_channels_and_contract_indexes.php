<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('guest_applications')) {
            Schema::table('guest_applications', function (Blueprint $table): void {
                if (!Schema::hasColumn('guest_applications', 'notify_email')) {
                    $table->boolean('notify_email')->default(true)->after('notifications_enabled');
                }
                if (!Schema::hasColumn('guest_applications', 'notify_whatsapp')) {
                    $table->boolean('notify_whatsapp')->default(false)->after('notify_email');
                }
                if (!Schema::hasColumn('guest_applications', 'notify_inapp')) {
                    $table->boolean('notify_inapp')->default(true)->after('notify_whatsapp');
                }
            });
        }

        // Performance indexes for contract-template and contract flow lists.
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_guest_contract_status_requested ON guest_applications (contract_status, contract_requested_at)');
        } catch (\Throwable) {
        }
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_guest_converted_student ON guest_applications (converted_student_id)');
        } catch (\Throwable) {
        }
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_guest_company_contract ON guest_applications (company_id, contract_status, contract_requested_at)');
        } catch (\Throwable) {
        }
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_guest_company_converted ON guest_applications (company_id, converted_to_student)');
        } catch (\Throwable) {
        }
        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_event_guest_contract ON system_event_logs (entity_type, entity_id, event_type)');
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('guest_applications')) {
            Schema::table('guest_applications', function (Blueprint $table): void {
                if (Schema::hasColumn('guest_applications', 'notify_inapp')) {
                    $table->dropColumn('notify_inapp');
                }
                if (Schema::hasColumn('guest_applications', 'notify_whatsapp')) {
                    $table->dropColumn('notify_whatsapp');
                }
                if (Schema::hasColumn('guest_applications', 'notify_email')) {
                    $table->dropColumn('notify_email');
                }
            });
        }

        try {
            DB::statement('DROP INDEX IF EXISTS idx_guest_contract_status_requested');
        } catch (\Throwable) {
        }
        try {
            DB::statement('DROP INDEX IF EXISTS idx_guest_converted_student');
        } catch (\Throwable) {
        }
        try {
            DB::statement('DROP INDEX IF EXISTS idx_guest_company_contract');
        } catch (\Throwable) {
        }
        try {
            DB::statement('DROP INDEX IF EXISTS idx_guest_company_converted');
        } catch (\Throwable) {
        }
        try {
            DB::statement('DROP INDEX IF EXISTS idx_event_guest_contract');
        } catch (\Throwable) {
        }
    }
};

