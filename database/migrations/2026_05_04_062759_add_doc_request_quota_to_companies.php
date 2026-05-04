<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D11: doc_request modulu icin SaaS quota — companies bazinda aylik limit.
 *
 * - NULL    = sinirsiz (Premium tier varsayilani)
 * - integer = aylik link uretim limiti (orn 50 = Gold tier)
 *
 * Limit asilirsa storeFor() 422 + "limite ulasildi, plani yukselt" prompt doner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (! Schema::hasColumn('companies', 'doc_request_monthly_limit')) {
                $table->unsignedInteger('doc_request_monthly_limit')->nullable()->after('enabled_modules');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (Schema::hasColumn('companies', 'doc_request_monthly_limit')) {
                $table->dropColumn('doc_request_monthly_limit');
            }
        });
    }
};
