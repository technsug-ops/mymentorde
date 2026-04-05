# Modül: Bayi (Dealer) Portali

**URL Prefix:** `/dealer`
**Middleware:** `EnsureDealerRole`
**Layout:** `resources/views/dealer/layouts/app.blade.php`

---

## Amaç

MentorDE iş ortaklarının (bayilerin) öğrenci yönlendirmelerini, kazançlarını, ödemelerini ve performanslarını takip ettiği portal. Bayi, sisteme aday kayıt ederek komisyon kazanır.

---

## Erişim

- Kullanıcı rolü: `dealer`
- `User::dealer_code` alanı dolu olmalı
- `app/Http/Middleware/EnsureDealerRole.php`
- `CheckDealerTypePermission` middleware ile tip bazlı özellik kontrolü

---

## Sayfa Listesi (13 sayfa)

| URL | View | Açıklama |
|-----|------|----------|
| `/dealer/dashboard` | `dealer/dashboard` | Ana dashboard |
| `/dealer/lead-create` | `dealer/lead-form` | Yeni aday yönlendir |
| `/dealer/leads` | `dealer/leads` | Yönlendirmelerim |
| `/dealer/lead/{id}` | `dealer/lead-detail` | Aday detay |
| `/dealer/earnings` | `dealer/earnings` | Kazanç özeti |
| `/dealer/payments` | `dealer/payments` | Ödeme talepleri |
| `/dealer/advisor` | `dealer/advisor` | Danışman destek talepleri |
| `/dealer/training` | `dealer/training` | Eğitim merkezi |
| `/dealer/referral-links` | `dealer/referral-links` | UTM/referral linkleri |
| `/dealer/performance` | `dealer/performance` | Performans raporu |
| `/dealer/calendar` | `dealer/calendar` | Takvim |
| `/dealer/notifications` | `dealer/notifications` | Bildirimler |
| `/dealer/profile` | `dealer/profile` | Profil |
| `/dealer/settings` | `dealer/settings` | Ayarlar |

---

## Dashboard (`DealerDashboardController`)

**Dosya:** `app/Http/Controllers/DealerDashboardController.php`

### KPI Blokları

| KPI | Hesaplama |
|-----|-----------|
| Toplam Kazanç | `DealerStudentRevenue.total_earned` toplamı |
| Bekleyen Ödeme | `DealerStudentRevenue.total_pending` toplamı |
| Bu Ay Kazanç | Ay başından itibaren `total_earned` |
| Lead Sayısı | `dealer_code` eşleşen `GuestApplication` sayısı |
| Dönüşüm Oranı | `converted_student_id` dolı olanlar / toplam |
| Öğrenci Sayısı | `StudentAssignment.dealer_id` eşleşen |

### Grafikler
- Aylık kazanç eğrisi
- Başvuru tipi dağılımı (bachelor/master/phd)
- Lead kaynak kanalı dağılımı

---

## Temel İşlevler

### 1. Lead (Aday) Yönlendirme
- `/dealer/lead-create` — form doldurarak yeni `GuestApplication` oluştur
- Zorunlu alanlar: ad, soyad, telefon, başvuru tipi, KVKK onayı
- `lead_source = 'dealer_form'` ve `dealer_code` otomatik atanır
- Kayıt sonrası `TaskAutomationService` ve `NotificationService` tetiklenir
- **Dosya:** `DealerPortalController::storeLead()`

### 2. Yönlendirme Takibi
- Tüm yönlendirmeler `GuestApplication.dealer_code` üzerinden filtrelenir
- Lead status: `new → contacted → qualified → converted`
- Aday detay: sözleşme durumu, atanan senior, seçilen paket
- **Dosya:** `DealerPortalController::leads()`, `DealerPortalController::leadDetail()`

### 3. Kazanç & Komisyon Takibi
- `DealerStudentRevenue` — öğrenci bazlı komisyon kayıtları
- `milestone_progress` — milestone ilerlemesi (JSON)
- `total_earned` / `total_pending`
- **Dosya:** `resources/views/dealer/earnings.blade.php`, `public/js/dealer-dashboard.js`

