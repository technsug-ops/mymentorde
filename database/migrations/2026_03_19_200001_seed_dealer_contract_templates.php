<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        // Template 1: Dealer Referans Ortaklığı Sözleşmesi
        $referral = <<<'TXT'
DEALER REFERANS ORTAKLIĞI SÖZLEŞMESİ

Sözleşme No: {{sozlesme_no}}
Tarih: {{sozlesme_tarihi}}

─────────────────────────────────────────────────────────────

TARAFLAR

MentorDE Eğitim Danışmanlık A.Ş.
Adres: {{mentorde_adres}}
Vergi No: {{mentorde_vergi_no}}
Yetkili: {{mentorde_yetkili}}
(Bundan böyle "MentorDE" olarak anılacaktır)

{{dealer_firma_adi}}
Yetkili: {{dealer_yetkili_adi}}
Adres: {{dealer_adres}}
Vergi No: {{dealer_vergi_no}}
Telefon: {{dealer_telefon}}
E-posta: {{dealer_eposta}}
(Bundan böyle "Dealer" olarak anılacaktır)

─────────────────────────────────────────────────────────────

MADDE 1 — KONU VE KAPSAM

1.1. Bu Sözleşme, Dealer'ın MentorDE'nin Almanya yükseköğretim danışmanlık hizmetleri için aday/müşteri (bundan sonra "öğrenci" olarak anılacaktır) yönlendirmesi karşılığında belirlenmiş komisyon ilkelerine göre ücret almasını düzenler.

1.2. Dealer, kendi müşteri ağındaki öğrenci adaylarını MentorDE sistemine kayıt ederek yönlendirir. Hizmet sunumu, fiyatlandırma ve operasyonel süreçler tamamen MentorDE'nin sorumluluğundadır.

1.3. Dealer, MentorDE adına bağımsız olarak hizmet sunamaz; hizmet teklifleri yapamaz; resmi belge imzalayamaz.

─────────────────────────────────────────────────────────────

MADDE 2 — KOMİSYON YAPISI

2.1. MentorDE, Dealer tarafından sisteme kaydedilen ve MentorDE hizmetini satın alan öğrenciler için aşağıdaki komisyon dilimlerini uygular:

Yıllık Aktif Öğrenci Sayısı — Komisyon Oranı
0–{{komisyon_tier1_adet}} öğrenci: %{{komisyon_tier1_oran}}
{{komisyon_tier2_alt}}–{{komisyon_tier2_ust}} öğrenci: %{{komisyon_tier2_oran}}
{{komisyon_tier3_alt}}–{{komisyon_tier3_ust}} öğrenci: %{{komisyon_tier3_oran}}
{{komisyon_tier4_alt}}–{{komisyon_tier4_ust}} öğrenci: %{{komisyon_tier4_oran}}
{{komisyon_tier5_alt}}+ öğrenci: %{{komisyon_tier5_oran}}

2.2. Komisyon hesabı, öğrencinin ilk sözleşme bedelini ödediği tarihteki aktif öğrenci dilimine göre belirlenir.

2.3. Komisyon tutarı, öğrencinin ödemesinin MentorDE hesaplarına geçmesinden sonraki 10 (on) iş günü içinde hesaplanır ve Dealer'a bildirilir.

─────────────────────────────────────────────────────────────

MADDE 3 — KOMİSYON ÖDEMESİ

3.1. Komisyon ödemeleri, hak kazanılan tutarın tahakkuk etmesini takip eden ayın son iş günü aktarılır.

3.2. Ödeme, Dealer'ın MentorDE Dealer Paneli'nde tanımladığı banka hesabına (IBAN) Türk Lirası veya mutabık kalınan döviz üzerinden yapılır.

3.3. Minimum ödeme eşiği: {{min_odeme_esigi}} TL. Tutarın bu eşiğin altında kalması durumunda bir sonraki döneme aktarılır.

3.4. Her ödemeyle birlikte Dealer'a ayrıntılı bir komisyon dökümü iletilir.

3.5. Vergi yükümlülükleri: Dealer, kendi vergi yükümlülüklerinden sorumludur ve talep edildiğinde ilgili belgeleri sağlamakla yükümlüdür.

─────────────────────────────────────────────────────────────

MADDE 4 — DEALER'IN YÜKÜMLÜLÜKLERİ

