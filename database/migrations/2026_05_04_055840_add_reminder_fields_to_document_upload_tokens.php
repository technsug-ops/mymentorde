<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D7: Hatirlatma feature'i icin token'a alici bilgisi + reminder timestamps.
 *
 * - recipient_email: hatirlatma maili icin hedef adres (manager modaldan girer)
 * - recipient_phone: WhatsApp/SMS gelecek (D6/D7 phase 2)
 * - reminder_first_sent_at: 24h ilk hatirlatma gonderildi mi (idempotent)
 * - reminder_final_sent_at: expire'a 1 saat kala son uyari gonderildi mi
 *
 * Ikiisi NULL ise henuz hatirlatma yapilmamis demektir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_upload_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_upload_tokens', 'recipient_email')) {
                $table->string('recipient_email', 180)->nullable()->after('created_by_user_id');
            }
            if (! Schema::hasColumn('document_upload_tokens', 'recipient_phone')) {
                $table->string('recipient_phone', 50)->nullable()->after('recipient_email');
            }
            if (! Schema::hasColumn('document_upload_tokens', 'reminder_first_sent_at')) {
                $table->timestamp('reminder_first_sent_at')->nullable()->after('recipient_phone');
            }
            if (! Schema::hasColumn('document_upload_tokens', 'reminder_final_sent_at')) {
                $table->timestamp('reminder_final_sent_at')->nullable()->after('reminder_first_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_upload_tokens', function (Blueprint $table): void {
            foreach (['reminder_final_sent_at', 'reminder_first_sent_at', 'recipient_phone', 'recipient_email'] as $col) {
                if (Schema::hasColumn('document_upload_tokens', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
