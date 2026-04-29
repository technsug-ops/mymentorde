<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\KnowledgeSource;
use Illuminate\Database\Seeder;

/**
 * Almanya konsolosluğu vize portali (Auswärtiges Amt Auslandsportal — VIDEX)
 * AI Labs knowledge base'ine eklenir. Aday + senior + manager AI asistana
 * vize başvurusu sorduğunda buradan cevap alır.
 *
 * Kaynaklar:
 *  - Auslandsportal portal genel akış
 *  - VIDEX 7 bölüm rehber (Vertretung, Person, Kontaktdaten, Ausweispapiere, Reisedaten, Referenz, Lebensunterhalt)
 *  - 14 zorunlu belge listesi
 *  - Sperrkonto + Krankenversicherung pratik notlar
 */
class VisaPortalKnowledgeSourceSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->select('id')->get();
        if ($companies->isEmpty()) return;

        $sources = $this->sources();

        foreach ($companies as $company) {
            foreach ($sources as $src) {
                KnowledgeSource::query()
                    ->updateOrCreate(
                        ['company_id' => $company->id, 'url' => $src['url']],
                        $src + ['company_id' => $company->id]
                    );
            }
        }

        $this->command?->info('VisaPortalKnowledgeSourceSeeder: ' . (count($sources) * $companies->count()) . ' kaynak seedlendi.');
    }

    /** @return array<int,array<string,mixed>> */
    private function sources(): array
    {
        return [
            [
                'title' => 'Almanya Vize Portali (Auswärtiges Amt Auslandsportal — Genel Akış)',
                'type' => 'url',
                'category' => 'Vize',
                'url' => 'https://auslandsportal.diplo.de',
                'content_markdown' => $this->portalGenelMd(),
                'target_audience' => 'both',
                'is_active' => true,
                'visible_to_roles' => ['student', 'senior', 'manager'],
            ],
            [
                'title' => 'VIDEX Vize Başvuru Formu — 7 Bölüm Rehberi',
                'type' => 'url',
                'category' => 'Vize',
                'url' => 'https://videx-national.diplo.de',
                'content_markdown' => $this->videx7BolumMd(),
                'target_audience' => 'both',
                'is_active' => true,
                'visible_to_roles' => ['student', 'senior', 'manager'],
            ],
            [
                'title' => 'Vize Başvurusu — 14 Zorunlu Belge',
                'type' => 'url',
                'category' => 'Vize',
                'url' => 'https://www.auswaertiges-amt.de/de/visa-service/-/231148',
                'content_markdown' => $this->belgelerMd(),
                'target_audience' => 'both',
                'is_active' => true,
                'visible_to_roles' => ['student', 'senior', 'manager'],
            ],
            [
                'title' => 'Sperrkonto + Krankenversicherung — Vize için Geçim İspatı',
                'type' => 'url',
                'category' => 'Vize',
                'url' => 'https://www.auswaertiges-amt.de/de/visa-service/lebensunterhalt',
                'content_markdown' => $this->sperrkontoMd(),
                'target_audience' => 'both',
                'is_active' => true,
                'visible_to_roles' => ['student', 'senior', 'manager'],
            ],
        ];
    }

    private function portalGenelMd(): string
    {
        return <<<'MD'
# Almanya Vize Portali (Auswärtiges Amt Auslandsportal)

## Resmi Portal

**URL:** https://auslandsportal.diplo.de
**Kapsam:** Almanya konsolosluğu online vize başvurusu (uzun dönem — Studium, Sprachkurs, Familienzusammenführung, Erwerbstätigkeit vb.)

## Akış (5 Aşama)

1. **Anmeldung / Registrierung** — Hesap aç:
   - Familienname, Vorname(n), E-Mail (2x), Passwort (2x)
   - Yetki temsili checkbox (firma adına başvuru için)
   - DSGVO onayı
   - **Bestätigungscode** (6-haneli email doğrulama)

2. **Vorgang oluşturma** — Yeni başvuru:
   - Visum tipi seç (long-stay national visa)
   - Ülke: Türkei
   - Konsolosluk: İstanbul / Ankara / İzmir

3. **3 ana bölüm**:
   - **Ihr Einstiegsformular** — sistemin kendi config'i (öğrenci doldurmaz)
   - **Ihr Antrag (VIDEX)** — 7/7 bölüm asıl form (BU REHBERİN ANA YAPISI)
   - **Ihre Dokumente** — 14/14 belge upload

4. **Antrag gönderim → Termin** — onay sonrası konsolosluk randevusu

5. **Bearbeitung** → Pass + diğer evraklar konsolosluktan / Dienstleister'dan geri alınır

## VIDEX Form — 7 Bölüm (Sıralı)

| # | Bölüm (Almanca) | Türkçe Karşılık |
|---|-----------------|------------------|
| 1 | Angaben zur Vertretung | Temsili kim için yapıyor (kendi/vekil/küçük) |
| 2 | Angaben zur Person | Kişisel bilgi (kapsamlı — ebeveyn dahil) |
| 3 | Kontaktdaten | İletişim adres |
| 4 | Ausweispapiere | Pasaport |
| 5 | Reisedaten | Seyahat amaç + tarih |
| 6 | Referenz | Almanya'daki referans (üni / dil okulu / firma) |
| 7 | Lebensunterhalt und Aufenthaltsdetails | Geçim + konaklama |

## Türkiye'den Başvuru

- Konsolosluklar: İstanbul (en yoğun), Ankara, İzmir
- Randevu: **iDATA** üzerinden (resmi service partner)
- Pasaport bizzat teslim → 4-12 hafta işleme süresi (öğrenci vize için 6-8 hafta tipik)
- Ücret: **75 EUR** (öğrenci vizesi) — iDATA ek hizmet bedeli ayrı

## MentorDE Vize Rehberi ile İlişki

`/manager/students/{studentId}/vize-rehber` — Auswärtiges Amt mavi temalı (#003c8f) 7 sekmeli klon. Manager kopyala-yapıştır ile hatasız doldurur.

## Kaynak

Resmi portal: https://auslandsportal.diplo.de
Genel info: https://www.auswaertiges-amt.de/de/visa-service
MD;
    }

    private function videx7BolumMd(): string
    {
        return <<<'MD'
# VIDEX Vize Başvuru Formu — 7 Bölüm Rehberi

VIDEX = **Vi**sa **D**aten **EX**plorer. Auswärtiges Amt'ın online vize formu.

## 1. Angaben zur Vertretung (Temsili Kim İçin)

**Stellen Sie den Antrag für sich selbst oder andere Personen?**
- 🔘 Ich besitze das Konto und stelle diesen Visaantrag für mich. *(default — öğrenci kendisi başvuruyor)*
- ⚪ Konto sahibim, başkası için (yetişkin) başvuruyorum
- ⚪ Konto sahibim, küçük (minderjährige) için

**Velayet:** Küçük için 18 yaş altı vekil → Vollmacht/velayet belgesi yüklenmeli.

## 2. Angaben zur Person

**Personendaten der/des Antragstellenden:**
- Familienname* / Vorname(n)*
- Geburtsname (varsa evlilik öncesi soyadı)
- Geburtsdatum (TT.MM.JJJJ)*
- Geburtsort* / Geburtsland* (Türkei)
- Geschlecht* (männlich / weiblich / divers / unbestimmt)
- Familienstand* (Ledig / Verheiratet / Geschieden / Verwitwet)
- Derzeitige Staatsangehörigkeit* (Türkei)
- Frühere Staatsangehörigkeit (boş)
- Haben Sie Kinder? (Ja/Nein) — yetişkin çocuklar dahil

**Eltern:**
- Familienname/Vorname(n) des Vaters*
- Staatsangehörigkeit/Geburtsdatum/Geburtsort/Wohnort des Vaters
- Aynısı Mutter için

**Beruf:**
- Erlernter Beruf* (örn. "Student/in, Praktikant/-in")
- Derzeitige berufliche Tätigkeit (sofern abweichend)

## 3. Kontaktdaten

**Aktuelle Anschrift:**
- Straße* + Hausnummer*
- Sonstige Adressangaben (apartman/blok)
- Postleitzahl*
- Ort* (örn. "ATASEHIR/ISTANBUL")
- Land* (Türkei)
- Telefon-/Mobilfunknummer* (uluslararası: 0090...)
- E-Mail (auto)
- Wohnsitz in einem anderen Staat? Ja/Nein

## 4. Ausweispapiere

**Reisedokument:**
- Art* (Reisepass / Sondernpass)
- Nummer* (örn. "U30028632")
- Ausstellungsdatum* (TT.MM.JJJJ)
- Ausstellender Staat* (Türkei)
- Gültig bis* (pasaport bitiş — başvuru tarihinden itibaren minimum 12 ay geçerli olmalı, bazıları 15 ay ister)
- Ausgestellt von / Ausgestellt in (örn. "ATASEHIR" / "ISTANBUL")

## 5. Reisedaten

**Zweck des Aufenthalts*:**
- Studium (lisans/yüksek lisans)
- Sprachkurs (Almanca dil kursu)
- Studienbewerbung (henüz kabul yok)
- Studienkolleg / Promotion / Erwerbstätigkeit / Familienzusammenführung / Sonstiges

**Beabsichtigte Dauer:**
- Von (TT.MM.JJJJ — başlangıç)
- Bis (TT.MM.JJJJ — bitiş)
- Checkbox: "12 aydan uzun kalmama niyetim" (öğrenci genelde tersini seçer)

## 6. Referenz

**Art der Referenz**:
- Bildungseinrichtung/Firma/Organisation → öğrenci için **dil okulu / üniversite**
- Privatperson → aile birleşiminde

**Bildungseinrichtung/Firma:**
- Name (örn. "INTERKULTURELLES BILDUNG ZENTRUM")
- Sitz: Ort + Land (Essen, Deutschland)
- Aufgabenstellung / Wirkungsbereich (dropdown)
- Name des Registers / Ort / Registernummer (Handelsregister bilgisi)
- **Ansprechperson:**
  - Familienname* / Vorname(n)* / Geschlecht / Geburtsdatum / Staatsangehörigkeit
  - Adres: Straße / Hausnummer / Postleitzahl / Ort / Land
  - Telefon / E-Mail

## 7. Lebensunterhalt und Aufenthaltsdetails

**Lebensunterhalt (Geçim):**
- 🔘 **SPERRKONTO** (en yaygın — bloke hesap, ~12,000€/yıl)
- ⚪ Stipendium (burs)
- ⚪ Verpflichtungserklärung (mali sorumluluk taahhütnamesi)
- ⚪ Eigenes Vermögen / Erwerbstätigkeit

**Verpflichtungserklärung var mı?**
- 🔘 Nein (Sperrkonto ile başvurularda default)
- ⚪ Ja, von der Referenz
- ⚪ Ja, durch abweichende(n) Verpflichtungsgebende(n)

**Vorgesehener Aufenthaltsort in Deutschland:**
- Straße + Hausnummer
- Postleitzahl + Ort (örn. "60435 FRANKFURT A.M")
- Wie werden Sie untergebracht? Einzelzimmer / Wohnung / Sammelunterkunft / Sonstiges
- Notiz (örn. "Wohnung gehört meiner Tante")
- Ständiger Wohnort außerhalb Deutschlands beibehalten? Ja/Nein
- Familienangehörige mit einreisen? Ja/Nein
- Krankenversicherung var mı? Ja/Nein

**Erklärung (3 zorunlu Nein/Ja):**
- Sind Sie vorbestraft? * → genelde **Nein**
- Sind Sie aus Deutschland ausgewiesen worden / wurde Antrag abgelehnt? * → genelde **Nein**
- Leiden Sie an: Pocken/Polio/Influenza/Cholera/Pest/Gelbfieber/Ebola/SARS? * → genelde **Nein**

## Kritik Pratik Notlar

- Tarih formatı **TT.MM.JJJJ** (Almanya) — Uni-Assist'te aynıydı
- Adres bilgileri **TÜM CAPS** girilmesi tipik (FRANKFURT A.M)
- Telefon uluslararası: **0090** + alan kodu (10 hane)
- Pasaport süresi: vize bitişinden itibaren **min. 3 ay** geçerli kalmalı
- Sperrkonto: 2025 itibarıyla **11,904 €/yıl** (Bafög seviyesi). 12,000+ tavsiye

## Kaynak

Resmi VIDEX: https://videx-national.diplo.de
MD;
    }

    private function belgelerMd(): string
    {
        return <<<'MD'
# Vize Başvurusu — 14 Zorunlu Belge

VIDEX form'u onaylandıktan sonra konsolosluk randevusunda **fiziksel** olarak teslim edilen belgeler.

## Belge Listesi

| # | Belge | Türkçe Karşılık | Notlar |
|---|-------|------------------|--------|
| 1 | **Pass** | Pasaport | Geçerlilik vize bitişinden +3 ay, son 10 yılda alınmış olmalı |
| 2 | **Schulabschluss** | Lise diploması | Apostilli + Almanca/İngilizce yeminli tercüme |
| 3 | **Anerkennung des Schulabschlusses** | Lise denkliği (VPD) | Uni-Assist'in çıkardığı doküman |
| 4 | **Sprachzertifikat** | Almanca dil sertifikası | Goethe/TestDaF/DSH/telc — A2-B1-B2-C1 (programa göre) |
| 5 | **Anmeldebestätigung Sprachkurs** | Dil okulu kayıt belgesi | Studienvorbereitend — ön kayıt resmi yazısı |
| 6 | **Erläuterung der Studienabsicht** | Motivasyon mektubu | Almanca/İngilizce, neden Almanya? |
| 7 | **Nachweis Studiengebühren** | Öğrenim ücreti finansman ispatı | Devlet üni'lerinde genelde yok, özelde gerekli |
| 8 | **Lebenslauf** | CV | Almanca/İngilizce, kronolojik |
| 9 | **Nachweis Lebensunterhalt** | Geçim ispatı | **Sperrkonto** (~12,000€/yıl) veya Stipendium veya Verpflichtungserklärung |
| 10 | **Krankenversicherung** | Sağlık sigortası | İlk 3 ay turistik (Mawista, Care Concept), sonra public/private statü değişir |
| 11 | **Zahlungsnachweis Dienstleister** | iDATA ödeme dekontu | TR'de iDATA'ya yapılan ücret |
| 12 | **Aufenthaltstitel im Antragsland** | Türkiye dışında ikamet izni | Opsiyonel — TR vatandaşıysa boş |
| 13 | **Sonstige Dokumente** | Diğer belgeler | Opsiyonel — APS, ek finansman vs. |
| 14 | **Email & Adresse + Wohnsitzbescheinigung** | Email/adres beyanı + Türkiye ikamet belgesi | Bazı konsolosluklar nüfus kayıt örneği ister |

## Tüm Belgeler İçin Standart Kurallar

- **Format:** Orijinal + 2 fotokopi (her biri için)
- **Dil:** Almanca veya İngilizce. TR belgeler için **yeminli tercüman** zorunlu
- **Boyut:** A4 — küçük belgeler (sertifika vs.) A4'e büyütülmeli
- **Apostil:** Lise diploması, evlilik cüzdanı, doğum belgesi → **Kaymakamlık apostil**

## Konsolosluk Randevusunda

- Belgeleri **konsolosluk-style klasöre** koyar (ayrılmış sıralı, etiketsiz)
- Memur sırayla isteyecek, sıraya göre numaralanmış olması kritik
- Eksik belgede randevu **iptal** edilebilir → tekrar 6-8 hafta beklenir

## Türkiye-Spesifik Belgeler

### APS Sertifikası (Türk başvuranlar için zorunlu)
- Akademik Tetkik Merkezi (Almanya konsolosluğu içinde)
- Lise diploması + transkript orijinallik kontrolü
- Ücret: ~125 USD, süre: 4-6 hafta
- **Lisans/Bachelor başvuranlar için zorunlu** — lise sonrası direkt Almanya'ya başvuran herkesten istenir

### Nüfus Kayıt Örneği
- E-Devlet'ten alınır, son 3 ay geçerli
- Almanca tercümesi yapılır

### Adli Sicil Kaydı (sadece bazı vize tipleri için)
- Erwerbstätigkeit / Familienzusammenführung için → evet
- Studium için genelde gerekmez

## Belge Kontrol Listesi (Manager için)

```
[ ] Pasaport (geçerlilik kontrolü — vize bitiş + 3 ay)
[ ] Lise diploması + transkript (apostil + tercüme)
[ ] Dil sertifikası (Goethe/TestDaF/DSH)
[ ] Dil okulu kayıt yazısı (öğrenci kabul)
[ ] Sperrkonto açılışı (12,000+ €) veya Verpflichtungserklärung
[ ] Sağlık sigortası (Mawista/Care Concept)
[ ] APS sertifikası (Türkiye'den lisans için)
[ ] Motivasyon mektubu (Almanca)
[ ] CV (Almanca, kronolojik)
[ ] iDATA ödeme dekontu
[ ] Nüfus kayıt örneği + tercüme
```

## Kaynak

https://www.auswaertiges-amt.de/de/visa-service
MD;
    }

    private function sperrkontoMd(): string
    {
        return <<<'MD'
# Sperrkonto + Krankenversicherung — Vize için Geçim İspatı

## Sperrkonto (Bloke Hesap)

### Nedir?
Almanya'ya öğrenci vizesiyle giden kişilerin, ülkede yıllık geçimini karşılayabileceğini ispatlayan bloke hesap. Vize sürecinde **zorunlu** (Stipendium veya Verpflichtungserklärung yoksa).

### Tutar (2025-2026)

| Yıl | Yıllık Tutar | Aylık Çekme |
|-----|---------------|--------------|
| 2024 | 11,208 € | 934 € |
| **2025** | **11,904 €** | **992 €** |
| **2026 tahmini** | ~12,500 € | ~1,041 € |

> **Pratik tavsiye:** 12,000 € yerine **12,500 €** yatır — yıl ortasında Bafög oranı yükselirse fark istenmesin

### Sperrkonto Açan Bankalar (Türkiye'den)

| Banka | Açılış Ücreti | Aylık Yönetim | Avantaj |
|-------|----------------|----------------|---------|
| **Coracle** | 99 € | yok | %100 online, 24-48 saat onay, EN destek |
| **Fintiba** | 89 € | 5,90 €/ay | Banka değil, partner: Sutor Bank |
| **Expatrio** | 49 € | 5 €/ay | TR konsolosluk-friendly, Care Concept paket bundle |
| **Deutsche Bank** | 150 € | yok | Klasik banka, randevu Almanya'da gerekli |

### Süreç

1. Online başvuru (web sitesinde) — 10-15 dakika
2. Pasaport scanlı yüklenir
3. **Identverify** (video kimlik doğrulama) — 10 dakika, EN/DE
4. IBAN ve havale talimatı gelir
5. Türkiye bankasından **OUR yöntemi ile EUR havalesi** (komisyon 30-50 €)
6. Para hesaba ulaştığında "Bestätigung" PDF'i hazırlanır (vize için kullanılır)

### Kritik Notlar

- ✅ **Fintiba/Coracle** birkaç saatte açılır — vize randevusuna 2 hafta varsa ideal
- ⚠ **Deutsche Bank** Türkiye'den açılması zor — Almanya'ya gittikten sonra ek şube ziyareti gerekir
- ⚠ Havale notu (**Verwendungszweck**) tam yazılmalı: "[Sperrkonto No] [Ad Soyad] [Doğum Tarihi]"
- 🔄 Vize alındıktan sonra Almanya'ya gidip **Anmeldung** yapınca → bloke hesap normal hesaba dönüşür, aylık 992 € çekilebilir

## Krankenversicherung (Sağlık Sigortası)

### Vize için Zorunlu Sigorta Türleri

**Aşama 1: Vize başvurusu için (ilk 3 ay)**
- **Reisekrankenversicherung** (turistik sağlık sigortası)
- Almanya geliş + ilk 90 gün için yeterli
- Tutar: 30,000+ €

**Aşama 2: Almanya'ya gelince**
- **Public sigorta (GKV)** — TK, AOK, Barmer
  - Aylık: ~120 € (öğrenci tarifesi, 30 yaş altı)
  - 30 yaş üstü → public ret eder
- **Private sigorta (PKV)** — Mawista, Care Concept
  - Aylık: 35-95 € (yaşa göre)
  - Avantaj: Lisans öğrencisi 30 yaş üstüyse zorunlu seçenek

### Vize Başvurusu için En Pratik Sigortalar

| Sigorta | Aylık | Süre | Vize Onayı | Notlar |
|---------|-------|------|------------|--------|
| **Mawista Student** | 33 € | 1-12 ay | ✅ Genelde geçer | %100 online, 5 dakikada poliçe |
| **Care Concept Standard** | 35-49 € | 1-60 ay | ✅ | Almanya'da en eski student sigortası |
| **DR-WALTER** | 38 € | 12 ay | ✅ | Öğrenci-spesifik tarife |
| **Hanse Merkur** | 30 € | 1-3 ay | ✅ | Sadece kısa dönem (turistik) |

### Pratik Akış

1. **Vize için:** Mawista'dan 12 aylık poliçe al — 396 €
2. Almanya'ya geldikten sonra **Anmeldung** + üniversite kaydı yap
3. Üniversite öğrenci sigortası (TK/AOK) için kaydını yaptır
4. Mawista'yı **iptal et** (TK kaydı geldikten sonra)
5. Sonraki tüm aylar TK üzerinden — Bafög-dostu

### Vize Başvurusunda İstenen Belge Format

- **Versicherungsbescheinigung** PDF
- En az **30,000 €** kapsama
- "Anerkannt vom Auswärtigen Amt" yazısı (Mawista/Care Concept'te otomatik var)
- Geçerlilik tarihi: vize başlangıç tarihinden itibaren **en az 90 gün**

## Manager Pratik Notları

### Sperrkonto açtırırken
1. Öğrenciye **Coracle veya Fintiba** öner — TR'den hızlı + EN destek
2. **OUR havale** kuralını anlat (gönderen tüm masrafları öder)
3. 12,500 € yatırılmasını söyle (12,000 yetersiz olabilir)
4. Bestätigung PDF'i geldiğinde MentorDE doc_request ile yükle

### Sigorta seçimi
- Vize için: Mawista 12 aylık (33 €/ay × 12 = 396 €)
- Almanya'ya gelince TK'ya geç (TK öğrenci sigortası 120 €/ay)

## Kaynak

Auswärtiges Amt: https://www.auswaertiges-amt.de/de/visa-service/lebensunterhalt
Bafög seviyesi: https://www.bafög.de
Expatrio Sperrkonto: https://www.expatrio.com
MD;
    }
}