4.1. Dealer, MentorDE sistemine yönlendirdiği adaylarla ilgili doğru, eksiksiz ve güncel bilgi sağlamakla yükümlüdür.

4.2. Dealer, adaylarına MentorDE'nin resmi hizmet paketleri ve fiyatları dışında vaat veya taahhütte bulunamaz.

4.3. Dealer, MentorDE markasını yalnızca onaylı materyaller çerçevesinde kullanabilir.

4.4. Dealer, günlük operasyonel yükümlülükleri için ayrıca imzaladığı "Dealer Operasyon Sözleşmesi"ne uymakla yükümlüdür.

─────────────────────────────────────────────────────────────

MADDE 5 — MentorDE'NİN YÜKÜMLÜLÜKLERİ

5.1. MentorDE, Dealer'a Dealer Paneli erişimi ve operasyonel destek sağlar.

5.2. MentorDE, komisyon hesaplamalarını şeffaf ve denetlenebilir biçimde yapar; Dealer Paneli üzerinden raporlar sunar.

5.3. MentorDE, sisteme tanımlı öğrencilerin başvuru süreçlerini mesleki standartlarda yürütür.

─────────────────────────────────────────────────────────────

MADDE 6 — GİZLİLİK

6.1. Taraflar, bu Sözleşme kapsamında edindikleri ticari bilgileri, komisyon yapısını ve müşteri verilerini gizli tutar.

6.2. Gizlilik yükümlülüğü Sözleşme'nin sona ermesinden sonra da 2 (iki) yıl süreyle devam eder.

─────────────────────────────────────────────────────────────

MADDE 7 — SÜRE VE FESİH

7.1. Bu Sözleşme, imzalandığı tarihte yürürlüğe girer ve {{sozlesme_bitis_tarihi}} tarihine kadar geçerlidi. Taraflarca feshedilmediği takdirde her yıl otomatik olarak yenilenir.

7.2. Her iki taraf da 30 (otuz) gün öncesinden yazılı bildirim yaparak sözleşmeyi feshedebilir.

7.3. Aşağıdaki durumlar Sözleşme'nin derhal feshine gerekçe oluşturur:
a) Dealer'ın sahte veya yanıltıcı bilgi sunması
b) Dealer'ın rakip bir firma adına öğrenci yönlendirmesi
c) Veri güvenliği ihlali
d) İmzalanan Dealer Operasyon Sözleşmesi'ndeki yükümlülüklerin ağır biçimde ihlal edilmesi

7.4. Fesih durumunda, fesih tarihine kadar hak kazanılmış komisyonlar ödenir.

─────────────────────────────────────────────────────────────

MADDE 8 — UYGULANACAK HUKUK VE UYUŞMAZLIK ÇÖZÜMÜ

8.1. Bu Sözleşme Türk Hukuku'na tabidir.

8.2. Uyuşmazlıklar önce Dealer Paneli kayıtları esas alınarak dostane yollarla çözülmeye çalışılır. Otuz (30) gün içinde çözüm sağlanamazsa {{yetkili_mahkeme}} Mahkemeleri yetkilidir.

─────────────────────────────────────────────────────────────

MADDE 9 — YÜRÜRLÜK

Bu Sözleşme iki (2) nüsha olarak hazırlanmış ve taraflarca imzalanmıştır.

MentorDE Eğitim Danışmanlık A.Ş.               {{dealer_firma_adi}}
Adı Soyadı: _______________________            Adı Soyadı: _______________________
Unvanı: ___________________________            Unvanı: ___________________________
Tarih: ____________________________            Tarih: ____________________________
İmza/Kaşe: ________________________            İmza/Kaşe: ________________________
TXT;

        // Template 2: Dealer Operasyon Sözleşmesi
        $operations = <<<'TXT'
DEALER OPERASYON SÖZLEŞMESİ

Sözleşme No: {{sozlesme_no}}
Tarih: {{sozlesme_tarihi}}

─────────────────────────────────────────────────────────────

TARAFLAR

MentorDE Eğitim Danışmanlık A.Ş.
Adres: {{mentorde_adres}}
Vergi No: {{mentorde_vergi_no}}
Yetkili: {{mentorde_yetkili}}
(Bundan böyle "MentorDE" olarak anılacaktır)

