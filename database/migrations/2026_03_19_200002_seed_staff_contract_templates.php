<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        // ─── 1. Tam Zamanlı - Belirsiz Süreli (Sabit Maaş) ───────────────────
        $tpl1 = <<<'TXT'
İŞVEREN-İŞÇİ SÖZLEŞMESİ
Tam Zamanlı - Belirsiz Süreli (Sabit Maaş)

Sözleşme Tarihi: {{sozlesme_tarihi}}
Sözleşme No: EMP-FT-IND-{{sozlesme_no}}

─────────────────────────────────────────────────────────────

1. TARAFLAR

İŞVEREN:
- Adı: {{isverenler_adi}}
- Adres: {{isverenler_adres}}
- Vergi No: {{isverenler_vergi_no}}
- Telefon: {{isverenler_telefon}}

İŞÇİ:
- Adı: {{isci_adi}}
- Kimlik No: {{isci_kimlik_no}}
- Doğum Tarihi: {{isci_dogum_tarihi}}
- Adres: {{isci_adres}}
- Telefon: {{isci_telefon}}

─────────────────────────────────────────────────────────────

2. SÖZLEŞME KONUSU

İşveren, İşçi'yi aşağıdaki pozisyonda çalıştırmaya, İşçi de bu pozisyonda çalışmaya gönüllü olarak kabul etmiştir:

- Pozisyon: {{pozisyon}}
- Departman: {{departman}}
- Yönetici: {{yonetici_adi}}
- Başlangıç Tarihi: {{baslangic_tarihi}}
- Çalışma Yeri: {{calisma_yeri}}
- Meslek: {{meslek}}

─────────────────────────────────────────────────────────────

3. SÖZLEŞME TÜRÜ VE SÜRESİ

- Sözleşme Türü: Belirsiz Süreli İş Sözleşmesi
- Çalışma Şekli: Tam Zamanlı (40 saat/hafta)
- İş Günleri: Pazartesi - Cuma, 09:00 - 17:00 (1 saat ara dahil)
- İzin Günleri: Cumartesi, Pazar ve Resmi Tatiller

─────────────────────────────────────────────────────────────

4. ÜCRET VE ÖDEME KOŞULLARI

4.1 Aylık Maaş
- Brüt Aylık Maaş: {{aylik_maas}} TRY (Sözleşme imzası tarihi itibariyle geçerli)
- Para Birimi: Türk Lirası (TRY)
- Ödeme Yöntemi: Banka Transferi
- Ödeme Tarihi: Her ayın 25'i (resmi tatil hariç, ertesi gün)

4.2 Maaş Bileşenleri
- Sabit Ücret: {{sabit_ucret}} TRY
- Sağlık/Ulaşım Yardımı: {{yardim}} TRY
- Diğer Yardımlar: {{diger_yardim}} TRY

4.3 Kesintiler
İşveren, yasalara uygun olarak aşağıdaki kesintileri yapacaktır:
- Gelir Vergisi (KKGI)
- Sosyal Güvenlik Prim (İşçi Payı, %14)
- İşsizlik Sigortası (İşçi Payı, %1)
- Sağlık Sigortası (İşçi Payı)

4.4 Yıllık Zam
Performans ve ekonomik koşullar dikkate alınarak yılda en az bir kez zam yapılması değerlendirilir. Zam miktarı ve tarihi Taraflar arasında mutabakatla belirlenecektir.

─────────────────────────────────────────────────────────────

5. ÇALIŞMA SAATLERİ VE İZİNLER

5.1 Çalışma Saatleri
- Günlük Çalışma Süresi: 8 saat
- Haftalık Çalışma Süresi: 40 saat
- Başlama ve Bitme Saati: 09:00 - 17:00 (ara dahil)
- Ara Süresi: 1 saat (12:00 - 13:00)

5.2 Fazla Mesai
- Fazla mesai saatleri İşveren tarafından talep edilebilir
- Fazla mesai saati 1,5 kat ücretle hesaplanır
- Cumartesi çalışması, 2 kat ücretle ödenecektir

5.3 Yıllık İzin
- Yıllık İzin: 20 iş günü (4 hafta)
- İzin Kullanımı: İşveren ve İşçi'nin mutabakatıyla planlanır
- İzin Carryover: Maksimum 5 gün bir sonraki yıla devredilebilir
- İzin Ücreti: İzin döneminde tam maaş ödenir

5.4 Hastalık İzni
- İlk 3 gün İşçi, sonrası İşveren tarafından ödenir
- Doktor raporu gereklidir (2 günden fazlaysa)

5.5 Resmi Tatiller
- Tüm resmi tatiller ücretli tatildir
- Resmi tatilde çalışılması durumunda 2 kat ücret ödenir

─────────────────────────────────────────────────────────────

6. HAKLAR VE YÜKÜMLÜLÜKLER

