# Entegrasyon: RBAC & Güvenlik

---

## Amaç

Çok katmanlı yetkilendirme sistemi: rol tabanlı erişim kontrolü (RBAC) + izin şablonları + güvenlik middleware'leri + anomali tespiti + denetim izi.

---

## Rol Sistemi

**Dosya:** `app/Models/User.php` — `ROLE_*` sabitleri

| Rol Sabiti | Değer | Portal / Erişim | Varsayılan İzinler |
|-----------|-------|-----------------|-------------------|
| `ROLE_MANAGER` | `manager` | `/manager` | config, student, revenue, approval, notification, ticket |
| `ROLE_SENIOR` | `senior` | `/senior` | — (EnsureSeniorRole kontrol eder) |
| `ROLE_MENTOR` | `mentor` | `/senior` (sınırlı) | — |
| `ROLE_STUDENT` | `student` | `/student` | — |
| `ROLE_GUEST` | `guest` | `/guest` | — |
| `ROLE_DEALER` | `dealer` | `/dealer` | — |
| `ROLE_SYSTEM_ADMIN` | `system_admin` | `/manager` | config, notification, role.template, ticket |
| `ROLE_SYSTEM_STAFF` | `system_staff` | Sınırlı | — |
| `ROLE_OPERATIONS_ADMIN` | `operations_admin` | `/manager` | config, assignment, approval, notification, ticket |
| `ROLE_OPERATIONS_STAFF` | `operations_staff` | Sınırlı | — |
| `ROLE_FINANCE_ADMIN` | `finance_admin` | `/manager` | config, revenue, notification |
| `ROLE_FINANCE_STAFF` | `finance_staff` | Sınırlı | — |
| `ROLE_MARKETING_ADMIN` | `marketing_admin` | `/mktg-admin` | marketing.dashboard, campaign.manage |
| `ROLE_MARKETING_STAFF` | `marketing_staff` | `/mktg-admin` (sınırlı) | — |
| `ROLE_SALES_ADMIN` | `sales_admin` | `/mktg-admin` | marketing.dashboard |
| `ROLE_SALES_STAFF` | `sales_staff` | Sınırlı | — |

### Rol Grupları
```
manager (tek başına)
system   → system_admin > system_staff
operations → operations_admin > operations_staff
finance  → finance_admin > finance_staff
marketing → marketing_admin > marketing_staff
sales    → sales_admin > sales_staff
advisory → senior > mentor
```

---

## Permission Sistemi

### effectivePermissionCodes()

**Dosya:** `app/Models/User.php:251`

```php
1. UserRoleAssignment (is_active=true)
      └── RoleTemplate → Permission[] → code[]

2. ROLE_DEFAULT_PERMISSION_CODES[role] (fallback)

= array_unique(merge)
```

- Sonuç `_permissionCodesCache`'e alınır (request başına tek DB sorgusu)
- `hasPermissionCode(string $code): bool` — tek nokta kontrol

### RoleTemplate → Permission İlişkisi

```
RoleTemplate (name, description)
  └── permissions() many-to-many → Permission (code, name, group)
        └── UserRoleAssignment (user_id, template_id, is_active, expires_at)
```

**Tablo:** `role_templates`, `permissions`, `role_template_permission`, `user_role_assignments`

---

## Middleware Katmanı

**Dizin:** `app/Http/Middleware/`

| Middleware | Amaç | Kullanım |
|-----------|------|----------|
| `EnsureManagerRole` | `manager` + admin rolleri | Manager portal tüm route'ları |
| `EnsureSeniorRole` | `senior` veya `mentor` + opsiyonel permission | Senior portal |
| `EnsureStudentRole` | `student` rolü | Student portal |
| `EnsureGuestRole` | `guest` rolü | Guest portal |
| `EnsureDealerRole` | `dealer` rolü | Dealer portal |
| `EnsureMarketingAccess` | `MARKETING_ACCESS_ROLES` | Marketing portal |
| `EnsureMarketingAdminOnly` | `marketing_admin` veya `manager` | Admin-only aksiyonlar |
| `EnsureManagerOrPermission` | Manager rolü VEYA belirli permission | Esnekli koruma |
| `EnsurePermission` | Tek permission kodu kontrolü | Granüler erişim |
| `EnsureManagerKey` | `X-Manager-Key` header | API güvenliği |
| `EnsureGuestOwnsDocument` | Guest sadece kendi belgesi | Belge erişim güvenliği |
| `EnsureGuestOwnsTicket` | Guest sadece kendi ticketı | Ticket erişim güvenliği |
| `CheckDealerTypePermission` | Bayi tipi bazlı özellik | Dealer tip kısıtlaması |
| `CheckProcessOutcomeVisibility` | Outcome görünürlük | Süreç sonucu erişimi |
| `SecurityHeaders` | CSP, HSTS, X-Frame vb. | Global — tüm yanıtlar |
| `SetCompanyContext` | company_id bağlama | Global — multi-tenant |
| `FieldRuleValidator` | Dinamik alan kuralları | Form gönderimlerinde |
| `EnsureTaskAccess` | Task görünürlük kuralı | Task Board |

