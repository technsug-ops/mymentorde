<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table): void {
            $table->longText('annex_payment_text')->nullable()->after('annex_commitment_text');
        });

        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->longText('contract_annex_payment_text')->nullable()->after('contract_annex_commitment_text');
        });
    }

    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table): void {
            $table->dropColumn('annex_payment_text');
        });

        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->dropColumn('contract_annex_payment_text');
        });
    }
};
