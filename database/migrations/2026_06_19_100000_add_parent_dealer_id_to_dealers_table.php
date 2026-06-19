<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bayi hiyerarşisi (2 seviye): parent_dealer_id NULL => bölge bayisi,
 * dolu => alt bayi. Self-referencing FK, alt bayi silinince parent korunur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('dealers', 'parent_dealer_id')) {
            Schema::table('dealers', function (Blueprint $table): void {
                $table->unsignedBigInteger('parent_dealer_id')->nullable()->index()->after('company_id');
                $table->foreign('parent_dealer_id')
                    ->references('id')->on('dealers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('dealers', 'parent_dealer_id')) {
            Schema::table('dealers', function (Blueprint $table): void {
                $table->dropForeign(['parent_dealer_id']);
                $table->dropColumn('parent_dealer_id');
            });
        }
    }
};