{{dealer_firma_adi}}
Yetkili: {{dealer_yetkili_adi}}
Adres: {{dealer_adres}}
Vergi No: {{dealer_vergi_no}}
Telefon: {{dealer_telefon}}
E-posta: {{dealer_eposta}}
(Bundan böyle "Dealer" olarak anılacaktır)

─────────────────────────────────────────────────────────────

AMAÇ VE KAPSAM

İşbu Dealer Operasyon Sözleşmesi, MentorDE ile Dealer arasındaki günlük operasyonel ilişkiyi düzenlemek amacıyla hazırlanmıştır. Sözleşme; sistem kullanımı, bilgi kalitesi, iletişim standartları, eğitim, marka kullanımı, veri koruma ve denetim konularını kapsar. Ticari ve finansal koşullar, ayrıca imzalanan Referans Ortaklığı Sözleşmesi'nde belirlenir; işbu Sözleşme o belgeyi tamamlar.

─────────────────────────────────────────────────────────────

MADDE 1 — SİSTEM KULLANIMI VE ERİŞİM GÜVENLİĞİ

1.1. MentorDE, Dealer'a MentorDE ERP platformu üzerindeki Dealer Paneli'ne erişim sağlar. Dealer, bu erişimi yalnızca yetkili çalışanları aracılığıyla kullanır.

1.2. Panel erişim kimlik bilgileri Dealer'ın münhasır sorumluluğundadır. Bu bilgiler üçüncü şahıslarla paylaşılamaz.

1.3. Dealer çalışanı işten ayrıldığında veya görev değişikliğinde, Dealer yönetimi bu durumu en geç 1 (bir) iş günü içinde MentorDE'ye bildirir.

1.4. Sistem güvenliğini tehlikeye atan her türlü kullanım sözleşmenin derhal feshine gerekçe oluşturur.

─────────────────────────────────────────────────────────────

MADDE 2 — ADAY BİLGİSİ KALİTESİ VE VERİ DOĞRULUĞU

2.1. Dealer, Dealer Paneli'ne kaydedeceği her aday için kimlik, iletişim, akademik geçmiş ve program tercihlerini eksiksiz ve doğru girer.

2.2. Gerçeğe aykırı veya eksik bilgi kaydından doğan zarardan Dealer sorumludur.

2.3. Dealer, aday bilgilerinde değişiklik olması durumunda bu değişikliği en geç 24 (yirmi dört) saat içinde günceller.

─────────────────────────────────────────────────────────────

MADDE 3 — İLETİŞİM STANDARTLARI VE YANIT SÜRELERİ

3.1. Tüm resmi iletişim Dealer Paneli üzerinden yürütülür.

3.2. Yanıt süreleri:
— Acil bildirimler: {{max_yanit_suresi_saat}} saat
— Rutin soru ve talepler: 1 iş günü
— Öğrenci şikayetiyle ilgili bildirimler: 4 saat

─────────────────────────────────────────────────────────────

MADDE 4 — EĞİTİM VE SERTİFİKASYON

4.1. Dealer, kendi adına çalışacak her çalışanın Başlangıç Eğitimi'ni tamamlamasını sağlar.

4.2. Yenileme eğitimi her {{egitim_yenileme_ay}} ayda bir yapılır. İki dönem üst üste tamamlamayan Dealer'ın panel yetkileri kısıtlanabilir.

─────────────────────────────────────────────────────────────

MADDE 5 — PAZARLAMA VE MARKA KULLANIM STANDARTLARI

5.1. Dealer, MentorDE markasını yalnızca güncel marka kılavuzuna uygun biçimde kullanır.

5.2. MentorDE adına reklam kampanyaları öncesinde MentorDE'nin yazılı onayı alınır.

5.3. Marka ihlali tespitinde MentorDE, Dealer'a 48 (kırk sekiz) saat içinde düzeltme talebi gönderir.

─────────────────────────────────────────────────────────────

MADDE 6 — VERİ KORUMA (KVKK / GDPR)

6.1. Dealer, MentorDE Dealer Paneli aracılığıyla eriştiği tüm kişisel verileri 6698 sayılı KVKK ve GDPR kapsamında işler.

6.2. Veri ihlali yaşanması durumunda Dealer, bu ihlali öğrendiği andan itibaren {{veri_ihlali_bildirim_saat}} saat içinde MentorDE'ye yazılı olarak bildirir.

