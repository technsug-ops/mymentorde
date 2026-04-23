<?php

namespace Database\Seeders;

use App\Models\ActionTemplate;
use Illuminate\Database\Seeder;

class DefaultActionTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // WhatsApp - Aday re-engagement
            [
                'name'        => 'Aday Re-engagement',
                'channel'     => 'whatsapp',
                'target_type' => 'guest',
                'subject'     => null,
                'body'        => "Merhaba {{first_name}}, ben {{senior_name}}, {{company_name}} ekibinden. "
                               . "Almanya'da eğitim hayalin hakkında geçen konuştuklarımıza dönmek istedim. "
                               . "Senin için uygun bir zaman var mı? 15 dakikalık kısa bir görüşme olur.",
                'variables'   => 'first_name, senior_name, company_name',
            ],
            // WhatsApp - Booking link
            [
                'name'        => 'Randevu Linki Paylaş',
                'channel'     => 'whatsapp',
                'target_type' => 'both',
                'subject'     => null,
                'body'        => "Merhaba {{first_name}}! {{company_name}} ekibinden ücretsiz ön-değerlendirme "
                               . "randevusu oluşturabilirsin: https://panel.mentorde.com/randevu — "
                               . "uygun saati seç, anında onay alırsın.",
                'variables'   => 'first_name, company_name',
            ],
            // WhatsApp - Ödeme hatırlatma
            [
                'name'        => 'Öğrenci Ödeme Hatırlatma',
                'channel'     => 'whatsapp',
                'target_type' => 'student',
                'subject'     => null,
                'body'        => "Merhaba {{first_name}}, vadesi geçmiş bir ödemen var. "
                               . "Ödeme linkine tıklayarak hızlıca tamamlayabilirsin. "
                               . "Bir sıkıntı varsa bana direkt yazabilirsin — çözeriz.",
                'variables'   => 'first_name',
            ],
            // Email - Aday re-engagement
            [
                'name'        => 'Aday Re-engagement Email',
                'channel'     => 'email',
                'target_type' => 'guest',
                'subject'     => 'Almanya eğitim planın için bir dakikan var mı?',
                'body'        => "Merhaba {{first_name}},\n\n"
                               . "Birkaç hafta önce Almanya'da eğitim hayalin için bizimle iletişime geçmiştin. "
                               . "Süreç yoğun geçiyor, anlıyorum — ama doğru adımları şimdi atmak önemli.\n\n"
                               . "15 dakikalık kısa bir görüşmeyle nerede olduğunu görelim ve bir sonraki adımı planlayalım. "
                               . "Uygun bir saati şuradan seçebilirsin: https://panel.mentorde.com/randevu\n\n"
                               . "Herhangi bir sorun için bana direkt yanıtlayabilirsin.\n\n"
                               . "Sevgiler,\n{{senior_name}}\n{{company_name}}",
                'variables'   => 'first_name, senior_name, company_name',
            ],
            // Email - Öğrenci motivasyon
            [
                'name'        => 'Öğrenci Motivasyon',
                'channel'     => 'email',
                'target_type' => 'student',
                'subject'     => 'Seninle ilgilenmek istiyorum, {{first_name}}',
                'body'        => "Merhaba {{first_name}},\n\n"
                               . "Bir süredir platformda görüşmedik. Süreçte takıldığın bir nokta var mı?\n\n"
                               . "Ben {{senior_name}}, herhangi bir konuda yardımcı olmak için buradayım. "
                               . "Kısa bir görüşme yapalım mı?\n\n"
                               . "Sevgiler,\n{{senior_name}}",
                'variables'   => 'first_name, senior_name',
            ],
            // Çağrı scripti
            [
                'name'        => 'Çağrı Scripti — Aday ilk aranışta',
                'channel'     => 'call_script',
                'target_type' => 'guest',
                'subject'     => null,
                'body'        => "1. Kendini tanıt: 'Merhaba {{first_name}}, ben {{senior_name}}, {{company_name}} ekibinden.'\n\n"
                               . "2. Amacı söyle: 'Başvurunu aldık, süreci değerlendirmek ve sana en uygun yolu göstermek istiyorum.'\n\n"
                               . "3. Durumunu sor: 'Şu an hangi aşamadasın? Üniversite seçimi yaptın mı, dil sertifikan var mı?'\n\n"
                               . "4. İlgi alanı: 'Seni en çok endişelendiren konu ne? Vize mi, konut mu, maliyet mi?'\n\n"
                               . "5. Sonraki adım: 'Önümüzdeki hafta 30 dk'lık online görüşme yapalım, yol haritanı çıkaralım.'",
                'variables'   => 'first_name, senior_name, company_name',
            ],
            // Not template
            [
                'name'        => 'Hızlı Not — Telefonda konuştum',
                'channel'     => 'note',
                'target_type' => 'both',
                'subject'     => null,
                'body'        => "Telefonda konuştum. Durum: [_____]. İlgilendiği konu: [_____]. Sonraki adım: [_____].",
                'variables'   => '',
            ],
        ];

        foreach ($templates as $tpl) {
            // Her company için default oluştur — company_id=null = global template
            ActionTemplate::updateOrCreate(
                ['name' => $tpl['name'], 'channel' => $tpl['channel'], 'company_id' => null],
                array_merge($tpl, ['is_active' => true, 'company_id' => null])
            );
        }
    }
}
