# MentorDE — Sistem Genel Bakış

---

## Proje Tanımı

MentorDE, Almanya'da yükseköğretim süreçlerini destekleyen Türk danışmanlık firmalarına yönelik geliştirilmiş çok kiracılı (multi-tenant) bir ERP + CRM + Marketing platformudur.

---

## Tech Stack

| Katman | Teknoloji |
|--------|-----------|
| Backend | PHP 8.4 / Laravel 12 |
| Veritabanı | MySQL (üretim) / SQLite (test) |
| Frontend | Blade + Vanilla JS (portal-unified-v2.css) |
| Queue | Laravel Queue (database driver) |
| Cache | Laravel Cache (file/Redis) |
| Storage | Laravel Storage (`public` disk) + Firebase Storage (opsiyonel) |
| Realtime | Polling-tabanlı (WhatsApp/Push: Meta Cloud API) |
| Testing | PHPUnit (74 test, 598 assertion) + Playwright E2E |

---

## Portal Grupları (7 adet)

| Portal | URL Prefix | Kullanıcı Rolü | Açıklama |
|--------|-----------|----------------|----------|
| Manager | `/manager` | `manager` | Sistem yönetimi, raporlama, RBAC |
| Senior | `/senior` | `senior`, `mentor` | Öğrenci danışmanlığı, belge yönetimi |
| Student | `/student` | `student` | Öğrenci self-service portalı |
| Guest | `/guest`, `/apply` | `guest` | Aday başvuru ve takip portalı |
| Dealer | `/dealer` | `dealer` | Bayi komisyon ve lead yönetimi |
| Marketing Admin | `/mktg-admin` | `marketing_admin`, `sales_admin`, `marketing_staff`, `sales_staff` | Kampanya, CMS, pipeline yönetimi |
| Config | `/config` | `manager` | Sistem konfigürasyon paneli |

---

## Rol Hiyerarşisi (16 rol)

```
system_admin
  └── manager
        ├── operations_admin / operations_staff
        ├── finance_admin / finance_staff
        ├── senior / mentor
        ├── marketing_admin / sales_admin
        ├── marketing_staff / sales_staff
        ├── student
        ├── dealer
        └── guest
```

**RBAC:** `role_templates` + `user_role_assignments` tabloları. `effectivePermissionCodes()` metodu — şablon izinleri + `ROLE_DEFAULT_PERMISSION_CODES` fallback.

---

## Veri Modeli — Ana Tablolar

| Tablo | Model | Açıklama |
|-------|-------|----------|
| `users` | `User` | 16 rol, `company_id` FK |
| `companies` | `Company` | Multi-tenant kimliği |
| `guest_applications` | `GuestApplication` | Aday → öğrenci dönüşüm hub'ı |
| `student_assignments` | `StudentAssignment` | Öğrenci–senior atama |
| `dealers` | `Dealer` | Bayi profili |
| `documents` | `Document` | Tüm belgeler (SoftDeletes) |
| `marketing_tasks` | `MarketingTask` | Tüm departman görevleri |
| `notification_dispatches` | `NotificationDispatch` | Bildirim kuyruğu |
| `process_outcomes` | `ProcessOutcome` | Süreç adımı kayıtları |
| `dm_threads` / `dm_messages` | `DmThread`, `DmMessage` | İç mesajlaşma |
| `audit_trails` | `AuditTrail` | K3 audit log |

---

## Multi-Tenant Mimarisi

- `BelongsToCompany` trait → tüm modellerde `WHERE company_id = X` global scope
- `SetCompanyContext` middleware → `current_company_id` session/user'dan bind edilir
- Marketing Admin'de `withoutGlobalScope('company')` ile çapraz firma sorguları (yetkili)

---

## Servis Katmanı — Ana Servisler

| Servis | Açıklama |
|--------|----------|
| `DashboardKPIService` | Manager/Senior dashboard sorguları (Cache 300s) |
| `GuestListService` / `StudentListService` | Filtrelenmiş liste + KPI + filtre seçenekleri |
| `TaskAutomationService` | Olay tetiklemeli otomatik görev oluşturma |
| `ContractTemplateService` | Sözleşme şablonu PDF/preview/imza |
| `DocumentBuilderService` | CV/motivasyon/referans belge üretici |
| `WhatsAppService` | Meta Cloud API v19.0 template mesaj |
| `CurrencyRateService` | EUR/TRY kur takibi (Cache 3600s) |
| `LeadScoreService` | 8 faktörlü lead puanlama |
| `EscalationService` | SLA kuralı kontrolü + bildirim tetikleme |
| `WorkflowEngineService` | Enrollment bazlı akış motoru (A/B split dahil) |
| `IntegrationFactory` | Takvim/e-imza/e-posta/video adapter seçici |
| `NotificationService` | `NotificationDispatch` oluşturma |
| `RiskScoreService` | Öğrenci risk puanı hesaplama |
| `AnonymizationService` | GDPR Art.17 kişisel veri anonimleştirme |