6.1 İşçi'nin Yükümlülükleri
- Sözleşmede belirtilen işleri dürüst ve güvenilir bir şekilde yapma
- İşveren'in makul talimatlarına uymak
- İş yerinin düzen ve güvenliğini sağlama
- Ticari sırları gizli tutma
- İş yaparken işçi kolaylığı ve İşveren'in çıkarlarını gözetme

6.2 İşveren'in Yükümlülükleri
- Ücretleri zamanında ve eksiksiz ödeme
- Sosyal sigorta primlerini ödeme
- Güvenli çalışma ortamı sağlama
- İş sözleşmesi koşullarına uyma
- Ayrımcılık yapmama

─────────────────────────────────────────────────────────────

7. GİZLİLİK VE FİKRİ MÜLKİYET

7.1 Gizlilik
İşçi, İşveren'in iş sırlarını, müşteri listesini, teknik bilgilerini ve diğer gizli bilgileri:
- Çalışma döneminde gizli tutacak
- İştiraki sona erdikten sonra 2 yıl boyunca gizli tutacak
- Yazılı onay olmaksızın üçüncü kişilere veya medyaya açıklamayacak

İhlal Cezası: Hukuki takip ve zarardan sorumlu tutulma (min. 3 aylık maaş)

7.2 Fikri Mülkiyet
- İşçi tarafından sözleşme kapsamında oluşturulan tüm çalışmalar İşveren'e aittir
- İşçi, bu çalışmaları başka kurumlarla paylaşmayacak

─────────────────────────────────────────────────────────────

8. DİSİPLİN VE CEZALAR

Ceza Türleri (Kademeli):
1. Yazılı Uyarı: İlk ihlal
2. Yazılı İhtar: İkinci ihlal (30 gün içinde)
3. Ücret Kesintisi: Üçüncü ihlal (1-2 günlük maaş, max %50)
4. Askıya Alma: Dördüncü ihlal (1-5 gün)
5. Fesih: Beşinci ihlal veya ağır ihlal

Derhal Fesih Nedenleri:
- İş yerinde hırsızlık, zimmet, dolandırıcılık
- Gizlilik ihlali
- Şiddet, taciz, mobbing
- Alkol/Madde Kullanımı
- İşveren'in izni olmaksızın başka işte çalışma

─────────────────────────────────────────────────────────────

9. SÖZLEŞME FESHİ

9.1 Deneme Süresi
- Deneme Süresi: 2 ay
- Deneme Süresi Sonu: 1 hafta öncesinden yazılı ihtar ile feshedilebilir

9.2 İşveren Tarafından Fesih
- İhbar Süresi: 2 hafta (deneme süresi sonrasında)
- Yazılı Bildirim: Resmi kurye veya e-mail

9.3 İşçi Tarafından Fesih (İstifa)
- İhbar Süresi: 2 hafta
- Yazılı Başvuru: İmzalı ve tarihli olmalı

9.4 Fesih Sonrası
- Final Maaş: Tahakkuk etmiş tüm ücretler + izin + dönem sonu
- Dosya Teslimi: Şirket belgelerinin iadesi
- Çalışma Belgesi: 3 gün içinde verilir
- Sigorta Kaydı: Kapanır

─────────────────────────────────────────────────────────────

10. HUKUKİ ÇERÇEVE VE UYUŞMAZLIk ÇÖZÜMÜ

Bu sözleşme Türk İş Kanunu (4857 sayılı) ve Sosyal Sigorta mevzuatına tabidir.
Uyuşmazlıklar {{yetkili_mahkeme}} Mahkemesi'nde görülür.

─────────────────────────────────────────────────────────────

11. İMZA

İşbu sözleşme, tarafların yüksek iradeleri ile imzalanmıştır.

