<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $body = <<<'TXT'
YURT DIŞI EĞİTİM DANIŞMANLIK HİZMET SÖZLEŞMESİ
Sözleşme No: {{contract_number}}
Sözleşme Tarihi: {{contract_date}}

MADDE 1 — TARAFLAR

1.1. DANIŞMAN:
Unvan: {{advisor_company_name}}
Adres: {{advisor_company_address}}
Vergi Dairesi / No: {{advisor_tax_info}}
Yetkili: {{advisor_authorized_person}}
Telefon: {{advisor_phone}}
E-posta: {{advisor_email}}
Web: {{advisor_website}}
(Bundan sonra "Danışman" olarak anılacaktır.)

1.2. ÖĞRENCİ / YASAL TEMSİLCİ:
Ad Soyad: {{student_full_name}}
Öğrenci No: {{student_id}}
T.C. Kimlik / Pasaport No: {{student_identity_no}}
Doğum Tarihi: {{student_birth_date}}
Adres: {{student_address}}
Telefon: {{student_phone}}
E-posta: {{student_email}}
(Bundan sonra "Öğrenci" olarak anılacaktır.)

(Öğrenci 18 yaşından küçük ise bu sözleşmeyi yasal temsilcisi imzalar:)
Yasal Temsilci Ad Soyad: {{guardian_full_name}}
T.C. Kimlik No: {{guardian_identity_no}}
Öğrenci ile Yakınlık Derecesi: {{guardian_relation}}

MADDE 2 — SÖZLEŞMENİN KONUSU VE KAPSAMI

Bu sözleşme, Danışman tarafından Öğrenci'nin {{application_type}} kapsamında Almanya'da {{education_level}} alması amacıyla başvuru ve danışmanlık süreçlerinin yürütülmesini düzenler. Danışman'ın bu sözleşme kapsamındaki yükümlülüğü, başvuru sürecini profesyonel standartlarda yönetmek olup; üniversite kabulü veya vize onayı alınmasına yönelik herhangi bir sonuç garantisi içermemektedir.

MADDE 3 — DANIŞMANLIK HİZMETLERİ

Danışman, seçilen paket kapsamında aşağıdaki başvuru yönetimi hizmetlerini sunar:

3.1. Üniversite Başvuru Süreci
a) Öğrencinin profiline uygun üniversite ve programların araştırılması.
b) Başvurulacak üniversitelere yönelik belgelerin usulüne uygun düzenlenmesi ve başvuruların yapılması.
c) Başvuru süreçlerinin ve kurumlarla iletişimin takip edilmesi.

3.2. Vize Başvuru Süreci
a) Vize türüne göre gerekli belge listesinin öğrenciye iletilmesi ve dosya hazırlığına destek olunması.
b) Vize görüşmesi süreçleri hakkında bilgilendirme yapılması.
c) Bloke hesap açılışı prosedürlerinde rehberlik sağlanması.

3.3. Konaklama ve Dil Kursu Rehberliği
a) Almanya'daki yurt ve konaklama seçenekleri hakkında bilgilendirme yapılması.
b) İhtiyaç halinde uygun dil kursu alternatiflerinin sunulması.

3.4. Seçilen Hizmet Paketi ve Ek Hizmetler
Seçilen Paket: {{package_name}}
Paket Kapsamı: {{service_scope}}
Ek Hizmetler (varsa): {{extra_services}}
(Sadece seçilen paket kapsamına dahil olan hizmetler ifa edilecektir.)

MADDE 4 — HİZMET BEDELİ VE ÖDEME ŞARTLARI

4.1. Danışmanlık hizmet bedeli toplamı {{service_total_price}} olarak belirlenmiştir.
4.2. Ödeme planı aşağıdaki şekildedir:
{{payment_plan}}
4.3. Belirtilen hizmet bedeline öğrencinin vize harcı, sigorta primi, uçak bileti, okul harçları, konaklama, yeminli tercüme, noter, kargo ve benzeri üçüncü taraf masrafları dahil değildir. Tüm bu masraflar Öğrenci'ye aittir.
4.4. Öğrenci, vadesi gelen ödemeyi en geç 5 (beş) iş günü içinde yapmakla yükümlüdür. Gecikme yaşanması halinde Danışman, önceden ihtara gerek kalmaksızın hizmetleri durdurma veya sözleşmeyi tek taraflı haklı nedenle feshetme hakkına sahiptir. Bu durumda o ana kadar yapılan ödemeler iade edilmez.

MADDE 5 — ÖĞRENCİ'NİN YÜKÜMLÜLÜKLERİ

