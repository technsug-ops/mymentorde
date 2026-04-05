<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            // Sözleşmeden son güncelleme zamanı — doluysa "değişiklik" uyarısı gösterilir
            $table->timestamp('contract_updated_at')->nullable()->after('notes');
            // Değişiklik geçmişi (tutar, paket vs.) — her değişimde satır eklenir
            $table->text('contract_change_log')->nullable()->after('contract_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table) {
            $table->dropColumn(['contract_updated_at', 'contract_change_log']);
        });
    }
};
