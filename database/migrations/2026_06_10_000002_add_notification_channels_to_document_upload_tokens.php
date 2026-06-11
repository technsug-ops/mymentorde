<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D6: Belge talep token'lara cok-kanalli bildirim destegi.
 *
 *   - notification_channels: JSON dizi (["email"], ["whatsapp"], ["email","whatsapp"])
 *   - whatsapp_first_sent_at: ilk WhatsApp mesaji gonderildi mi (idempotent)
 *   - whatsapp_final_sent_at: son uyari WhatsApp mesaji gonderildi mi
 *
 * Default ["email"] — geriye uyum: eski tokenlar otomatik email kanalindan
 * hatirlatma alir. Yeni token uretirken manager UI'dan kanal secimi yapilir.
 *
 * Addon-safe: tum kolonlar nullable / hasColumn check ile idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_upload_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('document_upload_tokens', 'notification_channels')) {
                // MySQL/MariaDB'de JSON nullable; SQLite testte text fallback'i otomatik
                $table->json('notification_channels')->nullable()->after('recipient_phone');
            }
            if (! Schema::hasColumn('document_upload_tokens', 'whatsapp_first_sent_at')) {
                $table->timestamp('whatsapp_first_sent_at')->nullable()->after('reminder_final_sent_at');
            }
            if (! Schema::hasColumn('document_upload_tokens', 'whatsapp_final_sent_at')) {
                $table->timestamp('whatsapp_final_sent_at')->nullable()->after('whatsapp_first_sent_at');
            }
        });

        // Mevcut tokenlar icin default ["email"] backfill — sadece JSON kolonu eklendiyse
        try {
            DB::table('document_upload_tokens')
                ->whereNull('notification_channels')
                ->update(['notification_channels' => json_encode(['email'])]);
        } catch (\Throwable $e) {
            // Backfill basarisiz olsa bile migration devam etsin (production safety)
        }
    }

    public function down(): void
    {
        Schema::table('document_upload_tokens', function (Blueprint $table): void {
            foreach (['whatsapp_final_sent_at', 'whatsapp_first_sent_at', 'notification_channels'] as $col) {
                if (Schema::hasColumn('document_upload_tokens', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