---

## Cron Görevleri

| Komut | Schedule | Açıklama |
|-------|----------|----------|
| `currency:sync-rates` | Her gün 06:00 | EUR/TRY/USD kur güncelleme |
| `leads:recalculate-scores` | Her gün 02:30 | Lead skorları yeniden hesapla |
| `senior:snapshot-performance` | Her ay 1. 03:30 | Senior aylık performans snapshot |
| `gdpr:enforce-retention` | Her gün 03:00 | GDPR veri silme politikası |
| `integration:health-check` | Her gün | Entegrasyon bağlantı kontrolü |
| `social:sync-metrics` | Periyodik | Sosyal medya metrikleri güncelleme |
| `security:anomaly-check` | Her saat | Güvenlik anomali tespiti |
| `senior:send-reminders` | Her gün 08:30 | Senior hatırlatıcı bildirimleri |

---

## Güvenlik Katmanları

- `ValidFileMagicBytes` — dosya yükleme MIME doğrulama
- `SecurityHeaders` — global middleware (HSTS, CSP, X-Frame-Options)
- `EnsureGuestOwnsDocument/Ticket` — sahiplik kontrolü
- `AuditTrail` model + observer — tüm model değişiklikleri loglanır
- `SecurityAnomalyService` — 3 anomali türü kontrolü (saatlik)
- `MasksPii` trait — API response'da PII maskeleme
- `FieldRuleEngine` — dinamik alan doğrulama kuralları
- RBAC middleware — 18 farklı middleware sınıfı

---

## CSS Mimarisi

**Dosya:** `public/css/portal-unified-v2.css`

Tüm portallar bu tek CSS dosyasını paylaşır.

| Sınıf / Değişken | Açıklama |
|------------------|----------|
| `--u-*` | Tek namespace: `--u-ok`, `--u-warn`, `--u-danger`, `--u-brand`, `--u-line`, `--u-card`, `--u-text`, `--u-muted`, `--u-bg` |
| `.grid2/3/4` | Responsive grid |
| `.kpi` | KPI sayı göstergesi (font 26px) |
| `.badge` + `.ok/.warn/.danger/.info/.pending` | Durum rozetleri |
| `.item` | `.list` içi satır elemanı |
| `.card` | Bağımsız kart |
| `.btn.alt/.warn/.ok` | Secondary/danger/green button |
| `--theme-*` | Tema sistemi değişkenleri (PortalTheme::toCssVars()) |

---

## JS Mimarisi

**Yaklaşım:** `public/js/` altında 30+ statik JS dosyası, her portal/özellik için ayrı.

**Blade → JS Bridge:** `window.__xxx = @json(...)` pattern

**Temel JS dosyaları:**
- `task-board.js` / `task-kanban.js` — kanban + list view geçişi
- `shipments.js` — kargo yönetimi
- `guest-registration-form.js` — çok adımlı başvuru formu
- `student-card.js` — öğrenci kartı React alanları
- `config-panel.js` — konfigürasyon paneli AJAX
- `messaging.js` — DM polling (10s)

---

## Test Altyapısı

| Tür | Araç | Durum |
|-----|------|-------|
| Unit/Feature | PHPUnit | 74 test, 598 assertion ✅ |
| E2E | Playwright | 6 spec, 7 rol |

**E2E roller:** manager, marketing_admin (omer@), marketing_staff (sule@), senior (seniorww@), student, dealer, guest

---

## Deployment

**Dosya:** `docs/DEPLOYMENT.md`

Temel adımlar:
1. `composer install --no-dev`
2. `php artisan migrate`
3. `php artisan db:seed` (opsiyonel)
4. `.env` konfigürasyonu (Firebase, WhatsApp, Integrations)
5. Queue worker: `php artisan queue:work`
6. Cron: `* * * * * php artisan schedule:run`

---

## Dosya Yapısı Özeti

```
app/
  Http/Controllers/     — Portal controller'ları
  Models/               — Eloquent modeller
  Services/             — İş mantığı servisleri
  Jobs/                 — Queue job'ları
  Console/Commands/     — Artisan komutları
  Http/Middleware/       — 18+ middleware
  Rules/                — Özel validation kuralları
  Support/              — Yardımcı sınıflar (ApiResponse, PortalTheme)
config/                 — Laravel + özel konfigürasyonlar
database/
  migrations/           — 80+ migration
  seeders/              — Geliştirici + demo seed
docs/                   — Teknik dokümantasyon
public/
  css/                  — portal-unified-v2.css
  js/                   — 30+ statik JS dosyası
resources/views/        — Blade şablonları
routes/
  web.php               — Tüm web rotaları
  console.php           — Cron tanımları
```
