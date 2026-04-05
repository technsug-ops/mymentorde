<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'selected_extra_services')) {
                $table->json('selected_extra_services')->nullable()->after('selected_package_price');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_status')) {
                $table->string('contract_status', 32)->default('not_requested')->after('selected_extra_services');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_requested_at')) {
                $table->timestamp('contract_requested_at')->nullable()->after('contract_status');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_signed_at')) {
                $table->timestamp('contract_signed_at')->nullable()->after('contract_requested_at');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_approved_at')) {
                $table->timestamp('contract_approved_at')->nullable()->after('contract_signed_at');
            }
            if (!Schema::hasColumn('guest_applications', 'contract_signed_file_path')) {
                $table->string('contract_signed_file_path', 500)->nullable()->after('contract_approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            foreach ([
                'contract_signed_file_path',
                'contract_approved_at',
                'contract_signed_at',
                'contract_requested_at',
                'contract_status',
                'selected_extra_services',
            ] as $column) {
                if (Schema::hasColumn('guest_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

