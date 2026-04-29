<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sözleşme onaylanmış ama ödeme bekleyen adaylar için
 * kademeli hatırlatma + manuel ödeme teyidi alanları.
 *
 * Akış:
 *  - cron L1..L4'ü otomatik gönderir, manager Level 5'i manuel tetikler
 *  - manager hatırlatmayı duraklatabilir (banka sorunu, mücbir sebep)
 *  - manager ödemeyi manuel teyit eder (otomasyon öncesi MVP)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            // En son gönderilen hatırlatma seviyesi (0 = hiç gönderilmedi, 5 = son bildirim)
            $table->unsignedTinyInteger('payment_reminder_level')->default(0)->after('contract_approved_at');
            $table->timestamp('payment_reminder_last_sent_at')->nullable()->after('payment_reminder_level');

            // Hatırlatma duraklatıldıysa
            $table->timestamp('payment_reminders_paused_at')->nullable()->after('payment_reminder_last_sent_at');
            $table->text('payment_reminders_paused_reason')->nullable()->after('payment_reminders_paused_at');
            $table->unsignedBigInteger('payment_reminders_paused_by')->nullable()->after('payment_reminders_paused_reason');

            // Manuel ödeme teyidi
            $table->timestamp('payment_received_at')->nullable()->after('payment_reminders_paused_by');
            $table->unsignedBigInteger('payment_received_by')->nullable()->after('payment_received_at');
            $table->text('payment_received_notes')->nullable()->after('payment_received_by');
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->dropColumn([
                'payment_reminder_level',
                'payment_reminder_last_sent_at',
                'payment_reminders_paused_at',
                'payment_reminders_paused_reason',
                'payment_reminders_paused_by',
                'payment_received_at',
                'payment_received_by',
                'payment_received_notes',
            ]);
        });
    }
};
