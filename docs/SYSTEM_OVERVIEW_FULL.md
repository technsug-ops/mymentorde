# MentorDE — Sistem Genel ve Detay Dokümanı

**Proje:** MentorDE — Almanya Eğitim Danışmanlığı ERP
**Stack:** Laravel 12 · PHP 8.4 · MySQL 8 · Vite · Blade
**Son güncelleme:** 15 Nisan 2026
**Production:** panel.mentorde.com (KASSERVER)

---

## İçindekiler

1. [Proje Özeti](#1-proje-özeti)
2. [Mimari ve Katmanlar](#2-mimari-ve-katmanlar)
3. [Portal Kılavuzu (7 Portal)](#3-portal-kılavuzu)
4. [Rol ve İzin Matrisi](#4-rol-ve-i̇zin-matrisi)
5. [Veritabanı Şeması](#5-veritabanı-şeması)
6. [Eloquent Modelleri ve İlişkiler](#6-eloquent-modelleri)
7. [Servis Katmanı](#7-servis-katmanı)
8. [Controllers ve Routing](#8-controllers-ve-routing)
9. [Middleware ve Güvenlik](#9-middleware-ve-güvenlik)
10. [Core Modüller](#10-core-modüller)
11. [Dış Entegrasyonlar](#11-dış-entegrasyonlar)
12. [Queue, Jobs ve Scheduled Commands](#12-queue-jobs-ve-scheduled-commands)
13. [Multi-Tenant Architecture](#13-multi-tenant-architecture)
14. [Deploy Pipeline](#14-deploy-pipeline)
15. [Development Workflow](#15-development-workflow)
16. [Sık Yapılan Hatalar](#16-sık-yapılan-hatalar)

---

## 1. Proje Özeti

**MentorDE**, Türkiye'den Almanya'ya lisans/yüksek lisans eğitimi için eğitim danışmanlığı hizmeti veren kurumsal bir ERP sistemidir. Multi-tenant çalışır (tek codebase üzerinden birden fazla şirket yönetebilir), 7 farklı kullanıcı rolü için ayrı portaller sunar ve aday → öğrenci → mezun yaşam döngüsünü eksiksiz destekler.

### Ana Kullanım Senaryoları

- **Aday başvurusu:** Potansiyel öğrenci web formundan kayıt olur, otomatik senior'a atanır, kayıt formunu doldurur, belgelerini yükler
- **Sözleşme ve ödeme:** Aday → öğrenci dönüşümünde hukuki sözleşme üretilir, dijital imza, milestone bazlı ödeme takibi
- **Süreç yönetimi:** Senior ekip öğrencinin üniversite başvurusu, vize, konaklama süreçlerini takip eder
- **Bayi ağı:** 3 tip bayi (Lead Generation, Freelance, Operasyon) aday getirir, komisyon kazanır
- **İç iletişim:** Slack-tarzı IM sistemi (DM, grup, oda, duyuru kanalı), telefon rehberi, bildirim altyapısı
- **Dijital varlık yönetimi:** DAM modülü — dosya, versiyon, paylaşım linki, toplu bildirim
- **Pazarlama:** Lead scoring, campaign tracking, attribution, A/B test, sosyal medya entegrasyonu

### Teknoloji Stack'i

| Katman | Teknoloji |
|---|---|
| Backend | PHP 8.4, Laravel 12 |
| Veritabanı | MySQL 8.0+ (prod), SQLite (test) |
| Frontend | Blade + Vanilla JS + Vite bundling |
| CSS | Tailwind-less, custom `portal-unified-v2.css` + per-portal override |
| Queue | Database (default), Redis (opsiyonel) |
| Cache | Database (default), Redis (opsiyonel) |
| Mail | SMTP (Sendgrid/Mailgun/Resend) |
| Storage | Local disk (prod), S3 destekli |
| Auth | Laravel Breeze tabanlı + 2FA (pragmarx/google2fa) |
| PDF | barryvdh/laravel-dompdf |
| Office | phpoffice/phpword (docx üretimi) |
| Deploy | GitHub Actions → lftp → KASSERVER |

### Composer Paketleri (Production)

- `laravel/framework:^12.0`
- `pragmarx/google2fa:^9.0` — 2FA TOTP
- `bacon/bacon-qr-code:^3.0` — 2FA QR üretim
- `barryvdh/laravel-dompdf:^3.0` — PDF export
- `phpoffice/phpword:^1.1` — Word belge üretimi
- `stripe/stripe-php:^20.0` — Ödeme altyapısı (hazır, tam aktif değil)
- `google/cloud-storage:^1.49` — GCS (S3 alternatif)

---

## 2. Mimari ve Katmanlar

MentorDE, klasik Laravel MVC üzerine **service layer** + **trait-based multi-tenant scoping** ekleyen bir yapı kullanır.

### Dizin Yapısı

```
app/
├── Console/
│   ├── Commands/           # 40+ artisan command (scheduler'da çağrılır)
│   └── Kernel.php          # Command + schedule kayıtları
├── Http/
│   ├── Controllers/        # ~120 controller (portal bazında klasörlü)
│   │   ├── Api/            # REST API controllers
│   │   ├── Auth/           # Login, register, 2FA
│   │   ├── Dealer/         # Bayi portal
│   │   ├── Guest/          # Aday portal
│   │   ├── Hr/             # İnsan Kaynakları
│   │   ├── Manager/        # Manager portal
│   │   ├── MarketingAdmin/ # Marketing
│   │   ├── Senior/         # Eğitim danışmanı
│   │   ├── Shared/         # Çok rollü (ör. DigitalAssetController)
│   │   ├── Student/        # Öğrenci portal
│   │   └── TaskBoard/      # Görev panosu
│   ├── Middleware/         # 24 custom middleware
│   └── Requests/           # Form request validators
├── Jobs/                   # Queued background jobs
├── Mail/                   # Mailable sınıflar
├── Models/                 # ~100 Eloquent model
│   └── Concerns/           # Trait'ler (BelongsToCompany vb.)
├── Services/               # ~60 servis sınıfı (business logic)
│   ├── DigitalAsset/       # DAM alt servisleri
│   ├── Integrations/       # Dış API entegrasyonları
│   └── Marketing/          # Marketing-specific servisler
├── Support/                # Helper sınıflar
└── Providers/              # Service provider'lar
config/                     # Laravel config + custom config
database/
├── migrations/             # 80+ migration
├── seeders/                # Dev + prod seeder'ları
└── factories/              # Model factory'leri
resources/
├── views/                  # Blade template'ler (portal bazında)
├── js/                     # Vanilla JS (Vite bundled)
└── css/                    # Premium.css + minimalist.css
routes/
├── api.php                 # 328 line — REST API
├── web.php                 # 139 line — auth + misc
├── common.php              # Shared routes
├── manager.php             # Manager portal
├── senior.php              # Senior portal
├── marketing-admin.php     # Marketing portal
├── dealer.php              # Dealer portal
├── guest.php               # Guest portal
├── student.php             # Student portal
├── tasks.php               # Task board + IM
└── console.php             # Artisan scheduler
tests/                      # PHPUnit Feature + Unit
```

### MVC + Service Layer

```
HTTP Request
    ↓
Middleware (auth, company.context, role, permission)
    ↓
Controller (thin — validation + response)
    ↓
Service (business logic + DB ops)
    ↓
Model (Eloquent ORM + traits + scopes)
    ↓
Database (MySQL)
```

---

## 3. Portal Kılavuzu

MentorDE **7 farklı portal** sunar. Her portal kendi layout'u, sidebar yapısı ve route prefix'i ile çalışır.

### 3.1 Manager Portal

- **Layout:** `resources/views/manager/layouts/app.blade.php`
- **Route prefix:** `/manager`
- **Middleware:** `company.context` + `auth` + `verified` + `manager.role` + `require.2fa`
- **Kullanan roller:** `ROLE_MANAGER`
- **2FA:** Zorunlu
- **Branding:** "MentorDE Manager Panel"

**Sidebar grupları:**
1. **Genel** → Dashboard, Duyurular
2. **İletişim & Görevler** → Görevlerim, Ticket Merkezi, IM, Müsaitlik, Manager Talep
3. **Kullanıcı Yönetimi** → Adaylar, Öğrenciler, Eğitim Danışmanları, Bayiler
4. **İnsan Kaynakları** → HR Dashboard, Personel, Performans, İzin, Sertifika, Devam, İşe Alım, Maaş
5. **Finans** → Finance Dashboard, Raporlar, İşlemler, Komisyonlar, Ödemeler
6. **Belgeler & Sözleşmeler** → Dijital Varlıklar, Üniversite Gereklilikleri, Doküman Şablonları, Sözleşmeler, Sözleşme Analitiği
7. **Sistem** → Sistem Ayarları, Güvenlik, Rol Yönetimi, IP Kuralları, Denetim Günlüğü, GDPR Dashboard, Bildirim Stats, Webhooks, Tema, Branding, Config

### 3.2 Senior Portal (Eğitim Danışmanı)

- **Layout:** `resources/views/senior/layouts/app.blade.php`
- **Route prefix:** `/senior`
- **Middleware:** `company.context` + `auth` + `senior.role`
- **Kullanan roller:** `ROLE_SENIOR`, `ROLE_MENTOR`
- **Branding:** "Danışman Paneli"

**Sidebar grupları:**
1. **Genel** → Dashboard, Duyurular
2. **İletişim** → Gelen Kutusu, Danışan İletişim (/im), Ticket, Görevlerim, Şablon Yanıtlar, Manager'a Talep
3. **Öğrenci İşleri** → Öğrencilerim, Belge Onayları, Başvuru & Süreç, Aday Pipeline, Kanban, Randevular, Sözleşmeler, Toplu İnceleme
4. **Lojistik** → Gizli Notlar, Hesap Kasası
5. **İçerik** → Doküman Oluştur, AI Asistan, Materyaller & KB, Servisler, Dijital Varlıklar
6. **Kişisel** → Performansım, Profil, Ayarlar, Müsaitlik, İzin Taleplerim

**Senior DM istisnası (15 Nisan 2026):** Senior DM picker'ı iç ekip + kendi aday öğrencileri (guest) — telefon rehberi mantığı.

### 3.3 Marketing-Admin Portal

- **Layout:** `resources/views/marketing-admin/layouts/app.blade.php`
- **Route prefix:** `/mktg-admin`
- **Middleware:** `company.context` + `auth` + `marketing.access`
- **Kullanan roller:** `ROLE_MARKETING_ADMIN`, `ROLE_MARKETING_STAFF`, `ROLE_SALES_ADMIN`, `ROLE_SALES_STAFF`, `ROLE_MANAGER`

**Sidebar grupları:**
1. **Genel** → Dashboard, Duyurular
2. **İletişim Merkezi** → IM, Görevler, Manager Talep
3. **Satış Süreci** → Pipeline (tablo), Pipeline Kanban
4. **İçerik & Kampanya** → Kampanyalar, İçerik, Email, Sosyal Medya, Tracking Links, Etkinlikler, Workflows, A/B Test
5. **Analiz & Raporlar** → Attribution, KPI, Raporlar, Bütçe
6. **Yönetim** → Entegrasyonlar, Takım, Ayarlar
7. **Satış Panel** → Pipeline & Scoring, Attribution, Bayi Yönetimi, Lider Tablosu
8. **Hesap** → Bildirimler, Profil

### 3.4 Dealer Portal

- **Layout:** `resources/views/dealer/layouts/app.blade.php`
- **Route prefix:** `/dealer`
- **Middleware:** `company.context` + `auth` + `dealer.role`
- **Kullanan roller:** `ROLE_DEALER`

**Sidebar grupları:**
1. **Genel** → Dashboard
2. **Öğrenci İşleri** → Aday Ekle, Adaylarım, Süreç Takibi
3. **Finans** → Kazançlar, Hesapla, Ödemeler, Sözleşmeler
4. **Araçlar** → Danışman Desteği, Eğitim, Referral Bağlantıları, Performans, Takvim
5. **Hesap** → Bildirimler, Profil, Ayarlar
6. **Belgeler** → Dijital Varlıklar

### 3.5 Guest Portal (Aday)

- **Layout:** `resources/views/guest/layouts/app.blade.php`
- **Route prefix:** `/guest`
- **Middleware:** `company.context` + `auth` + `verified` + `guest.role` + `throttle:600,1`
- **Kullanan roller:** `ROLE_GUEST`

**Sidebar grupları:**
1. **Genel** → Dashboard, Başvuru Formu, Belgelerim, Hizmetler, Sözleşme, Zaman Çizelgesi, Maliyet Hesaplayıcı, AI Asistan
2. **İletişim** → Mesajlar, Destek Talepleri, Geri Bildirim
3. **Keşfet** → Üniversite/Doküman/Başarı/Yaşam/Vize Rehberi, Keşfet, Kaydedilenler
4. **Hesap** → Profil, Ayarlar

### 3.6 Student Portal

- **Layout:** `resources/views/student/layouts/app.blade.php`
- **Route prefix:** `/student`
- **Middleware:** `company.context` + `auth` + `verified` + `student.role` + `throttle:600,1`
- **Kullanan roller:** `ROLE_STUDENT`

**Sidebar grupları:**
1. **Genel** → Dashboard
2. **Kayıt Süreci** → Kayıt Bilgileri, Belgelerim, Sözleşme, Süreç Takibi, Üniversite Başvuruları, Randevular
3. **İletişim** → Mesajlar, Destek
4. **Araçlar** → Servisler, AI Asistan, Doküman Oluştur, Maliyet Hesaplayıcı
5. **Keşfet** → Vize, Konut, Materyaller, Keşfet, Kaydedilenler
6. **Hesap** → Ödemeler, Profil, Ayarlar

### 3.7 Staff Layout (Ortak)

- **Layout:** `resources/views/layouts/staff.blade.php`
- **Kullanım:** HR ve iç araç sayfalarında (system_admin, finance_*, operations_*, sales_*, marketing_staff)
- **İçerik:** Dashboard, İş Araçları (Duyurular, Görevler, Ticket, IM, Manager Talep), Kişisel (İzin, Devam, Sertifika, Onboarding)

**B3 Pattern:** `/tasks`, `/tickets-center`, `/messages`, `/bulletins`, `/hr/my/*` gibi ortak sayfalar kullanıcı rolüne göre **dinamik layout seçer** — staff layout yerine senior/manager layout kullanılır, böylece portal değiştirme hissi oluşmaz.

---

## 4. Rol ve İzin Matrisi

### Rol Listesi (`app/Models/User.php` — ROLE_* sabitleri)

| Rol Kodu | Rol Adı | Katman | Açıklama |
|---|---|---|---|
| `manager` | Yönetici | 1 | Full access, 2FA zorunlu |
| `system_admin` | Sistem Yöneticisi | 2 | Config + sistem yönetimi, 2FA zorunlu |
| `system_staff` | Sistem Personeli | 4 | Sistem desteği |
| `operations_admin` | Operasyon Yöneticisi | 2 | Operasyon yönetimi, 2FA zorunlu |
| `operations_staff` | Operasyon Personeli | 4 | Operasyon icracı |
| `finance_admin` | Finans Yöneticisi | 2 | Finansal raporlar, 2FA zorunlu |
| `finance_staff` | Finans Personeli | 4 | Finansal işlem giriş |
| `marketing_admin` | Pazarlama Yöneticisi | 2 | Marketing + DAM |
| `marketing_staff` | Pazarlama Personeli | 4 | İçerik + DAM |
| `sales_admin` | Satış Yöneticisi | 2 | Satış pipeline |
| `sales_staff` | Satış Personeli | 4 | Lead takibi |
| `senior` | Eğitim Danışmanı | 3 | Öğrenci havuzu, DAM yetkili |
| `mentor` | Mentor | 3 | Senior'a bağlı yardımcı |
| `dealer` | Bayi | 5 | Komisyonlu referral |
| `guest` | Aday | 6 | Başvuru süreci |
| `student` | Öğrenci | 6 | Aktif müşteri |

### ROLE_GROUPS (Hiyerarşi)

```
manager (katman 1)
  └── system_admin → system_staff
  └── operations_admin → operations_staff
  └── finance_admin → finance_staff
  └── marketing_admin → marketing_staff
  └── sales_admin → sales_staff
  └── senior → mentor
```

### Varsayılan İzinler (User.php — ROLE_DEFAULT_PERMISSION_CODES)

| Rol | İzinler |
|---|---|
| **manager** | config.view/manage, student.*, revenue.manage, approval.manage, notification.manage, role.template.manage, ticket.center.*, dam.* (full) |
| **senior** | student.assignment.manage, student.card.view, dam.view/download/upload/update/folder.manage |
| **mentor** | senior ile aynı (daha kısıtlı klasör erişimi) |
| **dealer** | dam.view, dam.download |
| **marketing_admin** | marketing.dashboard.view, marketing.campaign.manage, dam.* (full) |
| **marketing_staff** | marketing.dashboard.view, dam.view/download/upload/update/folder.manage |
| **sales_admin** | marketing.dashboard.view, dam.view/download |
| **sales_staff** | marketing.dashboard.view, dam.view/download |
| **system_admin** | config.view/manage, notification.manage, role.template.manage, ticket.center.view, dam.view/download |
| **operations_admin** | config.view, student.assignment.manage, approval.manage, notification.manage, ticket.center.view, dam.view/download |
| **finance_admin** | config.view, revenue.manage, notification.manage, dam.view/download |

### İzin Sistemi Altyapısı

Roller *statik değil* — `role_templates` tablosundan dinamik okunur:

```
User → user_role_assignments → role_templates → permissions (many-to-many)
```

- **permissions** tablosu: tüm sistem izinlerini tutar (`code` unique, `category` gruplar için)
- **role_templates**: versionlu şablonlar (`version_applied` ile eski kullanıcı eski izinleri kullanabilir)
- **user_role_assignments**: kullanıcı → şablon eşleşmesi (bir kullanıcıda birden fazla template olabilir)
- `User::hasPermissionCode($code)`: effective izinleri kontrol eder

---

## 5. Veritabanı Şeması

MentorDE ~100 tabloya sahip. Aşağıda modüller bazında organize edilmiştir.

### 5.1 Auth & Authorization

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `users` | id, email (UNIQUE), name, password, role, company_id, senior_code, student_id, dealer_code, is_active, deleted_at | Tüm rollerin ortak tablosu |
| `sessions` | id (string PK), user_id, ip_address, user_agent, payload, last_activity | Laravel session |
| `password_reset_tokens` | email (PK), token, created_at | Şifre sıfırlama |
| `permissions` | id, code (UNIQUE), category, description, is_system | Sistem izinleri (dam.upload, config.view vb.) |
| `role_templates` | id, code (UNIQUE), name, parent_role, version, is_system, is_active | Rol şablonları (versionlu) |
| `role_template_permissions` | id, role_template_id, permission_id | Rol ↔ izin many-to-many |
| `user_role_assignments` | id, user_id, role_template_id, assigned_by_user_id, version_applied, is_active, assigned_at, revoked_at | User → role template atamaları |
| `role_change_audits` | id, actor_user_id, action, target_type, target_id, payload (JSON) | Rol değişiklikleri audit |
| `user_two_factor` | id, user_id, secret (TEXT, encrypted), backup_codes (JSON), is_enabled, last_used_at | 2FA state |
| `ip_access_rules` | id, user_id, ip_address, is_allowed, expires_at | IP whitelist/blacklist |

### 5.2 Guest Applications & Student Conversion

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `guest_applications` | id, company_id, tracking_token (UNIQUE), guest_user_id, first_name, last_name, email, phone, application_type, assigned_senior_email, lead_source, dealer_code, campaign_code, lead_status, priority, risk_level, converted_to_student, converted_student_id, registration_form_draft (JSON), selected_package_code, contract_status, notify_inapp, is_archived, deleted_at | Danışan başvuruları (90+ kolon) |
| `guest_registration_snapshots` | id, guest_application_id, snapshot_version, submitted_by_email, payload_json, meta_json, submitted_at | Kayıt formu versiyonları |
| `guest_required_documents` | id, guest_application_id, document_code, status, doc_category_code | Aday için gereken belgeler |
| `guest_onboarding_steps` | id, guest_id, step_number, step_name, status, completed_at | Onboarding progress |
| `guest_achievements` | id, guest_id, achievement_code, awarded_at | Milestones |
| `guest_referrals` | id, guest_id, referred_guest_id, status | Referral sistemi |
| `guest_tickets` + `guest_ticket_replies` | — | Aday destek talepleri |
| `guest_feedback` | id, guest_id, rating, feedback_text | Feedback |
| `guest_payment_requests` | id, company_id, guest_id, amount, status | Ödeme talepleri |

### 5.3 Student Management

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `student_assignments` | id, student_id (UNIQUE), senior_email, branch, risk_level, payment_status, dealer_id, student_type, is_archived, company_id | Student → Senior eşleşmesi |
| `student_appointments` | id, student_id, senior_email, title, scheduled_at, duration_minutes, channel, meeting_url, status | Randevu yönetimi |
| `student_checklists` + `student_checklist_items` | — | Onboarding checklists |
| `student_onboarding_steps` | — | Progress tracking |
| `student_achievements` | — | Başarı takibi |
| `student_material_reads` | — | Materyal okuma |
| `student_shipments` | — | Paket takibi |
| `student_visa_applications` | — | Vize başvurusu |
| `student_accommodations` | — | Konaklama |
| `student_institution_documents` | — | Kurumsal belgeler |
| `student_university_applications` | — | Üniversite başvurusu |
| `student_language_courses` | — | Dil kursu |
| `student_payments` | — | Ödeme geçmişi |
| `student_process_task_completions` | — | Süreç adım tamamlama |
| `student_risk_scores` | — | Risk skoru |

### 5.4 Dealer Management

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `dealers` | id, company_id, code (UNIQUE), name, email, phone, whatsapp, dealer_type_code, is_active, is_archived, deleted_at | Bayi master |
| `dealer_types` | id, code (UNIQUE), name_tr, name_de, name_en, description_*, permissions (JSON), default_commission_config (JSON), is_active, sort_order | 5 tip: lead_generation, referrer, freelance_danisman, b2b_partner, operational |
| `dealer_type_histories` | id, dealer_id, old_type, new_type, changed_at | Tip değişiklik audit |
| `dealer_student_revenues` | id, dealer_id, student_id, dealer_type, milestone_progress (JSON), total_earned, total_pending | Gelir payı tracking |
| `dealer_revenue_milestones` | id, dealer_id, milestone_name, achieved_at, revenue_amount | Milestone log |
| `dealer_payout_accounts` | id, dealer_code, bank_name, iban, account_holder, is_default | Ödeme hesabı |
| `dealer_payout_requests` | id, dealer_code, payout_account_id, amount, currency, status, requested_by_email, approved_by, approved_at, paid_at, receipt_url | Ödeme talep lifecycle |
| `dealer_utm_links` | — | UTM tracking linkler |
| `dealer_material_reads` | — | Materyal okuma takibi |

### 5.5 Conversations (Internal Messaging)

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `conversations` | id, company_id, type (direct/group/room/announcement), title, created_by_user_id, context_type, context_id, is_archived, archived_at, archived_by_user_id, last_message_at, last_message_preview, deleted_at | IM core — Slack tarzı Conversation |
| `conversation_participants` | id, conversation_id, user_id, role (admin/member), joined_at, last_read_at, is_muted, is_pinned | Pivot + metadata |
| `messages` | id, conversation_id, sender_id, body, reply_to_message_id, attachment_path, attachment_name, attachment_size, attachment_mime, is_system, is_edited, edited_at, deleted_at | Mesaj |
| `message_reactions` | id, message_id, user_id, emoji | Emoji reaksiyonları |
| `message_templates` | id, code (UNIQUE), name, content, category | Şablon mesajlar |

### 5.6 Direct Messaging (Guest/Student Support)

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `dm_threads` | id, company_id, thread_type, guest_application_id, student_id, advisor_user_id, status, sla_hours, next_response_due_at, last_message_at, last_message_preview | Destek sohbeti thread'i |
| `dm_messages` | id, thread_id, sender_user_id, sender_role, message, is_quick_request, attachment_*, is_read_by_advisor, is_read_by_participant | Destek mesajları |

### 5.7 Digital Asset Management (DAM)

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `digital_asset_folders` | id, company_id, parent_id, name, slug, path, depth, description, color, icon, is_system, allowed_roles (JSON), created_by, deleted_at | Klasör hiyerarşisi |
| `digital_assets` | id, company_id, folder_id, uuid, name, original_filename, mime_type, extension, size_bytes, disk, path, thumbnail_path, category, tags (JSON), description, metadata (JSON), download_count, last_downloaded_at, is_pinned, external_url, source_type, doc_code, version_group_id, version_number, created_by, deleted_at | Dosya/link master |
| `digital_asset_favorites` | id, asset_id, user_id | Favori dosyalar |
| `digital_asset_folder_favorites` | id, folder_id, user_id | Favori klasörler |
| `digital_asset_activity_log` | id, asset_id, user_id, action (upload/view/download/share/delete/mention), metadata (JSON), ip | Aktivite log (audit) |
| `digital_asset_share_links` | id, asset_id, created_by, share_token, password_hash, expires_at, max_downloads, download_count | Dış paylaşım linkleri |
| `digital_asset_saved_searches` | id, user_id, name, query_params (JSON) | Kullanıcı saved searches |

### 5.8 Business Contracts

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `business_contract_templates` | id, company_id, contract_type (dealer/staff), template_code (UNIQUE), name, body_text, notes, is_active | Sözleşme şablonları |
| `business_contracts` | id, company_id, contract_type, dealer_id, user_id, template_id, contract_no (UNIQUE), title, body_text, meta (JSON), status (draft→issued→signed_uploaded→approved/cancelled), issued_at, signed_at, approved_at, signed_file_path, issued_by, approved_by, notes, deleted_at | Sözleşme lifecycle |
| `contract_audit_logs` | id, contract_id, action, actor_id, changes (JSON) | Sözleşme audit |

**State machine:** `draft → issued → signed_uploaded → approved` veya `cancelled`

### 5.9 Company Bulletins

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `company_bulletins` | id, company_id, author_id, title, body, category (genel/duyuru/acil/ik/kutlama/motivasyon), is_pinned, published_at, expires_at, target_roles (JSON), target_departments (JSON) | Şirket duyuruları |
| `bulletin_reads` | id, bulletin_id, user_id, read_at | Okunma takibi |
| `bulletin_reactions` | id, bulletin_id, user_id, emoji | Emoji reaksiyonları |

### 5.10 Marketing & Tasks

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `marketing_campaigns` | id, company_id, code, name, type, status, start_date, end_date, budget | Kampanyalar |
| `marketing_tasks` | id, company_id, title, description, status (todo/in_progress/in_review/done), priority, due_date, assigned_user_id, created_by_user_id, process_type, workflow_stage, parent_task_id, depends_on_task_id, checklist_total, checklist_done, template_id, estimated_hours, is_recurring, escalate_after_hours, mentioned_user_ids (JSON), deleted_at | Görev panosu (Kanban) |
| `task_comments`, `task_attachments`, `task_checklists`, `task_checklist_items`, `task_activity_logs`, `task_watchers`, `task_templates`, `task_template_items` | — | Task detay tabloları |
| `marketing_teams`, `marketing_reports`, `marketing_tracking_links`, `marketing_tracking_clicks`, `marketing_external_metrics`, `marketing_budget`, `lead_source_data`, `lead_source_options`, `marketing_admin_settings` | — | Marketing destek tabloları |

### 5.11 Notifications

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `notification_dispatches` | id, user_id, guest_id, student_id, company_id, template_id, channel (email/sms/push/in_app), category, subject, body, variables (JSON), status (pending/queued/sent/failed/skipped), source_type, source_id, triggered_by, queued_at, sent_at, failed_at | Bildirim gönderim log |
| `notification_preferences` | id, user_id, channel, category, is_enabled | Per-user tercihler |
| `scheduled_notifications` | — | Zamanlanmış bildirimler |

### 5.12 Documents & HR

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `documents` | id, document_id, student_id, category_id, process_tags (JSON), original_file_name, standard_file_name, storage_path, mime_type, status, uploaded_by, approved_by, approved_at, review_note, deleted_at | Öğrenci belge merkezi |
| `document_categories` | id, code (UNIQUE), name_tr, name_de, name_en, is_active, sort_order, top_category | Belge kategorileri |
| `document_builder_templates` | — | Dinamik belge şablonları |
| `hr_person_profiles`, `hr_recruitment_posts`, `hr_onboarding_checklists`, `hr_leave_requests`, `hr_certifications`, `hr_attendances` | — | HR modülü tabloları |
| `staff_kpi_targets` | — | KPI hedefleri |

### 5.13 System, Audit, Automation

| Tablo | Ana Kolonlar | Açıklama |
|---|---|---|
| `audit_trails` | id, user_id, action, entity_type, entity_id, old_values (JSON), new_values (JSON) | Genel audit |
| `system_event_logs` | — | Sistem events |
| `account_access_logs` | — | Login/logout geçmişi |
| `account_vaults` | id, user_id, vault_key, encrypted_data | Şifreli kullanıcı verileri |
| `field_rules`, `field_rule_approvals` | — | Alan doğrulama kuralları |
| `consent_records` | — | GDPR/KVKK consent log |
| `data_retention_policies` | — | Veri saklama |
| `automation_workflows`, `automation_workflow_nodes`, `automation_enrollments`, `automation_enrollment_logs` | — | Otomasyon altyapısı |
| `ab_tests`, `ab_test_variants`, `ab_test_assignments` | — | A/B testing |
| `lead_scoring_rules`, `lead_score_logs`, `lead_touchpoints` | — | Lead scoring |
| `internal_notes` | — | Öğrenci üzerine iç notlar |
| `escalation_rules`, `escalation_events` | — | Eskalasyon kuralları |
| `knowledge_base_articles` | — | Yardım merkezi |
| `currency_rates` | — | Döviz kurları |
| `germany_cities` | — | Alman şehir listesi |
| `senior_performance_snapshots`, `senior_response_templates`, `senior_performance_targets` | — | Senior performans |

---

## 6. Eloquent Modelleri

Kritik modellerin özellikleri, relations ve scopes tabloda:

### Core Models

| Model | Trait/Extends | Relations | Scopes | SoftDelete |
|---|---|---|---|---|
| `User` | Authenticatable, MustVerifyEmail, BelongsToCompany | HasMany: conversations, messages; BelongsTo: company | `active`, `visibleToUser` | ✓ |
| `Company` | Model | HasMany: users, guests, tasks | — | — |
| `GuestApplication` | BelongsToCompany, SoftDeletes | BelongsTo: senior, guestUser; HasOne: studentAssignment; HasMany: snapshots, tickets | `active`, `byStatus`, `visibleToUser` | ✓ |
| `StudentAssignment` | BelongsToCompany | BelongsTo: senior, guestApplication | `archived` | — |
| `Dealer` | BelongsToCompany, SoftDeletes | HasMany: payouts, revenues; BelongsTo: dealerType | `active`, `byType` | ✓ |
| `Conversation` | BelongsToCompany, SoftDeletes | HasMany: messages, participants; BelongsToMany: participantUsers | `notArchived`, `forUser` | ✓ |
| `Message` | SoftDeletes | BelongsTo: conversation, sender, replyTo; HasMany: reactions | `system`, `mentions` | ✓ |
| `DigitalAsset` | BelongsToCompany, SoftDeletes | BelongsTo: folder, creator; BelongsToMany: favoritedBy | `inFolder`, `byCategory`, `pinned` | ✓ |
| `DigitalAssetFolder` | BelongsToCompany, SoftDeletes | HasMany: children, assets; BelongsTo: parent | `roots`, `accessibleByRole` | ✓ |
| `BusinessContract` | BelongsToCompany, SoftDeletes | BelongsTo: dealer, staffUser, template, issuedByUser, approvedByUser | `byStatus`, `byType` | ✓ |
| `CompanyBulletin` | BelongsToCompany | HasMany: reads, reactions; BelongsTo: author | `active`, `visibleToUser` | — |
| `MarketingTask` | BelongsToCompany, SoftDeletes | HasMany: subtasks, comments, attachments, checklists, watchers; BelongsTo: assignedUser | `pendingOrInProgress`, `byStatus`, `overdue` | ✓ |
| `NotificationDispatch` | Model | BelongsTo: template | `pending`, `sent`, `failed` | — |
| `Document` | SoftDeletes | BelongsTo: category | `approved`, `pending` | ✓ |

### Önemli Cast'lar (JSON/Array)

- `guest_applications`: `language_skills`, `registration_form_draft`, `selected_extra_services` → array
- `digital_assets`: `tags`, `metadata`, `is_pinned` → array/boolean
- `digital_asset_folders`: `allowed_roles` → array
- `company_bulletins`: `target_roles`, `target_departments` → array
- `marketing_tasks`: `mentioned_user_ids`, `recurrence_pattern` → array
- `notification_dispatches`: `variables` → json
- `dealer_student_revenues`: `milestone_progress` → array

---

## 7. Servis Katmanı

`app/Services/` altında **~60 servis sınıfı** bulunur. Business logic controller'lar yerine servislerde toplanır.

### İletişim & Bildirim Servisleri

| Servis | Sorumluluk | Önemli Metodlar |
|---|---|---|
| **NotificationService** | Tüm bildirim dağıtımı (email/sms/push/in_app) | `send($params)`, `sendToMany($userIds, $params)` |
| **NotificationPreferenceService** | Per-user opt-out/opt-in kontrolü | `isEnabled($userId, $channel, $category)` |
| **NotificationScopeService** | Bildirim hedef kitlesi hesaplama | `scopeFor($event)` |
| **TemplateRenderer** | Mesaj şablonları render | `render($template, $vars, $lang)` |
| **ConversationService** | IM core (DM, grup, oda, duyuru) | `findOrCreateDm`, `createGroup`, `sendMessage`, `markRead`, `unreadCountForUser`, `canPerform`, `archiveConversation`, `destroyConversation`, `promoteToAdmin` |
| **PresenceService** | Online/offline durum | `updatePresence`, `isOnline` |
| **AutoReplyService** | Otomatik yanıt | `autoReplyIfApplicable` |

### DAM Servisleri

| Servis | Sorumluluk |
|---|---|
| **DigitalAssetService** | Dosya yükleme, indirme, silme, favori toggle |
| **DigitalAssetFolderService** | Klasör ağacı, taşıma, silme, role-based erişim |
| **DocumentTagService** | Tag normalizasyonu |
| **DocumentNamingService** | Standart dosya adlandırma (doc_code üretimi) |
| **ImageOptimizationService** | Görsel optimizasyon/resize |

### Student/Guest Servisleri

| Servis | Sorumluluk |
|---|---|
| **GuestRegistrationFieldSchemaService** | Dinamik form şema (B11/B14/B15 validation) |
| **GuestResolverService** | Guest ID resolve (email/tracking_token/user_id) |
| **GuestListService** | Guest listeleme + filtre |
| **GuestTimelineService** | Guest etkinlik timeline |
| **GuestViewDataService** | Guest portal view data hazırlama |
| **StudentListService** | Öğrenci listeleme |
| **StudentSearchService** | Öğrenci arama |
| **StudentCardService** | Öğrenci kart verisi |
| **StudentAchievementService** | Milestone takibi |
| **StudentGuestResolver** | Student ↔ Guest eşleşme |
| **LeadScoreService** | Lead skorlama |
| **LeadSourceTrackingService** | Kaynak takibi (UTM) |
| **AttributionService** | Kaynak atama (ilk/son touch) |

### Sözleşme & Finans

| Servis | Sorumluluk | Metodlar |
|---|---|---|
| **BusinessContractService** | İş sözleşmeleri | `create($type, $templateId, $dealerId, $userId, ...)`, `issue`, `uploadSigned`, `approve`, `cancel`, `dealerPlaceholders` |
| **ContractTemplateService** | Şablon yönetimi | `render($template, $vars)` |
| **DealerRevenueService** | Bayi gelir hesaplama | `getActiveMilestones`, `initializeDealerStudentRevenue`, `triggerMilestonesForStudent`, `syncMilestonePaidForStudent` |
| **RevenueMilestoneService** | Milestone trigger | `evaluate($student, $event)` |
| **CurrencyRateService** | Döviz kur çekme/cache | `rate($from, $to)` |

### Task & Automation

| Servis | Sorumluluk |
|---|---|
| **TaskAutomationService** | Task otomasyonu (auto-assign, escalation) |
| **TaskEscalationService** | Süresi geçmiş task'ları manager'a eskalasyon |
| **TaskFeedbackService** | Task feedback yönetimi |
| **TaskRecurringService** | Recurring task oluşturma |
| **TaskTemplateService** | Task şablonları |
| **WorkflowEngineService** | Otomasyon workflow engine |
| **EscalationService** | Genel eskalasyon kuralları (dm_threads, tasks, tickets) |
| **SeniorAutomationService** | Senior events (student_added, status_changed → otomatik aksiyonlar) |

### AI & Asistan

| Servis | Sorumluluk |
|---|---|
| **AiGuestAssistantService** | Guest portal AI asistan (multi-provider: Claude, Gemini, ChatGPT, OpenRouter) |
| **AiWritingService** | İçerik yazım asistanı |
| **SeniorAiAssistantService** | Senior için AI asistan |

### Marketing

| Servis | Sorumluluk |
|---|---|
| **ABTestingService** | A/B test variant assignment |
| **DashboardKPIService** | KPI hesaplama |
| **DashboardPayloadService** | Dashboard veri hazırlama |
| **StaffKpiService** | Personel KPI |
| **SeniorPerformanceService** | Senior performans ölçümü |

### Güvenlik & Audit

| Servis | Sorumluluk |
|---|---|
| **AccountVaultService** | Şifreli credential saklama (account_vaults) |
| **AnonymizationService** | GDPR kişisel veri anonimize |
| **PersonalDataExportService** | GDPR data export |
| **SecurityAnomalyService** | Anormal login/erişim tespit |
| **EventLogService** | Sistem event log |
| **DataScopeService** | Veri scope kontrol |
| **FieldRuleEngine** | Alan doğrulama kuralları |
| **EntityCatalogService** | Entity catalog yönetimi |

### Diğer

| Servis | Sorumluluk |
|---|---|
| **WhatsAppService** | WhatsApp Cloud API entegrasyonu |
| **InternalNoteService** | Öğrenci iç notları |
| **PipelineProgressService** | Satış pipeline ilerleme |
| **ProcessOutcomeService** | Süreç sonuç yönetimi |
| **DocumentBuilderService** | Dinamik doküman üretimi |
| **CvTemplateService** | CV şablon render |
| **StudentAchievementService** | Milestone tracking |
| **RiskScoreService** | Öğrenci risk skoru |
| **LeadScoreService** | Lead puanı |
| **Marketing/*** | Marketing alt servisleri (CampaignService, AttributionService, ReportService vb.) |

---

## 8. Controllers ve Routing

### Route Dosyaları

| Dosya | Satır | Amaç |
|---|---|---|
| `routes/api.php` | 328 | REST API (v1) |
| `routes/web.php` | 139 | Auth, public, misc |
| `routes/common.php` | 71 | Çok rollü ortak sayfalar |
| `routes/manager.php` | 279 | Manager portal |
| `routes/senior.php` | 141 | Senior portal |
| `routes/marketing-admin.php` | 334 | Marketing portal |
| `routes/dealer.php` | 80 | Dealer portal |
| `routes/guest.php` | 127 | Guest portal |
| `routes/student.php` | 184 | Student portal |
| `routes/tasks.php` | 130 | Task board + IM |
| `routes/console.php` | 234 | Artisan scheduler + commands |

### Önemli Rotalar

**Auth flow (`web.php`):**
- `GET /login` → `AuthController@showLogin`
- `POST /login` → `AuthController@login`
- `POST /logout` → `AuthController@logout`
- `GET /register` (eğer açıksa) → `AuthController@showRegister`
- `GET /password/reset` → Password reset flow
- `GET /2fa/setup` → `TwoFactorSetupController`
- `POST /2fa/challenge` → `TwoFactorChallengeController`
- `GET /apply` → Public guest application form

**IM (`tasks.php`):**
- `GET /im` → UnifiedMessagingHubController
- `POST /im/dm/{targetUserId}` → DM start
- `POST /im/group` → Grup/oda oluştur
- `POST /im/conversations/{convId}/send` → Mesaj gönder
- `POST /im/conversations/{convId}/archive` → Arşivle
- `DELETE /im/conversations/{convId}` → Sil
- `POST /im/conversations/bulk-destroy` → Toplu sil
- `POST /im/conversations/{convId}/members` → Üye ekle

**DAM (Route::macro('dam') — 4 portal):**
- `GET /{portal}/digital-assets` → Index
- `POST /{portal}/digital-assets` → Upload
- `POST /{portal}/digital-assets/{asset}/notify` → Post-upload mention
- `POST /{portal}/digital-assets/bulk-download` → ZIP
- `POST /{portal}/digital-assets/{asset}/share` → Share link
- `GET /{portal}/digital-assets/reports` → Raporlar

---

## 9. Middleware ve Güvenlik

### Custom Middleware (`app/Http/Middleware/`)

| Middleware | Amaç |
|---|---|
| `SecurityHeaders` | CSP, HSTS, X-Frame-Options, Permissions-Policy |
| `SetCompanyContext` | Current company resolve (session/default) |
| `SetLocale` | Dil seçimi (tr/de/en) |
| `Require2FA` | 2FA zorunlu roller için challenge yönlendirme |
| `EnsureManagerRole` | `role == manager` kontrolü |
| `EnsureSeniorRole` | `role in [senior, mentor]` |
| `EnsureDealerRole` | `role == dealer` |
| `EnsureStudentRole` | `role == student` |
| `EnsureGuestRole` | `role == guest` |
| `EnsureMarketingAccess` | marketing_* + sales_* + manager |
| `EnsureMarketingAdminOnly` | marketing_admin only |
| `EnsureMarketingTeam` | Marketing ekip erişimi |
| `EnsureTaskAccess` | Task modülü erişimi |
| `EnsurePermission` | `permission:dam.upload` gibi dynamic izin kontrolü |
| `EnsureManagerOrPermission` | Manager veya spesifik izin |
| `EnsureManagerKey` | API key bazlı manager auth |
| `EnsureGuestOwnsDocument` | Guest kendi belgesine erişim |
| `EnsureStudentOwnsDocument` | Student kendi belgesine erişim |
| `EnsureGuestOwnsTicket` | Ticket ownership |
| `CheckDealerTypePermission` | Dealer type bazlı izin |
| `CheckProcessOutcomeVisibility` | Process outcome görünürlük |
| `FieldRuleValidator` | Alan kuralı validation |
| `UpdateUserPresence` | Presence status güncelleme |

### SecurityHeaders — CSP Detayı

```
default-src 'self'
script-src 'self' 'unsafe-inline' 'nonce-{random}' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com
font-src 'self' https://fonts.gstatic.com data:
img-src 'self' data: https: blob:
connect-src 'self' https:
frame-src 'self' https://www.youtube.com
object-src 'none'
base-uri 'self'
form-action 'self'
```

**Önemli:** CSP Level 3 kuralı — `nonce-*` ile `unsafe-inline` birlikte olunca `unsafe-inline` görmezden gelinir. Yani inline `onclick="..."` gibi handler'lar **bloklanır**. Doğru pattern: `addEventListener` + nonce'lu script bloğu.

### 2FA

- **Kütüphane:** `pragmarx/google2fa:^9.0` (TOTP)
- **QR üretim:** `bacon/bacon-qr-code:^3.0`
- **Secret column:** `user_two_factor.secret` TEXT (encrypted)
- **Recovery codes:** 8 adet 10-char, one-time use
- **Zorunlu roller:** manager, system_admin, operations_admin, finance_admin
- **Setup:** `/2fa/setup` → QR göster → confirm 6-digit code
- **Challenge:** Login sonrası `/2fa/challenge`

### Rate Limiting

| Endpoint | Limit | Amaç |
|---|---|---|
| `/login`, `/2fa/*` | 5/min | Brute force |
| `/apply` public form | 30/min | DOS |
| Guest/Student portal | 600/min | Normal kullanım |
| Manager/Senior portal | 60/min | Normal |
| DAM upload | 15/min | Dosya yükleme |
| DAM bulk download | 10/min | ZIP üretimi |
| DAM preview | 240/min | Thumbnail |
| DAM download | 120/min | Dosya indirme |

### Permission System

```php
// Controller veya middleware:
Route::post('/path', ...)->middleware('permission:dam.upload');

// Blade:
@can('dam.upload')
  <button>Yükle</button>
@endcan

// Service:
if ($user->hasPermissionCode('dam.folder.manage')) { ... }
```

---

## 10. Core Modüller

### 10.1 Internal Messaging (IM)

**Mimari:**
- `conversations` tablosunda 4 tip: `direct`, `group`, `room`, `announcement`
- `conversation_participants` her üye için role + pin/mute durumu
- `messages` polymorphic, reply support, attachment, reaction
- Slack-tarzı permission model: `none → member → admin → manager`

**Özellikler:**
- DM (1-1), Grup (ekip), Oda (konu), Duyuru Kanalı (announcement)
- Mesaj pin, favorite, archive
- Bulk grup silme
- Senior DM telefon rehberi (staff + kendi guest'leri)
- Max 4 pinned conversation
- Kendi mesajın unread sayılmaz
- Arşivli/silinen konuşmalar total unread'e dahil değil

### 10.2 Digital Asset Management (DAM)

**Özellikler:**
- Klasör hiyerarşisi (parent_id, path, depth)
- Role-based klasör erişimi (`allowed_roles` JSON)
- Dosya kategorileri: image, video, audio, document, archive, other
- External link desteği (YouTube auto-thumbnail)
- Favori (dosya + klasör)
- Tag sistemi + arama
- Advanced search (uploader, size, category, date range)
- Bulk ZIP download
- Share link (password, expiry, max downloads)
- Saved searches
- Activity log (audit)
- Reports dashboard
- **Mention/Notify sistemi (Nisan 2026):**
  - Upload anında veya post-upload 📢 Bildir
  - 3 bayi kategorisi + öğrenci/guest/senior/mentor/marketing rol grupları
  - NotificationService entegrasyonu (in_app + 24h dedup)

### 10.3 Business Contracts

**Template'ler:**
- `dealer_referral_v1` → Referans Ortaklığı (Lead Gen + Freelance)
- `dealer_operations_v1` → Operasyon Sözleşmesi
- Staff şablonları (iş sözleşmesi)

**3 Bayi Kategorisi:**
- 📣 **Lead Generation** → `lead_generation`, `referrer`
- 🎯 **Freelance Danışmanlık** → `freelance_danisman`
- 🏢 **Operasyon** → `operational`, `b2b_partner`

**State Machine:**
```
draft → issued → signed_uploaded → approved
                                 └→ cancelled
```

### 10.4 Bulletins

**Özellikler:**
- 6 kategori: genel, duyuru, acil, ik, kutlama, motivasyon
- Pin (sabitle)
- Target: roles JSON + departments JSON (OR mantığı ile görünürlük)
- Expires_at (auto deactivate)
- Reactions (emoji)
- Read tracking
- Analytics (read rate, reaction breakdown)

### 10.5 Task Board (Marketing Tasks)

**Özellikler:**
- Kanban workflow (todo → in_progress → in_review → done)
- Priority (low/normal/high/urgent)
- Subtask, dependency, parent/child
- Checklist
- Attachments, comments, activity log
- Watchers (notification)
- Recurring
- Escalation (süre geçince manager'a)
- Template'ler
- Workflow stages (process_type)
- @mentions

### 10.6 Guest Registration Flow

**Aşamalar:**
1. Public `/apply` form → `guest_applications` kaydı + `guest_user_id` (auto User creation)
2. Senior auto-assign (email bazlı, `assigned_senior_email`)
3. Setup link email (password reset token) ile portal aktivasyonu
4. Kayıt formu (multi-step, auto-save)
5. Belge yükleme (document_categories bazlı checklist)
6. Sözleşme seçimi + paket seçimi
7. Ödeme onayı → `converted_to_student=true` → student conversion

---

## 11. Dış Entegrasyonlar

| Servis | Config | ENV | Kullanım |
|---|---|---|---|
| **SMTP** | `config/mail.php` | `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | Transactional email |
| **Postmark** | `config/services.php` | `POSTMARK_API_KEY` | Alternative email provider |
| **Resend** | `config/services.php` | `RESEND_API_KEY` | Alternative email |
| **AWS SES** | `config/services.php` | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION` | Alt email |
| **AWS S3** | `config/filesystems.php` | `AWS_BUCKET`, `AWS_USE_PATH_STYLE_ENDPOINT` | Dosya depolama (opsiyonel) |
| **Giphy** | `config/services.php` | `GIPHY_API_KEY` | IM GIF picker |
| **WhatsApp Cloud** | `config/services.php` | `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_VERIFY_TOKEN`, `WHATSAPP_API_VERSION` | WhatsApp bildirimi |
| **Stripe** | `config/services.php` | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Ödeme (hazır, tam aktif değil) |
| **AI Writer** | `config/services.php` | `AI_WRITER_API_KEY`, `AI_WRITER_BASE_URL`, `AI_WRITER_MODEL` | OpenAI-compatible API (Claude/Gemini/ChatGPT/OpenRouter) |
| **Slack** | `config/services.php` | `SLACK_BOT_USER_OAUTH_TOKEN` | Sistem bildirim kanalı |
| **Meta/Facebook Ads** | Marketing config | `MKTG_META_ACCESS_TOKEN`, `MKTG_META_AD_ACCOUNT_ID`, `MKTG_META_API_VERSION` | Campaign sync |
| **Google Ads** | Marketing config | `MKTG_GOOGLE_ADS_*` | Campaign sync |
| **LinkedIn Ads** | Marketing config | `MKTG_LINKEDIN_*` | Campaign sync |
| **TikTok Ads** | Marketing config | `MKTG_TIKTOK_*` | Campaign sync |
| **Instagram** | Marketing config | `MKTG_INSTAGRAM_*` | Social sync |

### AI Multi-Provider

Guest portal'da `AiGuestAssistantService` **4 provider** destekler:
- Claude (Anthropic)
- Gemini (Google)
- ChatGPT (OpenAI)
- OpenRouter (multi-model)

Config: Marketing-admin panelinden provider + API key + model seçilir.

---

## 12. Queue, Jobs ve Scheduled Commands

### Queue Config

- **Connection:** `database` (default), Redis destekli
- **Worker:** `php artisan queue:work --tries=1`
- **Failed jobs:** `failed_jobs` tablosu

### Jobs (`app/Jobs/`)

| Job | Amaç |
|---|---|
| `SendNotificationJob` | Tek bildirim gönderimi |
| `SendBulkNotificationJob` | Toplu bildirim |
| `SendEmailCampaignJob` | Email kampanyası |
| `ExportCsvJob` | CSV export |
| `GenerateReportJob` | Rapor üretimi |
| `CleanupExpiredDataJob` | Süresi geçmiş veri temizliği |

### Console Commands (`app/Console/Commands/` — 40+ komut)

**Bildirim & İletişim:**
- `DispatchNotificationsCommand` — Pending notification'ları dispatch et
- `DispatchScheduledEmailCampaignsCommand` — Zamanlanmış email kampanyaları
- `ProcessScheduledNotificationsCommand` — Zamanlı bildirim
- `ProcessEmailDripCommand` — Email drip kampanyası
- `ProcessEmailQueueCommand` — Email queue process
- `BirthdayBulletinCommand` — Doğum günü duyuru otomasyonu
- `SendScheduledReportsCommand` — Zamanlanmış raporlar
- `SendTaskDueRemindersCommand` — Task due reminder
- `SeniorRemindersCommand` — Senior için hatırlatıcı
- `UniversityDeadlineReminderCommand` — Üniversite son başvuru
- `MilestoneApproachingReminderCommand` — Milestone yaklaşıyor
- `GuestInactivityReminderCommand` — Guest inactivity
- `ContractReminderCommand` — Sözleşme hatırlatma

**Task & Workflow:**
- `CheckTaskEscalationsCommand` — Task escalation check
- `CloneRecurringTasksCommand` — Recurring task oluşturma
- `ProcessEscalationsCommand` — Escalation trigger
- `ProcessTaskAutomationCommand` — Task automation
- `ProcessWaitingWorkflowsCommand` — Pending workflow'lar
- `CheckWorkflowGoalsCommand` — Workflow goal check
- `PublishScheduledPostsCommand` — Sosyal medya post scheduling

**Analytics & Scoring:**
- `LeadScoreRecalculateCommand` — Lead skorları tekrar hesapla
- `ApplyScoreDecayCommand` — Skor decay
- `CalculateRiskScoresCommand` — Öğrenci risk skoru
- `RecalculateAttributionCommand` — Attribution tekrar
- `LeadReengagementCheckCommand` — Re-engagement check
- `CheckABTestWinnersCommand` — A/B test kazanan
- `SeniorSnapshotCommand` — Senior performance snapshot

**Sistem & Ops:**
- `CriticalCheckCommand` — Kritik sistem kontrolü
- `SelfHealCommand` — Self-heal routines
- `ArchiveInactiveRecordsCommand` — Inaktif kayıt arşivle
- `EnforceDataRetentionCommand` — Veri saklama politikası
- `RunCleanupCommand` — Genel temizlik
- `SecurityAnomalyCheckCommand` — Güvenlik anomali
- `SyncContractPaymentsCommand` — Sözleşme ödeme sync
- `SyncCurrencyRatesCommand` — Döviz kuru sync
- `SyncExternalMetricsCommand` — Dış metrik sync
- `SyncLeadSourceCommand` — Lead source sync
- `SocialMetricsSyncCommand` — Sosyal medya metric
- `CheckMarketingIntegrationsHealthCommand` — Marketing entegrasyon sağlık
- `IntegrationHealthCheckCommand` — Genel entegrasyon sağlık
- `ProbeThirdPartyCommand` — Dış servis probe
- `ExportAuditReportCommand` — Audit raporu
- `GenerateScheduledReportsCommand` — Zamanlanmış rapor
- `ManagerReportSnapshotCommand` — Manager rapor snapshot
- `ApiRegressionSmokeCommand` — API smoke test
- `MvpSmokeCommand` — MVP smoke
- `SeedMulticompanyDemoCommand` — Çok-şirketli demo data

### Scheduler (`routes/console.php` veya `Kernel.php`)

Yaklaşık 20+ scheduled job — her saat, her gün, haftalık:
- Saatlik: notification dispatch, task escalation
- Günlük: lead score recalc, reports, inactivity check, cleanup
- Haftalık: audit reports, archive, risk score

---

## 13. Multi-Tenant Architecture

### BelongsToCompany Trait

**Dosya:** `app/Models/Concerns/BelongsToCompany.php`

**Davranış:**
- Global scope otomatik `WHERE company_id = ?` ekler
- `creating` event'inde yeni model'e `company_id` otomatik atar
- Console (artisan) komutlarında scope bypass eder
- `forCompany($id)` scope ile specific company query

### Current Company Resolution

`SetCompanyContext` middleware'i her request'te:
1. ERP area'sında (manager, student, guest, senior, dealer) → default company
2. Marketing area'sında → session `current_company_id` veya user company_id
3. `app()->instance('current_company_id', $id)` ile DI'ya bind
4. `View::share('currentCompany', $company)` ile view'lara aktarır

### Data Isolation

- Tüm multi-tenant tabloların `company_id` kolonu var
- Eloquent query'leri otomatik filter'lanır
- Cross-company erişim sadece `withoutGlobalScope('company')` ile (admin/system_admin)

---

## 14. Deploy Pipeline

### GitHub Actions Workflow

**Dosya:** `.github/workflows/deploy.yml`

**Tetikleyici:** `main` branch'e push

**Adımlar:**
1. `actions/checkout@v4`
2. `shivammathur/setup-php@v2` → PHP 8.4 + extensions
3. Composer cache (key: `${{ runner.os }}-php8.4-composer-${{ hashFiles('**/composer.lock') }}`)
4. `composer install --no-dev --prefer-dist --optimize-autoloader`
5. `actions/setup-node@v4` → Node 20 + npm cache
6. `npm ci`
7. `npm run build` → Vite → `public/build/` (hashed assets)
8. `sudo apt-get install -y lftp`
9. `python3 scripts/lftp-deploy.py` → lftp mirror

### KASSERVER Production

- **Host:** `w0217487.kasserver.com` (FTP port 21, plain FTP)
- **User:** `f018350d` chroot to `/panel.mentorde.com/`
- **MySQL host:** `w0216a46.kasserver.com` DB `d046c403` (farklı host!)
- **PHP:** 8.4
- **Web:** Apache
- **.env:** FileZilla ile manuel yüklendi (chroot kökünde `/.env`)

### Secret Variables (GitHub Settings)

- `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`
- `FTP_SERVER_DIR=/`

### Post-Deploy (KASSERVER'da manuel)

```bash
cd /panel.mentorde.com
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan queue:restart
php artisan cache:clear
```

### Production .env Kritik Ayarlar

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxxxx
APP_URL=https://panel.mentorde.com
DB_CONNECTION=mysql
DB_HOST=w0216a46.kasserver.com
DB_DATABASE=d046c403
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.mentorde.com
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
```

---

## 15. Development Workflow

### composer.json Scripts

```
composer setup   → install + key + migrate + npm build
composer dev     → concurrent: serve + queue:listen + pail + vite dev
composer test    → config:clear + artisan test
composer test:critical → sadece critical suite
```

### package.json Scripts

```
npm run build        → Vite production
npm run dev          → Vite dev server (HMR)
npm run build:prod   → build + minify
npm run e2e:*        → Playwright smoke testleri (guest/student/dealer/senior/manager/marketing)
```

### CLAUDE.md Workflow Kuralları

- **Plan first** (3+ adım için plan mode)
- **Subagent strategy** (context koruması için)
- **Self-improvement loop** (`memory/lessons.md`)
- **Verification before done** (test, log, diff)
- **Demand elegance** (hack yerine root cause)
- **Autonomous bug fixing** (el tutmayı bekleme)
- **Simplicity first, no laziness, minimal impact**

### Test Altyapısı

- **PHPUnit** Feature + Unit
- **Playwright** E2E smoke tests (per-portal)
- **Test DB:** SQLite in-memory
- Kritik testler:
  - `ApiErrorContractTest`
  - `SecurityMiddlewareTest`
  - `StudentAccessControlTest`
  - `GuestCriticalFlowTest`
  - `PasswordResetFlowTest`
  - `OpsCommandsTest`

---

## 16. Sık Yapılan Hatalar

(`DEV_HANDBOOK.md` §12'den)

| Hata | Kök Neden | Çözüm |
|---|---|---|
| **419 Page Expired** | Session cookie SameSite + cross-site POST | `APP_URL=https://` + `SESSION_SECURE_COOKIE=true` |
| **CSP Inline Script Blok** | Nonce + unsafe-inline conflict | `addEventListener` + nonce'lu script bloğu |
| **BOM Namespace Error** | PowerShell UTF-8 BOM | Node.js BOM strip (`charCodeAt(0)===0xFEFF`) |
| **Queue Job Çalışmıyor** | Worker down | `php artisan queue:work`, `queue:failed` kontrol |
| **Edit Tool Cache Stale** | External tool değiştirdi dosyayı | Önce `Read`, sonra `Edit` |
| **Test $fillable Kısıtı** | Mass assign exclude | `forceFill([...])->save()` |
| **Raw Query Eloquent Scope Bypass** | `DB::table()` global scope'u çalıştırmaz | Manuel `whereNull('deleted_at')` ekle |
| **Composer Cache Poisoning** | Eski PHP 8.3 cache 8.4'e yükleniyor | Cache key'e PHP versiyon prefix ekle |
| **Alpine.js CI Hatası** | Local'de var, package.json'da yok | `package.json`'a explicit add |
| **Nested max-width cascade** | Parent + child %95 → text crunch | `overflow-wrap: anywhere` + tek max-width |
| **Bulletin AND filter** | `target_departments` dolu → herkesten gizli | OR logic (role OR dept) |

---

## Son Değişiklikler (15 Nisan 2026)

Aşağıdaki özellikler bu hafta içinde eklendi:

### DAM
- Card aksiyon butonları unified "ghost icon" style
- Upload mention + post-upload 📢 Bildir
- Grid card bulk select (checkbox overlay)
- Klasör ayarları sidebar'dan düzenlenebilir
- Senior `dam.folder.manage` izni
- Klasör silme → parent/root redirect
- Turkish upload error messages

### IM
- Senior DM directory (staff + kendi guest'leri)
- Bulk group delete (selection mode)
- Group delete grup admin'e genişletildi
- Archive/destroy redirect → tab=internal
- HTML5 validation messages Turkish
- Own messages unread dışı
- Deleted/archived unread dışı
- Message bubble wrap bug fix
- Max 4 pinned cap

### Business Contracts
- SQL crash fix (missing columns)
- 3 dealer category optgroup
- Auto-suggest template

### Bulletins
- visibleToUser OR logic fix
- Form checkbox grid + Tümünü Seç

### Fix'ler
- B5 Senior sidebar CSP inline onclick
- GIF picker manager + marketing layout'lar
- Bulletins dynamic layout swap

### Dokümanlar
- `docs/DEALER_ONBOARDING_PROCESS.md` + PDF
- `docs/USER_CREATION_PROCESS.md` + PDF
- `docs/SYSTEM_OVERVIEW_FULL.md` + PDF (bu doküman)

---

*MentorDE System Overview — Full Detail*
*Laravel 12 · PHP 8.4 · MySQL 8 · 15 Nisan 2026*
