# Kullanıcı Oluşturma Süreci

**Proje:** MentorDE
**Kapsam:** 5 farklı kullanıcı tipinin sisteme eklenme akışı
**Son güncelleme:** 15.04.2026

---

## Kullanıcı Tipleri ve Oluşturma Yöntemleri

MentorDE'de `users` tablosunda tüm roller aynı tabloda tutulur, `role` sütununda ayrıştırılır.
Aşağıdaki 5 ana tip için farklı giriş noktaları ve farklı veri şemaları vardır.

| Tip | Rol Kodu | Giriş Noktası | Ek Alanlar |
|---|---|---|---|
| Ekip / Personel | `manager`, `system_*`, `operations_*`, `finance_*`, `marketing_*`, `sales_*` | `/manager/staff/create` | `role_template` + `department` |
| Eğitim Danışmanı | `senior`, `mentor` | `/api/admin/senior-management` | `senior_code`, `senior_type`, `max_capacity` |
| Bayi Kullanıcısı | `dealer` | `/api/portal-users` (+ önce `dealers` kaydı) | `dealer_code` |
| Aday (Guest) | `guest` | Otomatik: `GuestApplication` oluşturulunca | — |
| Öğrenci | `student` | Aday dönüşümü veya `/api/portal-users` | `student_id` |

---

## 1. Ekip / Personel Kullanıcısı

> Manager, admin ve personel seviyesinde iç ekip üyeleri.

### Roller

- **Katman 1 — Manager:** `manager`
- **Katman 2 — Admin:** `system_admin`, `operations_admin`, `finance_admin`, `marketing_admin`, `sales_admin`
- **Katman 3 — Senior/Mentor:** `senior`, `mentor` *(ayrı flow — Bölüm 2)*
- **Katman 4 — Personel:** `system_staff`, `operations_staff`, `finance_staff`, `marketing_staff`, `sales_staff`

### MentorDE tarafında (manager yetkisi gerekli)

1. **Giriş:** `/manager/staff/create` (Manager panelinden "Yeni Personel")
2. **Form alanları:**
   - Ad Soyad
   - E-posta (unique)
   - Rol seçimi (departman + katman)
   - Şifre (manuel belirle, `min:8`, confirmed)
3. **Kayıt:**
   - `StaffController::store()` validate + `User::create()`
   - `company_id` oturumdan otomatik atanır
   - `is_active = true`
4. **Rol Template otomatik eşleşmesi:**
   - `RoleTemplate::where('parent_role', $user->role)->where('is_active', true)`
   - Varsayılan şablon varsa `UserRoleAssignment` kaydı açılır → izinler otomatik uygulanır
5. **Yönlendirme:** `/manager/hr/persons/{id}` (HR kartına düşer)

### Yeni personel tarafında

1. **Davet bilgilendirmesi:** Manager e-posta + şifreyi manuel iletir *(otomatik invitation mail şu an devrede değil)*
2. **İlk giriş:** `/login` → rol bazlı layout açılır (`manager.layouts.app`, `senior.layouts.app`, vs.)
3. **2FA kurulumu (zorunlu roller için):**
   - Manager, system_admin, operations_admin, finance_admin → ilk girişte `/security/2fa/setup` yönlendirmesi
   - Google Authenticator ile QR kod + secret kaydı (`user_two_factor.secret` TEXT)
   - Backup kodları indirilir
4. **Şifre değişikliği:** Kendi profil sayfasından (`/profile`)
5. **İzin seviyesi:** Rol şablonundan otomatik → özel izin gerekirse manager'a talep

### Güvenlik notları

- `STAFF_ROLES` listesi dışındaki roller manager panelinden eklenemez
- Senior/Mentor için ayrı endpoint kullanılır (senior_code otomatik üretimi gerekli)
- Failed login ≥ N olursa `locked_until` set edilir
- 2FA ENFORCED_ROLES: `manager`, `system_admin`, `operations_admin`, `finance_admin`

---

## 2. Eğitim Danışmanı (Senior / Mentor)

> Öğrenci havuzunu yöneten, aday öğrencilerle birebir çalışan ekip.

### MentorDE tarafında

1. **Giriş:** `POST /api/admin/senior-management`
   *(UI: Manager paneli → Eğitim Danışmanları → Yeni)*
2. **Form alanları:**
   - Ad Soyad
   - E-posta (unique)
   - Rol (`senior` veya `mentor`)
   - Senior tipi (opsiyonel, serbest metin — örn. "Almanya uzmanı")
   - Max kapasite (default: rol bazlı, opsiyonel override)
   - `auto_assign_enabled` — otomatik guest atanması (default: true)
   - `can_view_guest_pool` — havuzu görme yetkisi (default: false)
   - Şifre (opsiyonel — boş bırakılırsa 14 karakter random üretilir)
