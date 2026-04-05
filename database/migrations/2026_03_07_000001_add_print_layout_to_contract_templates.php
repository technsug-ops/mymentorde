<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table): void {
            $table->longText('print_header_html')->nullable()->after('annex_commitment_text');
            $table->longText('print_footer_html')->nullable()->after('print_header_html');
        });
    }

    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table): void {
            $table->dropColumn(['print_header_html', 'print_footer_html']);
        });
    }
};