5.1. Danışman tarafından talep edilen belgeleri zamanında, eksiksiz ve gerçeğe uygun şekilde teslim etmek. Belgelerin geç veya hatalı tesliminden doğacak hak kayıplarından Danışman sorumlu tutulamaz.
5.2. Hizmet bedellerini sözleşmede belirtilen tarihlerde eksiksiz ödemek.
5.3. Danışman'ın yönlendirmelerine ve belirlediği takvime uymak.

MADDE 6 — DANIŞMAN'IN YÜKÜMLÜLÜKLERİ

6.1. Danışman, başvuru sürecini bilgi ve tecrübesi doğrultusunda, özen yükümlülüğü çerçevesinde yürütecektir.
6.2. Danışman, Öğrenci'nin sunduğu belgeleri yalnızca başvuru işlemleri için kullanacak ve yetkili kurumlar dışında üçüncü kişilerle paylaşmayacaktır.

MADDE 7 — SÖZLEŞMENİN SÜRESİ

7.1. Bu sözleşme, imzalandığı tarihte yürürlüğe girer ve seçilen paketteki hizmetlerin tamamlanması, üniversite/vize başvuru sonuçlarının Öğrenci'ye iletilmesi veya sözleşmenin feshi ile sona erer.

MADDE 8 — SÖZLEŞMENİN FESHİ

8.1. Öğrenci Tarafından Fesih
Öğrenci sözleşmeyi tek taraflı olarak feshedebilir. Ancak bu durumda, fesih anına kadar yapılan ödemeler Danışman'ın harcadığı mesai ve danışmanlık hizmetinin karşılığı olarak kabul edilir ve kesinlikle iade edilmez. Kalan bakiye varsa muaccel hale gelir.

8.2. Danışman Tarafından Fesih
Danışman; Öğrenci'nin ödeme yükümlülüklerini aksatması, istenen belgeleri zamanında temin etmemesi, sahte belge sunması veya kurumlara yanıltıcı beyanda bulunması durumlarında sözleşmeyi derhal ve tek taraflı olarak feshedebilir. Bu durumda herhangi bir ücret iadesi yapılmaz ve Danışman'ın maddi tazminat hakkı saklıdır.

MADDE 9 — BAŞVURU SONUÇLARI VE ÜCRET İADESİ DURUMU

9.1. Üniversite Kabulü veya Vize Reddi Durumu
Danışman'ın temel yükümlülüğü başvuru dosyalarının eksiksiz hazırlanması ve kurumlara iletilmesidir. Üniversitelerin kabul kriterleri, kontenjan durumları veya konsoloslukların vize kararları tamamen ilgili makamların bağımsız inisiyatifindedir. Bu nedenle, üniversitelerden kabul alınamaması, başvurunun reddedilmesi veya vize başvurusunun olumsuz sonuçlanması durumunda Danışman hizmetini ifa etmiş sayılır ve Öğrenci'ye herhangi bir ücret iadesi yapılmaz.
9.2. Öğrenci'nin eksik, hatalı bilgi vermesi veya mülakatlarda başarısız olması nedeniyle yaşanacak olumsuzluklarda Danışman'ın hiçbir hukuki veya mali sorumluluğu bulunmamaktadır.

MADDE 10 — SORUMLULUK SINIRI VE GÜVENCE KAPSAMI

10.1. Danışman, hiçbir resmi kurumun (üniversite, göçmenlik bürosu, konsolosluk vb.) karar organı veya temsilcisi değildir. Alınacak kararlar üzerinde hiçbir yaptırımı veya taahhüdü yoktur.
10.2. Üniversitelerin veya resmi kurumların kendi iç işleyişlerinden kaynaklanan gecikmeler, ani kural değişiklikleri veya sistem hatalarından Danışman sorumlu tutulamaz.

MADDE 11 — KİŞİSEL VERİLERİN KORUNMASI (KVKK / DSGVO)

Öğrenci, Danışman'ın başvuru süreçlerini yürütebilmesi amacıyla kişisel ve özel nitelikli kişisel verilerini işlemesine ve yurt dışındaki ilgili kurumlara aktarmasına açık rıza gösterir. Ayrıntılar Ek-1'de yer almaktadır.

MADDE 12 — GİZLİLİK

Taraflar, sözleşme kapsamında edindikleri ticari ve kişisel bilgileri gizli tutmayı kabul eder.

MADDE 13 — UYUŞMAZLIK ÇÖZÜMÜ

İşbu sözleşmeden doğabilecek uyuşmazlıklarda Türkiye Cumhuriyeti kanunları uygulanacak olup, {{jurisdiction_city}} Mahkemeleri ve İcra Daireleri yetkilidir.

MADDE 14 — YÜRÜRLÜK

Toplam 14 (on dört) maddeden oluşan işbu sözleşme, taraflarca okunarak iradelerine uygun bulunmuş ve imzalanarak yürürlüğe girmiştir.

