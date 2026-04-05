<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('guest_applications', 'contract_amount_eur')) {
                $table->decimal('contract_amount_eur', 10, 2)->nullable()->after('selected_package_price')
                    ->comment('Sözleşme tutar EUR (numeric; selected_package_price string\'den ayrı)');
            }
        });

        // Mevcut kayıtları PHP'de parse et (REGEXP_REPLACE MariaDB uyumsuzluğu)
        DB::table('guest_applications')
            ->whereNotNull('selected_package_price')
            ->where('selected_package_price', '!=', '')
            ->whereNull('contract_amount_eur')
            ->select(['id', 'selected_package_price'])
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $cleaned = preg_replace('/[^0-9.,]/', '', $row->selected_package_price ?? '');
                    $cleaned = str_replace('.', '', $cleaned); // Binlik nokta kaldır
                    $cleaned = str_replace(',', '.', $cleaned); // Ondalık virgülü noktaya çevir
                    $amount  = (float) $cleaned;
                    if ($amount > 0) {
                        DB::table('guest_applications')
                            ->where('id', $row->id)
                            ->update(['contract_amount_eur' => $amount]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('guest_applications', 'contract_amount_eur')) {
                $table->dropColumn('contract_amount_eur');
            }
        });
    }
};
