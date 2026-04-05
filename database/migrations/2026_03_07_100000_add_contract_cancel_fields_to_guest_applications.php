<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->string('contract_cancel_category', 64)->nullable()->after('contract_approved_at');
            $table->string('contract_cancel_reason_code', 64)->nullable()->after('contract_cancel_category');
            $table->text('contract_cancel_note')->nullable()->after('contract_cancel_reason_code');
            $table->string('contract_cancel_attachment_path')->nullable()->after('contract_cancel_note');
            $table->timestamp('contract_cancelled_at')->nullable()->after('contract_cancel_attachment_path');
            $table->string('contract_cancelled_by', 180)->nullable()->after('contract_cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'contract_cancel_category',
                'contract_cancel_reason_code',
                'contract_cancel_note',
                'contract_cancel_attachment_path',
                'contract_cancelled_at',
                'contract_cancelled_by',
            ]);
        });
    }
};
