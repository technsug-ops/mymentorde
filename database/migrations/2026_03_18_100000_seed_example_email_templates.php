<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $adminId = DB::table('users')->orderBy('id')->value('id');

        // Test ortamında (RefreshDatabase) henüz kullanıcı yok → seed atlanır.
        if ($adminId === null) {
            return;
        }

        $templates = [
            // 1 — Hoş Geldin (automated)
            [
                'name'                  => 'Hoş Geldin E-postası',
                'type'                  => 'automated',
                'category'              => 'welcome',
                'trigger_event'         => 'guest_registered',
                'trigger_delay_minutes' => 0,
                'trigger_conditions'    => json_encode([]),
                'trigger_is_active'     => 1,
                'subject_tr'            => 'Almanya yolculuğuna hoş geldin, {{name}}!',
                'subject_de'            => 'Willkommen bei MentorDE, {{name}}!',
                'subject_en'            => null,
                'body_tr'               => <<<BODY
Merhaba {{name}},

MentorDE ailesine katıldığın için çok mutluyuz!

Almanya'da eğitim ve kariyer hedeflerine ulaşman için yanındayız. Başvuru sürecin boyunca danışmanın {{advisor_name}} seninle iletişime geçecek.

Bir sonraki adım olarak profilini tamamlamanı öneriyoruz:
→ {{portal_url}}

Herhangi bir sorun olursa bize her zaman ulaşabilirsin.

Başarılar dileriz,
MentorDE Ekibi
BODY,
                'body_de'               => null,
                'body_en'               => null,
                'available_placeholders'=> json_encode(['name','email','advisor_name','portal_url']),
                'from_name'             => 'MentorDE',
                'from_email'            => 'noreply@mentorde.com',
                'reply_to'              => 'destek@mentorde.com',
                'is_active'             => 1,
                'stat_total_sent'       => 0,
                'stat_open_rate'        => 0,
                'stat_click_rate'       => 0,
                'created_by'            => $adminId,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // 2 — Belge Hatırlatma (automated)
            [
                'name'                  => 'Eksik Belge Hatırlatması',
                'type'                  => 'automated',
                'category'              => 'reminder',
                'trigger_event'         => 'document_missing',
                'trigger_delay_minutes' => 1440, // 24 saat
                'trigger_conditions'    => json_encode(['days_inactive' => 3]),
                'trigger_is_active'     => 1,
                'subject_tr'            => '{{name}}, {{missing_count}} belgen eksik — son {{deadline}} günün kaldı',
                'subject_de'            => '{{name}}, {{missing_count}} Dokument(e) fehlen noch',
                'subject_en'            => null,
                'body_tr'               => <<<BODY
Merhaba {{name}},

Başvuru dosyanda henüz yüklenmemiş {{missing_count}} belge bulunuyor:

{{missing_documents}}

Bu belgeler olmadan başvurunu tamamlayamıyoruz. Lütfen portal üzerinden yüklemeni rica ediyoruz:
→ {{documents_url}}

Son yükleme tarihi: {{deadline}}

Yardıma ihtiyaç duyarsan danışmanın {{advisor_name}} seninle iletişime geçecektir.

İyi günler,
MentorDE Ekibi
BODY,
                'body_de'               => null,
                'body_en'               => null,
                'available_placeholders'=> json_encode(['name','advisor_name','missing_count','missing_documents','documents_url','deadline']),
                'from_name'             => 'MentorDE',
                'from_email'            => 'noreply@mentorde.com',
                'reply_to'              => 'destek@mentorde.com',
                'is_active'             => 1,
                'stat_total_sent'       => 0,
                'stat_open_rate'        => 0,
                'stat_click_rate'       => 0,
                'created_by'            => $adminId,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // 3 — Randevu Onayı (automated)
            [
                'name'                  => 'Randevu Onayı',
                'type'                  => 'automated',
                'category'              => 'appointment',
                'trigger_event'         => 'appointment_created',
                'trigger_delay_minutes' => 0,
                'trigger_conditions'    => json_encode([]),
                'trigger_is_active'     => 1,
                'subject_tr'            => 'Randevun onaylandı — {{appointment_date}}',
                'subject_de'            => 'Dein Termin ist bestätigt — {{appointment_date}}',
                'subject_en'            => null,
                'body_tr'               => <<<BODY
Merhaba {{name}},

{{advisor_name}} ile randevun oluşturuldu.

Tarih   : {{appointment_date}}
Saat    : {{appointment_time}}
Tür     : {{appointment_type}}
{{#meeting_url}}Toplantı: {{meeting_url}}{{/meeting_url}}

Randevuya katılamayacaksan lütfen en az 24 saat öncesinden bize bildir.

İyi günler,
MentorDE Ekibi
BODY,
                'body_de'               => null,
                'body_en'               => null,
                'available_placeholders'=> json_encode(['name','advisor_name','appointment_date','appointment_time','appointment_type','meeting_url']),
                'from_name'             => 'MentorDE',
                'from_email'            => 'noreply@mentorde.com',
                'reply_to'              => 'destek@mentorde.com',
                'is_active'             => 1,
                'stat_total_sent'       => 0,
                'stat_open_rate'        => 0,
                'stat_click_rate'       => 0,
                'created_by'            => $adminId,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // 4 — Sözleşme İmza Daveti (automated)
            [
                'name'                  => 'Sözleşme İmza Daveti',
                'type'                  => 'automated',
                'category'              => 'contract',
                'trigger_event'         => 'contract_sent',
                'trigger_delay_minutes' => 0,
                'trigger_conditions'    => json_encode([]),
                'trigger_is_active'     => 1,
                'subject_tr'            => 'Sözleşmen hazır — imzalamak için tıkla',
                'subject_de'            => 'Dein Vertrag wartet auf deine Unterschrift',
                'subject_en'            => null,
                'body_tr'               => <<<BODY
Merhaba {{name}},

MentorDE hizmet sözleşmen hazırlandı ve imzanı bekliyor.

Sözleşmeyi incelemek ve imzalamak için aşağıdaki bağlantıyı kullan:
→ {{contract_url}}

Sözleşme {{contract_expiry_date}} tarihine kadar geçerlidir. Bu tarihten sonra yeni bir sözleşme gönderilecektir.

Sorularını destek ekibimize iletebilirsin.

Saygılarımızla,
MentorDE Ekibi
BODY,
                'body_de'               => null,
                'body_en'               => null,
                'available_placeholders'=> json_encode(['name','contract_url','contract_expiry_date']),
                'from_name'             => 'MentorDE',
                'from_email'            => 'noreply@mentorde.com',
                'reply_to'              => 'destek@mentorde.com',
                'is_active'             => 1,
                'stat_total_sent'       => 0,
                'stat_open_rate'        => 0,
                'stat_click_rate'       => 0,
                'created_by'            => $adminId,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // 5 — Kampanya: WS 2026/27 Başvuru (manual)
            [
                'name'                  => 'WS 2026/27 Başvuru Duyurusu',
                'type'                  => 'manual',
                'category'              => 'campaign',
                'trigger_event'         => null,
                'trigger_delay_minutes' => 0,
                'trigger_conditions'    => json_encode([]),
                'trigger_is_active'     => 0,
                'subject_tr'            => "Almanya'da WS 2026/27 başvuruları başladı — hemen başvur!",
                'subject_de'            => 'Bewerbungsstart WS 2026/27 — Jetzt bewerben!',
                'subject_en'            => null,
                'body_tr'               => <<<BODY
Merhaba {{name}},

Almanya'daki üniversiteler için WS 2026/27 dönemi başvuruları başladı!

Bu dönem için kontenjanlar sınırlıdır. Erken başvuran adaylar kabul sürecinde öncelikli değerlendirme almaktadır.

Neden MentorDE?
✓ Almanya'da 200+ üniversite ile iş birliği
✓ Vize ve dil sınavı desteği
✓ Adım adım başvuru rehberliği
✓ Sperrkonto ve sigorta yardımı

Hemen başvurmak için:
→ {{apply_url}}

Veya danışmanımızla görüşmek için randevu al:
→ {{booking_url}}

Fırsatı kaçırma!

MentorDE Ekibi
BODY,
                'body_de'               => null,
                'body_en'               => null,
                'available_placeholders'=> json_encode(['name','apply_url','booking_url']),
                'from_name'             => 'MentorDE Marketing',
                'from_email'            => 'kampanya@mentorde.com',
                'reply_to'              => 'info@mentorde.com',
                'is_active'             => 1,
                'stat_total_sent'       => 0,
                'stat_open_rate'        => 0,
                'stat_click_rate'       => 0,
                'created_by'            => $adminId,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],

            // 6 — Re-Engagement (manual)
            [
                'name'                  => 'Re-Engagement — Seni Bekleriz',
                'type'                  => 'manual',
                'category'              => 're_engagement',
                'trigger_event'         => null,
                'trigger_delay_minutes' => 0,
                'trigger_conditions'    => json_encode([]),
                'trigger_is_active'     => 0,
                'subject_tr'            => '{{name}}, Almanya hedeflerin hâlâ seni bekliyor',
                'subject_de'            => '{{name}}, deine Ziele warten noch auf dich',
                'subject_en'            => null,
                'body_tr'               => <<<BODY
Merhaba {{name}},

Bir süredir senden haber alamadık. Almanya'da eğitim hedeflerin hâlâ gündeminde mi?

Bugün başvurunu tamamlarsan {{benefit}} avantajından yararlanabilirsin.

Başvuruna kaldığın yerden devam et:
→ {{portal_url}}

Sorularını yanıtlamak için buradayız.

Sevgilerle,
MentorDE Ekibi
BODY,
                'body_de'               => null,
                'body_en'               => null,
                'available_placeholders'=> json_encode(['name','benefit','portal_url']),
                'from_name'             => 'MentorDE',
                'from_email'            => 'noreply@mentorde.com',
                'reply_to'              => 'destek@mentorde.com',
                'is_active'             => 1,
                'stat_total_sent'       => 0,
                'stat_open_rate'        => 0,
                'stat_click_rate'       => 0,
                'created_by'            => $adminId,
                'created_at'            => $now,
                'updated_at'            => $now,
            ],
        ];

        // Sadece tablo boşsa ekle (idempotent)
        if (DB::table('email_templates')->count() === 0) {
            DB::table('email_templates')->insert($templates);
        }
    }

    public function down(): void
    {
        DB::table('email_templates')
            ->whereIn('category', ['welcome','reminder','appointment','contract','campaign','re_engagement'])
            ->delete();
    }
};
