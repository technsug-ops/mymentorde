# MentorDE ERP — Kapsamlı Sistem Dokümantasyonu

> **Versiyon:** 5.0 (P3+P5 tamamlandı)
> **Tarih:** 2026-02-28
> **Platform:** Laravel 12 / PHP 8.4 / MySQL / Firebase
> **Domain:** https://netsparen.de

---

## İçindekiler

1. [Sistem Genel Bakış](#1-sistem-genel-bakış)
2. [Teknoloji Yığını](#2-teknoloji-yığını)
3. [Dizin Yapısı](#3-dizin-yapısı)
4. [Rol ve İzin Sistemi](#4-rol-ve-i̇zin-sistemi)
5. [Portal Grupları ve Sayfalar](#5-portal-grupları-ve-sayfalar)
6. [Veritabanı Şeması — Tüm Tablolar](#6-veritabanı-şeması--tüm-tablolar)
7. [Web Route Listesi](#7-web-route-listesi)
8. [API Route Listesi](#8-api-route-listesi)
9. [Marketing Admin Route Listesi](#9-marketing-admin-route-listesi)
10. [Servis Katmanı](#10-servis-katmanı)
11. [Integration Adapter Sistemi](#11-integration-adapter-sistemi)
12. [Middleware Listesi](#12-middleware-listesi)
13. [Artisan Komutları ve Zamanlayıcı](#13-artisan-komutları-ve-zamanlayıcı)
14. [Test Suite](#14-test-suite)
15. [GDPR ve Güvenlik Özellikleri](#15-gdpr-ve-güvenlik-özellikleri)
16. [Frontend Mimarisi](#16-frontend-mimarisi)
17. [Zamanlayıcı ve Arka Plan İşleri](#17-zamanlayıcı-ve-arka-plan-i̇şleri)
18. [Deployment (Hostinger)](#18-deployment-hostinger)
19. [Geliştirici Rehberi](#19-geliştirici-rehberi)

---

## 1. Sistem Genel Bakış

**MentorDE**, Almanya'da yükseköğretime başvuran uluslararası öğrencilerin süreçlerini yönetmek için geliştirilmiş çok-portal ERP sistemidir.

### Ana İş Süreçleri

```
Aday (Guest) Başvurusu
       ↓
   Lead Oluştu  ←── Dealer (Bayi) Yönlendirmesi
       ↓
  Senior Atanır
       ↓
  Kayıt Formu + Belge Yükleme
       ↓
  Sözleşme (Contract) İmzalanır
       ↓
  Öğrenci (Student) Statüsü
       ↓
  Süreç Takibi (Process Tracking)
       ↓
  Gelir Takibi (Revenue Milestones)
```

### Kullanıcı Kitlesi

| Portal | Kullanıcı Tipi | Açıklama |
|--------|----------------|----------|
| **Manager Portal** | Manager / System Admin / Operations / Finance | Sistem yönetimi, raporlama |
| **Senior Portal** | Senior / Mentor | Öğrenci danışmanlığı |
| **Student Portal** | Student | Başvuru takibi |
| **Guest Portal** | Guest | Ön başvuru aşaması |
| **Dealer Portal** | Dealer | Bayi/aracı yönetimi |
| **Marketing Admin** | Marketing Admin/Staff, Sales | Pazarlama ve kampanya yönetimi |

---

## 2. Teknoloji Yığını

### Backend
| Bileşen | Versiyon | Açıklama |
|---------|----------|----------|
| PHP | 8.4 | Ana dil |
| Laravel | 12.x | Framework |
| MySQL | 8.x | Ana veritabanı (Hostinger) |
| Firebase Firestore | REST API | Yardımcı koleksiyon veritabanı |
| Google Cloud Storage | 1.49 | Dosya depolama (Firebase Storage) |
| PHPWord | 1.1 | Word belge oluşturma (CV/sözleşme) |

### Frontend
| Bileşen | Kullanım |
|---------|----------|
| Blade Templates | Tüm view'lar |
| Vanilla JavaScript | Tüm interaktivite (framework yok) |
| Portal Unified CSS | `public/css/portal-unified-v2.css` — tüm portallara ortak |
| Vite | Asset bundle (resources/js) |
| React (JSX) | Yalnızca CV Builder widget |
| Playwright | E2E test |

### Paketler (composer.json)
```json
"require": {
    "php": "^8.2",
    "google/cloud-storage": "^1.49",
    "laravel/framework": "^12.0",
    "laravel/tinker": "^2.10.1",
    "phpoffice/phpword": "^1.1"
}
```

---

## 3. Dizin Yapısı

```
mentorde/
├── app/
│   ├── Console/Commands/       # 3 özel Artisan komutu
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/            # ~35 API controller
│   │   │   ├── Auth/           # ForgotPassword, ResetPassword
│   │   │   ├── Guest/          # PortalController, WorkflowController
│   │   │   ├── Manager/        # ContractTemplate, ManagerPortal, Theme
│   │   │   └── MarketingAdmin/ # ~20 Marketing controller
│   │   └── Middleware/         # 18 middleware
│   ├── Models/
│   │   ├── Marketing/          # 14 marketing-specific model
│   │   └── Concerns/           # BelongsToCompany trait
│   ├── Rules/                  # ValidFileMagicBytes, StrongPassword
│   ├── Services/
│   │   ├── Integrations/
│   │   │   ├── Adapters/       # Calendar, ESign, EmailMarketing, Video, PM
│   │   │   └── Contracts/      # 5 interface
│   │   └── Marketing/
│   │       └── ExternalMetrics/
│   └── Support/                # ApplicationCountryCatalog, GuestRegistrationFormCatalog
├── config/                     # app, auth, database, firebase, marketing_external, vb.
├── database/
│   ├── migrations/             # 121 migration dosyası
│   └── seeders/
├── public/
│   ├── css/portal-unified-v2.css
│   └── js/                     # ~30 statik JS dosyası
├── resources/views/
│   ├── apply/                  # Kamuya açık başvuru formu
│   ├── dealer/
│   ├── guest/
│   ├── manager/
│   ├── marketing-admin/
│   ├── senior/
│   └── student/
├── routes/
│   ├── web.php                 # Ana web rotaları
│   ├── api.php                 # REST API rotaları
│   ├── marketing-admin.php     # Marketing admin rotaları
│   └── console.php             # Zamanlayıcı + inline komutlar
└── tests/
    ├── Feature/                # 35 feature test
    └── e2e/                    # Playwright E2E testleri
```

---

## 4. Rol ve İzin Sistemi

### Kullanıcı Rolleri (`User.php`)

#### İdari Roller
| Sabit | Değer | Erişim |
|-------|-------|--------|
| `ROLE_MANAGER` | `manager` | Tüm yönetim ekranları |
| `ROLE_SYSTEM_ADMIN` | `system_admin` | Sistem konfigürasyonu |
| `ROLE_SYSTEM_STAFF` | `system_staff` | Sistem okuma |
| `ROLE_OPERATIONS_ADMIN` | `operations_admin` | Operasyon yönetimi |
| `ROLE_OPERATIONS_STAFF` | `operations_staff` | Operasyon okuma |
| `ROLE_FINANCE_ADMIN` | `finance_admin` | Gelir yönetimi |
| `ROLE_FINANCE_STAFF` | `finance_staff` | Finans okuma |
| `ROLE_MARKETING_ADMIN` | `marketing_admin` | Marketing tam yetki |
| `ROLE_MARKETING_STAFF` | `marketing_staff` | Marketing okuma |
| `ROLE_SALES_ADMIN` | `sales_admin` | Satış yönetimi |
| `ROLE_SALES_STAFF` | `sales_staff` | Satış okuma |

#### Portal Rolleri
| Sabit | Değer | Portal |
|-------|-------|--------|
| `ROLE_SENIOR` | `senior` | Senior Portal |
| `ROLE_MENTOR` | `mentor` | Senior Portal (junior) |
| `ROLE_STUDENT` | `student` | Student Portal |
| `ROLE_GUEST` | `guest` | Guest Portal |
| `ROLE_DEALER` | `dealer` | Dealer Portal |

### Rol Grupları
```
ADMIN_PANEL_ROLES      → manager, system_admin, operations_admin, finance_admin
MARKETING_ACCESS_ROLES → manager, system_admin, system_staff, marketing_admin,
                         sales_admin, sales_staff, marketing_staff
TASK_ACCESS_ROLES      → manager, senior, mentor + tüm admin/staff roller
```

### İzin Kodları (Permission Codes)

| Kod | Açıklama |
|-----|----------|
| `config.view` | Konfigürasyon görüntüleme |
| `config.manage` | Konfigürasyon yönetimi |
| `student.assignment.manage` | Öğrenci atama yönetimi |
| `student.card.view` | Öğrenci kartı görüntüleme |
| `revenue.manage` | Gelir yönetimi |
| `approval.manage` | Field rule onayları |
| `notification.manage` | Bildirim yönetimi |
| `role.template.manage` | RBAC şablon yönetimi |
| `ticket.center.view` | Ticket merkezi görüntüleme |
| `ticket.center.route` | Ticket yönlendirme |
| `marketing.dashboard.view` | Marketing dashboard |
| `marketing.campaign.manage` | Kampanya yönetimi |

### RBAC Mimarisi
```
RoleTemplate ──── Permission (M:N — role_template_permissions pivot)
    │
UserRoleAssignment ──── User
```
Her kullanıcı birden fazla `UserRoleAssignment` → `RoleTemplate` zinciri üzerinden izin alır.
Fallback: `User::ROLE_DEFAULT_PERMISSION_CODES[role]` — veritabanı tanımı yoksa statik izinler.

---

## 5. Portal Grupları ve Sayfalar

### 5.1 Public / Unauthenticated

| URL | Açıklama |
|-----|----------|
| `GET /` | `/apply`'a redirect |
| `GET /apply` | Başvuru formu (public) |
| `POST /apply` | Başvuru kaydet (throttle:5,1 + field rule validator) |
| `GET /apply/success` | Başvuru başarı sayfası |
| `GET /apply/status/{token}` | Başvuru durumu sorgulama |
| `GET /go/{code}` | UTM Tracking link redirect |
| `GET /landing/mentorde` | Landing sayfası |
| `GET /login` | Giriş |
| `GET /forgot-password` | Şifre sıfırlama isteği |
| `GET /reset-password/{token}` | Şifre sıfırlama |

### 5.2 Manager Portal (`/manager/*`)

**Middleware:** `company.context`, `auth`, `manager.role`

| URL | Açıklama |
|-----|----------|
| `/manager/dashboard` | Ana dashboard (KPI, snapshot, raporlar) |
| `/manager/dashboard/snapshot` | Anlık rapor oluştur/görüntüle/sil |
| `/manager/guests` | Guest listesi (sayfalama + filtre + CSV) |
| `/manager/guests/{guest}` | Guest detayı |
| `/manager/students` | Öğrenci listesi (CSV export dahil) |
| `/manager/seniors` | Senior listesi |
| `/manager/dealers` | Dealer listesi |
| `/manager/commissions` | Komisyon talepleri (onay/red/ödendi) |
| `/manager/contract-template` | Sözleşme şablonu yönetimi |
| `/manager/theme` | Portal tema ayarları |
| `/manager/preview/student/{id}` | Öğrenci portal önizlemesi |
| `/config` | Sistem konfigürasyonu (8 kategori) |
| `/student-card` | Öğrenci kartı arama/görüntüleme |
| `/tasks` | Task board (tüm departmanlar) |
| `/tickets-center` | Ticket merkezi |
| `/messages-center` | Mesaj merkezi |

### 5.3 Senior Portal (`/senior/*`)

**Middleware:** `company.context`, `auth`, `senior.role`

| URL | Açıklama |
|-----|----------|
| `/senior/dashboard` | Senior dashboard (KPI cache) |
| `/senior/students` | Atanmış öğrenciler listesi |
| `/senior/registration-documents` | Öğrenci belgeleri |
| `/senior/process-tracking` | Süreç takibi |
| `/senior/appointments` | Randevular |
| `/senior/tickets` | Ticket yönetimi |
| `/senior/materials` | Eğitim materyalleri (Knowledge Base) |
| `/senior/contracts` | Sözleşmeler |
| `/senior/services` | Servis yönetimi |
| `/senior/vault` | Hesap kasası (AES şifreli) |
| `/senior/notes` | Dahili notlar |
| `/senior/knowledge-base` | Bilgi tabanı |
| `/senior/performance` | Performans raporu |
| `/senior/document-builder` | CV/belge oluşturucu |
| `/senior/profile` | Profil + müsaitlik takvimi |
| `/senior/settings` | Ayarlar + şifre |
| `/senior/messages` | DM mesajlaşma |

### 5.4 Student Portal (`/student/*`)

**Middleware:** `company.context`, `auth`, `student.role`

| URL | Açıklama |
|-----|----------|
| `/student/dashboard` | Öğrenci dashboard |
| `/student/registration` | Kayıt formu (auto-save + submit) |
| `/student/registration/documents` | Belge yükleme (stage='student') |
| `/student/process-tracking` | Süreç takibi |
| `/student/document-builder` | Belge oluşturucu |
| `/student/appointments` | Randevu oluştur/iptal |
| `/student/tickets` | Destek talebi |
| `/student/materials` | Materyaller (okundu işareti) |
| `/student/contract` | Sözleşme (talep/imzala/indir) |
| `/student/services` | Paket + ek servis seçimi |
| `/student/profile` | Profil + fotoğraf |
| `/student/settings` | Ayarlar + şifre |
| `/student/messages` | DM mesajlaşma |
| `/student/notifications` | Bildirimler |
| `/student/payments` | Gelir takibi |
| `/student/vault` | Hesap kasası (görüntüle) |
| `/student/gdpr/export` | GDPR veri dışa aktarma |
| `/student/gdpr/erasure` | GDPR silme talebi |

### 5.5 Guest Portal (`/guest/*`)

**Middleware:** `company.context`, `auth`, `guest.role`, `throttle:240,1`

| URL | Açıklama |
|-----|----------|
| `/guest/dashboard` | Guest dashboard |
| `/guest/registration/form` | Kayıt formu (auto-save + submit) |
| `/guest/registration/documents` | Belge yükleme (stage='guest') |
| `/guest/services` | Paket/servis seçimi |
| `/guest/contract` | Sözleşme talep/yükle |
| `/guest/tickets` | Destek talepleri |
| `/guest/profile` | Profil + fotoğraf |
| `/guest/settings` | Ayarlar + şifre |
| `/guest/messages` | DM mesajlaşma |
| `/guest/gdpr/export` | GDPR veri dışa aktarma |
| `/guest/gdpr/erasure` | GDPR silme talebi |

### 5.6 Dealer Portal (`/dealer/*`)

**Middleware:** `company.context`, `auth`, `dealer.role`

| URL | Açıklama |
|-----|----------|
| `/dealer/dashboard` | Dashboard (dealer.type.permission:canViewStudentDetails) |
| `/dealer/lead-create` | Yeni lead formu |
| `/dealer/leads` | Lead listesi |
| `/dealer/leads/{lead}` | Lead detayı |
| `/dealer/earnings` | Kazanç raporu (CSV export) |
| `/dealer/payments` | Ödeme hesapları + payout talebi |
| `/dealer/advisor` | Danışman desteği (ticket) |
| `/dealer/training` | Eğitim materyalleri (stub) |
| `/dealer/referral-links` | UTM referral linkler |
| `/dealer/profile` | Profil |
| `/dealer/settings` | Ayarlar + şifre + veri export |

### 5.7 Marketing Admin (`/mktg-admin/*`)

**Middleware:** `company.context`, `auth`, `marketing.access`

#### Kampanya Yönetimi
| URL | Açıklama |
|-----|----------|
| `/mktg-admin/dashboard` | KPI dashboard (benchmark + delta) |
| `/mktg-admin/suggestions/audience` | Hedef kitle önerisi API |
| `/mktg-admin/campaigns` | Kampanya CRUD |
| `/mktg-admin/campaigns/{id}/report` | Kampanya raporu |

#### CMS
| URL | Açıklama |
|-----|----------|
| `/mktg-admin/content` | İçerik yönetimi |
| `/mktg-admin/categories` | CMS kategorileri |
| `/mktg-admin/media` | Medya kütüphanesi (gerçek dosya yükleme) |

#### E-posta Marketing
| URL | Açıklama |
|-----|----------|
| `/mktg-admin/email/templates` | E-posta şablonları |
| `/mktg-admin/email/segments` | E-posta segmentleri |
| `/mktg-admin/email/campaigns` | E-posta kampanyaları |
| `/mktg-admin/email/log` | Gönderim logu |

#### Sosyal Medya
| URL | Açıklama |
|-----|----------|
| `/mktg-admin/social/accounts` | Hesap yönetimi |
| `/mktg-admin/social/posts` | Post yönetimi + yayın takvimi |
| `/mktg-admin/social/calendar` | Haftalık takvim görünümü |
| `/mktg-admin/social/metrics` | Metrik analitiği |

#### Diğer
| URL | Açıklama |
|-----|----------|
| `/mktg-admin/lead-sources` | Lead kaynak analizi + UTM |
| `/mktg-admin/events` | Etkinlik yönetimi |
| `/mktg-admin/pipeline` | Satış pipeline |
| `/mktg-admin/dealers` | Bayi ilişkileri |
| `/mktg-admin/budget` | Bütçe yönetimi |
| `/mktg-admin/kpi` | KPI raporları |
| `/mktg-admin/tracking-links` | İzleme linkleri |
| `/mktg-admin/tasks` | Task board (Liste + Kanban) |
| `/mktg-admin/team` | Ekip yönetimi (Admin only) |
| `/mktg-admin/integrations` | Entegrasyon ayarları (Admin only) |
| `/mktg-admin/settings` | Genel ayarlar |
| `/mktg-admin/notifications` | Bildirimler |
| `/mktg-admin/profile` | Profil |

---

## 6. Veritabanı Şeması — Tüm Tablolar

> Toplam: **121 migration dosyası** (varsayılan 3 + 118 özel)

### 6.1 Temel Tablolar

#### `users`
| Kolon | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | |
| `company_id` | bigint FK | |
| `name` | string | |
| `email` | string unique | |
| `role` | string | manager/senior/student/guest/dealer/... |
| `student_id` | string null | Öğrenci ID referansı |
| `dealer_code` | string null | Dealer kodu |
| `senior_code` | string null | Senior kodu |
| `senior_internal_sequence` | int | Sıra no |
| `senior_type` | string null | |
| `max_capacity` | int | Maksimum öğrenci kapasitesi |
| `auto_assign_enabled` | boolean | Otomatik atama |
| `can_view_guest_pool` | boolean | Guest havuzu erişimi |
| `is_active` | boolean | |
| `password` | string hashed | |
| `deleted_at` | timestamp | SoftDelete |

#### `companies`
| Kolon | Tip |
|-------|-----|
| `id`, `name`, `slug`, `is_active`, `settings` (JSON) |

#### `guest_applications`
| Kolon | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | |
| `company_id` | bigint FK | |
| `guest_user_id` | bigint FK users | |
| `tracking_token` | string unique | Durum sorgulama |
| `first_name`, `last_name` | string | |
| `email`, `phone`, `gender` | string | |
| `application_country` | string | Başvuru ülkesi (select) |
| `communication_language` | string | |
| `application_type` | string | bachelor/master/vb. |
| `assigned_senior_email` | string null | |
| `target_term`, `target_city` | string null | |
| `language_level` | string null | |
| `lead_source`, `dealer_code` | string null | |
| `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` | string | UTM parametreler |
| `click_id`, `tracking_link_code` | string null | |
| `lead_status` | string | new/contacted/docs_ready/vb. |
| `is_archived` | boolean | |
| `converted_to_student` | boolean | |
| `converted_student_id` | string null | |
| `registration_form_draft` | JSON | Kayıt formu taslak |
| `registration_form_submitted_at` | timestamp | |
| `selected_package_code` | string null | Seçilen paket |
| `selected_extra_services` | JSON array | |
| `contract_status` | string | none/requested/signed/approved/rejected |
| `contract_snapshot_text` | text | Sözleşme snapshot |
| `profile_photo_path` | string null | |
| `kvkk_consent` | boolean | GDPR onayı |
| `deleted_at` | timestamp | SoftDelete |

#### `student_assignments`
| Kolon | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | |
| `company_id` | bigint FK | |
| `student_id` | string | Öğrenci ID (GST-XXXXXXXX) |
| `internal_sequence` | int | |
| `senior_email` | string | Atanmış senior |
| `branch` | string | Ofis şubesi |
| `risk_level` | string | low/medium/high |
| `payment_status` | string | |
| `dealer_id` | bigint FK null | |
| `student_type` | string | |
| `is_archived` | boolean | |
| `deleted_at` | timestamp | SoftDelete |

#### `dealers`
| Kolon | Tip |
|-------|-----|
| `id`, `code` (unique), `internal_sequence`, `name`, `email`, `phone`, `whatsapp`, `dealer_type_code`, `is_active`, `is_archived`, `archived_at`, `deleted_at` |

### 6.2 Finans Tabloları

#### `revenue_milestones`
Genel milestone tanımları (şirket düzeyinde)

#### `student_revenues`
Her öğrenci için gelir takibi (init/triggered/confirmed/paid statüleri)

#### `dealer_revenue_milestones`
Dealer'a özel milestone tanımları

#### `dealer_student_revenues`
Dealer başına öğrenci gelir paylaşımı

#### `dealer_payout_accounts`
Dealer ödeme hesapları (IBAN/banka bilgisi)

#### `dealer_payout_requests`
Komisyon talepleri (requested/approved/paid/rejected)

### 6.3 Belge ve İçerik Tabloları

#### `document_categories`
Belge kategorileri (top_category dahil)

#### `documents`
Öğrenci/guest belgeleri (Firebase Storage + review_note + approve/reject)

#### `guest_required_documents`
Gerekli belge tanımları (stage: 'guest' veya 'student')

#### `guest_registration_fields`
Dinamik kayıt formu alanları (field_type/field_key/sort_order/is_required)

#### `guest_registration_snapshots`
Kayıt formu anlık görüntüleri

#### `contract_templates`
Sözleşme şablonları (PDF/Word)

### 6.4 İletişim Tabloları

#### `dm_threads`
DM (Direct Message) konuşma başlıkları

#### `dm_messages`
Bireysel DM mesajları (dosya eki dahil)

#### `guest_tickets`
Guest destek talepleri (department/sla/assignment)

#### `guest_ticket_replies`
Ticket yanıtları

#### `message_templates`
Mesaj şablonları (bildirim için)

#### `notification_dispatches`
Bildirim gönderim kuyruk kayıtları

### 6.5 Süreç ve Otomasyon Tabloları

#### `process_definitions`
Süreç tanımları (adım adım iş akışı)

#### `process_outcomes`
Süreç sonuçları (görünürlük yönetimiyle)

#### `escalation_rules`
Otomatik yükseltme kuralları

#### `escalation_events`
Tetiklenen yükseltme olayları

#### `field_rules`
Alan doğrulama kuralları (rule_key, condition, action)

#### `field_rule_approvals`
Field rule onay kayıtları

#### `manager_requests`
Manager iç talepleri (departman bazlı)

#### `batch_operation_runs`
Toplu işlem geçmişi

### 6.6 Güvenlik ve Denetim Tabloları

#### `account_vaults`
AES şifreli portal hesapları (is_visible_to_student flag)

#### `account_access_logs`
Vault erişim denetim logu

#### `system_event_logs`
Sistem olay logu (event_type, level, actor, payload)

#### `consent_records`
GDPR onay kayıtları (kvkk_version, ip, user_agent)

#### `data_retention_policies`
Veri saklama politikaları (anonimleştirme kuralları)

#### `role_change_audits`
Rol değişiklik geçmişi

#### `user_role_assignments`
RBAC atama tablosu

### 6.7 Marketing Tabloları

#### Kampanya
- `marketing_campaigns` — Kampanya tanımları (channel/status/budget/spent)
- `marketing_tracking_links` — UTM izleme linkleri
- `marketing_tracking_clicks` — Tıklama kayıtları
- `lead_source_data` — Lead kaynak verileri
- `lead_source_options` — Kaynak seçenekleri
- `marketing_external_metrics` — Meta/GA4/Google Ads/TikTok/LinkedIn metrikleri
- `marketing_reports` — Oluşturulan raporlar
- `marketing_integration_connections` — OAuth token depolama

#### CMS
- `cms_contents` — İçerikler (draft/published/scheduled)
- `cms_content_revisions` — İçerik versiyonları
- `cms_categories` — CMS kategorileri
- `cms_media_library` — Medya dosyaları

#### E-posta
- `email_templates` — Şablonlar
- `email_segments` — Segmentler
- `email_campaigns` — E-posta kampanyaları
- `email_send_log` — Gönderim logu

#### Etkinlik
- `marketing_events` — Etkinlik tanımları
- `event_registrations` — Katılımcı kayıtları

#### Sosyal Medya
- `social_media_accounts` — Hesaplar (is_connected, last_synced_at)
- `social_media_posts` — Gönderiler
- `social_media_monthly_metrics` — Aylık metrikler

#### Ekip ve Bütçe
- `marketing_teams` — Ekip üyelikleri
- `marketing_tasks` — Görevler (status/priority/column_order/parent_task_id)
- `marketing_budgets` — Dönemsel bütçeler
- `marketing_admin_settings` — Genel ayarlar

### 6.8 Diğer Tablolar

- `student_types` — Öğrenci tipi tanımları
- `dealer_types` — Dealer tipi tanımları
- `dealer_type_histories` — Dealer tipi değişiklik geçmişi
- `dealer_utm_links` — Dealer UTM linkleri
- `student_risk_scores` — Risk skoru hesaplamaları
- `student_appointments` — Öğrenci randevuları
- `student_material_reads` — Materyal okundu takibi
- `knowledge_base_articles` — Bilgi tabanı makaleleri
- `internal_notes` — Dahili notlar (pin desteği)
- `integration_configs` — Entegrasyon konfigürasyonları
- `external_provider_connections` — Harici sağlayıcı bağlantıları
- `user_portal_preferences` — Kullanıcı portal tercihleri
- `role_templates` — RBAC şablonları
- `permissions` — İzin tanımları

---

## 7. Web Route Listesi

### Public

```
GET  /                              → redirect /apply
GET  /landing/mentorde              → landing.mentorde (view)
GET  /go/{code}                     → TrackedLinkRedirectController
GET  /apply                         → GuestApplicationController@create
POST /apply                         → GuestApplicationController@store [throttle:5,1]
GET  /apply/success                 → GuestApplicationController@success
GET  /apply/status/{token}          → GuestApplicationController@status [throttle:30,60]
GET  /login                         → AuthController@showLogin
POST /login                         → AuthController@login [throttle:5,1]
GET  /forgot-password               → ForgotPasswordController@show
POST /forgot-password               → ForgotPasswordController@send [throttle:5,1]
GET  /reset-password/{token}        → ResetPasswordController@show
POST /reset-password                → ResetPasswordController@reset [throttle:5,1]
```

### Manager (middleware: manager.role)

```
GET  /manager/dashboard             → ManagerDashboardController@index
POST /manager/dashboard/snapshot    → storeSnapshot
GET  /manager/dashboard/snapshot/{id} → showSnapshot
POST /manager/dashboard/snapshot/{id}/mark-sent
POST /manager/dashboard/snapshot/mark-sent-bulk
GET  /manager/dashboard/snapshot/{id}/print
DELETE /manager/dashboard/snapshot/{id}
GET  /manager/dashboard/export-csv
GET  /manager/guests                → ManagerPortalController@guests
GET  /manager/guests/export-csv
GET  /manager/guests/{guest}
PATCH /manager/guests/{guest}/status
PATCH /manager/guests/{guest}/assign
GET  /manager/students
GET  /manager/students/export-csv
GET  /manager/students/{id}
PATCH /manager/students/{id}/update
GET  /manager/seniors
GET  /manager/seniors/{email}
GET  /manager/dealers
GET  /manager/dealers/{code}
GET  /manager/commissions
PATCH /manager/commissions/{id}/approve
PATCH /manager/commissions/{id}/reject
PATCH /manager/commissions/{id}/mark-paid
GET  /manager/contract-template
POST /manager/contract-template
POST /manager/contract-template/student-services
POST /manager/contract-template/start-contract
POST /manager/contract-template/decision
POST /manager/contract-template/company-settings
GET  /manager/theme
POST /manager/theme
GET  /manager/preview/student/{id}
GET  /manager/preview/dealer/{code}
GET  /manager/preview/senior/{email}
GET  /config                        → view config.index
GET  /config/export-code/safe       → [throttle:3,60]
GET  /student-card
GET/POST/PUT/DELETE /tasks          → TaskBoardController
GET/POST /tickets-center            → TicketCenterController
GET/POST /messages-center           → MessageCenterController
GET/POST /manager/requests          → ManagerRequestController
```

### Senior (middleware: senior.role)

```
GET  /senior/dashboard
GET  /senior/students
GET  /senior/students/export-csv
GET  /senior/registration-documents
GET  /senior/process-tracking
GET  /senior/appointments
GET  /senior/tickets
GET  /senior/materials
GET  /senior/contracts
GET  /senior/services
GET  /senior/vault
GET  /senior/notes
GET  /senior/knowledge-base
GET  /senior/performance
GET  /senior/performance/report-print
GET  /senior/performance/report-csv
GET  /senior/profile
GET  /senior/settings
GET  /senior/messages
GET  /senior/document-builder
POST /senior/document-builder/generate
POST /senior/process-outcomes
POST /senior/process-outcomes/{id}/make-visible
POST /senior/vault                  → storeVault
DELETE /senior/vault/{id}           → destroyVault
POST /senior/vault/{id}/toggle-visibility
POST /senior/profile
POST /senior/settings
POST /senior/settings/password
POST /senior/messages/{thread}/send
```

### Student (middleware: student.role)

```
GET  /student/dashboard
POST /student/workflow/request-next-step
GET  /student/registration
POST /student/registration/form/auto-save
POST /student/registration/form/submit
GET  /student/registration/documents
POST /student/registration/documents/upload
GET  /student/registration/documents/{doc}/download
DELETE /student/registration/documents/{doc}
GET  /student/process-tracking
GET  /student/document-builder
POST /student/document-builder/generate
GET  /student/appointments
POST /student/appointments
POST /student/appointments/{id}/cancel
GET  /student/tickets
POST /student/tickets
POST /student/tickets/{id}/reply
POST /student/tickets/{id}/close
POST /student/tickets/{id}/reopen
GET  /student/materials
POST /student/materials/{id}/read
GET  /student/contract
GET  /student/contract/download-signed
POST /student/contract/request
POST /student/contract/upload-signed
POST /student/contract/addendum-request
GET  /student/services
POST /student/services/select-package
POST /student/services/add-extra
DELETE /student/services/remove-extra/{code}
GET  /student/profile
GET  /student/settings
POST /student/profile/photo
POST /student/profile
POST /student/settings
POST /student/settings/password
GET  /student/messages
POST /student/messages/send
GET  /student/notifications
GET  /student/payments
GET  /student/vault
GET  /student/vault/{id}/reveal    → [throttle:20,1]
GET  /student/gdpr/export          → [throttle:5,60]
POST /student/gdpr/erasure         → [throttle:3,60]
```

---

## 8. API Route Listesi

### Public API (`/api/v1/public/*`)

```
GET  /api/v1/public/apply-suggestions       → [throttle:60,1]
GET  /api/v1/public/lead-source-options     → [throttle:60,1]
```

### Config API (`/api/v1/config/*`)

> Middleware: `web`, `company.context`, `auth`, `manager.role`

#### Şirket / RBAC
```
GET/POST    /companies
PUT         /companies/{id}
POST        /companies/switch
GET         /rbac/permissions
POST        /rbac/permissions
GET/POST    /rbac/templates
PUT         /rbac/templates/{id}
POST        /rbac/templates/{id}/permissions/sync
GET/POST    /rbac/assignments
POST        /rbac/assignments/{id}/revoke
GET         /rbac/users/{id}/effective-permissions
GET/POST    /portal-users
PUT         /portal-users/{id}
POST        /portal-users/{id}/reset-password
DELETE      /portal-users/{id}
```

#### Tip Yönetimi
```
GET/POST/PUT /student-types
GET/POST/PUT /dealer-types
GET/POST/PUT /process-definitions
GET/POST     /lead-source-options
PUT          /lead-source-options/{id}
```

#### Senior Yönetimi
```
GET/POST    /seniors
PUT         /seniors/{id}
POST        /seniors/{id}/reset-password
POST        /seniors/{id}/transfer
DELETE      /seniors/{id}
```

#### Dealer Yönetimi
```
GET/POST    /dealers
GET         /dealers/type-history
PUT         /dealers/{id}
POST        /dealers/{id}/archive
POST        /dealers/{id}/unarchive
DELETE      /dealers/{id}
```

#### Öğrenci / Atama
```
GET/POST    /student-assignments
GET         /student-assignments/branches
POST        /student-assignments/generate-id
POST        /student-assignments/bulk-assign
POST        /student-assignments/auto-assign
POST        /student-assignments/{id}/archive
POST        /student-assignments/{id}/unarchive
GET/POST    /student-revenues/{id}
POST        /student-revenues/init|trigger|confirm|pay
GET/POST/PUT /dealer-revenue-milestones
GET/POST    /dealer-student-revenues/{dealer}/{student}
GET/POST    /revenue-milestones
PUT         /revenue-milestones/{id}
GET         /student-risk-scores
POST        /student-risk-scores/calculate-now
```

#### Belge / İçerik
```
GET/POST    /document-categories
GET/POST/PUT/DELETE /documents
POST        /documents/{id}/approve|reject
POST        /documents/preview-name
GET/POST/PUT/DELETE /guest-registration-fields
POST        /guest-registration-fields/{id}/move|clone
GET/POST/PUT/DELETE /guest-required-documents
POST        /guest-required-documents/publish
```

#### Guest Ops
```
GET         /guest-applications               → [permission:student.assignment.manage]
GET         /guest-applications/{id}/conversion-readiness
POST        /guest-applications/archive-stale
POST        /guest-applications/{id}/approve-contract
POST        /guest-applications/{id}/reject-contract
POST        /guest-applications/{id}/convert
GET         /guest-ops/tickets
POST        /guest-ops/tickets/{id}/status|reply
GET         /guest-ops/documents
POST        /guest-ops/documents/{id}/decision
```

#### Süreç / Kural / Bildirim
```
GET/POST/PUT /field-rules
POST        /field-rules/evaluate
GET/POST    /field-rule-approvals
POST        /field-rule-approvals/{id}/approve|reject
POST        /field-rule-approvals/archive-bulk|cleanup-bulk
GET/POST    /escalation-rules
PUT         /escalation-rules/{id}
POST        /escalation-rules/process-now
GET/POST/PUT /message-templates
GET/POST    /notification-dispatches
POST        /notification-dispatches/dispatch-now|retry-failed
POST        /notification-dispatches/{id}/mark-sent|mark-failed
GET/POST    /batch-operations
POST        /batch-operations/notification-broadcast
```

#### Öğrenci Kartı / Vault / Notlar
```
GET         /student-card/search
GET         /student-card/{id}
GET/POST/PUT/DELETE /account-vault
GET         /account-vault/{id}/reveal
GET         /account-vault-logs
GET/POST/POST/DELETE /internal-notes
POST        /internal-notes/{id}/pin|unpin
GET/POST/PUT/DELETE /process-outcomes
POST        /process-outcomes/{id}/make-visible
```

#### Sistem
```
GET         /system-health
POST        /system-health/run-critical-check
GET         /system-event-logs
GET/POST/PUT /external-provider-connections
GET         /firebase-storage/status
POST        /firebase-storage/test-upload
GET         /firestore/status
POST        /firestore/test-write
GET/POST    /integration-configs/{category}
POST        /integration-configs/{category}/test
GET         /role-catalog
GET         /entity-catalog
POST        /entity-catalog/suggest
GET         /suggestions
GET         /knowledge-base
POST        /knowledge-base
PUT         /knowledge-base/{id}
```

### Marketing API (`/api/v1/marketing-admin/*`)

```
GET  /companies
POST /companies/switch
GET  /suggestions
GET/POST/PUT/DELETE /campaigns
GET  /analytics/kpis
GET  /analytics/source-performance
GET  /analytics/external-performance
```

### Student API (`/api/v1/student/*`)

```
GET  /process-outcomes/{id}         → [middleware: process.outcome.visibility]
```

---

## 9. Marketing Admin Route Listesi

Prefix: `/mktg-admin`, Middleware: `marketing.access`

```
GET  /dashboard                         KPI + benchmark
GET  /suggestions/audience              Hedef kitle önerileri

GET/POST/PUT/DELETE /campaigns          Kampanya CRUD
PUT  /campaigns/{id}/pause|resume
GET  /campaigns/{id}/report
GET  /campaigns/{id}/daily-metrics

GET/POST/PUT/DELETE /content            CMS içerik
PUT  /content/{id}/publish|unpublish|schedule|feature
GET  /content/{id}/stats|revisions
GET/POST/PUT/DELETE /categories
GET  /media
POST /media/upload
DELETE /media/{id}

GET/POST/PUT/DELETE /email/templates
POST /email/templates/{id}/test-send
GET/POST/PUT/DELETE /email/segments
GET  /email/segments/{id}/preview
GET/POST/PUT/DELETE /email/campaigns
POST /email/campaigns/{id}/send|schedule
GET  /email/campaigns/{id}/stats
GET  /email/log

GET/POST/PUT/DELETE /social/accounts
GET/POST/PUT/DELETE /social/posts
PUT  /social/posts/{id}/publish|metrics
GET  /social/metrics
GET  /social/metrics/monthly/{period}
GET  /social/calendar

GET  /lead-sources                      + funnel/utm/tracking-codes/dropoff/source-verify
GET/POST/PUT/DELETE /events
PUT  /events/{id}/publish|cancel
GET  /events/{id}/registrations
PUT  /events/{id}/registrations/{reg}/status
GET  /events/{id}/report|survey-results
POST /events/{id}/send-reminder

GET  /pipeline                          + value/loss-analysis/conversion-time
GET  /dealers
GET  /dealers/{id}/performance
GET/POST/PUT /budget
GET  /kpi
GET  /reports
GET  /reports/{id}/download/{format}
GET/POST/PUT /tracking-links
GET/POST/PUT/DELETE /tasks
GET  /tasks/kanban
PUT  /tasks/{id}/kanban                 Kanban drag-drop
GET/PUT /profile
GET  /settings|integrations|notifications
PUT  /notifications/{id}/read

# Admin-only (middleware: marketing.admin):
GET/POST/PUT/DELETE /team
PUT  /settings
PUT/POST /integrations
POST /integrations/test/{provider}
POST /integrations/refresh/{provider}
GET  /integrations/oauth/{provider}/start|callback
POST/PUT /budget
POST /dealers/broadcast|materials
POST /dealers/{code}/broadcast          Tek dealer broadcast
POST /reports/generate
POST /notifications/dispatch-now|retry-failed
POST /notifications/{id}/mark-sent|mark-failed
```

---

## 10. Servis Katmanı

### Core Services

| Servis | Açıklama |
|--------|----------|
| `DashboardKPIService` | Manager + Senior dashboard KPI'ları (Cache::remember 300s) |
| `GuestListService` | Guest listesi filtreleme, KPI, export (BelongsToCompany trait ile) |
| `StudentListService` | Öğrenci listesi filtreleme, KPI, export |
| `AccountVaultService` | AES-256 şifreleme/çözme (account_password_encrypted) |
| `FieldRuleEngine` | Alan doğrulama kurallarını değerlendirir |
| `LeadSourceTrackingService` | UTM + tracking click kaydetme |
| `RiskScoreService` | Öğrenci risk skoru hesaplama |
| `RevenueMilestoneService` | Gelir milestone tetikleme ve onay |
| `DealerRevenueService` | Dealer gelir paylaşımı |
| `EscalationService` | Otomatik yükseltme kurallarını işler |
| `EventLogService` | SystemEventLog kayıt kolaylığı |
| `ProcessOutcomeService` | Süreç sonucu görünürlük yönetimi |
| `TaskAutomationService` | Task otomasyon kuralları |
| `StudentSearchService` | Öğrenci arama (multi-field) |
| `StudentGuestResolver` | Öğrenci/Guest tekil kimlik çözümleyici |
| `StudentCardService` | Öğrenci kartı veri toplama |
| `InternalNoteService` | Dahili not CRUD + pin |
| `ContractTemplateService` | Sözleşme şablonu render/snapshot |
| `CvTemplateService` | CV belgesi oluşturma (PHPWord) |
| `DocumentNamingService` | Belge adlandırma kuralları |
| `DocumentTagService` | Belge etiketleme |
| `EntityCatalogService` | Entity kataloğu yönetimi |
| `GuestRegistrationFieldSchemaService` | Dinamik form şema oluşturma |
| `AiWritingService` | AI yazı yardımcısı stub |
| `AnonymizationService` | GDPR - PII anonimleştirme |
| `PersonalDataExportService` | GDPR - JSON veri dışa aktarma |
| `FirebaseStorageService` | Google Cloud Storage dosya yükleme |
| `FirestoreRestService` | Firestore REST API çağrıları |

### Marketing Services

| Servis | Açıklama |
|--------|----------|
| `ExternalMetricsSyncService` | Meta/GA4/Google Ads/TikTok/LinkedIn/Instagram metrics sync |
| `IntegrationHealthService` | Entegrasyon sağlık kontrolü |

---

## 11. Integration Adapter Sistemi

### Mimari

```
IntegrationFactory
    ├── CalendarIntegrationInterface
    │   ├── GoogleCalendarAdapter  (createEvent/cancelEvent/getAvailability + freeBusy)
    │   ├── CalendlyAdapter        (getSchedulingLink → event_types API)
    │   ├── CalComAdapter          (getSchedulingLink → /api/v1/event-types)
    │   └── AbstractCalendarAdapter (getToken → MarketingIntegrationConnection)
    │
    ├── EmailMarketingInterface
    │   ├── MailchimpAdapter    (datacenter prefix + createCampaign/sendCampaign/getCampaignStats)
    │   ├── SendGridAdapter     (singlesends API + schedule + stats)
    │   ├── ZohoAdapter         (getCampaignStats → Zoho Campaigns REST)
    │   └── AbstractEmailMarketingAdapter
    │
    ├── VideoConferenceInterface
    │   ├── ZoomAdapter        (/v2/users/me/meetings + delete + join_url)
    │   ├── GoogleMeetAdapter  (Calendar API + conferenceData, composite ID: eventId|joinUrl)
    │   ├── TeamsAdapter       (MSGraph /me/onlineMeetings + joinWebUrl)
    │   └── AbstractVideoAdapter
    │
    ├── ElectronicSignatureInterface
    │   ├── PandaDocAdapter    (/public/v1/documents + send + session)
    │   ├── DocuSignAdapter    (envelopes + views/recipient → signing URL)
    │   ├── HelloSignAdapter   (Basic auth + unified create+send in one call)
    │   └── AbstractESignAdapter
    │
    └── ProjectManagementInterface
        ├── ClickUpAdapter     (/v2/list/{listId}/task + status/assign)
        ├── MondayAdapter      (GraphQL mutations: create_item/change_column_value)
        ├── NotionAdapter      (/v1/pages + Notion-Version header + properties)
        └── AbstractProjectManagementAdapter
```

### Token Yönetimi

Her Abstract adapter'da `getToken()` metodu:
1. `MarketingIntegrationConnection` tablosunda şirket + provider bazında token arar
2. `token_expires_at->isPast()` → expired ise null döner
3. Null → parent stub'a fall back (sessizce devam eder)

`ExternalMetricsSyncService::refreshTokenIfNeeded()`:
- GA4 / Google Ads: OAuth2 token refresh (`oauth2.googleapis.com/token`)
- LinkedIn: LinkedIn OAuth2 token refresh (`linkedin.com/oauth/v2/accessToken`)
- Güncellenen token `MarketingAdminSetting`'e de yazılır

### External Metrics Providers

| Provider | API | Metrikler |
|----------|-----|-----------|
| **Meta (Facebook Ads)** | Graph API v21.0 | spend, clicks, impressions, conversions |
| **GA4** | Data API v1beta | sessions, conversions, revenue |
| **Google Ads** | Google Ads API | clicks, impressions, ctr, cpc, cost |
| **TikTok Ads** | Business API v1.3 | spend, impressions, clicks, conversions |
| **LinkedIn Ads** | Marketing API v2 | spend, impressions, clicks (campaign-level) |
| **Instagram** | Graph API v21.0 | reach, impressions, profile_views (account-level) |

---

## 12. Middleware Listesi

| Middleware | Alias | Açıklama |
|------------|-------|----------|
| `SetCompanyContext` | `company.context` | Oturum/cookie'den company_id bağlar |
| `EnsureManagerRole` | `manager.role` | `ADMIN_PANEL_ROLES` kontrolü |
| `EnsureManagerOrPermission` | `manager.or.permission:{code}` | Manager VEYA izin kodu |
| `EnsureSeniorRole` | `senior.role` | Senior/Mentor rolü + isteğe bağlı permission |
| `EnsureStudentRole` | `student.role` | Student rolü (stage=student filtreli) |
| `EnsureGuestRole` | `guest.role` | Guest rolü |
| `EnsureDealerRole` | `dealer.role` | Dealer rolü |
| `EnsureMarketingAccess` | `marketing.access` | `MARKETING_ACCESS_ROLES` kontrolü |
| `EnsureMarketingAdminOnly` | `marketing.admin` | Marketing Admin rolü zorunlu |
| `EnsurePermission` | `permission:{code}` | Belirli izin kodu kontrolü |
| `EnsureManagerKey` | `manager.key` | Manager özel anahtar doğrulama |
| `EnsureTaskAccess` | `task.access` | `TASK_ACCESS_ROLES` kontrolü |
| `CheckDealerTypePermission` | `dealer.type.permission:{perm}` | Dealer tipi izin kontrolü |
| `CheckProcessOutcomeVisibility` | `process.outcome.visibility` | Süreç sonucu görünürlük |
| `FieldRuleValidator` | `field.rule.validator:{context},{field}` | Başvuru alanı kuralı |
| `EnsureGuestOwnsDocument` | `guest.owns.document` | Guest IDOR koruması |
| `EnsureGuestOwnsTicket` | `guest.owns.ticket` | Guest IDOR koruması |
| `SecurityHeaders` | (global) | X-Frame-Options, X-Content-Type, CSP, Referrer-Policy |

---

## 13. Artisan Komutları ve Zamanlayıcı

### Ayrı Komut Dosyaları (`app/Console/Commands/`)

| Komut | Açıklama |
|-------|----------|
| `php artisan integrations:health-check` | Tüm integration provider'lar için ping + status güncelleme + log |
| `php artisan social:sync-metrics` | Sosyal medya hesaplarından aylık metrik çekme |
| `php artisan gdpr:enforce-retention` | Veri saklama politikalarını uygula (PII anonimleştirme) |

#### `integrations:health-check` Seçenekleri
```
--provider=   Belirli provider'ı test et
--company=    Belirli şirketi filtrele
--dry-run     DB güncellemesi yapmadan çalıştır
```

#### `social:sync-metrics` Seçenekleri
```
--account=    Belirli hesap ID'si
--dry-run     Konsola yaz, kaydetme
```

**Desteklenen platformlar:** Instagram, Facebook, Twitter, YouTube, LinkedIn, TikTok

### Inline Komutlar (`routes/console.php`)

| Komut | Açıklama |
|-------|----------|
| `manager:report-snapshot` | Periyodik yönetici raporu oluşturma |
| `marketing:sync-external-metrics` | Harici metrik senkronizasyonu |
| `escalation:process` | Yükseltme kurallarını işle |
| `risk:calculate-all` | Tüm öğrenciler için risk skoru hesapla |
| `lead:check-source-match` | Lead kaynak eşleşme kontrolü |

### Zamanlayıcı

| Komut | Zamanlama | Açıklama |
|-------|-----------|----------|
| `integrations:health-check` | Her gün 06:00 | Entegrasyon sağlık kontrolü |
| `social:sync-metrics` | Her gün 07:00 | Sosyal medya metrik sync |
| `gdpr:enforce-retention` | Her gece 03:00 | Veri saklama politikası |
| `marketing:sync-external-metrics` | Her gün (var ise) | Harici metrik sync |
| `escalation:process` | Periyodik | Yükseltme işleme |

---

## 14. Test Suite

### Toplam: 35 Feature Test Dosyası

#### Core / Auth Tests
| Test | Kapsam |
|------|--------|
| `ExampleTest` | Temel test örneği |
| `PasswordResetFlowTest` (6 test) | Şifre sıfırlama akışı tam coverage |
| `SecurityMiddlewareTest` (6 test) | Security headers + throttle reset |
| `GdprFlowTest` (8 test) | GDPR export/erasure, IDOR, throttle |
| `StudentAccessControlTest` | Öğrenci erişim kontrolü |

#### Guest / Student Tests
| Test | Kapsam |
|------|--------|
| `GuestCriticalFlowTest` | Guest kayıt + belge + ticket akışı |
| `StudentCriticalFlowTest` | Öğrenci kritik süreç akışı |
| `StudentModuleSmokeTest` | Tüm student rotalarının 200/302 dönüşü |
| `StudentPortalSmokeRoutesTest` | Student portal rota smoke test |
| `PublicApplySuggestionsTest` | Başvuru önerileri API |
| `PublicLandingMentordeTest` | Landing sayfası görünürlüğü |
| `ContractTemplateFlowTest` | Sözleşme oluşturma akışı |

#### Marketing Tests
| Test | Kapsam |
|------|--------|
| `MarketingAdminDashboardDataTest` | Dashboard KPI verileri |
| `MarketingBudgetPageTest` | Bütçe sayfası |
| `MarketingCampaignLiveMetricsTest` | Kampanya canlı metrikler |
| `MarketingCampaignManagementPageTest` | Kampanya yönetim sayfası |
| `MarketingCmsContentPageTest` | CMS içerik sayfası |
| `MarketingDealerRelationsPageTest` | Bayi ilişkileri |
| `MarketingEmailCampaignsPageTest` | E-posta kampanyaları |
| `MarketingEmailSegmentsPageTest` | E-posta segmentleri |
| `MarketingEmailTemplatesPageTest` | E-posta şablonları |
| `MarketingEventsPageTest` | Etkinlikler |
| `MarketingKpiReportsTest` | KPI raporları |
| `MarketingLeadSourcesPagesTest` | Lead kaynakları sayfaları |
| `MarketingNotificationsPageTest` | Bildirimler |
| `MarketingProfilePageTest` | Profil sayfası |
| `MarketingSalesPipelineLiveTest` | Satış pipeline |
| `MarketingSettingsPageTest` | Ayarlar |
| `MarketingSocialModulePageTest` | Sosyal medya modülü |
| `MarketingTeamPageTest` | Ekip sayfası |
| `MarketingTrackingLinkFlowTest` | Tracking link akışı |

#### Integration / System Tests
| Test | Kapsam |
|------|--------|
| `ApiErrorContractTest` | API hata format sözleşmesi |
| `OpsCommandsTest` | Artisan komut testi |
| `LeadSourceTrackingIntegrationTest` | Lead kaynak izleme entegrasyonu |
| `TicketRequestCenterCriticalTest` | Ticket + request merkezi kritik testler |

### E2E Test (Playwright)

```javascript
// tests/e2e/student-smoke.spec.js
// Playwright ile Chrome/Firefox tam tarayıcı testi
// Öğrenci giriş + dashboard + temel akışlar
```

### Test Çalıştırma

```bash
php artisan test                          # Tüm testler (74+ test)
php artisan test --filter=Marketing       # Marketing testleri
php artisan test --filter=Student         # Öğrenci testleri
php artisan test --filter=Gdpr            # GDPR testleri
composer run test:critical                # Kritik testler
```

---

## 15. GDPR ve Güvenlik Özellikleri

### GDPR Uyumluluğu

#### Madde 20 — Veri Taşınabilirliği
- `GET /student/gdpr/export` → `PersonalDataExportService` → JSON
- `GET /guest/gdpr/export` → JSON (throttle:5,60)

#### Madde 17 — Unutulma Hakkı
- `POST /student/gdpr/erasure` → `ManagerRequest` oluştur
- `POST /guest/gdpr/erasure` → `ManagerRequest` oluştur (throttle:3,60)
- `AnonymizationService` → PII alanlarını hard delete yerine anonimleştirir

#### Onay Kaydı
- `ConsentRecord` — her başvuruda KVKK versiyonu + IP + user-agent
- `GuestApplicationController::store()` → otomatik kayıt

#### Veri Saklama
- `DataRetentionPolicy` tablosu
- `gdpr:enforce-retention` komutu (her gece 03:00)
- Guest: 3 yıl, User: 5 yıl (varsayılan)

### Güvenlik Özellikleri

#### Şifreleme
- `AccountVaultService` → AES-256 şifreleme (portal şifreleri)
- `Hash::make()` → bcrypt ile kullanıcı şifreleri

#### Dosya Güvenliği
- `ValidFileMagicBytes` Rule → `finfo` ile gerçek MIME doğrulama
- 5 upload noktasında aktif (belge yükleme, profil fotoğrafı, vb.)
- Test ortamında skip (fake dosyalar)

#### Şifre Karmaşıklığı
```php
Password::min(8)->letters()->mixedCase()->numbers()->symbols()
```
4 controller'da zorunlu (Student, Guest, Senior, Dealer şifre değiştirme)

#### HTTP Güvenlik Başlıkları (`SecurityHeaders` middleware — global)
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Content-Security-Policy: default-src 'self' ...
Referrer-Policy: strict-origin-when-cross-origin
```

#### Rate Limiting (Throttle)
| Endpoint | Limit |
|----------|-------|
| Login | 5/dakika |
| Başvuru gönder | 5/dakika |
| Vault şifre görüntüle | 20/dakika |
| GDPR export | 5/saat |
| GDPR silme talebi | 3/saat |
| Config export safe | 3/saat |
| Guest genel | 240/dakika |

#### IDOR Koruması
- `EnsureGuestOwnsDocument` — Guest kendi belgelerine erişir
- `EnsureGuestOwnsTicket` — Guest kendi ticketlarına erişir
- Senior portal — `assignedStudentIds()` ile öğrenci IDOR koruması
- Student vault reveal — IDOR kontrolü

### Denetim Loglama
```
SystemEventLog olayları:
- vault.revealed      → Hesap kasası şifre görüntüleme
- gdpr.bulk_export    → GDPR dışa aktarma
- gdpr.pii_access     → PII erişimi
- integration.health  → Entegrasyon sağlık kontrolü
- social.sync         → Sosyal medya metrik sync
```

---

## 16. Frontend Mimarisi

### CSS Sistemi

**Ana dosya:** `public/css/portal-unified-v2.css`

Tüm portal grupları bu dosyayı paylaşır. Ek framework CSS yok.

#### CSS Değişkenleri (namespace: `--u-*`)
```css
--u-brand    : Ana mavi (#0a67d8)
--u-ok       : Yeşil (başarı)
--u-warn     : Sarı (uyarı)
--u-danger   : Kırmızı (hata/silme)
--u-info     : Mavi (bilgi)
--u-line     : Çizgi rengi
--u-card     : Kart arkaplanı
--u-text     : Ana metin
--u-muted    : Soluk metin
--u-bg       : Sayfa arkaplanı
```

#### Layout Grid
```css
.grid2  → 2 kolon
.grid3  → 3 kolon
.grid4  → 4 kolon
```

#### Bileşen Sınıfları
```css
.card / .panel  → Kart bileşeni (aynı stil)
.kpis           → KPI grid container (4 kolon)
.kpi            → KPI kart
.list           → Liste container
.item           → Liste öğesi (list > item kombinasyonu)
.badge          → Durum rozeti temel
.badge.ok       → Yeşil roset
.badge.warn     → Sarı roset
.badge.danger   → Kırmızı roset
.badge.info     → Mavi roset
.badge.pending  → Gri roset
.btn            → Buton temel
.btn.primary    → Ana buton
.btn.alt        → İkincil buton
.btn.warn       → Tehlike butonu
.empty          → Boş durum mesajı
.guide          → Kullanım kılavuzu
.pill-link      → Yuvarlak hap link
.pill-links     → Hap link container
```

### JavaScript Dosyaları (`public/js/`)

| Dosya | Açıklama |
|-------|----------|
| `config-panel.js` | Config sayfası (~4300 satır) |
| `task-kanban.js` | Kanban board (HTML5 drag-drop) |
| `task-board.js` | Task board checkbox sync |
| `guest-registration-form.js` | Guest kayıt formu |
| `student-registration-form.js` | Öğrenci kayıt formu |
| `student-registration-documents.js` | Belge yükleme dropzone |
| `guest-registration-documents.js` | Guest belge yükleme |
| `guest-contract.js` | Guest sözleşme (data bridge) |
| `student-contract.js` | Öğrenci sözleşme (data bridge) |
| `student-card.js` | Öğrenci kartı |
| `tracking-links.js` | Tracking linkleri |
| `marketing-admin-dashboard.js` | Dashboard |
| `marketing-admin-integrations.js` | Entegrasyonlar |
| `senior-document-builder.js` | CV builder (bridge) |
| `manager-theme.js` | Tema yönetimi (bridge) |
| `manager-dashboard.js` | Manager dashboard |
| `dealer-dashboard.js` | Dealer dashboard + Chart.js |
| `messages-center.js` | Mesaj merkezi |
| `ticket-center.js` | Ticket merkezi |
| `apply-form.js` | Başvuru formu (UTM + KVKK) |
| `auth-login.js` | Login formu |
| `landing-utm.js` | Landing UTM geçişi |
| `mktg-company-switch.js` | Şirket değiştirme AJAX |
| `marketing-email-segments.js` | Segment üye ekleme |
| `guest-tickets.js` | Departman öneri (IIFE) |
| `guest-services.js` | Ek servis seçimi |
| `student-tickets.js` | Ticket filtre |
| `student-services.js` | Servis dropdown sync |
| `student-messages.js` | Mesaj filtre |

#### Data Bridge Paterni
Blade verisi JS'e şu pattern ile geçilir:
```blade
<script>window.__contractData = @json($contractData)</script>
<script src="/js/guest-contract.js"></script>
```

### Kanban Board (`task-kanban.js`)

```
4 kolon: todo | in_progress | blocked | done
HTML5 drag-drop: draggable=true, dragstart/dragend/dragover/drop
API: GET /mktg-admin/tasks/kanban (JSON yükle)
     PUT /mktg-admin/tasks/{id}/kanban (status + column_order)
localStorage: mktg_task_view (liste/kanban tercihi kalıcı)
```

---

## 17. Zamanlayıcı ve Arka Plan İşleri

### Çalışma Zamanı Gereksinimleri

```bash
# Scheduler aktif etmek için (crontab veya Hostinger cron):
* * * * * cd /home/user/mentorde_app && php artisan schedule:run >> /dev/null 2>&1
```

### Zamanlama Tablosu

| Saat | Komut | Davranış |
|------|-------|----------|
| 03:00 | `gdpr:enforce-retention` | Eski PII anonimleştirme |
| 06:00 | `integrations:health-check` | Tüm entegrasyonları test et |
| 07:00 | `social:sync-metrics` | Sosyal medya metrik senkron |
| Periyodik | `escalation:process` | Yükseltme kuralı işleme |
| Periyodik | `risk:calculate-all` | Risk skoru güncelleme |
| Periyodik | `marketing:sync-external-metrics` | Harici reklam metrikleri |

### Queue Konfigürasyonu
- `.env`: `QUEUE_CONNECTION=sync` (eşzamanlı, harici queue gerektirmez)
- Üretim için Redis/database queue önerilir

---

## 18. Deployment (Hostinger)

### Bağlantı Bilgileri

```env
APP_URL=https://netsparen.de
DB_HOST=db5019270447.hosting-data.io
DB_PORT=3306
DB_DATABASE=dbs15110928
DB_USERNAME=dbu2613625
```

### Sunucu Dizin Yapısı

```
/home/kullanici/
├── mentorde_app/          ← Laravel root (public_html DIŞINDA)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/           ← 755 izin gerekli
│   ├── vendor/
│   ├── artisan
│   └── .env
└── public_html/           ← Web kökü
    ├── index.php          ← Değiştirilmiş (mentorde_app'e işaret eder)
    ├── .htaccess
    ├── css/
    └── js/
```

### Deploy Araçları (proje içinde)

| Dosya | Açıklama |
|-------|----------|
| `HOSTING_DEPLOY.bat` | Deploy paket oluşturucu (çift tıkla) |
| `scripts/build-hostinger-deploy.ps1` | PowerShell script |
| `scripts/hostinger/public_html_index.php` | Değiştirilmiş index.php |
| `exports/hostinger/{timestamp}/` | Oluşturulan ZIP paketleri |

### Deploy Adımları

```bash
# 1. Paketleri oluştur (Windows'ta):
HOSTING_DEPLOY.bat

# 2. Hostinger hPanel > File Manager:
# public_html ile aynı seviyede mentorde_app/ oluştur
# 2_mentorde_app_no_vendor.zip → mentorde_app/ içine çıkart
# 3_vendor.zip → mentorde_app/ içine çıkart (vendor/ klasörü oluşur)
# 1_public_html.zip → public_html/ içine çıkart

# 3. mentorde_app/.env dosyası oluştur

# 4. Hostinger SSH Terminal:
cd ~/mentorde_app
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. İzinler:
chmod -R 755 ~/mentorde_app/storage
chmod -R 755 ~/mentorde_app/bootstrap/cache
```

### Sorun Giderme

| Sorun | Çözüm |
|-------|-------|
| 500 Internal Error | `storage/logs/laravel.log` kontrol et |
| DB bağlantı hatası | `.env` DB bilgilerini doğrula, Remote MySQL whitelist |
| 404 Not Found | `public_html/.htaccess` varlığını kontrol et |
| Permission denied | `chmod -R 755 storage bootstrap/cache` |
| `pdo_mysql` driver yok | PHP.ini'de `extension=pdo_mysql` yorumunu kaldır |

---

## 19. Geliştirici Rehberi

### Yeni Bir Sayfa Ekleme

1. **Route** ekle (`routes/web.php` veya `routes/marketing-admin.php`)
2. **Controller** metodu yaz
3. **View** oluştur (`resources/views/[portal]/`)
4. Gerekirse **Migration** ve **Model** ekle
5. **Test** yaz (`tests/Feature/`)

### CSS Kuralları

```blade
{{-- DOĞRU kullanım --}}
<div class="grid2">
  <div class="card">...</div>
  <div class="card">
    <div class="list">
      <div class="item">...</div>
    </div>
  </div>
</div>

{{-- YANLIŞ: Inline style'da portal-unified class override etme --}}
<style>
  .item { padding: 20px; } /* YANLIŞ! Portal-unified bozulur */
</style>
```

### Yeni API Endpoint Ekleme

```php
// routes/api.php veya marketing-admin.php
Route::get('/my-resource', [MyController::class, 'index'])
    ->middleware('permission:config.view');

// Controller
public function index(Request $request): JsonResponse
{
    return response()->json([
        'ok' => true,
        'data' => [...],
    ]);
}
```

### Multi-Company Desteği

`BelongsToCompany` trait kullanımı:
```php
class MyModel extends Model
{
    use BelongsToCompany;
    // company_id otomatik olarak SetCompanyContext middleware'den bağlanır
}
```

### Entegrasyon Adapter Ekleme

```php
// 1. Interface'i implement eden adapter oluştur
class MyAdapter extends AbstractCalendarAdapter
{
    public function createEvent(array $data): string
    {
        $token = $this->getToken(); // MarketingIntegrationConnection'dan
        if (!$token) return parent::createEvent($data); // stub'a fall back

        // Gerçek HTTP çağrısı...
        $response = Http::withToken($token)->post('...', $data);
        return $response->json('id');
    }
}

// 2. IntegrationFactory'ye ekle
// 3. Config'e provider bloğu ekle (config/marketing_external.php)
```

### Test Yazma Kuralları

```php
class MyNewTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_feature(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->actingAs($user);

        $response = $this->get('/manager/my-page');
        $response->assertStatus(200);
        $response->assertSee('Beklenen İçerik');
    }
}
```

### Ortam Değişkenleri (Tüm .env Anahtarları)

```env
# Uygulama
APP_NAME=MentorDE
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://netsparen.de

# Veritabanı
DB_CONNECTION=mysql
DB_HOST=db5019270447.hosting-data.io
DB_PORT=3306
DB_DATABASE=dbs15110928
DB_USERNAME=dbu2613625
DB_PASSWORD=...

# Önbellek / Session
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Firebase
FIREBASE_PROJECT_ID=mentorde-app-de
FIREBASE_PRIVATE_KEY_ID=...
FIREBASE_PRIVATE_KEY="..."
FIREBASE_CLIENT_EMAIL=...
FIREBASE_CLIENT_ID=...

# External Marketing Providers
META_ACCESS_TOKEN=...
GA4_PROPERTY_ID=...
GA4_SERVICE_ACCOUNT_JSON=...
GOOGLE_ADS_CUSTOMER_ID=...
GOOGLE_ADS_DEVELOPER_TOKEN=...
TIKTOK_ADS_ENABLED=false
TIKTOK_ADVERTISER_ID=...
TIKTOK_ACCESS_TOKEN=...
LINKEDIN_ADS_ENABLED=false
LINKEDIN_AD_ACCOUNT_ID=...
LINKEDIN_ACCESS_TOKEN=...
INSTAGRAM_ENABLED=false
INSTAGRAM_USER_ID=...

# E-posta
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=info@netsparen.de
```

---

## Özet İstatistikler

| Metrik | Değer |
|--------|-------|
| PHP Sınıfı (Model) | ~65 model |
| Marketing Model | 14 ayrı model |
| Migration Dosyası | 121 |
| Route Sayısı | ~250+ |
| Feature Test | 35 dosya / 74+ test |
| Middleware | 18 |
| Service Sınıfı | ~28 |
| Integration Adapter | 15 (5 kategori × 3) |
| External Provider | 6 (Meta/GA4/Google Ads/TikTok/LinkedIn/Instagram) |
| Statik JS Dosyası | ~30 |
| Blade View | ~120 |

---

*Bu döküman MentorDE ERP sisteminin v5.0 release'ini kapsamaktadır.*
*Son güncelleme: 2026-02-28*
