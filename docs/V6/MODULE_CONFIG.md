# Modül: Config Panel

---

## Amaç

Manager rolüne özel sistem konfigürasyon paneli. Kullanıcı yönetimi, bayi ayarları, süreç/entegrasyon konfigürasyonu, belge yönetimi, içerik şablonları, analitik ve guest/portal kullanıcı ayarlarını tek ekrandan yönetir.

---

## Erişim

**URL:** `/config`
**Middleware:** `EnsureManagerRole` (veya `EnsureManagerOrPermission`)
**Layout:** `manager.layouts.app`

---

## Sekme Yapısı (7 Sekme)

Plan: 7 sekmeli navigasyon ile tek seferde tüm bölümleri göstermek yerine sekme bazlı gösterim.

| Sekme | Partial Dosyası | Açıklama |
|-------|----------------|----------|
| 🏢 Firma & Kullanıcılar | `_company-users` | Firma bilgisi + kullanıcı CRUD |
| 🤝 Bayiler | `_dealers` | Bayi konfigürasyonu |
| ⚙ Süreç & Entegrasyon | `_processes-integrations` | Process + entegrasyon ayarları |
| 📄 Belgeler | `_documents` | Belge kategorileri + gereklilik kuralları |
| 📝 İçerik & Şablonlar | `_content` | Mesaj şablonları + bildirim şablonları |
| 📊 Analitik | `_analytics` | Analytics konfigürasyonu |
| 👥 Başvurular & Portallar | `_guests` + `_portal-users` | Guest kayıt formu + portal kullanıcıları |

**URL Hash:** `#firma`, `#bayiler`, `#surec-entegrasyon`, `#belgeler`, `#icerik`, `#analitik`, `#basvurular`
Sayfa yenilenince son açık sekme korunur.

---

## CSS Scope

Tüm config panel stilleri `.cfg-page` namespace'i altında tanımlı.

```css
.cfg-page .card    — kart container
.cfg-page .list    — max-height:320px, overflow:auto liste
.cfg-page .item    — liste satırı
.cfg-page .row     — input + button grup
.cfg-page input    — form girdi alanı
.cfg-page button   — aksiyon butonu
.cfg-page .status  — geri bildirim mesajı
```

**Önemli:** `.cfg-page` scope'u dışında bu sınıflar tanımlanmaz — `portal-unified-v2.css`'e müdahale etmez.

---

## Partial Bölümleri

### `_company-users` — Firma & Kullanıcılar

- **Firma bilgisi** — isim, kod, logo, iletişim
- **Kullanıcı listesi** — rol, email, aktif/pasif
- **Kullanıcı ekleme** — ad, soyad, email, rol, şifre
- **Kullanıcı düzenleme** — rol değiştirme, aktiflik toggle
- **Şifre sıfırlama** — e-posta ile sıfırlama linki

**API endpoints:** `/api/users/*` (RbacController)

---

### `_dealers` — Bayi Konfigürasyonu

- **Bayi listesi** — isim, tip, durum, komisyon oranı
- **Bayi tipi yönetimi** — `DealerType` CRUD
- **Komisyon oranları** — tip bazlı % tanımı
- **Bayi ekleme/düzenleme** — profil, banka bilgisi, UTM link

**API endpoints:** `/api/dealers/*`, `/api/dealer-types/*`

---

### `_processes-integrations` — Süreç & Entegrasyon

- **Process Definition** — akış tanımı listesi + aktifleştirme
- **Field Rules** — dinamik alan doğrulama kuralları
- **Entegrasyon bağlantıları** — takvim, e-posta, e-imza, video, proje yönetimi
- **Firebase durumu** — `FirebaseStorageService::getStatus()`
- **WhatsApp konfigürasyonu** — phone_number_id testi

**API endpoints:** `/api/process-definitions/*`, `/api/field-rules/*`, `/api/integration-config/*`

---

### `_documents` — Belge Yönetimi

- **Belge kategorileri** — `DocumentCategory` CRUD
- **Üst kategori** — top_category alanı
- **Gerekli belgeler** — `GuestRequiredDocument` listesi + stage atama
- **Belge kodları** — `institution_document_catalog.php` referansı
- **Görünürlük kuralları** — student/dealer görebilir mi

