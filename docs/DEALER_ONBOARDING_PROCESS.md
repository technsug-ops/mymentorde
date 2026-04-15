# Bayi Onboarding Süreci — 3 Senaryo

**Proje:** MentorDE
**Kapsam:** Bayi aday, bayilik modelini kabul ettikten sonra süreç
**Son güncelleme:** 15.04.2026

---

## Genel Akış Şeması

```
Aday kabul eder
      │
      ▼
MentorDE: Dealer kaydı + User hesabı
      │
      ▼
MentorDE: Sözleşme taslağı (draft)
      │
      ▼
Manager "Gönder" ─────▶  status: issued
      │
      ▼
Bayi imzalar, upload eder ─▶ status: signed_uploaded
      │
      ▼
Manager "Onayla" ────▶  status: approved  ✅ BAYİ AKTİF
      │
      └──(iptal)──▶  status: cancelled
```

---

## Senaryo 1 — Lead Generation Bayi

> Sadece lead (isim + iletişim) paylaşır. Aktif ikna veya operasyon yok.

### MentorDE tarafında

1. **Bayi kaydı** — Manager panelinden `Dealer` olarak oluşturulur
   (`dealer_type_code: lead_generation` veya `referrer`)
2. **Kullanıcı hesabı** — Aynı kişi için `User (role: dealer, dealer_code: LEA-XXXXXX)` açılır, aktivasyon maili gider
3. **Sözleşme hazırlama** — `/manager/business-contracts/create?type=dealer` → bayi seçimi → "Dealer Referans Ortaklığı v1" otomatik seçilir → meta alanlar dolar
4. **Sözleşme gönderimi** — `issue()` → status `draft → issued` → bayiye e-posta / portalda notification
5. **İmzalı sözleşme alınır** — `uploadSigned()` → status `signed_uploaded`
6. **Onay** — Manager `approve()` → status `approved` → bayi artık aktif
7. **Komisyon kurulumu** — `dealers.default_commission_config` lead başı flat ücret veya yüzdelik (ayarlanır)
8. **Eğitim** — Link paylaşım araçları: UTM link üretici (`marketing_tracking_links`), WhatsApp mesaj şablonları

### Bayi tarafında

1. **Davet maili / SMS** alır, şifre belirler
2. **Portala giriş** (`/login` → dealer layout)
3. **Sözleşme ekranı** — dashboard'da bekleyen sözleşme kartı görünür, metni okur
4. **İmza süreci** — (a) PDF indirir, fiziksel imzalar, tarar, yükler *veya* (b) dijital imza akışı (ileride)
5. **Onay bekleme** — MentorDE tarafı approved yaptıktan sonra portal tam aktif olur
6. **Lead paylaşımı başlar** — "Link Oluştur" ile UTM'li kişisel linklerini alır, WhatsApp/Telegram/sosyal medyada paylaşır
7. **Raporlama** — Kendi paylaştığı linkten gelen adayların sayısı + durumları dealer dashboard'unda

**Hakediş:** Aday öğrenci sözleşmeyi imzaladığında (student dönüşümü) → flat/yüzde komisyon → `dealer_student_revenues` kaydı → aylık payout.

---

## Senaryo 2 — Freelance Danışmanlık Bayi

> Aktif yönlendirme + ön ikna yapar, danışmanlık verir. Sözleşme aşaması bayinin bilgisine dayanır.

### MentorDE tarafında

1. **Bayi kaydı** (`dealer_type_code: freelance_danisman`) + User hesabı
2. **Yetkinlik onayı** — Freelance danışman CV / referans kontrolü (manuel)
3. **Sözleşme hazırlama** — bayi seçimi → "Dealer Referans Ortaklığı v1" otomatik (aynı hukuki şablon)
4. **Eğitim içerik paylaşımı** — DAM'dan "Freelance Bayi" kategorisine erişim yetkisi + satış sunumları / fiyat listesi
5. **Komisyon ayarı** — Lead Gen'den daha yüksek, aday sözleşmesi imzalandığında + opsiyonel "ön ikna" bonusu
6. **Mentor pairing** — Her freelance bayiye bir senior/mentor atanır (haftalık Q&A kanalı)
7. **Performans takibi** — Conversion oranı, ortalama paket değeri → manager analitik panelinde

### Bayi tarafında

