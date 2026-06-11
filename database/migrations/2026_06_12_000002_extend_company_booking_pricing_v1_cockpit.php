<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace Phase 4 — Manager Pricing Cockpit v1
 *
 * Mevcut company_booking_pricing tablosuna 3 yeni alan ekler:
 *  - allow_invitee_reschedule: invitee (öğrenci/guest) randevuyu yeniden tarihlendirebilir mi
 *  - max_advance_booking_days: bugünden kaç gün ileriye randevu alınabilir
 *  - booking_terms: randevu sayfası footer'ına basılacak şartlar metni
 *
 * Nullable + default'lu — addon prensibi (eski kayıtlar bozulmaz).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_booking_pricing', function (Blueprint $t): void {
            if (!Schema::hasColumn('company_booking_pricing', 'allow_invitee_reschedule')) {
                $t->boolean('allow_invitee_reschedule')->default(true)->after('cancellation_window_hours');
            }
            if (!Schema::hasColumn('company_booking_pricing', 'max_advance_booking_days')) {
                $t->unsignedSmallInteger('max_advance_booking_days')->default(60)->after('allow_invitee_reschedule');
            }
            if (!Schema::hasColumn('company_booking_pricing', 'booking_terms')) {
                $t->text('booking_terms')->nullable()->after('max_advance_booking_days');
            }
            if (!Schema::hasColumn('company_booking_pricing', 'is_free')) {
                $t->boolean('is_free')->default(false);
            }
        });

        // Index'ler (varsa atla)
        try {
            Schema::table('company_booking_pricing', function (Blueprint $t): void {
                $t->index('is_free', 'company_booking_pricing_is_free_idx');
            });
        } catch (\Throwable $e) {
            // Index zaten varsa veya backend desteklemiyorsa sessiz geç
        }
    }

    public function down(): void
    {
        Schema::table('company_booking_pricing', function (Blueprint $t): void {
            try { $t->dropIndex('company_booking_pricing_is_free_idx'); } catch (\Throwable $e) {}

            foreach (['allow_invitee_reschedule', 'max_advance_booking_days', 'booking_terms'] as $col) {
                if (Schema::hasColumn('company_booking_pricing', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
