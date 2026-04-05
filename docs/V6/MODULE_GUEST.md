# Modül: Guest (Aday) Portali

**URL Prefix:** `/guest` (portal) + `/apply` (başvuru)
**Middleware:** `EnsureGuestRole`
**Layout:** `resources/views/guest/layouts/app.blade.php`

---

## Amaç

Almanya'da eğitim almak isteyen adayların başvuru yapıp süreçlerini takip ettiği portal. İki ayrı akıştan oluşur:
1. **Başvuru Akışı** (`/apply`) — Kayıtsız veya bağlantısız kullanıcı başvurusu
2. **Guest Portal** (`/guest`) — Kayıtlı aday kendi sürecini yönetir

---

## Başvuru Akışı (`/apply`)

**Controller:** `app/Http/Controllers/GuestApplicationController.php`

| URL | Açıklama |
|-----|----------|
| `/apply` | Başvuru formu (create.blade.php) |
| `/apply/success` | Başvuru başarı sayfası |
| `/apply/status` | Tracking token ile durum sorgulama |

- Başvuru oluşturulunca `GuestApplication` kaydı yaratılır
- `lead_status = 'new'` ile başlar
- `tracking_token` ile anonim takip mümkün
- UTM parametreleri (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `click_id`) otomatik kaydedilir
- Dealer linki üzerinden gelindiyse `dealer_code` atanır

---

## Guest Portal (`/guest`)

**Controller:** `app/Http/Controllers/Guest/PortalController.php`

### Sayfa Listesi

| URL | View | Açıklama |
|-----|------|----------|
| `/guest/dashboard` | `guest/dashboard` | Ana dashboard + timeline |
| `/guest/registration-form` | `guest/registration-form` | Kayıt formu doldurma |
| `/guest/registration/documents` | `guest/registration-documents` | Belge yükleme |
| `/guest/services` | `guest/services` | Servis paketi seçimi |
| `/guest/contract` | `guest/contract` | Sözleşme görüntüleme/imzalama |
| `/guest/profile` | `guest/profile` | Profil |
| `/guest/messages` | `guest/messages` | DM mesajlaşma |
| `/guest/tickets` | `guest/tickets` | Destek talepleri |
| `/guest/settings` | `guest/settings` | Bildirim ve tercihler |

---

## Dashboard & Gerçek Zamanlı İlerleme Timeline

**Dosya:** `app/Http/Controllers/Guest/PortalController.php:dashboard()`

### Progress Steps (5 adım)
Dashboard'da yatay timeline ile gösterilir:

| # | Adım | Tamamlanma Koşulu |
|---|------|-------------------|
| 1 | Kayıt Formu | `registration_form_submitted_at` dolu |
| 2 | Belgeler | Zorunlu belgeler yüklenmiş |
| 3 | Paket Seçimi | `selected_package_code` dolu |
| 4 | Sözleşme / Onay | `contract_status = signed` |
| 5 | Kayıt Tamamlandı | `converted_to_student = true` |

### Next Step CTA
```php
$nextStep = collect($progress)->slice(0, 4)->first(fn($s) => !$s['done']);
$heroNextStep = [
    'label', 'url', 'icon', 'cta_text', 'estimated_time'
]
```
Her adım için tahmini süre ve CTA metni otomatik belirlenir.

### Motivasyon Mesajı
İlerleme yüzdesine göre (`0% → 100%`) 7 farklı motivasyon mesajı.

### Senior Kartı
Atanan senior'ın bilgileri + DM yönlendirme linki.

---

## GuestApplication Modeli

**Dosya:** `app/Models/GuestApplication.php`
**Trait'ler:** `BelongsToCompany`, `SoftDeletes`

### Önemli Alan Grupları

| Grup | Alanlar |
|------|---------|
| Kimlik | `first_name`, `last_name`, `email`, `phone`, `gender` |
| Başvuru | `application_type`, `application_country`, `target_term`, `target_city` |
| Kaynak | `lead_source`, `dealer_code`, `campaign_code`, `tracking_link_code` |
| UTM | `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `click_id`, `landing_url` |
| Atama | `assigned_senior_email`, `assigned_at`, `assigned_by` |
| Durum | `lead_status`, `priority`, `risk_level` |
| Sözleşme | `contract_status`, `contract_template_id`, `contract_signed_at`, `contract_snapshot_text` |
| Paket | `selected_package_code`, `selected_package_title`, `selected_package_price` |
| Dönüşüm | `converted_to_student`, `converted_student_id`, `converted_at` |
| Scoring | `lead_score`, `lead_score_tier` |
| Bildirim | `notifications_enabled`, `notify_email`, `notify_whatsapp`, `notify_inapp` |

---

## Lead Status Akışı

```
new → contacted → qualified → negotiation → converted
                                          → rejected
                                          → lost
any → archived (soft-delete benzeri)
```

**Geçişler:** `ManagerPortalController` ve `SeniorPortalController` tarafından yönetilir.

---

## Onboarding

- `GuestOnboardingStep` modeli — adım bazlı ilk kullanım rehberi
- Dashboard ilk açıldığında modal olarak gösterilir
- `STEPS` sabiti ile tanımlı adım sırası
- `completed_at` veya `skipped_at` → adım tamamlandı sayılır

---

## Rozet Sistemi (Achievements)

- `GuestAchievement` modeli
- `config/guest_achievements.php` — rozet tanımları (icon, points, label)
- Dashboard'da kazanılan rozetler + toplam puan
- Sonraki rozet için ipucu (`nextAchievement`)

---

## Workflow Otomasyonu Entegrasyonu

Guest başvurusu belirli aşamalara geldiğinde `WorkflowEngineService::enroll()` çağrılır:
- Lead status değişimi → workflow trigger
- Enrollment → node'lar çalışır (email gönder, puan ekle, task oluştur)
- **Dosya:** `app/Services/WorkflowEngineService.php`

---

## Bağımlı Servisler

| Servis | Kullanım |
|--------|----------|
| `ContractTemplateService` | Sözleşme şablonu yönetimi |
| `GuestRegistrationFieldSchemaService` | Dinamik kayıt formu alanları |
| `GuestTimelineService` | İlerleme adımları hesaplama |
| `AiGuestAssistantService` | AI destekli yardımcı |
| `CurrencyRateService` | Döviz kuru gösterimi |

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller (Portal) | `app/Http/Controllers/Guest/PortalController.php` |
| Controller (Başvuru) | `app/Http/Controllers/GuestApplicationController.php` |
| Controller (Workflow) | `app/Http/Controllers/Guest/WorkflowController.php` |
| Model | `app/Models/GuestApplication.php` |
| Middleware | `app/Http/Middleware/EnsureGuestRole.php` |
| Middleware | `app/Http/Middleware/EnsureGuestOwnsDocument.php` |
| Middleware | `app/Http/Middleware/EnsureGuestOwnsTicket.php` |
| JS (Kayıt Formu) | `public/js/guest-registration-form.js` |
| JS (Belgeler) | `public/js/guest-registration-documents.js` |
| JS (Mesajlar) | `public/js/guest-messages.js` |
| JS (Tickets) | `public/js/guest-tickets.js` |
| JS (Sözleşme) | `public/js/guest-contract.js` |
| JS (Profil) | `public/js/guest-profile.js` |
| View (Başvuru) | `resources/views/apply/create.blade.php` |
| View (Dashboard) | `resources/views/guest/dashboard.blade.php` |
