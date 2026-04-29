<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\KnowledgeSource;
use Illuminate\Database\Seeder;

/**
 * Uni-Assist resmi ödeme sayfaları AI Labs knowledge base'ine eklenir.
 * Aday + senior + manager AI asistana sorduğunda buradan cevap alır.
 *
 * Kaynaklar:
 *  - Bearbeitungskosten (işlem ücretleri)
 *  - Zahlungsoptionen (ödeme yöntemleri)
 *  - Rückzahlung (iade)
 */
class UniAssistKnowledgeSourceSeeder extends Seeder
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

        $this->command?->info('UniAssistKnowledgeSourceSeeder: ' . (count($sources) * $companies->count()) . ' kaynak seedlendi.');
    }

    /** @return array<int,array<string,mixed>> */
    private function sources(): array
    {
        return [
            [
                'title' => 'Uni-Assist İşlem Ücretleri (Bearbeitungskosten)',
                'type' => 'url',
                'category' => 'Uni-Assist',
                'url' => 'https://www.uni-assist.de/bewerben/kosten-zahlen/bearbeitungskosten/',
                'content_markdown' => $this->bearbeitungskostenMd(),
                'target_audience' => 'both',
                'is_active' => true,
                'visible_to_roles' => ['student', 'senior', 'manager'],
            ],
            [
                'title' => 'Uni-Assist Ödeme Yöntemleri (Zahlungsoptionen)',
                'type' => 'url',
                'category' => 'Uni-Assist',
                'url' => 'https://www.uni-assist.de/bewerben/kosten-zahlen/zahlungsoptionen/',
                'content_markdown' => $this->zahlungsoptionenMd(),
                'target_audience' => 'both',
                'is_active' => true,
                'visible_to_roles' => ['student', 'senior', 'manager'],
            ],
            [
                'title' => 'Uni-Assist İade (Rückzahlung von Guthaben)',
                'type' => 'url',
                'category' => 'Uni-Assist',
                'url' => 'https://www.uni-assist.de/bewerben/kosten-zahlen/rueckzahlung-von-guthaben/',
                'content_markdown' => $this->rueckzahlungMd(),
                'target_audience' => 'both',
                'is_active' => true,
                'visible_to_roles' => ['student', 'senior', 'manager'],
            ],
        ];
    }

    private function bearbeitungskostenMd(): string
    {
        return <<<'MD'
# Uni-Assist İşlem Ücretleri (Bearbeitungskosten)

## Ücret Tablosu

| Tür | Ücret | Açıklama |
|-----|-------|----------|
| **İlk başvuru (Erstantrag)** | **75,00 EUR** | İlk Studienwunsch — uluslararası sertifika değerlendirmesi + üniversite kriterleri kontrolü dahil |
| **Ek başvuru (jeder weitere Antrag)** | **30,00 EUR** | Aynı dönem içinde her ek programa başvuru |
| **VPD-Verfahren** | 75,00 EUR (aynı) | VPD-only başvuru standart prosedürle aynı |
| **Yeni dönem (kış→yaz)** | Ayrı tam ücret | Her dönem için yeni 75 + (N-1)×30 EUR |

## Önemli Kurallar

- Para birimi: **Euro (EUR)** — başka birim kabul edilmez
- Belirtilmiş belgeler için ek ücret YOK
- "Ücret başvuru sonucundan bağımsız" — kabul olmasa da ödenir, geri alınmaz
- Bazı yüksek okullar masrafları karşılar; durum **My assist** hesabında görünür

## Toplam Hesaplama

Formül: `Toplam = 75 + (üniversite_sayısı − 1) × 30`

Örnek toplam:
- 1 üni: 75 EUR
- 3 üni: 135 EUR
- 5 üni: 195 EUR
- 10 üni: 345 EUR

## Kaynak

Resmi sayfa: https://www.uni-assist.de/bewerben/kosten-zahlen/bearbeitungskosten/
MD;
    }

    private function zahlungsoptionenMd(): string
    {
        return <<<'MD'
# Uni-Assist Ödeme Yöntemleri (Zahlungsoptionen)

## Kabul Edilen Yöntemler

1. **Kredi Kartı** (Visa / MasterCard) — **3D Secure ZORUNLU**, bankada kayıtlı olmak gerekir
2. **Banka Havalesi** (online veya geleneksel SEPA)

## Banka Havalesi Detayları

- Kişisel **IBAN** numarası **My Assist hesabından** alınır (her başvuru için unique)
- **Verwendungszweck (Açıklama)** zorunlu format:
  ```
  [Başvuru numarası] [Ad Soyad] [Doğum tarihi]
  ```
- Yanlış / eksik açıklama → ödeme eşleşmez, gecikir

## Türkiye'den Para Gönderimi (KRİTİK)

- Banka ücretleri (havale komisyonu, SWIFT vs.) **başvuran karşılar**
- **OUR yöntemi** önerilir → tüm ücretler gönderen tarafından, alıcıya tam tutar ulaşır
- Aksi halde Uni-Assist hesabında 75 EUR yerine 73 EUR görünebilir → eksik ödeme uyarısı çıkar
- Döviz kuru: gönderim anındaki kur — Türk bankası komisyonu + alıcı bankası komisyonu çıkar

## Ödeme Sonrası Akış

- **Kart**: anında onay
- **Havale**: birkaç iş günü (uluslararası 3-5 gün)
- **Ödeme tamamlanana kadar başvuru incelemeye alınmaz**
- Başvuru süresi içinde ödeme yapılmazsa → başvuru iptal edilir

## Pratik Tavsiye

- **Hızlı çözüm**: Visa/MasterCard 3D Secure
- **Tasarruflu**: SEPA havale (TR'den OUR ile gönder)
- **Acil durum**: kart kullan, havale 5 gün gecikebilir

## Kaynak

Resmi sayfa: https://www.uni-assist.de/bewerben/kosten-zahlen/zahlungsoptionen/
MD;
    }

    private function rueckzahlungMd(): string
    {
        return <<<'MD'
# Uni-Assist İade (Rückzahlung von Guthaben)

## İade Hakkı Doğan Durumlar

- **Fazla ödeme** (yanlış tutar gönderildi)
- **Ücretsiz başvuru** için yanlışlıkla ödeme (bazı yüksekokullar maliyeti karşılar)
- **Başvuru işleme alınmadan** geri çekilirse

## İade YAPILMAYAN Durumlar

- ⚠ **Süresi geçtikten sonra** geri çekilirse → **10 EUR idari harç** kesilir
- Ödeme yapan kişi **dışında** başkası talep edemez
- Başvuru sonucu olumsuz olduğu için iade istenemez (ücret sonuçtan bağımsız)

## İade Talep Süreci

1. **My Assist iletişim formu** kullanılır:
   https://my.uni-assist.de/hilfe/105
2. Formda **"Rückzahlung des Guthabens wünschen"** seçilir
3. Genel kontakt: https://www.uni-assist.de/kontakt/

## İade Detayları

- **Para birimi**: Ödediğin para biriminde geri alınır (genelde EUR)
- **Banka ücretleri**:
  - SEPA içi (AB ülkeleri): ücretsiz
  - **SEPA dışı (Türkiye dahil) iade**: banka ücretleri **iade edenden düşülür**
  - Pratik: Türkiye'ye 75 EUR iade istenirse ~5-10 EUR ücret kesilebilir
- İşlem süresi: Web'de belirtilmiyor (genelde 2-4 hafta, form üzerinden sorgulanmalı)

## Pratik Tavsiye

- 14 gün içinde başvur (idari harç riski yok)
- Banka ücretleri iadeden düşeceğini öğrenciye söyle (özellikle Türkiye banka)
- İade için ödeme yapılan **aynı kişi** başvurmalı

## Kaynak

Resmi sayfa: https://www.uni-assist.de/bewerben/kosten-zahlen/rueckzahlung-von-guthaben/
MD;
    }
}