DANIŞMAN
Unvan: {{advisor_company_name}}
Yetkili: {{advisor_authorized_person}}
Tarih: {{contract_date}}
İmza: ________________________

ÖĞRENCİ / YASAL TEMSİLCİ
Ad Soyad: {{student_full_name}}
Tarih: {{contract_date}}
İmza: ________________________
TXT;

        $annexKvkk = <<<'TXT'
EK-1: KİŞİSEL VERİLERİN İŞLENMESİNE İLİŞKİN AYDINLATMA VE AÇIK RIZA METNİ (KVKK & DSGVO)

1. VERİ SORUMLUSUNUN KİMLİĞİ
6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") ve Avrupa Birliği Genel Veri Koruma Tüzüğü ("DSGVO/GDPR") uyarınca, kişisel verileriniz veri sorumlusu sıfatıyla {{advisor_company_name}} ("Şirket" veya "Danışman") tarafından aşağıda açıklanan kapsamda işlenebilecektir.

2. İŞLENEN KİŞİSEL VERİLERİNİZ
Yurt dışı eğitim danışmanlık hizmetlerinin yürütülebilmesi amacıyla aşağıdaki veri kategorileri işlenmektedir:

Kimlik ve Pasaport Bilgileri: Ad, soyad, T.C. Kimlik No, doğum tarihi, pasaport kopyası, uyruk vb.
İletişim Bilgileri: Telefon numarası, e-posta adresi, ikamet adresi.
Eğitim ve Akademik Bilgiler: Diploma, transkript, dil yeterlilik belgeleri (IELTS, TOEFL, TestDaF vb.), niyet mektupları, CV, referans mektupları.
Finansal Bilgiler: Vize ve bloke hesap süreçleri için gereken banka hesap dökümleri, sponsor bilgileri, ödeme dekontları.
Özel Nitelikli Kişisel Veriler: Vize ve sağlık sigortası işlemleri için zorunlu olması halinde sağlık raporları, biyometrik veriler (vize fotoğrafı) veya adli sicil kaydı.

3. KİŞİSEL VERİLERİN İŞLENME AMACI
Toplanan kişisel verileriniz; Almanya'daki üniversitelere, dil kurslarına ve Uni-Assist gibi başvuru portallarına kayıt işlemlerinin yapılması, konsolosluklar ve yetkili aracı kurumlar nezdinde vize başvuru dosyalarının hazırlanması ve randevuların alınması, bloke hesap (Sperrkonto) açılışı ve zorunlu sağlık sigortası (Krankenversicherung) işlemlerinin yürütülmesi, konaklama ve yurt başvurularının yapılması, Şirketimizin sözleşmesel yükümlülüklerini yerine getirmesi ve muhasebe/faturalandırma süreçlerinin yönetilmesi amaçlarıyla, hukuka ve dürüstlük kurallarına uygun olarak işlenmektedir.