6.3. İhlal bildiriminin gecikmesinden doğan idari para cezaları ve zararlar Dealer'a rücu edilir.

─────────────────────────────────────────────────────────────

MADDE 7 — PERFORMANS TAKİBİ VE RAPORLAMA

7.1. MentorDE, Dealer Paneli üzerinden aylık performans raporları üretir ve sunar.

7.2. Birbirini izleyen 3 (üç) ay boyunca performans hedeflerinin %60'ının altında kalan Dealer için bir iyileştirme görüşmesi başlatılır.

─────────────────────────────────────────────────────────────

MADDE 8 — DENETİM HAKKI

8.1. Rutin denetimler {{denetim_bildirim_gun}} iş günü öncesinden Dealer'a bildirilir.

8.2. Veri ihlali şüphesi veya öğrenci şikayeti gibi acil durumlarda MentorDE bildirim süresi aranmaksızın denetim başlatabilir.

─────────────────────────────────────────────────────────────

MADDE 9 — ŞİKAYET VE İTİRAZ YÖNETİMİ

9.1. Dealer'a yönlendirilen şikayetlerde Dealer, 4 (dört) saat içinde ilk yanıtı ve 3 (üç) iş günü içinde çözüm önerisi sunar.

─────────────────────────────────────────────────────────────

MADDE 10 — ÇIKAR ÇATIŞMASI VE REKABETÇİ FAALİYETLER

10.1. Dealer, Almanya yükseköğretim danışmanlığı alanında rakip bir firma ile iş ilişkisi kurması durumunda bunu {{rakip_bildirim_gun}} takvim günü içinde MentorDE'ye bildirir.

10.2. Dealer, MentorDE öğrenci veritabanını veya panel üzerinden elde ettiği bilgileri rakip firmaya aktaramaz.

─────────────────────────────────────────────────────────────

MADDE 11 — SÜRE VE UYGULANACAK HUKUK

11.1. Bu Sözleşme, Referans Ortaklığı Sözleşmesi ile eş zamanlı olarak yürürlüğe girer ve aynı süre boyunca geçerliliğini korur.

11.2. Bu Sözleşme Türk Hukuku'na tabidir. Uyuşmazlıklarda {{yetkili_mahkeme}} Mahkemeleri yetkilidir.

─────────────────────────────────────────────────────────────

EK A — EŞIK TABLOSU

Acil bildirim yanıt süresi: {{max_yanit_suresi_saat}} saat
Veri ihlali bildirim süresi: {{veri_ihlali_bildirim_saat}} saat
Eğitim yenileme periyodu: {{egitim_yenileme_ay}} ay
Rutin denetim ön bildirimi: {{denetim_bildirim_gun}} iş günü
Rakip ilişki bildirim süresi: {{rakip_bildirim_gun}} takvim günü

─────────────────────────────────────────────────────────────

İMZA

MentorDE Eğitim Danışmanlık A.Ş.               {{dealer_firma_adi}}
Adı Soyadı: _______________________            Adı Soyadı: _______________________
Unvanı: ___________________________            Unvanı: ___________________________
Tarih: ____________________________            Tarih: ____________________________
İmza/Kaşe: ________________________            İmza/Kaşe: ________________________
TXT;

        DB::table('business_contract_templates')->insert([
            [
                'company_id'    => 0,
                'contract_type' => 'dealer',
                'template_code' => 'dealer_referral_v1',
                'name'          => 'Dealer Referans Ortaklığı Sözleşmesi v1',
                'body_text'     => $referral,
                'notes'         => 'Komisyon yapısı, ödeme koşulları ve fesih hükümlerini içerir. {{komisyon_tier1_oran}} vb. değerler sözleşme oluşturulurken girilir.',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'company_id'    => 0,
                'contract_type' => 'dealer',
                'template_code' => 'dealer_operations_v1',
                'name'          => 'Dealer Operasyon Sözleşmesi v1',
                'body_text'     => $operations,
                'notes'         => 'Sistem kullanımı, iletişim standartları, veri koruma ve denetim konularını kapsar. Referans Ortaklığı Sözleşmesi ile birlikte imzalanır.',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('business_contract_templates')
            ->whereIn('template_code', ['dealer_referral_v1', 'dealer_operations_v1'])
            ->delete();
    }
};