1. **Davet + giriş** (standart)
2. **Eğitim modülü** — Dashboard'da "İlk adım eğitimi" (ürün bilgisi, fiyatlandırma, itiraz cevapları)
3. **Sözleşme imzası** (standart akış)
4. **CRM-lite** — Kendi adaylarını (leads) ekleyebilir, görüşme notları tutabilir, aşamalarını güncelleyebilir
5. **Aday yönlendirme** — Ön görüşmeyi kendisi yapar → hazır olanı MentorDE'ye "ready_for_contract" flag'iyle iletir
6. **Mentor desteği** — Atanmış mentor ile IM üzerinden iletişim (zaten mevcut)
7. **Ödeme** — Aday student'a dönüşünce komisyon → dealer payout ekranından takip

**Hakediş:** Lead Gen'den daha yüksek oran, bazen ön iknadan bonus + aday öğrenci oldukça yüzde.

---

## Senaryo 3 — Operasyon / B2B Partner Bayi

> Sürecin tamamını yönetir: aday bulma, satış, operasyon, belge takibi.

### MentorDE tarafında

1. **Kurumsal anlaşma ön görüşmesi** — Tüzel kişi, ofis, ekip bilgisi doğrulanır
2. **Bayi kaydı** (`dealer_type_code: operational` veya `b2b_partner`) + en az bir User hesabı (bayi yöneticisi)
3. **Ek kullanıcı desteği** — Bayi ekibindeki operasyon asistanları için ayrıca User hesabı açılabilir
4. **Sözleşme hazırlama** — "Dealer Operasyon Sözleşmesi v1" otomatik seçilir (ayrı hukuki şablon)
5. **Eğitim bütünü** — DAM'dan tam erişim: operasyon SOP'leri, vize/üniversite rehberi, sözleşme şablonları
6. **Bölge/kapasite ataması** — Bayiye özel şehir/ülke kısıtı
7. **Payout altyapısı** — `dealer_payout_accounts` IBAN/hesap bilgisi, otomatik payout milestone'ları

### Bayi tarafında

1. **Davet + onboarding** — Kapsamlı eğitim modülü (birkaç saat)
2. **Sözleşme imzası** (operasyon şablonu)
3. **Portal tam yetki** — Kendi adaylarını, öğrencilerini, belge akışını, fatura/ödemeleri görür
4. **Öğrenci ekleme** — Kendi CRM'i gibi kullanır: aday ekle → paket seç → sözleşme üret → ödeme takibi
5. **Belge yükleme** — Pasaport, diploma, dil belgesi, transkript — bayi kendi yükler
6. **Operasyon takibi** — Vize randevusu, YÖS/dil kursu kaydı, uçak bileti — tüm adımları kendi günceller
7. **Ödeme akışı** — Öğrenciden alınan paket tutarı üzerinden %X komisyon, milestone'lu payout
8. **Raporlama** — Bayi dashboard'u: aktif öğrenci sayısı, aylık gelir, milestone durumu, payout geçmişi

**Hakediş:** En yüksek yüzde, milestone bazlı (sözleşme imzası + vize + kayıt tamamlanma). Düzenli payout.

---

## Sözleşme Hukuki Akış (Ortak)

| Aşama | Kim tetikler | Teknik çağrı | Status |
|---|---|---|---|
| Taslak | Manager (create form) | `BusinessContractService::create()` | `draft` |
| Gönderim | Manager | `issue()` | `issued` |
| İmzalı yükleme | Bayi veya Manager | `uploadSigned()` | `signed_uploaded` |
| Onay | Manager | `approve()` | `approved` |
| İptal | Manager | `cancel()` | `cancelled` |

---

## Template ↔ Kategori Eşleşmesi

| Bayi Kategorisi | dealer_type_code | Sözleşme Template |
|---|---|---|
| Lead Generation | `lead_generation`, `referrer` | `dealer_referral_v1` |
| Freelance Danışmanlık | `freelance_danisman` | `dealer_referral_v1` |
| Operasyon | `operational`, `b2b_partner` | `dealer_operations_v1` |

Lead Generation ve Freelance Danışmanlık aynı hukuki şablonu paylaşır; iş modeli farkı
sistemde kategori etiketiyle ayrılır. Operasyon ayrı bir hukuki şablon kullanır.

---

*MentorDE — Business Contracts Module*
