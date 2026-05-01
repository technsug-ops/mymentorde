<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * uni-assist üye üniversite metadatası.
 *
 * is_uni_assist_member: bu üniversite uni-assist üzerinden başvuru kabul ediyor mu?
 * uni_assist_id: uni-assist iç ID (region/city ile birlikte programlara link kurabilmek için)
 *
 * Wizard sonuç ekranında: üye ise → "VPD gerekli + 14 belge" rozeti
 *                          değilse → "Direkt başvuru → 6 belge" rozeti
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('universities')) return;

        Schema::table('universities', function (Blueprint $table) {
            if (! Schema::hasColumn('universities', 'is_uni_assist_member')) {
                $table->boolean('is_uni_assist_member')->default(false)->after('is_public');
            }
            if (! Schema::hasColumn('universities', 'uni_assist_id')) {
                $table->unsignedInteger('uni_assist_id')->nullable()->after('is_uni_assist_member');
                $table->index('is_uni_assist_member', 'universities_is_uni_assist_member_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('universities')) return;

        Schema::table('universities', function (Blueprint $table) {
            if (Schema::hasColumn('universities', 'uni_assist_id')) {
                $table->dropIndex('universities_is_uni_assist_member_idx');
                $table->dropColumn('uni_assist_id');
            }
            if (Schema::hasColumn('universities', 'is_uni_assist_member')) {
                $table->dropColumn('is_uni_assist_member');
            }
        });
    }
};