---

## 2FA (İki Faktörlü Doğrulama)

**Dosya:** `app/Http/Controllers/Api/TwoFactorController.php`
**Model:** `app/Models/UserTwoFactor.php`

### Mevcut Durum

| Endpoint | İşlev | Production |
|----------|-------|-----------|
| `POST /api/v1/2fa/enable` | TOTP setup başlat | Çalışıyor (QR URL üretir) |
| `POST /api/v1/2fa/verify` | Kodu doğrula → aktifleştir | **503 döner** |
| `POST /api/v1/2fa/challenge` | Giriş sırasında kod doğrula | **503 döner** |
| `DELETE /api/v1/2fa/disable` | 2FA kaldır | Çalışıyor |

Production'da `app()->isProduction()` kontrolü ile stub bypass engellendi.

### Gerçek TOTP için
```bash
composer require pragmarx/google2fa
```
`TwoFactorController.php` içindeki yorum satırı aktif edilir. Secret `strtoupper(Str::random(32))` yerine gerçek Base32 ile üretilir.

---

## Güvenlik Katmanları

### SecurityHeaders Middleware
**Dosya:** `app/Http/Middleware/SecurityHeaders.php`

- `Content-Security-Policy`
- `Strict-Transport-Security` (HSTS)
- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy`

### ValidFileMagicBytes
**Dosya:** `app/Rules/ValidFileMagicBytes.php`

Dosya yüklemelerinde MIME sniffing yerine gerçek magic byte kontrolü.
Desteklenen: PDF, JPEG, PNG, GIF, DOCX, XLSX, ZIP.

### Vault Throttling
`/api/v1/vault/*` → `throttle:30,1` (dakikada 30 istek)

### IP Access Rules
`/api/v1/config/ip-rules` — IP whitelist/blacklist yönetimi
**Model:** `app/Models/IpAccessRule.php`

### AuditTrail (Observer Pattern)
**Model:** `app/Models/AuditTrail.php`
**Migration:** `create_audit_trails_table`

Model observer üzerinden kritik model değişikliklerini kaydeder:
- `event_type`: created, updated, deleted
- `subject_type` / `subject_id`: hangi model
- `old_values` / `new_values`: JSON diff
- `causer_id`: işlemi yapan kullanıcı

### SecurityAnomalyService
**3 kontrol:**
1. Kısa sürede aşırı başarısız login denemeleri
2. Alışılmadık saatlerde admin erişimi
3. Büyük veri export'ları

**Cron:** `security:anomaly-check` → saatlik (`routes/console.php`)

---

## RBAC API Endpoint'leri

**Dosya:** `app/Http/Controllers/Api/RbacController.php`

| Endpoint | Açıklama |
|----------|----------|
| `GET /api/v1/rbac/templates` | RoleTemplate listesi |
| `POST /api/v1/rbac/templates` | Yeni şablon oluştur |
| `PUT /api/v1/rbac/templates/{id}` | Şablon güncelle |
| `DELETE /api/v1/rbac/templates/{id}` | Şablon sil |
| `POST /api/v1/rbac/assign` | Kullanıcıya şablon ata |
| `DELETE /api/v1/rbac/assign/{id}` | Atamayı kaldır |
| `GET /api/v1/rbac/permissions/usage` | Permission kullanım raporu |

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Model (User + Roller) | `app/Models/User.php` |
| Model (RoleTemplate) | `app/Models/RoleTemplate.php` |
| Model (Permission) | `app/Models/Permission.php` |
| Model (UserRoleAssignment) | `app/Models/UserRoleAssignment.php` |
| Model (AuditTrail) | `app/Models/AuditTrail.php` |
| Model (UserTwoFactor) | `app/Models/UserTwoFactor.php` |
| Model (IpAccessRule) | `app/Models/IpAccessRule.php` |
| Controller (RBAC) | `app/Http/Controllers/Api/RbacController.php` |
| Controller (2FA) | `app/Http/Controllers/Api/TwoFactorController.php` |
| Controller (Security) | `app/Http/Controllers/SecurityController.php` |
| Middleware | `app/Http/Middleware/SecurityHeaders.php` |
| Rule | `app/Rules/ValidFileMagicBytes.php` |
| Migration (RBAC) | `database/migrations/2026_02_15_150001_create_rbac_core_tables.php` |
| Migration (2FA) | `database/migrations/*_create_user_two_factor_table.php` |