3. **`senior_code` otomatik üretimi:**
   - `SeniorManagementController::generateAdvisoryCode($role)`
   - Format: `SEN-000001`, `MEN-000001` (sequential internal)
4. **Kayıt + dönüş:**
   - `User::create()` → rol + senior_code + senior_type + capacity atanır
   - Response: `{ user, generated_password }` → manager'a gösterilir

### Senior tarafında

1. **Davet bilgilendirmesi:** Manager e-posta + plain password iletir
2. **İlk giriş:** `/login` → `senior.layouts.app`
3. **Aday atama akışı:**
   - Auto-assign açıksa: yeni guest başvurularında otomatik eşleşme
   - Manuel: manager veya self-pickup (havuz yetkisi varsa)
4. **DM + CRM:** Atanmış adaylarla ve öğrencilerle doğrudan iletişim (telefon rehberi + kendi guest'leri)
5. **Performans takibi:** KPI dashboard (aktif öğrenci, conversion, aylık skor)

### Hiyerarşi

- **Senior** → bağımsız danışman, kendi havuzunu yönetir
- **Mentor** → Senior'a bağlı, belirli konuda destek (daha kısıtlı yetki)

---

## 3. Bayi Kullanıcısı

> Dealer tablosundaki firma/kişiye bağlı portal kullanıcısı.

> **Önkoşul:** Önce `dealers` tablosunda Dealer kaydı açılmış olmalı.
> Bayi onboarding akışı için `docs/DEALER_ONBOARDING_PROCESS.md` belgesine bakınız.

### MentorDE tarafında

1. **Dealer kaydı kontrol:** İlgili dealer'ın `code` değeri alınır (örn. `LEA-000001`)
2. **Giriş:** `POST /api/portal-users` (veya Manager → Bayiler → Kullanıcı Ekle)
3. **Form alanları:**
   - Ad Soyad
   - E-posta (unique)
   - Rol = `dealer` (sabit)
   - `dealer_code` (zorunlu — `dealers.code`'a FK gibi davranır, string match)
   - Şifre (opsiyonel — boş bırakılırsa 12 karakter random)
4. **Kayıt:** `PortalUserController::store()` → validation + `User::create()`
5. **Birden fazla kullanıcı:** Operasyon bayileri için ek kullanıcılar aynı `dealer_code` ile eklenebilir (ekip desteği)

### Bayi tarafında

1. **Davet:** Manager plain password'ü iletir (e-posta + sözleşme süreci ile birlikte)
2. **Giriş:** `/login` → `dealer.layouts.app`
3. **Sözleşme ekranı:** İlk girişte aktif sözleşme yoksa portal kısıtlı
4. **Aktif olduktan sonra:**
   - Lead Generation → UTM link üretici
   - Freelance → kendi aday havuzu + mentor Q&A
   - Operasyon → tam portal yetki (öğrenci, belge, ödeme)

---

## 4. Aday (Guest)

> Başvuru formundan otomatik oluşan kullanıcı.

### Otomatik akış

1. **Giriş:** `/apply` public başvuru formu (visitor)
2. **Form gönderim:**
   - `GuestApplicationController::store()` → `GuestApplication` kaydı
   - `ensureGuestPortalUser($firstName, $lastName, $email)` çağrısı
3. **Mevcut user kontrolü (güvenlik):**
   - E-posta daha önce kayıtlıysa: **mevcut user'a dokunulmaz** (şifre sıfırlanmaz)
   - *Geçmiş bir güvenlik açığı buradan kapatıldı — eski kod re-apply'da şifreyi yeniliyordu*
4. **Yeni user oluşumu:**
   - `User::create(['role' => 'guest', 'is_active' => true, 'password' => Str::random(12)])`
   - `GuestApplication.guest_user_id` → yeni user'a bağlanır
5. **Senior atama:** Auto-assign aktifse guest'e bir senior atanır (`assigned_senior_email`)

### Aday tarafında

1. **Başvuru tamamlandı ekranı:** tracking_token gösterilir
2. **Bildirim maili:**
   - Başvuru onayı + `password.reset` token linki (24 saat geçerli)
   - **Plain şifre asla gönderilmez** → kullanıcı kendisi belirler
3. **İlk giriş:** Şifre belirledikten sonra `/login` → `guest.layouts.app`
4. **Aktivite:** Sözleşme seçimi, paket seçimi, belge yükleme, mentor ile iletişim
5. **Dönüşüm:** Sözleşme imzalanıp ödeme yapıldığında aday → öğrenci'ye geçer (Bölüm 5)

### E-posta çakışması senaryoları

| Durum | Davranış |
|---|---|
| Yeni e-posta | Yeni user açılır, plain password random, setup link gönderilir |
| Mevcut guest — aynı user | Re-apply kabul edilir, yeni başvuru oluşur, user değişmez |
| Mevcut student/staff — farklı rol | Yeni başvuru oluşur ama user'a dokunulmaz (çakışma üzerine yeni rol açılmaz) |

---

## 5. Öğrenci

> Aday dönüşümünden veya manuel oluşturulan öğrenci kullanıcıları.

### Akış 1: Aday → Öğrenci dönüşümü (otomatik)

1. **Tetikleyici:** Aday sözleşmesi imzalanır ve ödeme onaylanır
2. **Dönüşüm:** `GuestApplication.converted_to_student = true`
3. **User güncellemesi:** Mevcut guest user'ın rolü `student`'a çevrilir, `student_id` atanır
4. **Atama:** `StudentAssignment` kaydı açılır, senior bilgisi taşınır

### Akış 2: Manuel öğrenci oluşturma

1. **Giriş:** `POST /api/portal-users` (veya Manager → Öğrenci → Yeni)
2. **Form alanları:**
   - Ad Soyad, e-posta (unique)
   - Rol = `student` (sabit)
   - `student_id` (zorunlu — benzersiz öğrenci numarası, örn. `STD-000123`)
   - Şifre (opsiyonel — random fallback)
3. **Kayıt:** `PortalUserController::store()` → `User::create()` + rol student

### Öğrenci tarafında

1. **Davet:** Setup link veya plain password
2. **Giriş:** `/login` → `student.layouts.app`
3. **Aktivite:** Belge takibi, süreç takibi, mentor ile iletişim, yurtdışı işlemleri

---

## Ortak Güvenlik Kuralları

### Şifre Politikası

- **Manuel belirleme:** min 8 karakter, confirmed
- **Random üretim:** `Str::random(12)` veya `Str::random(14)` (rol bazlı)
- **Plain şifre iletimi:** Manager UI'da tek seferlik görünür; e-posta ile gönderilmez
- **Tercih edilen akış:** Setup link (`password.reset` token, 24h geçerli)

### E-posta Benzersizliği

- `users.email` tüm rollere ortak unique constraint
- Mevcut kullanıcıya yeni rolle kayıt denemesi → 422
- Re-apply senaryosunda mevcut user korunur

### 2FA Zorunluluğu

- **ENFORCED_ROLES:** `manager`, `system_admin`, `operations_admin`, `finance_admin`
- İlk girişte `/security/2fa/setup` yönlendirmesi
- `user_two_factor.secret` TEXT (VARCHAR'dan genişletildi, commit `87ac86d`)
- Geçici disable durumu: commit `1289d77` sonrası yeniden aktif edilecek

### Company İzolasyonu

- Yeni user `company_id` oturumdan otomatik atanır
- Multi-tenant kısıtı: kullanıcılar yalnızca kendi şirketlerinin verilerini görür
- Cross-company erişim sadece `system_admin` için (manager + yukarısı değil)

### Soft Delete

- `users.deleted_at` kolonu mevcut
- Silinen kullanıcılar listede görünmez ama kayıt korunur (audit + re-activate imkanı)

---

## Role Template Sistemi

Rol bazlı permission'lar statik değil — `role_templates` tablosundan gelir.

1. **Oluşum:** Yeni kullanıcı kaydı sırasında `parent_role` eşleşmesi aranır
2. **Atama:** `UserRoleAssignment` kaydı açılır (template_id + version)
3. **Versiyonlama:** Template güncellendiğinde `version` artar, eski atamalar `version_applied` ile eski izinleri tutar
4. **Override:** Manager tek user'a özel template atayabilir (standart template yerine)

---

## Oluşturma Yöntemleri Özeti

| Kullanıcı Tipi | Endpoint | Yetki | Otomasyon |
|---|---|---|---|
| Staff (admin/personel) | `POST /manager/staff` | `manager` | Role template otomatik eşleşir |
| Senior/Mentor | `POST /api/admin/senior-management` | `manager`, `system_admin` | `senior_code` otomatik üretilir |
| Dealer | `POST /api/portal-users` | `manager` | `dealer_code` manuel (önce dealer kaydı gerekli) |
| Guest | Otomatik (`/apply`) | Public | `ensureGuestPortalUser()` çağrısı |
| Student | Dönüşüm veya `POST /api/portal-users` | `manager`, `senior` | `student_id` zorunlu |

---

*MentorDE — User Management Module*
