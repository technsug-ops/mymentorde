<?php

namespace Database\Seeders;

use App\Models\SeniorResponseTemplate;
use Illuminate\Database\Seeder;

class SeniorResponseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'category' => 'document',
                'title'    => 'Belge Eksik — Diploma',
                'body'     => 'Sayın {{student_name}}, diploma belgenizin yeminli tercümesi eksik görünmektedir. Lütfen en kısa sürede yükleyiniz. Son tarih: {{deadline}}.',
            ],
            [
                'category' => 'document',
                'title'    => 'Belge Reddedildi — Düşük Kalite',
                'body'     => 'Sayın {{student_name}}, yüklediğiniz belge okunaklı değil. Lütfen daha yüksek çözünürlükte tarayıp tekrar yükleyiniz.',
            ],
            [
                'category' => 'visa',
                'title'    => 'Vize Randevusu Bilgilendirme',
                'body'     => 'Sayın {{student_name}}, {{university}} için vize randevunuz yaklaşmaktadır. Lütfen şu belgelerin hazır olduğundan emin olun: pasaport, bloke hesap dekontu, sağlık sigortası, kabul mektubu.',
            ],
            [
                'category' => 'language',
                'title'    => 'Dil Kursu Hatırlatma',
                'body'     => 'Sayın {{student_name}}, Almanca dil seviyenizi B1/B2 seviyesine çıkarmanız gerekmektedir. Önerilen kurs seçenekleri için materyaller bölümünü inceleyiniz.',
            ],
            [
                'category' => 'housing',
                'title'    => 'Konaklama Araştırması',
                'body'     => 'Sayın {{student_name}}, {{university}} için yurt başvuru süreci başlamıştır. Başvuru linki ve gerekli belgeler aşağıdadır.',
            ],
            [
                'category' => 'payment',
                'title'    => 'Ödeme Hatırlatma',
                'body'     => 'Sayın {{student_name}}, taksit ödemenizin vadesi yaklaşmaktadır. Lütfen ödeme planınızı kontrol ediniz.',
            ],
        ];

        foreach ($templates as $tpl) {
            SeniorResponseTemplate::firstOrCreate(
                ['title' => $tpl['title'], 'company_id' => null, 'owner_user_id' => null],
                array_merge($tpl, ['is_active' => true, 'usage_count' => 0])
            );
        }
    }
}