### 4. Ödeme Talepleri
- `DealerPayoutRequest` — bayi ödeme talebi
- `DealerPayoutAccount` — ödeme hesabı bilgileri (IBAN vb.)
- Dekont yükleme (`receipt_url`)
- **Dosya:** `resources/views/dealer/payments.blade.php`

### 5. UTM / Referral Linkleri
- `DealerUtmLink` modeli
- Bayi kendine özel UTM parametreli linkler oluşturur
- Bu linklerden gelen başvurular `tracking_link_code` ile eşleştirilir
- **Dosya:** `resources/views/dealer/referral-links.blade.php`

### 6. Danışman (Advisor) Desteği
- Bayi, sisteme destek talebi oluşturur
- `GuestTicket` benzeri yapı — `dealer_advisor` kategorisi
- **Dosya:** `resources/views/dealer/advisor.blade.php`, `resources/views/dealer/advisor/ticket-detail.blade.php`

### 7. Eğitim Merkezi
- `KnowledgeBaseArticle` modelinden içerik
- `DealerMaterialRead` — okunma takibi
- **Dosya:** `resources/views/dealer/training.blade.php`

---

## Dealer Modeli

**Dosya:** `app/Models/Dealer.php`
**Trait'ler:** `SoftDeletes`

| Alan | Açıklama |
|------|----------|
| `code` | Bayi kodu (büyük harf, benzersiz) |
| `internal_sequence` | Sıra numarası |
| `name` | Bayi adı |
| `email` / `phone` / `whatsapp` | İletişim |
| `dealer_type_code` | Bayi tipi kodu |
| `is_active` | Aktif mi? |
| `is_archived` / `archived_at` | Arşiv |

---

## Bayi Tipleri (DealerType)

**Model:** `app/Models/DealerType.php`
**Migration:** Seeder ile doldurulur (`DealerTypeSeeder`)

- Her `DealerType` farklı komisyon kuralları ve özellik erişimi tanımlayabilir
- `CheckDealerTypePermission` middleware ile tip bazlı route koruması

---

## Revenue Milestone Sistemi

**Modeller:** `DealerRevenueMilestone`, `DealerStudentRevenue`

```
RevenueMilestone (genel eşikler)
  └── DealerRevenueMilestone (bayi bazlı özel eşikler)
        └── DealerStudentRevenue (öğrenci bazlı komisyon detayı)
```

- Milestone'lar belirli kazanç eşiklerini temsil eder
- Her öğrenci dönüşümünde `DealerStudentRevenue` güncellenir
- **Service:** `app/Services/DealerRevenueService.php`

---

## Bağımlı Servisler

| Servis | Kullanım |
|--------|----------|
| `DealerRevenueService` | Komisyon hesaplama |
| `TaskAutomationService` | Lead yönlendirme sonrası görev oluşturma |
| `NotificationService` | Bayi bildirimler |
| `EventLogService` | Aktivite loglama |

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller (Dashboard) | `app/Http/Controllers/DealerDashboardController.php` |
| Controller (Portal) | `app/Http/Controllers/DealerPortalController.php` |
| Model | `app/Models/Dealer.php` |
| Model | `app/Models/DealerStudentRevenue.php` |
| Model | `app/Models/DealerRevenueMilestone.php` |
| Model | `app/Models/DealerPayoutRequest.php` |
| Model | `app/Models/DealerPayoutAccount.php` |
| Model | `app/Models/DealerUtmLink.php` |
| Service | `app/Services/DealerRevenueService.php` |
| Middleware | `app/Http/Middleware/EnsureDealerRole.php` |
| Middleware | `app/Http/Middleware/CheckDealerTypePermission.php` |
| JS | `public/js/dealer-dashboard.js` |
| Seeder | `database/seeders/DealerTypeSeeder.php` |
| Seeder | `database/seeders/DealerRevenueMilestoneSeeder.php` |
