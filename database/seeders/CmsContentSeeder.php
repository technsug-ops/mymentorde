<?php

namespace Database\Seeders;

use App\Models\Marketing\CmsContent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', 'marketing_admin')->first()
            ?? User::query()->where('role', 'manager')->firstOrFail();

        $uid = $admin->id;

        $entries = [
            [
                'type'        => 'landing',
                'slug'        => 'almanya-universite-basvurusu-rehberi',
                'title_tr'    => 'Almanya Üniversite Başvurusu Rehberi',
                'summary_tr'  => 'Türk öğrenciler için Almanya üniversite başvuru sürecini adım adım açıklayan kapsamlı rehber.',
                'content_tr'  => "Almanya'da üniversite okumak isteyen Türk öğrenciler için başvuru süreci birkaç temel aşamadan oluşmaktadır.\n\n**1. Dil Hazırlığı**\nAlmanca programlar için genellikle DSH-2 veya TestDaF 4x4 belgesi istenir. İngilizce programlar için IELTS 6.5 veya TOEFL 90+ yeterlidir.\n\n**2. Denklik Belgesi (APS)**\nTürkiye'de alınan diplomaların tanınması için APS (Akademik Denklik Belgesi) zorunludur. Başvuru için transkript, diploma ve kimlik belgesi gerekir.\n\n**3. Motivasyon Mektubu**\nHer üniversite için ayrı bir motivasyon mektubu yazılmalıdır. Neden bu üniversiteyi ve bölümü seçtiğinizi, kariyer hedeflerinizi açıklamalısınız.\n\n**4. Uni-Assist Başvurusu**\nÇoğu devlet üniversitesi başvurularını uni-assist.de üzerinden alır. Başvuru ücreti her üniversite için ayrıca ödenir.\n\n**5. Sperrkonto Açılışı**\nAlmanya öğrenci vizesi için yaklaşık 11.208 € teminat tutarında Sperrkonto hesabı açtırmanız zorunludur.",
                'status'      => 'published',
                'is_featured' => true,
                'featured_order' => 1,
                'target_audience' => 'all',
                'category'    => 'rehber',
                'tags'        => ['almanya', 'universite', 'basvuru', 'rehber'],
                'seo_meta_title_tr' => 'Almanya Üniversite Başvurusu Rehberi | MentorDE',
                'seo_meta_description_tr' => 'Türk öğrenciler için Almanya üniversite başvuru süreci, APS, dil belgeleri ve gerekli dokümanlar.',
            ],
            [
                'type'        => 'blog',
                'slug'        => 'aps-belgesi-nasil-alinir',
                'title_tr'    => 'APS Belgesi Nasıl Alınır?',
                'summary_tr'  => 'Akademik Denklik Belgesi (APS) için gerekli belgeler, randevu süreci ve dikkat edilmesi gerekenler.',
                'content_tr'  => "APS (Akademik Prüfstelle), Almanya'da eğitim görmek isteyen Türk öğrencilerin diplomalarının tanınması için zorunlu bir belgedir.\n\n**Gerekli Belgeler**\n- Lise/üniversite diploması (noter onaylı)\n- Transkript (tüm dönemler)\n- Nüfus cüzdanı fotokopisi\n- Pasaport fotokopisi\n- Almanca program için dil belgesi\n- Doldurulmuş başvuru formu\n\n**Başvuru Süreci**\n1. APS Türkiye web sitesinden online ön kayıt yapın\n2. Belgeleri hazırlayın ve noter onaylı kopyaları edinin\n3. Randevu için İstanbul veya Ankara ofisini seçin\n4. Mülakata gelin — Almanca bilginiz test edilebilir\n5. Sonuç genellikle 4-8 hafta içinde gelir\n\n**Önemli Notlar**\n- APS belgesi 5 yıl geçerlidir\n- Birden fazla üniversiteye başvuruyorsanız aynı belgeyi kullanabilirsiniz\n- Mülakatta sakin olun; genel akademik bilginiz değerlendirilir",
                'status'      => 'published',
                'is_featured' => true,
                'featured_order' => 2,
                'target_audience' => 'all',
                'category'    => 'blog',
                'tags'        => ['aps', 'denklik', 'belge', 'almanya'],
                'seo_meta_title_tr' => 'APS Belgesi Nasıl Alınır? | MentorDE Blog',
                'seo_meta_description_tr' => 'APS başvurusu için gereken belgeler, mülakat süreci ve ipuçları.',
            ],
            [
                'type'        => 'announcement',
                'slug'        => 'kis-donemi-2026-bilgi-gunu-duyurusu',
                'title_tr'    => 'Kış Dönemi 2026 Bilgi Günü Duyurusu',
                'summary_tr'  => 'MentorDE Kış 2026 Bilgi Günü kayıtları başladı. Almanya üniversite başvurusu hakkında tüm sorularınızı yanıtlıyoruz.',
                'content_tr'  => "MentorDE olarak her dönem düzenlediğimiz Bilgi Günü etkinliğimiz Ocak 2026'da gerçekleşecektir.\n\n**Etkinlik Detayları**\n- Tarih: 18 Ocak 2026, Pazar\n- Saat: 14:00 - 17:00 (Türkiye saati)\n- Format: Online (Zoom)\n- Kontenjan: 50 kişi\n\n**Gündem**\n1. Almanya üniversite sistemi genel bakış (30 dk)\n2. APS ve dil belgesi süreci (20 dk)\n3. Başarılı öğrenci hikayeleri (20 dk)\n4. Soru-Cevap oturumu (40 dk)\n\n**Kayıt**\nKatılım ücretsizdir. Kontenjan sınırlı olduğundan hemen kayıt olmanızı öneririz.\n\nKayıt formu için danışmanınızla iletişime geçin veya web sitemizi ziyaret edin.",
                'status'      => 'published',
                'is_featured' => false,
                'target_audience' => 'all',
                'category'    => 'duyuru',
                'tags'        => ['etkinlik', 'bilgi-gunu', 'webinar', 'kış-2026'],
            ],
            [
                'type'        => 'blog',
                'slug'        => 'almanyada-yasam-maliyeti-2026',
                'title_tr'    => 'Almanya\'da Yaşam Maliyeti 2026',
                'summary_tr'  => 'Almanya\'da öğrenci olarak aylık ne kadar bütçe ayırmalısınız? Şehir şehir karşılaştırma ve pratik ipuçları.',
                'content_tr'  => "Almanya'da öğrenci olarak yaşam maliyeti şehre göre önemli farklılıklar gösterir.\n\n**Şehirlere Göre Aylık Ortalama Gider (2026)**\n\n| Şehir | Kira | Yaşam | Toplam |\n|-------|------|-------|--------|\n| Münih | €850 | €600 | €1.450 |\n| Hamburg | €750 | €550 | €1.300 |\n| Berlin | €700 | €500 | €1.200 |\n| Frankfurt | €780 | €530 | €1.310 |\n| Köln | €650 | €480 | €1.130 |\n| Dresden | €500 | €420 | €920 |\n| Leipzig | €450 | €400 | €850 |\n\n**Tasarruf İpuçları**\n- Öğrenci yurdu (Wohnheim) kira için en ekonomik seçenek\n- Aylık toplu taşıma aboneliği (Semesterticket) genellikle ücretsiz\n- Üniversite kantininde yemek €3-5 arası\n- Yarı zamanlı çalışma: Yılda 120 tam gün veya 240 yarım gün izni\n\n**Sperrkonto Gereksinimi**\nVize başvurusu için 2026 itibarıyla yıllık €11.208 teminat tutarı zorunludur (aylık €934).",
                'status'      => 'published',
                'is_featured' => false,
                'target_audience' => 'all',
                'category'    => 'blog',
                'tags'        => ['yasam-maliyeti', 'almanya', 'butce', 'ogrenci'],
                'seo_meta_title_tr' => 'Almanya\'da Öğrenci Yaşam Maliyeti 2026 | MentorDE',
                'seo_meta_description_tr' => 'Almanya\'da şehirlere göre aylık yaşam giderleri, kira ve tasarruf ipuçları.',
            ],
            [
                'type'        => 'blog',
                'slug'        => 'almanca-dil-sertifikalari-karsilastirma',
                'title_tr'    => 'Almanca Dil Sertifikaları Karşılaştırması: DSH, TestDaF, Goethe',
                'summary_tr'  => 'Almanya\'da üniversite için hangi dil sertifikası gerekli? DSH, TestDaF ve Goethe C1 arasındaki farklar.',
                'content_tr'  => "Almanya'da Almanca dilli programlara başvuru için çeşitli dil sertifikaları kabul edilmektedir.\n\n**DSH (Deutsche Sprachprüfung für den Hochschulzugang)**\n- Üniversitelerin kendi sınavı\n- DSH-1 (B2), DSH-2 (C1), DSH-3 (C1+)\n- Genellikle yalnızca başvurduğunuz üniversitede geçerli\n- Ücretsiz veya düşük ücretli\n\n**TestDaF (Test Deutsch als Fremdsprache)**\n- Bağımsız, tüm Almanya'da geçerli\n- Her beceri için 3, 4 veya 5 puan\n- Çoğu üniversite TestDaF 4x4 (her alanda min. 4) ister\n- Yılda birkaç kez dünya genelinde yapılır\n\n**Goethe-Zertifikat C1**\n- Uluslararası tanınırlık\n- Bazı üniversiteler doğrudan kabul eder\n- İş hayatında da değerli\n\n**Hangi Sertifikayı Seçmeli?**\nEğer birden fazla üniversiteye başvuruyorsanız TestDaF en güvenli seçimdir. Tek bir üniversiteye odaklanıyorsanız DSH daha ekonomik olabilir.",
                'status'      => 'published',
                'is_featured' => true,
                'featured_order' => 3,
                'target_audience' => 'all',
                'category'    => 'blog',
                'tags'        => ['almanca', 'dsh', 'testdaf', 'goethe', 'sertifika'],
                'seo_meta_title_tr' => 'DSH vs TestDaF vs Goethe — Almanca Sertifika Karşılaştırması | MentorDE',
                'seo_meta_description_tr' => 'Almanya üniversite başvurusu için DSH, TestDaF ve Goethe C1 sertifikalarının farkları ve hangisini seçmeniz gerektiği.',
            ],
            [
                'type'        => 'faq',
                'slug'        => 'almanya-vize-sureci-sss',
                'title_tr'    => 'Almanya Öğrenci Vizesi Süreci — Sık Sorulan Sorular',
                'summary_tr'  => 'Almanya öğrenci vizesi başvurusu için en sık sorulan soruların cevapları.',
                'content_tr'  => "**Öğrenci vizesi için ne zaman başvurmalıyım?**\nKabul mektubunu aldıktan sonra mümkün olan en kısa sürede başvuruda bulunun. Randevu süresi özellikle yoğun dönemlerde 2-3 aya kadar uzayabilir.\n\n**Hangi belgeler gerekli?**\n- Geçerli pasaport\n- Kabul mektubu\n- Sperrkonto belgesi (€11.208+)\n- Sağlık sigortası belgesi\n- Biyometrik fotoğraf\n- Dil sertifikası\n- APS belgesi\n- Konut belgesi (varsa)\n\n**Sperrkonto nedir?**\nAlmanya'da yaşam giderlerinizi karşılayabileceğinizi kanıtlamak için açtırılan bloke banka hesabıdır. Her ay belirli bir miktar çekebilirsiniz.\n\n**Vize ne kadar sürede çıkar?**\nGenellikle 4-12 hafta sürer. Yoğun dönemlerde daha uzun sürebilir.\n\n**Vize reddedilirse ne yapmalıyım?**\nRed gerekçesini öğrenin, eksik belgeyi tamamlayın ve yeniden başvurun. MentorDE danışmanınız size bu süreçte destek verecektir.",
                'status'      => 'published',
                'is_featured' => false,
                'target_audience' => 'all',
                'category'    => 'rehber',
                'tags'        => ['vize', 'basvuru', 'sss', 'almanya'],
                'seo_meta_title_tr' => 'Almanya Öğrenci Vizesi SSS | MentorDE',
                'seo_meta_description_tr' => 'Almanya öğrenci vizesi başvurusu, gerekli belgeler ve Sperrkonto hakkında sık sorulan sorular.',
            ],
            [
                'type'        => 'announcement',
                'slug'        => 'mentorde-hakkinda',
                'title_tr'    => 'MentorDE Hakkında',
                'summary_tr'  => 'MentorDE olarak Türk öğrencilerin Alman yükseköğrenimine erişimini kolaylaştırıyoruz.',
                'content_tr'  => "MentorDE, Türkiye'den Almanya'ya gitmek isteyen öğrencilere profesyonel danışmanlık hizmeti sunan bir eğitim danışmanlığı şirketidir.\n\n**Misyonumuz**\nAlmanya'da eğitim almak isteyen her Türk öğrenciye erişilebilir, güvenilir ve kapsamlı danışmanlık hizmeti sunmak.\n\n**Hizmetlerimiz**\n- Üniversite seçimi ve başvuru danışmanlığı\n- APS süreci desteği\n- Dil kursu yönlendirmesi\n- Vize başvurusu rehberliği\n- Konut ve adaptasyon desteği\n- Sperrkonto açılış yardımı\n\n**Neden MentorDE?**\n- Almanya'da yaşayan ve çalışan deneyimli danışmanlar\n- Şeffaf ve dürüst süreç yönetimi\n- Başvurudan yerleşime kadar tam destek\n- Başarı odaklı hizmet anlayışı\n\n**İletişim**\nDanışmanlık başvurusu yapmak için sitemizden online başvuru formunu doldurun.",
                'status'      => 'published',
                'is_featured' => false,
                'target_audience' => 'all',
                'category'    => 'kurumsal',
                'tags'        => ['mentorde', 'hakkinda', 'danismanlik'],
            ],
            [
                'type'        => 'blog',
                'slug'        => 'uni-assist-basvuru-rehberi',
                'title_tr'    => 'uni-assist Başvuru Rehberi',
                'summary_tr'  => 'uni-assist.de üzerinden üniversite başvurusu nasıl yapılır? Adım adım detaylı anlatım.',
                'content_tr'  => "uni-assist, Almanya'daki 170'ten fazla üniversitenin uluslararası öğrenci başvurularını yönettiği merkezi bir platformdur.\n\n**uni-assist'e Kimler Başvurmalı?**\nPartner üniversitelerine başvuran uluslararası öğrenciler (Türk öğrenciler dahil).\n\n**Başvuru Adımları**\n\n1. **Hesap Oluştur** — uni-assist.de üzerinden ücretsiz kayıt yapın\n2. **Belgeleri Yükle** — Transkript, diploma, dil belgesi, APS\n3. **Üniversite Seç** — İstediğiniz programları sepete ekleyin\n4. **Ücret Öde** — İlk başvuru €75, her ek başvuru €30\n5. **Bekle** — Değerlendirme 6-10 hafta sürer\n6. **Üniversiteye İlet** — uni-assist onayından sonra üniversite doğrudan kabul kararı verir\n\n**Önemli Tarihler**\n- Kış dönemi (WS): 15 Temmuz'a kadar başvur\n- Yaz dönemi (SS): 15 Ocak'a kadar başvur\n\n**İpuçları**\n- Belgelerin Almanca veya İngilizce çevirilerini hazır edin\n- uni-assist onayı = kabul demek değildir, üniversite kendi kararını verir\n- Birden fazla üniversiteye aynı anda başvurabilirsiniz",
                'status'      => 'published',
                'is_featured' => false,
                'target_audience' => 'all',
                'category'    => 'rehber',
                'tags'        => ['uni-assist', 'basvuru', 'almanya', 'universite'],
                'seo_meta_title_tr' => 'uni-assist Başvuru Rehberi | MentorDE',
                'seo_meta_description_tr' => 'uni-assist.de üzerinden Almanya üniversite başvurusu için adım adım rehber.',
            ],
        ];

        foreach ($entries as $data) {
            $slug = $data['slug'];

            // Skip if slug already exists
            if (CmsContent::query()->where('slug', $slug)->exists()) {
                continue;
            }

            CmsContent::query()->create([
                'type'                    => $data['type'],
                'slug'                    => $slug,
                'title_tr'                => $data['title_tr'],
                'summary_tr'              => $data['summary_tr'] ?? null,
                'content_tr'              => $data['content_tr'],
                'status'                  => $data['status'] ?? 'published',
                'published_at'            => now(),
                'is_featured'             => $data['is_featured'] ?? false,
                'featured_order'          => $data['featured_order'] ?? null,
                'target_audience'         => $data['target_audience'] ?? 'all',
                'category'                => $data['category'] ?? null,
                'tags'                    => $data['tags'] ?? [],
                'seo_meta_title_tr'       => $data['seo_meta_title_tr'] ?? null,
                'seo_meta_description_tr' => $data['seo_meta_description_tr'] ?? null,
                'current_revision'        => 1,
                'created_by'              => $uid,
                'last_edited_by'          => $uid,
                'approved_by'             => $uid,
            ]);
        }
    }
}
