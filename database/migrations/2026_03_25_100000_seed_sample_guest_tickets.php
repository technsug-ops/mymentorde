<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dev ortamı: Görev Panosu "Ticketler" sekmesi için örnek ticketlar oluşturur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guest_tickets') || ! Schema::hasTable('guest_applications')) {
            return;
        }

        // Mevcut company_id (ilk şirketi al, yoksa 1 kullan)
        $companyId = DB::table('companies')->value('id') ?? 1;

        // Bir guest application bul ya da oluştur (FK zorunlu)
        $guestApp = DB::table('guest_applications')
            ->whereNull('deleted_at')
            ->first();

        if (! $guestApp) {
            return; // Guest application yoksa (üretim ortamı vb.) pas geç
        }

        $guestAppId = $guestApp->id;

        // Zaten örnek ticketlar eklendiyse tekrar ekleme
        $existing = DB::table('guest_tickets')
            ->where('company_id', $companyId)
            ->where('subject', 'like', '[ÖRNEK]%')
            ->count();

        if ($existing > 0) {
            return;
        }

        $now = now();

        $samples = [
            [
                'company_id'            => $companyId,
                'guest_application_id'  => $guestAppId,
                'subject'               => '[ÖRNEK] Pasaport kopyası yükleme sorunu',
                'message'               => 'Pasaport kopyamı sisteme yüklemeye çalışıyorum fakat "Dosya boyutu aşıldı" hatası alıyorum. Dosyam 2.4 MB, PDF formatında. Nasıl çözebilirim?',
                'status'                => 'open',
                'priority'              => 'high',
                'department'            => 'operations',
                'created_by_email'      => $guestApp->email ?? 'guest@example.com',
                'last_replied_at'       => null,
                'created_at'            => $now->copy()->subHours(3),
                'updated_at'            => $now->copy()->subHours(3),
            ],
            [
                'company_id'            => $companyId,
                'guest_application_id'  => $guestAppId,
                'subject'               => '[ÖRNEK] Sözleşme imzalama süreci hakkında bilgi',
                'message'               => "Sözleşmeyi ne zaman imzalamam gerekiyor? Danışmanımdan haber beklemem mi gerekiyor yoksa kendiniz mi iletişime geçeceksiniz? Avrupa'dan bağlanıyorum, toplantı saati Türkiye saatine göre mi planlanacak?",
                'status'                => 'in_progress',
                'priority'              => 'normal',
                'department'            => 'advisory',
                'created_by_email'      => $guestApp->email ?? 'guest@example.com',
                'last_replied_at'       => $now->copy()->subHours(1),
                'created_at'            => $now->copy()->subDays(1),
                'updated_at'            => $now->copy()->subHours(1),
            ],
            [
                'company_id'            => $companyId,
                'guest_application_id'  => $guestAppId,
                'subject'               => '[ÖRNEK] Banka hesabı (Sperrkonto) açılış ücreti hakkında',
                'message'               => 'Sperrkonto açılışı için ne kadar ücret ödeyeceğim? Bu tutarı peşin mi yoksa taksitli mi ödeyebilirim? Hangi banka ile çalışıyorsunuz?',
                'status'                => 'waiting_response',
                'priority'              => 'normal',
                'department'            => 'finance',
                'created_by_email'      => $guestApp->email ?? 'guest@example.com',
                'last_replied_at'       => $now->copy()->subDays(2),
                'created_at'            => $now->copy()->subDays(3),
                'updated_at'            => $now->copy()->subDays(2),
            ],
            [
                'company_id'            => $companyId,
                'guest_application_id'  => $guestAppId,
                'subject'               => '[ÖRNEK] Vize randevusu için acil yardım',
                'message'               => 'Vize randevum 10 gün sonra fakat apostilli transkriptim henüz hazır değil. Bu durumda randevumu ertelememem gerekir mi? Konsolosluğa gerekli belgeleri eksik götürsem ne olur?',
                'status'                => 'open',
                'priority'              => 'urgent',
                'department'            => 'operations',
                'created_by_email'      => $guestApp->email ?? 'guest@example.com',
                'last_replied_at'       => null,
                'created_at'            => $now->copy()->subMinutes(45),
                'updated_at'            => $now->copy()->subMinutes(45),
            ],
            [
                'company_id'            => $companyId,
                'guest_application_id'  => $guestAppId,
                'subject'               => '[ÖRNEK] Üniversite kabul mektubunu nasıl indiririm?',
                'message'               => 'Üniversiteden kabul mektubu geldiğini öğrendim ama portal üzerinden nasıl indireceğimi bulamıyorum. Belge listesinde görünmüyor.',
                'status'                => 'open',
                'priority'              => 'low',
                'department'            => 'advisory',
                'created_by_email'      => $guestApp->email ?? 'guest@example.com',
                'last_replied_at'       => null,
                'created_at'            => $now->copy()->subDays(1)->subHours(2),
                'updated_at'            => $now->copy()->subDays(1)->subHours(2),
            ],
        ];

        DB::table('guest_tickets')->insert($samples);
    }

    public function down(): void
    {
        DB::table('guest_tickets')
            ->where('subject', 'like', '[ÖRNEK]%')
            ->delete();
    }
};
