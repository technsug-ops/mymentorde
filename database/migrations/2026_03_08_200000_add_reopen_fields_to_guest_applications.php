<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->text('reopen_reason')->nullable()->after('contract_cancelled_by');
            $table->timestamp('reopen_requested_at')->nullable()->after('reopen_reason');
            $table->string('reopen_decided_by', 190)->nullable()->after('reopen_requested_at');
            $table->timestamp('reopen_decided_at')->nullable()->after('reopen_decided_by');
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'reopen_reason',
                'reopen_requested_at',
                'reopen_decided_by',
                'reopen_decided_at',
            ]);
        });
    }
};
