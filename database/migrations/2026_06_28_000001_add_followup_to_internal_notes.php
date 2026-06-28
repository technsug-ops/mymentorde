<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aktivite günlüğünü pasif kayıttan AKTİF takip aracına çevirir (#11):
 *  - next_step      : görüşme sonrası "sonraki adım" (ne yapılacak)
 *  - follow_up_date : hatırlatma tarihi → günlüğün üstünde "bekleyen takipler"
 *                     listesi (geciken kırmızı, yaklaşan sarı) gösterilir.
 * İkisi de nullable — mevcut kayıtlar etkilenmez (addon bağımsızlığı).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_notes', function (Blueprint $table): void {
            if (!Schema::hasColumn('internal_notes', 'next_step')) {
                $table->string('next_step', 500)->nullable()->after('content');
            }
            if (!Schema::hasColumn('internal_notes', 'follow_up_date')) {
                $table->date('follow_up_date')->nullable()->after('next_step');
                $table->index('follow_up_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('internal_notes', function (Blueprint $table): void {
            if (Schema::hasColumn('internal_notes', 'follow_up_date')) {
                $table->dropIndex(['follow_up_date']);
                $table->dropColumn('follow_up_date');
            }
            if (Schema::hasColumn('internal_notes', 'next_step')) {
                $table->dropColumn('next_step');
            }
        });
    }
};