4. KİŞİSEL VERİLERİN AKTARILMASI VE YURT DIŞINA AKTARIM
Hizmetin doğası gereği, başvuru süreçlerinin yürütülebilmesi için kişisel verilerinizin yurt dışına (Almanya'ya) aktarılması zorunludur. Verileriniz, yalnızca yukarıda belirtilen amaçlar doğrultusunda; Almanya'daki eğitim kurumlarına, Alman Konsolosluklarına/Büyükelçiliklerine, Yabancılar Dairesine (Ausländerbehörde), sağlık sigortası şirketlerine, bloke hesap hizmeti sunan finansal kuruluşlara ve resmi makamlara aktarılacaktır.

5. KİŞİSEL VERİ TOPLAMANIN YÖNTEMİ VE HUKUKİ SEBEBİ
Kişisel verileriniz, Danışmanlık Sözleşmesi'nin kurulması ve ifası (KVKK m.5/2-c), veri sorumlusunun hukuki yükümlülüğünü yerine getirmesi (KVKK m.5/2-ç) ve temel haklarınıza zarar vermemek kaydıyla meşru menfaatlerimiz (KVKK m.5/2-f) hukuki sebeplerine dayanarak, fiziki veya elektronik ortamda toplanmaktadır. Sağlık bilgileri ve yurt dışına aktarım ise yalnızca açık rızanıza istinaden işlenir.

6. İLGİLİ KİŞİNİN HAKLARI (KVKK Madde 11 ve DSGVO)
Kişisel veri sahibi olarak Şirket'e başvurarak; verilerinizin işlenip işlenmediğini öğrenme, işlenmişse bilgi talep etme, işlenme amacını ve amaca uygun kullanılıp kullanılmadığını öğrenme, yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme, eksik/yanlış işlenmişse düzeltilmesini isteme, işlenmesini gerektiren sebeplerin ortadan kalkması halinde silinmesini/yok edilmesini isteme haklarına sahipsiniz.

AÇIK RIZA VE ONAY BEYANI
Yukarıda yer alan "Kişisel Verilerin İşlenmesine İlişkin Aydınlatma Metni"ni okudum, anladım ve haklarım konusunda bilgilendirildim.

Bu kapsamda; kimlik, iletişim, eğitim ve finansal verilerimin, yurt dışı eğitim ve vize başvurularımın yapılabilmesi amacıyla Almanya'daki eğitim kurumlarına, resmi makamlara ve ilgili aracı kurumlara (yurt dışına) aktarılmasına, vize ve sigorta işlemleri için zorunlu olması halinde sağlık verilerimin ve adli sicil kaydı gibi özel nitelikli kişisel verilerimin işlenmesine ve yurt dışındaki ilgili kurumlara aktarılmasına özgür irademle, açıkça rıza gösteriyorum.

ÖĞRENCİ / YASAL TEMSİLCİ
Ad Soyad: {{student_full_name}}
Tarih: {{contract_date}}
İmza: ________________________
TXT;

        $annexCommitment = <<<'TXT'
EK-2: SEÇİLEN HİZMET PAKETİ DETAYI VE KAPSAM LİSTESİ
Paket Adı: {{package_name}}

1. Kapsama Dahil Olan Hizmetler:

Akademik Planlama: Öğrencinin akademik geçmişine uygun en fazla {{max_university_count}} adet üniversite/bölüm tespit edilmesi ve başvuru takviminin oluşturulması.

Evrak Yönetimi: Üniversite başvuru evrak listesinin iletilmesi, motivasyon mektubu ve CV taslaklarının formata uygunluğunun kontrol edilmesi.

Başvuru İşlemleri: Belirlenen üniversitelere veya doğrudan Uni-Assist sistemi üzerinden başvuruların gerçekleştirilmesi ve yazışmaların yürütülmesi.

Vize Danışmanlığı: Almanya Ulusal Öğrenci/Dil Kursu Vizesi için gerekli güncel evrak listesinin temin edilmesi, başvuru dosyasının kontrolü ve vize randevu alım sürecinde rehberlik sağlanması. (Randevu tarihlerinin belirlenmesi tamamen konsolosluğun inisiyatifindedir; Danışman erken randevu garantisi vermez.)

Bloke Hesap Rehberliği: Almanya'nın talep ettiği bloke hesap (Sperrkonto) açılışı ve zorunlu sağlık sigortası başvuru süreçlerinde bilgi aktarımı ve yönlendirme.

2. Kapsam Dışında Kalan Hizmetler ve Masraflar (Öğrenciye Ait Olanlar):

- Üniversitelerin talep ettiği başvuru harçları ve Uni-Assist işlem ücretleri.
- Vize harcı, iDATA/VFS Global hizmet bedelleri.
- Noter tasdiki, apostil işlemleri ve yeminli tercüme masrafları.
- Seyahat sağlık sigortası ve bloke hesap açılış/aylık işletim ücretleri.
- Uçak bileti, Almanya'daki konaklama depozitoları, kira bedelleri ve genel yaşam masrafları.

Danışman, yukarıda sayılan üçüncü taraf kurumların talep ettiği ücretlerden, bu kurumlardan kaynaklanan ret kararlarından veya süreç gecikmelerinden sorumlu tutulamaz.

ÖĞRENCİ / YASAL TEMSİLCİ
Ad Soyad: {{student_full_name}}
Tarih: {{contract_date}}
İmza: ________________________
TXT;

        $annexPayment = <<<'TXT'
EK-3: ÖDEME PLANI VE BANKA BİLGİLERİ

Toplam Hizmet Bedeli: {{service_total_price}}

Ödeme Takvimi:
{{payment_plan}}

Ödeme Yapılacak Banka Bilgileri:
Banka: {{bank_name}}
Şube: {{bank_branch}}
IBAN: {{bank_iban}}

ÖNEMLİ: Ödeme açıklamasına öğrenci adı ve sözleşme numarası ({{contract_number}}) yazılmalıdır.
Geç ödeme halinde Danışman, sözleşme fesih hakkını kullanabilir.

ÖĞRENCİ / YASAL TEMSİLCİ
Ad Soyad: {{student_full_name}}
Tarih: {{contract_date}}
İmza: ________________________
TXT;

        DB::table('contract_templates')
            ->where('is_active', true)
            ->update([
                'name'                  => 'MentorDE Danışmanlık Sözleşmesi v2',
                'body_text'             => $body,
                'annex_kvkk_text'       => $annexKvkk,
                'annex_commitment_text' => $annexCommitment,
                'annex_payment_text'    => $annexPayment,
                'updated_at'            => now(),
            ]);
    }

    public function down(): void
    {
        // Geri alma yapılmaz — önceki metin yedeklenmedi
    }
};