İŞVEREN Adına:
İsim: {{isverenler_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}

İŞÇİ Adına:
İsim: {{isci_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}
TXT;

        // ─── 2. Tam Zamanlı - Belirli Süreli (Sabit Maaş) ────────────────────
        $tpl2 = <<<'TXT'
İŞVEREN-İŞÇİ SÖZLEŞMESİ
Tam Zamanlı - Belirli Süreli (Sabit Maaş)

Sözleşme Tarihi: {{sozlesme_tarihi}}
Sözleşme No: EMP-FT-FIX-{{sozlesme_no}}

─────────────────────────────────────────────────────────────

1. TARAFLAR

İŞVEREN:
- Adı: {{isverenler_adi}}
- Adres: {{isverenler_adres}}
- Vergi No: {{isverenler_vergi_no}}

İŞÇİ:
- Adı: {{isci_adi}}
- Kimlik No: {{isci_kimlik_no}}
- Adres: {{isci_adres}}

─────────────────────────────────────────────────────────────

2. SÖZLEŞME KONUSU

- Pozisyon: {{pozisyon}}
- Departman: {{departman}}
- Başlangıç Tarihi: {{baslangic_tarihi}}
- Bitiş Tarihi: {{bitis_tarihi}}
- Toplam Süre: {{toplam_sure}} ay/yıl
- Çalışma Şekli: Tam Zamanlı (40 saat/hafta)

─────────────────────────────────────────────────────────────

3. SÖZLEŞME TÜRÜ

- Sözleşme Türü: Belirli Süreli İş Sözleşmesi
- Süre: {{toplam_sure}} ({{baslangic_tarihi}} - {{bitis_tarihi}})
- Sona Erme: Bitiş tarihinde otomatik fesih (ihbar gerekmez)
- Yenileme: Tarafların yazılı mutabakatı halinde yenilenebilir

─────────────────────────────────────────────────────────────

4. ÜCRET VE ÖDEME KOŞULLARI

4.1 Aylık Maaş
- Brüt Aylık Maaş: {{aylik_maas}} TRY
- Ödeme Yöntemi: Banka Transferi
- Ödeme Tarihi: Her ayın 25'i

4.2 Sözleşme Bitiş Tazminatı
Sözleşme belirli süreli olduğu için, bitiş tarihinde:
- Tahakkuk etmiş tüm ücretler ödenir
- Kullanılmamış yıllık izin bedeli ödenir
- Dönem sonuna kadar olan sosyal sigorta primleri yatırılır

─────────────────────────────────────────────────────────────

5. ÇALIŞMA SAATLERİ VE İZİNLER

5.1 Çalışma Saatleri
- Günlük: 8 saat | Haftalık: 40 saat
- Çalışma Saati: 09:00 - 17:00

5.2 Yıllık İzin
- Yıllık İzin: Sözleşme süresi oranında (ör. 6 aylık sözleşme: 10 iş günü)
- Kullanım: İşveren talimatıyla veya sözleşme bitiminde bedel

5.3 Hastalık İzni
- İlk 3 gün İşçi, sonrası İşveren tarafından ödenir
- Doktor raporu gereklidir

5.4 Fazla Mesai
- Talep edilebilir (gerekli hallerde)
- 1,5 kat ücret ödenir

─────────────────────────────────────────────────────────────

6. HAKLAR VE YÜKÜMLÜLÜKLER

İşçi'nin Yükümlülükleri:
- İşleri dürüst ve sadakatle yapma
- İşveren'in talimatlarına uymak
- Gizlilik sağlamak
- İş güvenliği kurallarına uyma

İşveren'in Yükümlülükleri:
- Ücretleri zamanında ödeme
- Sosyal sigorta primlerini ödeme
- Güvenli çalışma ortamı sağlama

─────────────────────────────────────────────────────────────

7. GİZLİLİK

- İşçi, çalışma döneminde ve sonrasında 2 yıl boyunca gizlilik yükümlülüğü vardır
- Ticari sırlar, müşteri bilgileri, teknik veriler açıklanamaz
- İhlal durumunda hukuki takip yapılacaktır

─────────────────────────────────────────────────────────────

8. DİSİPLİN CEZALARI

Sözleşme ihlali durumunda: Yazılı Uyarı → Yazılı İhtar → Ücret Kesintisi → Askıya Alma → Fesih

Derhal fesih nedenleri: Hırsızlık, gizlilik ihlali, şiddet, taciz, madde/alkol kullanımı

─────────────────────────────────────────────────────────────

9. SÖZLEŞME BİTİŞ VE FESİH

9.1 Normal Sona Erme
- Tarih: {{bitis_tarihi}} — Otomatik fesih (ihbar gerekmez)

9.2 Erken Fesih (İşçi Tarafı)
- İhbar Süresi: 2 hafta — Yazılı Bildirim Gerekli

9.3 Erken Fesih (İşveren Tarafı)
- İhbar Süresi: 2 hafta (haklı sebeple 1 gün)

─────────────────────────────────────────────────────────────

10. UYGULANACAK HUKUK

Bu sözleşme Türk Hukuku'na ve Türk İş Kanunu (4857 sayılı) mevzuatına tabidir.
Uyuşmazlıklar {{yetkili_mahkeme}} Mahkemesi'nde görülür.

─────────────────────────────────────────────────────────────

11. İMZA

İŞVEREN:
İsim: {{isverenler_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}

İŞÇİ:
İsim: {{isci_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}
TXT;

        // ─── 3. Yarı Zamanlı - Belirsiz Süreli (Sabit + Bonus) ───────────────
        $tpl3 = <<<'TXT'
İŞVEREN-İŞÇİ SÖZLEŞMESİ
Yarı Zamanlı - Belirsiz Süreli (Sabit + Bonus)

Sözleşme Tarihi: {{sozlesme_tarihi}}
Sözleşme No: EMP-PT-IND-{{sozlesme_no}}

─────────────────────────────────────────────────────────────

1. TARAFLAR

İŞVEREN:
- Adı: {{isverenler_adi}}
- Adres: {{isverenler_adres}}
- Vergi No: {{isverenler_vergi_no}}

İŞÇİ:
- Adı: {{isci_adi}}
- Kimlik No: {{isci_kimlik_no}}
- Adres: {{isci_adres}}

─────────────────────────────────────────────────────────────

2. SÖZLEŞME KONUSU

- Pozisyon: {{pozisyon}}
- Başlangıç Tarihi: {{baslangic_tarihi}}
- Çalışma Şekli: Yarı Zamanlı
- Haftalık Saat: {{haftalik_saat}} saat (Tam zamanlı standardın %50'sinden az)

─────────────────────────────────────────────────────────────

3. ÇALIŞMA SAATLERİ

3.1 Haftalık Çalışma Planı
- Haftalık Saatler: {{haftalik_saat}} saat
- Çalışma Günleri: {{calisma_gunleri}}
- Çalışma Saatleri: {{calisma_saatleri}}
- Esneklik: İşveren ve İşçi mutabakatıyla değişebilir

3.2 Fazla Mesai
- Fazla mesai talep edilebilir
- 1,5 kat ücret ödenir
- Maksimum haftalık 10 saat izin alınabilir

─────────────────────────────────────────────────────────────

4. ÜCRET VE ÖDEME KOŞULLARI

4.1 Sabit Ücret
- Aylık Sabit Ücret: {{sabit_ucret}} TRY
- Saatlik Ücret: {{saatlik_ucret}} TRY/saat
- Ödeme Yöntemi: Banka Transferi
- Ödeme Tarihi: Her ayın 25'i

4.2 Performans Bonusu
- Hedef: {{bonus_kosulu}}
- Bonus Miktarı: {{bonus_miktar}} TRY (aylık, hedef tuttuğunda)
- Hesaplama: Başarılı ay sonunda ödenir

4.3 Diğer Ücretler
- Sağlık/Ulaşım Yardımı: {{yardim}} TRY/ay
- Performans Yardımı: {{perf_yardim}} TRY/ay (hedef tutarsa)

4.4 Kesintiler
- Gelir Vergisi, Sosyal Sigorta Primleri (İşçi Payı, %14), İşsizlik Sigortası

─────────────────────────────────────────────────────────────

5. SOSYAL GÜVENLİK

- İşçi, sosyal sigorta kapsamında kaydedilecektir
- İşveren, prim ödemektir
- İşçi, çalışma saati oranında sigortalı olur

─────────────────────────────────────────────────────────────

6. İZİNLER

6.1 Yıllık İzin
- Hakediş: Haftalık saat × 52 / 40 × 20 gün hesabıyla belirlenir
- Örnek: 20 saat/hafta çalışan → 26 iş günü
- Bedel: İzin döneminde tam ücret ödenir

6.2 Hastalık İzni
- İlk 3 gün İşçi, sonrası İşveren ödenir — Doktor raporu gereklidir

6.3 Resmi Tatiller
- Çalışılmadığı günler için ücret alır
- Çalışması halinde 2 kat ücret ödenir

─────────────────────────────────────────────────────────────

7. GİZLİLİK VE FİKRİ MÜLKİYET

- Gizlilik: 2 yıl devam
- Ticari Sırlar: Saklı tutulmalı
- Fikri Mülkiyet: Sözleşme kapsamında oluşturulan çalışmalar İşveren'e aittir
- İhlal: Hukuki takip (min. 3 aylık ücret cezası)

─────────────────────────────────────────────────────────────

8. DİSİPLİN CEZALARI

1. Yazılı Uyarı (ilk ihlal)
2. Yazılı İhtar (ikinci ihlal)
3. Ücret Kesintisi (üçüncü ihlal, %50 max)
4. Fesih (dördüncü ihlal)

Derhal Fesih: Hırsızlık, gizlilik ihlali, şiddet, taciz, madde/alkol

─────────────────────────────────────────────────────────────

9. SÖZLEŞME FESHİ

9.1 İşçi Tarafından Fesih — İhbar Süresi: 2 hafta — Yazılı bildirim

9.2 İşveren Tarafından Fesih — İhbar Süresi: 2 hafta — Yazılı bildirim

9.3 Deneme Süresi: 2 ay — 1 hafta ihbar ile feshedilebilir

─────────────────────────────────────────────────────────────

10. UYGULANACAK HUKUK

Uyuşmazlıklar {{yetkili_mahkeme}} Mahkemesi'nde görülür.

─────────────────────────────────────────────────────────────

11. İMZA

İŞVEREN:
İsim: {{isverenler_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}

İŞÇİ:
İsim: {{isci_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}
TXT;

        // ─── 4. Yarı Zamanlı - Belirli Süreli (Komisyon Bazlı) ───────────────
        $tpl4 = <<<'TXT'
İŞVEREN-İŞÇİ SÖZLEŞMESİ
Yarı Zamanlı - Belirli Süreli (Komisyon Bazlı)

Sözleşme Tarihi: {{sozlesme_tarihi}}
Sözleşme No: EMP-PT-FIX-COM-{{sozlesme_no}}

─────────────────────────────────────────────────────────────

1. TARAFLAR

İŞVEREN:
- Adı: {{isverenler_adi}}
- Adres: {{isverenler_adres}}
- Vergi No: {{isverenler_vergi_no}}

İŞÇİ:
- Adı: {{isci_adi}}
- Kimlik No: {{isci_kimlik_no}}
- Adres: {{isci_adres}}

─────────────────────────────────────────────────────────────

2. SÖZLEŞME KONUSU

- Pozisyon: {{pozisyon}} (Satış Temsilcisi / Alanlar Temsilcisi)
- Başlangıç Tarihi: {{baslangic_tarihi}}
- Bitiş Tarihi: {{bitis_tarihi}}
- Toplam Süre: {{toplam_sure}} ay
- Çalışma Şekli: Yarı Zamanlı, Esnek Saatler
- Haftalık Saat: {{haftalik_saat}} saat (veya minimum)

─────────────────────────────────────────────────────────────

3. ÇALIŞMA KOŞULLARI

3.1 Çalışma Saatleri
- Haftalık Saat: {{haftalik_saat}} saat (minimum)
- Esneklik: İşçi, saatleri {{calisma_gunleri}} gün içinde dağıtabilir
- Kendi İşletmesi: İşçi, başka yarı zamanlı işte çalışabilir (rekabetçi değilse)

3.2 Çalışma Yeri
- Lokasyon: Saha veya {{isyeri_adresi}}

─────────────────────────────────────────────────────────────

4. KOMİSYON YAPISI

4.1 Ücret Modeli
- Temel Saatlik Ücret: {{temel_saatlik_ucret}} TRY/saat (Minimum Garanti)
- Satış Hedefi: {{aylik_satis_hedefi}} TRY/ay
- Komisyon Oranı: %{{komisyon_yuzdesi}} (Hedef üstü satışlar)

4.2 Örnek Hesaplama
Hedef tutmadıysa: {{temel_saatlik_ucret}} TRY × {{haftalik_saat}} saat × 4.33 = {{ay_ucret}} TRY
Hedefi aştıysa: Temel ücret + (Aşım Tutarı × %{{komisyon_yuzdesi}}) komisyon

4.3 Ödeme Koşulları
- Ödeme Dönemi: Aylık (ayın 25'i)
- Hesaplama: Tahsilattan sonra (müşteri ödedi)
- Gecikmeli Tahsilat: Komisyon, müşteri ödedikten sonra geçerli
- Geri Ödeme: Müşteri iade etmezse, komisyon geri alınır

4.4 Diğer Ödeme Öğeleri
- Sağlık Yardımı: {{saglik_yardim}} TRY/ay
- Ulaşım: {{ulasim_yardim}} TRY/ay (Gerekçe göstermek gerekli)

─────────────────────────────────────────────────────────────

5. SOSYAL GÜVENLİK

- Sigorta: İşçi, sosyal sigorta kapsamında kaydedilecektir
- Prim: İşveren, minimum temel ücret × haftalık saat × 4.33'e göre prim öder
- Sigorta Türü: Çalışan olarak sigortalı

─────────────────────────────────────────────────────────────

6. İZİN VE DİNLENME

6.1 Yıllık İzin
- Hakediş: Haftalık saat × 52 / 40 × 20 gün
- İzin Bedeli: İzin döneminde ortalama aylık ücret ödenir (Temel + Ortalama Komisyon)
- Ortalama Komisyon: Önceki 3 aylık ortalama

6.2 Hastalık İzni — Doktor raporu gereklidir

6.3 Resmi Tatiller — Çalışmadıysa ücret yok; çalışırsa 2 kat ücret

─────────────────────────────────────────────────────────────

7. PERFORMANS HEDEFLERİ

7.1 Aylık Hedef
- Satış Hedefi: {{aylik_satis_hedefi}} TRY
- Müşteri Hedefi: {{musteri_sayisi}} yeni müşteri
- Başarı Oranı: Min. %80 hedef tutturma

7.2 Performans Değerlendirmesi
- Aylık: Satış raporu ve müşteri raporu
- Üçer Aylık: Performans review
- Yetersiz Performans: 3 ay tutturmadıysa sözleşme feshedilir

7.3 Terfi ve Zam
- Hedef %130+ tuttursa, aylık zam %5-10 değerlendirilir
- 6 ay başarılı olursa, tam zamanlı pozisyona terfi önerilir

─────────────────────────────────────────────────────────────

8. İŞ SAYGILIĞI VE GİZLİLİK

8.1 Müşteri Bilgisi
- İşçi, müşteri listesini ve iletişim bilgilerini gizli tutacak
- Sözleşme bitiminde müşteri listesi verilmeyecek
- İhlal cezası: {{gizlilik_cezasi}} TRY + hukuki takip

8.2 Ticari Sırlar
- Sözleşme sonrası 2 yıl gizlilik yükümlülüğü
- Rakip firmada çalışmama şartı: 1 yıl

8.3 Fikri Mülkiyet
- İşçi tarafından oluşturulan satış malzemeleri ve sunum İşveren'e aittir

─────────────────────────────────────────────────────────────

9. DİSİPLİN VE CEZALAR

9.1 Ceza Sistemi
1. Yazılı Uyarı: Hedefi tutturmadığında (1. ay)
2. Yazılı İhtar: 2. ay tutturmadığında
3. Fesih: 3. ay tutturmadığında

9.2 Derhal Fesih Nedenleri
- Gizlilik ihlali (müşteri listesi açıklamak)
- Hırsızlık, zimmet — Müşteri tacizi — Şiddet veya tehdit
- Başka işte açık rekabet

─────────────────────────────────────────────────────────────

10. SÖZLEŞME BİTİŞ VE FESİH

10.1 Normal Sona Erme — Tarih: {{bitis_tarihi}} — Otomatik fesih

10.2 Erken Fesih (İşçi Tarafı) — İhbar: 2 hafta — Yazılı

10.3 Erken Fesih (İşveren Tarafı) — İhbar: 1 hafta — Yazılı

─────────────────────────────────────────────────────────────

11. UYGULANACAK HUKUK

Uyuşmazlıklar {{yetkili_mahkeme}} Mahkemesi'nde görülür.

─────────────────────────────────────────────────────────────

12. İMZA

İŞVEREN:
İsim: {{isverenler_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}

İŞÇİ:
İsim: {{isci_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}
TXT;

        // ─── 5. Bağımsız Müteahhit / Freelance ───────────────────────────────
        $tpl5 = <<<'TXT'
BAĞIMSIZ MÜTEAHHİT / FREELANCE SÖZLEŞMESİ
Proje Bazlı - Bağımsız Müteahhit

Sözleşme Tarihi: {{sozlesme_tarihi}}
Sözleşme No: FREELANCE-{{sozlesme_no}}

─────────────────────────────────────────────────────────────

1. TARAFLAR

MÜŞTERİ (İŞVEREN):
- Adı: {{isverenler_adi}}
- Adres: {{isverenler_adres}}
- Vergi No: {{isverenler_vergi_no}}
- E-mail: {{isverenler_email}}

BAĞIMSIZ MÜTEAHHİT (FREELANCER):
- Adı: {{isci_adi}}
- Kimlik No: {{isci_kimlik_no}}
- Adres: {{isci_adres}}
- Vergi Statüsü: Serbest Meslek / Kooperatif / Şahıs İşletmesi
- E-mail: {{isci_email}}
- KDV Mükellefi: {{kdv_mukellef}}

─────────────────────────────────────────────────────────────

2. PROJE KONUSU

- Proje Adı: {{proje_adi}}
- Açıklaması: {{proje_aciklamasi}}
- Kapsamı: {{proje_kapsami}}
- Başlangıç Tarihi: {{baslangic_tarihi}}
- Bitiş Tarihi (Tahmini): {{bitis_tarihi}}
- Durumu: {{proje_durumu}} (Proposal / Approved / In Progress)

─────────────────────────────────────────────────────────────

3. SÖZLEŞME TÜRÜ

- İş Niteliği: Bağımsız Müteahhitlik / Freelance Hizmet
- Çalışan Statüsü: HAYIR — Müteahhit, çalışan değildir
- Sosyal Sigorta: Müteahhit kendi sorumluluğundadır
- Vergiler: Müteahhit kendi vergi sorumluluğundadır

─────────────────────────────────────────────────────────────

4. PROJE ÜCRETİ VE ÖDEME

4.1 Ücretlendirme Modeli

Seçenek A: Sabit Proje Ücreti
- Toplam Ücreti: {{toplam_ucret}} TRY
- KDV: %18 ek (KDV Mükellefi ise)
- Nihai Tutar: {{kdv_ile_tuttar}} TRY

Seçenek B: Saatlik Ücret
- Saatlik Ücret: {{saatlik_ucret}} TRY
- Tahmini Saat: {{tahmini_saat}} saat
- Tahmini Toplam: {{tahmini_ucret}} TRY
- Not: Gerçek saat sayısına göre fatura düzenlenir

Seçenek C: Kilometre/Taş Bazlı
- 1. Kilometre (Tasarım/Konsept): {{km1_ucret}} TRY
- 2. Kilometre (Geliştirme): {{km2_ucret}} TRY
- 3. Kilometre (Test): {{km3_ucret}} TRY
- 4. Kilometre (Yayına Alma): {{km4_ucret}} TRY
- Toplam: {{toplam_ucret}} TRY

4.2 Ödeme Planı

Proje Başlangıcı (%30): {{km1_odeme}} TRY — {{baslangic_tarihi}}
Kullanılabilir Prototype (%30): {{km2_odeme}} TRY — Önceden belirtilecek
Final Teslimat (%40): {{km3_odeme}} TRY — {{bitis_tarihi}}

4.3 Ödeme Koşulları
- Fatura: Müteahhit, aşamalar tamamlandıktan sonra fatura düzenler
- Ödeme Yöntemi: Banka Transferi (Müteahhit'in IBAN'ına)
- Süre: Fatura tarihinden 15 gün içinde
- Gecikmeli Ödeme: Aylık %3 faiz

4.4 Gider Ödemeleri
- Taşeron Maliyeti: Müteahhit, önceden onay almadıkça kendi öder
- Seyahat Masrafı: {{seyahat_karari}}

─────────────────────────────────────────────────────────────

5. İŞ KAPSAMINDA BAŞARI ÖLÇÜTLERİ

5.1 Teslim Edilecek Öğeler
- {{teslimat_1}}
- {{teslimat_2}}
- {{teslimat_3}}

5.2 Kabul Kriterleri
- Tüm öğeler proje kapsamında listelenen şekilde teslim edilmesi
- Kalite standartları karşılanması
- {{aciklama}} tamamlanması

5.3 Muhasebe Kontrolü
- Müşteri, 5 iş günü içinde ön muhasebe yapar
- Sorun varsa, müteahhit 5 gün içinde düzeltir
- Sorun çözülürse, ödeme yapılır

─────────────────────────────────────────────────────────────

6. FİKRİ MÜLKİYET VE TELİF HAKLARI

6.1 Mülkiyet
- Ürün Mülkiyeti: Tüm proje çıktıları Müşteri'ye aittir
- Kaynak Kodu: Müteahhit, kaynak kodu ve dokümantasyonu teslim eder

6.2 Müteahhit'in Hakları
- Müteahhit, projeyi portföyünde gösterebilir (Müşteri'nin izniyle)
- Müşteri'ye ait Sırlar açılmayacak

6.3 Üçüncü Taraf Mülkiyeti
- Müteahhit, açık kaynak veya ticari araçlar kullanabilir
- Müşteri, bu araçların lisans koşullarını kabul eder

─────────────────────────────────────────────────────────────

7. GİZLİLİK VE VERİ KORUMASI

7.1 Gizlilik Anlaşması
Müteahhit ve Müşteri, proje sırasında öğrendikleri bilgileri gizli tutmaktadırlar:
- Proje detayları, müşteri verileri, teknik bilgiler, iş sırları

7.2 Süre — Sözleşme döneminde zorunlu; sonrasında 2 yıl gizlilik

7.3 GDPR/KVKK
- Müteahhit, kişisel veri işlersen GDPR/KVKK'ya uyar

─────────────────────────────────────────────────────────────

8. YÜKÜMLÜLÜKLER

8.1 Müteahhit'in Yükümlülükleri
- İş, ileri ve profesyonel standartlarda yapılacak
- Zaman çerçevesine uyulacak
- Belirtilen spesifikasyonlara uyulacak
- Hataları zamanında raporlayacak — Düzenli güncellemeler verecek

8.2 Müşteri'nin Yükümlülükleri
- Zamanında ödeme yapacak
- Proje spesifikasyonlarını açıkça belirtecek
- Gerekli tüm bilgileri sağlayacak

─────────────────────────────────────────────────────────────

9. KAPSAM DIŞI İŞLER (EXTRA)

9.1 Kapsam Dışı Tanımı
- {{kapsam_disi_1}}
- {{kapsam_disi_2}}
- Yeni özellikler (Change Request)

9.2 Extra Ücretlendirme
- Müşteri, kapsam dışı iş talep edebilir
- Müteahhit, yeni bir teklif sunacak
- Müşteri kabul ettikten sonra işe başlanır

─────────────────────────────────────────────────────────────

10. SORUN ÇÖZÜ VE SORUMLULUK

10.1 Garanti
- Müteahhit, makul profesyonel standartlarda çalışacak
- Belli sorunlar için 30 gün boyunca düzeltme yapılacak

10.2 Sınırlandırılmış Sorumluluk
- Müteahhit'in sorumluluğu, ödenen ücretin %50'sini aşmaz
- Müteahhit, Müşteri'nin kaybı için sorumlu değildir (Indirect Damages)
- Siber saldırı, veri kaybı sorunlarında sorumlu değildir

─────────────────────────────────────────────────────────────

11. SÖZLEŞME FESHİ

11.1 Müteahhit Tarafından Fesih
- İhbar Süresi: 2 hafta
- Yapılan İş: Müteahhit, proportional ödeme talep eder

11.2 Müşteri Tarafından Fesih
- İhbar Süresi: 1 hafta
- Ödeme: Müşteri, yapılan işin oranında öder + %20 ceza

11.3 Derhal Fesih
- Gizlilik ihlali — Ödeme yapılmama (15 gün gecikmeli)
- Kontrat maddeleri çiğnenmesi — Proje imkânsız hale gelmesi

─────────────────────────────────────────────────────────────

12. SÖZLEŞME BİTİM

12.1 Final Teslim
- Müteahhit, tüm çıktıları teslim eder
- Kaynak kod, dokümantasyon, erişim bilgileri verilir
- Müşteri, 5 iş günü içinde kontrol eder

12.2 Final Ödeme
- Müşteri, kabulden sonra final %40'ını öder

12.3 Destek Süresi (Opsiyonel)
- Sözleşme bitiminden sonra 30 gün destek verilir (Bedelsiz)
- Critical Bug: 30 gün sonra da düzeltilir (Ücretli)

─────────────────────────────────────────────────────────────

13. GENEL ŞARTLAR

13.1 Bağımlılık Yok
- Müteahhit, Müşteri'nin çalışanı DEĞİLDİR
- Kendi saatlerini, araçlarını belirleyebilir

13.2 Vergiler ve Sosyal Sigorta
- Müteahhit kendi vergilerini öder
- Müteahhit kendi sigortasını sağlar
- Müşteri, kesinti yapmaz

13.3 Kanun Seçimi
- Bu sözleşme, Türk Hukuku'na tabidir
- Uyuşmazlık: {{yetkili_mahkeme}} Mahkemesi'nde çözülür

─────────────────────────────────────────────────────────────

14. İMZA

MÜŞTERİ:
İsim: {{isverenler_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}

BAĞIMSIZ MÜTEAHHİT:
İsim: {{isci_adi}}
İmza: ___________________________
Tarih: {{sozlesme_tarihi}}
TXT;

        DB::table('business_contract_templates')->insert([
            [
                'company_id'    => 0,
                'contract_type' => 'staff',
                'template_code' => 'staff_fulltime_indefinite_v1',
                'name'          => 'Tam Zamanlı - Belirsiz Süreli (Sabit Maaş)',
                'body_text'     => $tpl1,
                'notes'         => 'Standart tam zamanlı iş sözleşmesi. {{aylik_maas}}, {{pozisyon}}, {{departman}}, {{baslangic_tarihi}} zorunlu.',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'company_id'    => 0,
                'contract_type' => 'staff',
                'template_code' => 'staff_fulltime_fixed_v1',
                'name'          => 'Tam Zamanlı - Belirli Süreli (Sabit Maaş)',
                'body_text'     => $tpl2,
                'notes'         => 'Proje bazlı veya geçici kadro için. {{baslangic_tarihi}}, {{bitis_tarihi}}, {{toplam_sure}} zorunlu.',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'company_id'    => 0,
                'contract_type' => 'staff',
                'template_code' => 'staff_parttime_indefinite_v1',
                'name'          => 'Yarı Zamanlı - Belirsiz Süreli (Sabit + Bonus)',
                'body_text'     => $tpl3,
                'notes'         => 'Esnek çalışma + bonus sistemi. {{haftalik_saat}}, {{sabit_ucret}}, {{bonus_kosulu}} zorunlu.',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'company_id'    => 0,
                'contract_type' => 'staff',
                'template_code' => 'staff_parttime_commission_v1',
                'name'          => 'Yarı Zamanlı - Belirli Süreli (Komisyon Bazlı)',
                'body_text'     => $tpl4,
                'notes'         => 'Satış temsilcisi için. {{temel_saatlik_ucret}}, {{komisyon_yuzdesi}}, {{aylik_satis_hedefi}} zorunlu.',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'company_id'    => 0,
                'contract_type' => 'staff',
                'template_code' => 'staff_freelance_v1',
                'name'          => 'Bağımsız Müteahhit / Freelance (Proje Bazlı)',
                'body_text'     => $tpl5,
                'notes'         => 'Çalışan statüsü yok, proje bazlı ödeme. {{proje_adi}}, {{toplam_ucret}}, {{baslangic_tarihi}}, {{bitis_tarihi}} zorunlu.',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('business_contract_templates')
            ->whereIn('template_code', [
                'staff_fulltime_indefinite_v1',
                'staff_fulltime_fixed_v1',
                'staff_parttime_indefinite_v1',
                'staff_parttime_commission_v1',
                'staff_freelance_v1',
            ])
            ->delete();
    }
};