**API endpoints:** `/api/document-categories/*`, `/api/guest-required-documents/*`

---

### `_content` — İçerik & Şablonlar

- **Mesaj şablonları** — `MessageTemplate` CRUD
- **Bildirim şablonları** — `config/notification_templates.php` (20+ şablon)
- **Escalation kuralları** — `EscalationRule` yönetimi
- **Bilgi tabanı makaleleri** — `KnowledgeBaseArticle` yönetimi

**API endpoints:** `/api/message-templates/*`, `/api/escalation-rules/*`

---

### `_analytics` — Analitik Konfigürasyonu

- **Lead kaynak seçenekleri** — `LeadSourceOption` CRUD
- **Revenue milestone** — `RevenueMilestone` hedef tanımı
- **Marketing external metric** bağlantı ayarları
- **Risk scoring konfigürasyonu** — `config/risk_scoring.php`

**API endpoints:** `/api/lead-source-options/*`, `/api/revenue-milestones/*`

---

### `_guests` + `_portal-users` — Başvurular & Portallar

**Guest Kayıt Formu (`_guests`):**
- `GuestRegistrationField` — form alanları sırası + zorunluluk
- Başvuru portalı URL ayarı
- UTM tracking konfigürasyonu

**Portal Kullanıcıları (`_portal-users`):**
- `PortalUserController` — guest/student portal erişim yönetimi
- Portal şifre sıfırlama
- Portal tema ayarı linki

**API endpoints:** `/api/guest-registration-fields/*`, `/api/portal-users/*`

---

## JavaScript

**Dosya:** `public/js/config-panel.js`

Tüm config AJAX işlemleri:
- `cfgFetch(url, options)` — CSRF token dahil fetch wrapper
- `cfgStatus(msg, isError)` — durum mesajı gösterme
- Form submit interceptor (SPA tarzı sayfa yenilemeden kayıt)
- Accordion toggle
- Tab geçişi + hash yönetimi

---

## API Endpoint'leri (Config Panel Kullanımı)

| Endpoint | Model | Açıklama |
|----------|-------|----------|
| `/api/users` | `User` | Kullanıcı CRUD |
| `/api/dealers` | `Dealer` | Bayi CRUD |
| `/api/dealer-types` | `DealerType` | Bayi tipi |
| `/api/document-categories` | `DocumentCategory` | Belge kategorisi |
| `/api/guest-required-documents` | `GuestRequiredDocument` | Gerekli belge |
| `/api/field-rules` | `FieldRule` | Alan kuralları |
| `/api/field-rule-approvals` | `FieldRuleApproval` | Kural onay |
| `/api/escalation-rules` | `EscalationRule` | Escalation kuralı |
| `/api/message-templates` | `MessageTemplate` | Mesaj şablonu |
| `/api/lead-source-options` | `LeadSourceOption` | Lead kaynağı |
| `/api/integration-config` | `IntegrationConfig` | Entegrasyon ayarı |
| `/api/revenue-milestones` | `RevenueMilestone` | Gelir hedefi |
| `/api/role-catalog` | `RoleTemplate` | RBAC şablon |
| `/api/entity-catalog` | `EntityCatalogService` | Varlık kataloğu |
| `/api/system-health` | — | Sistem sağlık kontrolü |

---

## SystematicInput

**Dosya:** `app/Support/SystematicInput.php`

Config formlarında girdi normalleştirme:
- Geçersiz değerleri kırmızı border ile işaretler (`.systematic-invalid`)
- `field-hint` ile açıklama gösterir

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| View (Ana) | `resources/views/config/index.blade.php` |
| Partial | `resources/views/config/partials/_company-users.blade.php` |
| Partial | `resources/views/config/partials/_dealers.blade.php` |
| Partial | `resources/views/config/partials/_processes-integrations.blade.php` |
| Partial | `resources/views/config/partials/_documents.blade.php` |
| Partial | `resources/views/config/partials/_content.blade.php` |
| Partial | `resources/views/config/partials/_analytics.blade.php` |
| Partial | `resources/views/config/partials/_guests.blade.php` |
| Partial | `resources/views/config/partials/_portal-users.blade.php` |
| JS | `public/js/config-panel.js` |
