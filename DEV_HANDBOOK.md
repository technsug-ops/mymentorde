# MentorDE — Developer Handbook

**Version 1.0 | 2026**

> Geliştirici devri, onboarding ve teknik referans için hazırlanmıştır.
> Hem iş mantığını hem teknik implementasyonu kapsar.

---

## İçindekiler

1. [Sistem Mimarisi](#1-sistem-mimarisi)
2. [Dizin Yapısı](#2-dizin-yapısı)
3. [Veritabanı Şeması](#3-veritabanı-şeması)
4. [Kimlik Doğrulama & Güvenlik](#4-kimlik-doğrulama--güvenlik)
5. [Portal Yapıları ve Routing](#5-portal-yapıları-ve-routing)
6. [Temel Servisler](#6-temel-servisler)
7. [Queue & Cron](#7-queue--cron)
8. [Dış Entegrasyonlar](#8-dış-entegrasyonlar)
9. [CSS / Frontend Mimarisi](#9-css--frontend-mimarisi)
10. [Test Altyapısı](#10-test-altyapısı)
11. [Deploy & Ortam Değişkenleri](#11-deploy--ortam-değişkenleri)
12. [Sık Yapılan Hatalar](#12-sık-yapılan-hatalar)

---

## 1. Sistem Mimarisi

### Genel Bakış

MentorDE, Almanya üniversite başvurularını yöneten **çok-portal Laravel ERP**'dir.
Her kullanıcı rolü ayrı bir portal URL'si ve layout'u üzerinden çalışır.

```
Browser → Nginx → PHP-FPM (Laravel 12 / PHP 8.4) → MySQL
                                                   → Queue Worker (database driver)
                                                   → Cron (Task Scheduler)
                                                   → Stripe API (ödeme)
                                                   → Meta Cloud API (WhatsApp)
                                                   → SMTP (e-posta)
```

### Portal Grupları

| Portal | URL Prefix | Middleware | Layout |
|--------|-----------|------------|--------|
| Manager | `/manager/` | `manager.role` + `require.2fa` | `manager.layouts.app` |
| Senior | `/senior/` | `senior.role` | `senior.layouts.app` |
| Guest | `/guest/` | `guest.role` + `verified` | `guest.layouts.app` |
| Student | `/student/` | `student.role` + `verified` | `student.layouts.app` |
| Dealer | `/dealer/` | `dealer.role` | `dealer.layouts.app` |
| Marketing | `/mktg-admin/` | `marketing.access` | `marketing-admin.layouts.app` |

### Temel İş Akışı

```
1. Aday sisteme kayıt olur → guest rolü atanır
2. Senior danışman atanır (manager veya sistem tarafından)
3. Belgeler yüklenir → senior inceler → onaylar
4. Sözleşme oluşturulur → PDF + dijital imza
5. Ödeme tanımlanır → Stripe Checkout ile ödenir
6. Student'a geçiş: guest_applications.status = 'converted'
7. Üniversite başvuruları senior tarafından takip edilir
8. Dealer referans komisyonu izler
```

---

## 2. Dizin Yapısı

```
mentorde/
├── app/
│   ├── Console/Commands/          # Artisan komutları (cron işleri)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/               # REST API controller'ları
│   │   │   ├── Auth/              # Login, 2FA, Email Verify
│   │   │   ├── Dealer/            # Dealer portal controller'ları
│   │   │   ├── Guest/             # Guest portal controller'ları
│   │   │   ├── Hr/                # İK modülü
│   │   │   ├── Manager/           # Manager panel controller'ları
│   │   │   ├── MarketingAdmin/    # Pazarlama controller'ları
│   │   │   ├── Senior/            # Senior portal controller'ları
│   │   │   └── Student/           # Student portal controller'ları
│   │   └── Middleware/            # RBAC, 2FA, güvenlik middleware'leri
│   ├── Jobs/                      # Queue job'ları
│   ├── Models/                    # Eloquent modeller
│   ├── Observers/                 # Model observer'ları (AuditTrail)
│   └── Services/                  # İş mantığı servisleri (35+)
├── database/
│   ├── migrations/                # Tüm tablo migrasyonları
│   └── seeders/                   # ContentHubSeeder, ContentHubExtraSeeder
├── public/
│   ├── css/portal-unified-v2.css  # Tüm portallerin CSS'i
│   └── js/                        # Statik JS dosyaları (30+)
├── resources/
│   ├── css/                       # premium.css, minimalist.css
│   └── views/
│       ├── auth/                  # Login, 2FA, email verify view'ları
│       ├── dealer/                # Dealer portal view'ları
│       ├── guest/                 # Guest portal view'ları
│       ├── handbook/              # Handbook view'ları (tüm roller)
│       ├── manager/               # Manager portal view'ları
│       ├── marketing-admin/       # Pazarlama portal view'ları
│       ├── senior/                # Senior portal view'ları
│       └── student/              # Student portal view'ları
├── routes/
│   ├── web.php                   # Auth + genel rotalar
│   ├── guest.php                 # Guest portal rotaları
│   ├── student.php               # Student portal rotaları
│   ├── senior.php                # Senior portal rotaları
│   ├── dealer.php                # Dealer portal rotaları
│   ├── manager.php               # Manager portal rotaları
│   ├── marketing-admin.php       # Marketing portal rotaları
│   └── api.php                   # REST API rotaları
├── HANDBOOK_TR.md                # Kullanıcı el kitabı (Türkçe)
├── HANDBOOK_EN.md                # Kullanıcı el kitabı (İngilizce)
└── DEV_HANDBOOK.md               # Bu dosya
```

---

## 3. Veritabanı Şeması

### Kullanıcı & RBAC

| Tablo | Açıklama |
|-------|----------|
| `users` | Ana kullanıcı tablosu — `role` string kolonu ile RBAC |
| `user_two_factor` | TOTP secret, enabled_at |
| `ip_access_rules` | IP whitelist/blacklist |
| `audit_trails` | Tüm kritik işlemlerin logu |
| `user_sessions` | Aktif oturum takibi |

**Roller (User::ROLE_* sabitleri):**
`manager`, `system_admin`, `operations_admin`, `finance_admin`,
`system_staff`, `operations_staff`, `finance_staff`,
`senior`, `mentor`, `guest`, `student`, `dealer`,
`marketing_admin`, `marketing_staff`, `sales_admin`, `sales_staff`

### Guest & Başvuru

| Tablo | Açıklama |
|-------|----------|
| `guest_applications` | Ana başvuru tablosu (`status`, `pipeline_stage`, `senior_id`) |
| `guest_pipeline_logs` | Pipeline aşama geçiş logu |
| `guest_documents` | Yüklenen belgeler + onay durumu |
| `guest_tickets` | Destek talepleri |
| `field_rules` | Dinamik form validasyon kuralları |
| `field_rule_approvals` | Kural onay akışı |

**guest_applications.pipeline_stage değerleri:**
`new` → `in_review` → `docs_pending` → `docs_complete` → `contract_sent` → `converted`

### Sözleşme

| Tablo | Açıklama |
|-------|----------|
| `business_contracts` | Dealer + staff sözleşmeleri |
| `contract_templates` | PDF şablonları (JSON placeholder) |
| `document_builder_templates` | Belge builder şablonları |
| `consent_records` | GDPR onay kayıtları |

### Ödeme

| Tablo | Açıklama |
|-------|----------|
| `student_payments` | Öğrenci ödeme faturaları |
| `student_payments.stripe_session_id` | Stripe Checkout Session ID |
| `student_payments.stripe_payment_intent_id` | Stripe Payment Intent ID |
| `student_payments.status` | `pending` / `paid` / `overdue` / `cancelled` |

**Stripe webhook:** `POST /webhooks/stripe` → `PaymentCheckoutController::handleWebhook()`

### İçerik (CMS)

| Tablo | Açıklama |
|-------|----------|
| `cms_contents` | Tüm içerik (blog, video, podcast, sunum) |
| `cms_categories` | İçerik kategorileri |
| `user_saved_contents` | Kullanıcı kaydetme |
| `user_content_reactions` | Beğeni/reaksiyon |

**cms_contents.type:** `blog`, `video_feature`, `podcast`, `presentation`, `experience`, `career_guide`, `tip`
**cms_contents.category:** `student-life`, `culture-fun`, `careers`, `tips-tricks`, `city-content`, `uni-content`

### Mesajlaşma & Bildirimler

| Tablo | Açıklama |
|-------|----------|
| `conversations` | Konuşma başlıkları |
| `conversation_participants` | Katılımcılar |
| `messages` | Mesaj içerikleri + `is_pinned`, `forwarded_from`, `edit_count` |
| `notifications` | In-app bildirimler |
| `notification_preferences` | Kullanıcı bildirim tercihleri |

### Görev & Proje

| Tablo | Açıklama |
|-------|----------|
| `tasks` | Ana görev tablosu (`recurrence_rule`, `depends_on_task_id`) |
| `task_time_entries` | Zaman takibi |
| `task_comments` | Görev yorumları |
| `escalation_rules` | Otomatik görev iletme kuralları |

### Performans & Lead

| Tablo | Açıklama |
|-------|----------|
| `senior_performance_snapshots` | Aylık snapshot (otomatik) |
| `lead_scores` | LeadScoreService çıktıları |
| `currency_rates` | EUR/TRY kur tablosu |

### Diğer Modüller

| Tablo | Açıklama |
|-------|----------|
| `student_university_applications` | Üniversite başvuru takibi |
| `student_institution_documents` | Kurumsal belge takibi |
| `student_shipments` | Kargo takibi |
| `student_appointments` | Randevu + takvim entegrasyonu |
| `dealer_types` | Bayi tipi (3 katman: Standard, Premium, Elite) |
| `dealer_revenue_milestones` | Komisyon dönüm noktaları |
| `university_requirement_maps` | Üniversite belge haritası |

---

## 4. Kimlik Doğrulama & Güvenlik

### Login Akışı

```
POST /login
  → AuthController::login()
  → Kredansiyel doğrulama
  → Şirket context kontrolü (company_id)
  → 2FA gerekli mi? (Require2FA middleware)
    → Evet: /2fa/challenge
    → Hayır: role'e göre dashboard'a yönlendir
```

### 2FA

- Kütüphane: `pragmarx/google2fa`
- QR kod: `bacon/bacon-qr-code` → SVG
- Setup flow: `GET /2fa/setup` → QR göster → `POST /2fa/setup/confirm` → doğrula → `user_two_factor.enabled_at = now()`
- Challenge: `GET /2fa/challenge` → 6 haneli kod → `POST /2fa/challenge` → `session('2fa_passed', true)`
- **2FA zorunlu roller:** `manager`, `system_admin`, `operations_admin`, `finance_admin`
- Middleware: `App\Http\Middleware\Require2FA` → `bootstrap/app.php`'de `require.2fa` olarak kayıtlı

### Email Doğrulama

- `User implements MustVerifyEmail`
- Yeni kullanıcılar: `User::booted()` creating event → `email_verified_at = now()` (auto-verify)
- `verified` middleware: guest + student route gruplarında aktif
- Controller: `Auth\EmailVerificationController` → `GET /email/verify`, `POST /email/verify/{id}/{hash}`

### RBAC Middleware

| Middleware | Kontrol |
|-----------|---------|
| `manager.role` | `User::ROLE_MANAGER` ve türevleri |
| `senior.role` | `senior`, `mentor` |
| `guest.role` | `guest` |
| `student.role` | `student` |
| `dealer.role` | `dealer` |
| `marketing.access` | `marketing_admin`, `marketing_staff`, `sales_admin`, `sales_staff` |
| `require.2fa` | Manager rolleri için 2FA zorunluluğu |

### Güvenlik Servisleri

- `SecurityAnomalyService` — 3 kontrol: anomalous_login, unusual_download, bulk_export (saatlik cron)
- `RiskScoreService` — kullanıcı risk puanlama
- `SecurityHeaders` middleware — global, her request'te CSP + nonce üretir
- `ValidFileMagicBytes` — dosya yükleme magic byte doğrulaması
- `EnsureGuestOwnsDocument` / `EnsureGuestOwnsTicket` — ownership middleware

### CSP ve Inline Script

**ÖNEMLİ:** SecurityHeaders her request'te rastgele nonce üretir.
CSP Level 3'te `nonce-*` varsa `unsafe-inline` görmezden gelinir.
Bu nedenle tüm `onclick="..."` attribute'ları **çalışmaz**.

**Doğru pattern:**
```html
<script nonce="{{ $cspNonce ?? '' }}">
document.getElementById('btn')?.addEventListener('click', function() { ... });
</script>
```

---

## 5. Portal Yapıları ve Routing

### Route Dosya Yapısı

`bootstrap/app.php`'de tüm route dosyaları include edilir:
```php
->withRouting(
    web: [
        __DIR__.'/../routes/web.php',
        __DIR__.'/../routes/guest.php',
        __DIR__.'/../routes/student.php',
        __DIR__.'/../routes/senior.php',
        __DIR__.'/../routes/dealer.php',
        __DIR__.'/../routes/manager.php',
        __DIR__.'/../routes/marketing-admin.php',
    ],
    api: __DIR__.'/../routes/api.php',
)
```

### ViewData Pattern

Her portal için `ViewDataService` sınıfı bulunur. Dashboard'dan view'a gönderilen data standart bir `build()` metodu üzerinden hazırlanır.

```php
// GuestViewDataService
public function build(Request $request, GuestApplication $guest): array
{
    return [
        'guest'       => $guest,
        'progress'    => $this->calculateProgress($guest),
        'nextStep'    => $this->determineNextStep($guest),
        'unreadCount' => $this->getUnreadCount($guest),
    ];
}
```

### GuestResolver Pattern

`GuestResolverService` — auth kullanıcısından `GuestApplication` modeline ulaşır.
Senior portal'da: senior'ın atanmış guest'lerini filtreler.

### Shared Layout Pattern (Role-Aware Dynamic Layouts)

Bazı sayfalar (Görev Panosu, Ticket Merkezi, Mesaj Merkezi, HR özlük sayfaları) **birden fazla rol** tarafından kullanılır — manager, senior, staff hepsi aynı URL'yi kullanır.

**Problem:** Hardcoded `@extends('layouts.staff')` yapılırsa, manager/senior da staff layout görür → "yama" izlenimi, tutarsız UX.

**Çözüm:** Her böyle blade dosyasının başında role'e göre dinamik layout seç:

```blade
@php
    $role = auth()->user()?->role;
    $layout = in_array($role, ['senior','mentor'])
        ? 'senior.layouts.app'
        : ($role === 'manager' ? 'manager.layouts.app' : 'layouts.staff');
@endphp
@extends($layout)
```

**Uygulandığı dosyalar:**
- `resources/views/tasks/index.blade.php`
- `resources/views/tasks/show.blade.php`
- `resources/views/tickets/center.blade.php`
- `resources/views/messages/center.blade.php`
- `resources/views/hr/my/onboarding.blade.php`
- `resources/views/hr/my/leaves.blade.php`
- `resources/views/hr/my/certifications.blade.php`
- `resources/views/hr/my/attendance.blade.php`
- `resources/views/manager/requests/index.blade.php` (match() pattern)

**Layout section compatibility:**

Tüm 3 layout (manager, senior, staff) aynı section isimlerini yield eder:
- `@yield('title')`
- `@yield('page_title')`
- `@yield('page_subtitle')`
- `@yield('topbar-actions')`
- `@yield('content')`

Bu sayede drop-in replacement olarak çalışır, blade içeriği değişmez.

### View Transitions API (sayfa geçiş smooth'laması)

Her portal layout'u şunu içerir:

```html
<meta name="view-transition" content="same-origin">
<style>
    @view-transition { navigation: auto; }
    ::view-transition-old(root),
    ::view-transition-new(root) {
        animation-duration: 180ms;
        animation-timing-function: ease-out;
    }
    html, body, .app, .main, .content { background: var(--bg, #f1f5f9) !important; }
</style>
```

- **Chrome 126+** → MPA cross-fade otomatik, beyaz flash azalır (tamamen yok etmez)
- **Diğer browserlar** → progressive enhancement, etkilenmez
- **bg fallback** → transition sırasında beyaz yerine slate görünür

> Not: `php artisan serve` tek-thread olduğu için local'de TTFB yavaş (1-1.2s), View Transitions flash'ı tamamen gidermez. Prod Apache'de sorun yok. Future work: Laravel Herd / Turbo / SPA wrapper.

### FOUC Preload Trick (CSS async yükleme)

`app.css` body sonunda yüklenirse FOUC (flash of unstyled content) olur. Preload + onload swap pattern:

```html
<link rel="preload" href="/build/{$__headAppCss}" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="/build/{$__headAppCss}"></noscript>
```

Non-blocking yükleme + FOUC yok. Tüm 5 portal layout'unda uygulandı.

### IM (Internal Messaging) — Slack-tarzı Yönetim Modeli

Conversation'lar için 3 seviyeli permission hiyerarşisi:

```
Manager (workspace admin)
  └── Channel Admin (creator + promoted members)
        └── Member
```

**Permission matrisi:**

| Aksiyon | Member | Channel Admin | Manager |
|---------|:------:|:-------------:|:-------:|
| Mesaj gönder (non-archived) | ✓ | ✓ | ✓ |
| Kendi mesajını sil | ✓ | ✓ | ✓ |
| Başkasının mesajını sil | ✗ | ✓ | ✓ |
| Üye ekle | ✗ | ✓ | ✓ |
| Üye çıkar (kick) | ✗ | ✓ | ✓ |
| Kendisi ayrıl (leave) | ✓ | ✓ | ✓ |
| Member → Admin promote | ✗ | ✓ | ✓ |
| Admin → Member demote | ✗ | ✓ | ✓ |
| Archive (read-only + hidden) | ✗ | ✓ | ✓ |
| Unarchive | ✗ | ✓ | ✓ |
| **Destroy** (permanent delete) | ✗ | ✗ | **✓** |

**Kod yerleri:**
- Service: `ConversationService::permissionLevel()`, `canPerform()`, `archiveConversation()`, `destroyConversation()`, `promoteToAdmin()`, `demoteFromAdmin()`
- Controller: `InternalMessagingController` — `archive`, `unarchive`, `destroy`, `promoteMember`, `demoteMember`
- Routes: `POST /im/conversations/{id}/archive|unarchive|destroy`, `/members/{uid}/promote|demote`
- UI: `resources/views/hub/_partials/internal-conv-panel.blade.php` — collapsible settings panel (⚙️ button header'da, sadece group/room için)

**Archive davranışı:**
- `is_archived=true` + `archived_at=now()` + `archived_by_user_id=userId`
- `send` controller'ı `abort(403)` eder (yeni mesaj engellenir)
- `UnifiedMessagingHubController::loadInternalData` → `Conversation::notArchived()` scope → liste gizler
- Unarchive geri aktif hale getirir, notArchived scope yeniden dahil eder

**Destroy (permanent delete):**
- `Conversation` modeline `SoftDeletes` trait eklendi — `deleted_at` kolonu
- `destroyConversation()` sadece soft delete yapar (trashed() ile geri alınabilir)
- Manager/system_admin override ile çağrılır
- Messages tablosu `ON DELETE CASCADE` FK var, ama soft delete cascade etmez → mesajlar korunur
- UI'de sadece manager'a "Kalıcı Sil" butonu görünür

**Son admin koruması:**
- `demoteFromAdmin()` admin sayısı 1 iken false döner
- `groupRemoveMember` içinde aynı kontrol (zaten vardı)
- Kendi kendini çıkarma senaryosunda bu kural `isSelf` flag ile bypass oluyor — TODO: self-leave da son admin'se engelle

**HTML5 validation:**
- `hubGroupTitle` ve `hubRoomTitle` input'larında `required` + `minlength="2"` var
- Boş submit browser tarafında engellenir, server'a geçersiz istek gitmez

---

## 6. Temel Servisler

### İş Mantığı Servisleri

| Servis | Sorumluluk |
|--------|-----------|
| `NotificationService` | E-posta + WhatsApp bildirim gönderme |
| `WhatsAppService` | Meta Cloud API v19.0 entegrasyonu |
| `BusinessContractService` | PDF sözleşme oluşturma, imza akışı |
| `DocumentBuilderService` | Şablon bazlı belge üretimi |
| `WorkflowEngineService` | Pipeline geçiş kuralları |
| `LeadScoreService` | 8 faktörlü lead puanlama |
| `SeniorPerformanceService` | Aylık performans snapshot |
| `DashboardKPIService` | Manager dashboard DB sorguları (`Cache::remember(300s)`) |
| `CurrencyRateService` | EUR/TRY kur çekme + cache (3600s) |
| `SecurityAnomalyService` | Güvenlik anomali tespiti |
| `ConversationService` | Dahili mesajlaşma iş mantığı |
| `TaskEscalationService` | Görev iletme kuralları |

### AuditTrail Observer

`AuditTrail` model + `AuditTrailObserver` → kritik modellerde created/updated/deleted olayları loglanır.

```php
// Model'e eklemek için:
use App\Observers\AuditTrailObserver;
protected static function booted(): void {
    static::observe(AuditTrailObserver::class);
}
```

---

## 7. Queue & Cron

### Queue

- Driver: `database` (`.env`: `QUEUE_CONNECTION=database`)
- Tablo: `jobs`, `failed_jobs`
- Worker başlatma: `php artisan queue:work --sleep=3 --tries=3`

**Job'lar:**
- `SendNotificationJob` — e-posta + WhatsApp bildirim
- `ProcessDocumentJob` — belge işleme
- `RecalculateLeadScoreJob` — lead skor hesaplama

### Cron (Task Scheduler)

`app/Console/Kernel.php` veya route-based scheduler:

| Komut | Zamanlama | Açıklama |
|-------|-----------|----------|
| `gdpr:enforce-retention` | Günlük 03:00 | Veri saklama politikası uygula |
| `security:anomaly-check` | Saatlik | Güvenlik anomali tarama |
| `leads:recalculate-scores` | Günlük 02:30 | Lead skorları yenile |
| `senior:snapshot-performance` | Aylık 1. gün 03:30 | Performans snapshot |
| `currency:sync-rates` | 6 saatte bir | EUR/TRY kur güncelle |
| `integrations:health-check` | Günlük | Entegrasyon sağlık kontrolü |
| `social:metrics-sync` | Günlük | Sosyal medya metrik sync |

**Sunucuda cron ayarı:**
```
* * * * * cd /var/www/mentorde && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Dış Entegrasyonlar

### Stripe

```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

**Akış:**
1. `PaymentCheckoutController::checkout()` → Stripe Checkout Session oluştur
2. Öğrenci Stripe'a yönlendirilir
3. Başarılı ödeme → `POST /webhooks/stripe` → `handleWebhook()`
4. `StudentPayment.status = 'paid'`, `paid_at = now()`

**Webhook CSRF muaf:** `routes/web.php`'de `->withoutMiddleware([VerifyCsrfToken::class])`

### WhatsApp

```env
WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_ACCESS_TOKEN=...
WHATSAPP_TEMPLATE_NAMESPACE=...
```

**Kullanım:** `WhatsAppService::sendTemplateMessage($phone, $templateName, $params)`
Meta Cloud API v19.0 kullanılır.

### SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@mentorde.de
MAIL_FROM_NAME="MentorDE"
```

### Google 2FA (TOTP)

Kütüphane: `pragmarx/google2fa` + `bacon/bacon-qr-code`
Kullanıcı kendi authenticator uygulamasıyla (Google Auth, Authy) TOTP kurulumu yapar.

---

## 9. CSS / Frontend Mimarisi

### Ana CSS

`public/css/portal-unified-v2.css` — tüm portallar bu dosyayı kullanır.

**CSS Değişkenleri (namespace: `--u-*`):**
```css
--u-brand   /* Ana mavi */
--u-bg      /* Sayfa arka planı */
--u-card    /* Kart arka planı */
--u-line    /* Kenarlık rengi */
--u-text    /* Ana metin */
--u-muted   /* İkincil metin */
--u-ok      /* Yeşil */
--u-warn    /* Sarı */
--u-danger  /* Kırmızı */
```

**Layout bileşenleri:**
- `.grid2`, `.grid3`, `.grid4` — grid layout (inline style kullanma)
- `.kpi` — büyük KPI sayısı (font 26px)
- `.badge.ok`, `.badge.warn`, `.badge.danger`, `.badge.info`, `.badge.pending`
- `.btn`, `.btn.alt`, `.btn.warn`, `.btn.ok`
- `.card` — standalone kart
- `.list > .item` — liste satırı (`.list > .card` YANLIŞ)

### Tema Sistemi

- `resources/css/premium.css` — light mode + `[data-theme="dark"]` override
- `resources/css/minimalist.css` — minimalist tema
- Toggle fonksiyonları: `__dmToggle()`, `__designToggle()` — layout'un nonce'lu script bloğunda tanımlanır

### Statik JS

`public/js/` klasöründe 30+ statik JS dosyası.
Her portal kendi JS dosyasına sahip.

**Vite sadece CSS için:** `resources/css/` → build → `public/build/`
JS dosyaları statik, Vite bundle'ına dahil değil.

---

## 10. Test Altyapısı

### PHPUnit

```bash
php artisan test              # Tüm testler
php artisan test --filter Foo # Belirli test
```

**Test durumu (2026-04-04):** 123/123 test geçiyor.

**Test dizinleri:**
- `tests/Unit/` — model, servis unit testleri
- `tests/Feature/` — controller, route feature testleri

**Kritik test pattern:**
```php
// Sistem alanları ($fillable dışı) için:
$app = new GuestApplication();
$app->forceFill([
    'status' => 'active',
    'senior_id' => $senior->id,
])->save();
// create() kullanma — fillable kısıtlaması bypass etmez
```

**SQLite vs MySQL uyumluluk:**
```php
$isSqlite = config('database.default') === 'sqlite';
$dateDiff = $isSqlite
    ? DB::raw("CAST(julianday('now') - julianday(created_at) AS INTEGER)")
    : DB::raw("DATEDIFF(NOW(), created_at)");
```

### Playwright E2E

```bash
cd tests/e2e
npx playwright test
```

7 rol, 6 spec dosyası. `.env.testing`'de `APP_DEBUG=false` (PHP 8.4 Ignition bug bypass).

---

## 11. Deploy & Ortam Değişkenleri

### Gerekli `.env` Değişkenleri

```env
APP_NAME="MentorDE"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://mentorde.de

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mentorde
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database
CACHE_DRIVER=redis        # veya file
SESSION_DRIVER=database   # veya redis

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@mentorde.de
MAIL_FROM_NAME="MentorDE"

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_ACCESS_TOKEN=...
WHATSAPP_TEMPLATE_NAMESPACE=...

FILESYSTEM_DISK=local     # veya s3
```

### Deploy Komutları

```bash
# Kod güncelleme
git pull origin main

# Bağımlılıklar
composer install --no-dev --optimize-autoloader

# Uygulama
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# CSS build (gerektiğinde)
npm ci && npm run build

# Queue (systemd veya supervisor ile)
php artisan queue:restart
```

### Supervisor Konfigürasyonu

```ini
[program:mentorde-worker]
command=php /var/www/mentorde/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/mentorde-worker.log
```

---

## 12. Sık Yapılan Hatalar

### "419 Page Expired" (Production)

**Neden:** Session cookie `SameSite=Lax`, cross-site POST form'ları veya HTTP → HTTPS redirect.

**Çözüm:**
```php
// config/session.php
'secure'    => true,   // HTTPS zorunlu
'same_site' => 'lax',
'domain'    => '.mentorde.de',
```
`.env`'de `APP_URL=https://mentorde.de` (http değil, https).

### "Namespace declaration" PHP Hatası

**Neden:** PowerShell `Set-Content` UTF-8 BOM ekler → PHP "unexpected namespace" hatası.

**Çözüm:** Node.js ile BOM'u kaldır:
```js
const fs = require('fs');
let content = fs.readFileSync('file.php', 'utf8');
if (content.charCodeAt(0) === 0xFEFF) content = content.slice(1);
fs.writeFileSync('file.php', content, 'utf8');
```

### CSP Inline Script Bloğu

**Neden:** SecurityHeaders nonce üretir → `unsafe-inline` görmezden gelinir → `onclick=` çalışmaz.

**Çözüm:** Her zaman `addEventListener` kullan, nonce'lu script bloğu içinde.

### Queue Job'ları Çalışmıyor

**Kontrol:**
```bash
php artisan queue:work   # Manual başlat ve logları izle
php artisan queue:failed # Başarısız job'ları listele
php artisan queue:retry all
```

### Edit Tool Cache Geçersizliği

**Neden:** Harici araçla değiştirilmiş dosya → Edit tool önbelleği stale.

**Çözüm:** Önce `Read` tool ile dosyayı oku, sonra `Edit` yap.

### Test: `$fillable` Kısıtlaması

**Neden:** `Model::create([...])` `$fillable`'a dahil olmayan alanları yok sayar → test beklenmedik state'te başlar.

**Çözüm:** Test setup'ında sistem alanları için `$model->forceFill([...])->save()` kullan.

---

*MentorDE Developer Handbook — Güncelleme: Nisan 2026*
