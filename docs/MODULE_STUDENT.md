# Modül: Student (Öğrenci) Portali

**URL Prefix:** `/student`
**Middleware:** `EnsureStudentRole`
**Layout:** `resources/views/student/layouts/app.blade.php`

---

## Amaç

Guest başvurusu onaylanıp öğrenciye dönüştürülen kullanıcıların kendi süreçlerini, belgelerini, randevularını ve iletişimlerini yönettiği portal.

---

## Erişim

- Kullanıcı rolü: `student`
- `User::student_id` alanı dolu olmalı
- `StudentGuestResolver` servisi ile `GuestApplication` kaydı eşleştirilir (`converted_student_id` FK üzerinden)
- `app/Http/Middleware/EnsureStudentRole.php`

---

## Sayfa Listesi

| URL | View | Açıklama |
|-----|------|----------|
| `/student/dashboard` | `student/dashboard` | Ana dashboard |
| `/student/registration` | `student/registration-form` | Kayıt formu |
| `/student/registration/documents` | `student/registration-documents` | Belge yükleme |
| `/student/appointments` | `student/appointments` | Randevular |
| `/student/contract` | `student/contract` | Sözleşme görüntüleme |
| `/student/payments` | `student/payments` | Ödemeler + TRY chip |
| `/student/shipments` | `student/shipments` | Kargo takibi |
| `/student/messages` | `student/messages` | DM mesajlaşma |
| `/student/tickets` | `student/tickets` | Destek talepleri |
| `/student/university-applications` | `student/university-applications` | Üniversite başvuruları |
| `/student/institution-documents` | `student/institution-documents` | Kurumsal belgeler |
| `/student/document-builder` | `student/document-builder` | Döküman oluşturucu |
| `/student/cv-builder` | `student/cv-builder` | CV Builder (React) |
| `/student/materials` | `student/materials` | Materyaller |
| `/student/services` | `student/services` | Servis paketi görüntüleme |
| `/student/profile` | `student/profile` | Profil |
| `/student/settings` | `student/settings` | Ayarlar |

---

## Dashboard (`StudentDashboardController`)

**Dosya:** `app/Http/Controllers/StudentDashboardController.php`

Dashboard view'a aktarılan değişkenler:

| Değişken | Kaynak | Açıklama |
|----------|--------|----------|
| `$guestApplication` | `StudentGuestResolver` | Bağlı GuestApplication |
| `$assignment` | `StudentAssignment` | Atanan senior bilgisi |
| `$documents` | `Document` | Son 30 belge |
| `$docSummary` | Hesaplama | total/uploaded/approved/rejected |
| `$requiredChecklist` | `GuestRequiredDocument` | Zorunlu belge kontrol listesi |
| `$outcomes` | `ProcessOutcome` | Son 20 süreç sonucu |
| `$notifications` | `NotificationDispatch` | Son 20 bildirim |
| `$countdowns` | `ProcessOutcome` | 60 gün içindeki deadline'lar |
| `$alerts` | Hesaplama | Pasaport bitiş, reddedilen belge, eksik belge, yanıt bekleyen bilet |
| `$weekActivity` | Çoklu sorgu | 7 günlük aktivite özeti |
| `$checklistSummary` | `StudentChecklist` | total/done/percent/overdue |
| `$achievements` | `StudentAchievementService` | Kazanılan rozetler |
| `$achievementPoints` | `StudentAchievementService` | Toplam puan |
| `$onboardingPending` | `StudentOnboardingStep` | Onboarding tamamlandı mı? |
| `$dmThread` | `DmThread` | Aktif DM iş parçacığı |
| `$dmUnread` | `DmMessage` | Okunmamış mesaj sayısı |
| `$banners` | `CmsContent` | CMS'ten çekilen student banner'ları |
| `$greeting` | Saat bazlı | Günaydın / İyi günler / İyi akşamlar |

### Uyarı Kartları (Alerts)
Dashboard'da 5 tip otomatik uyarı:
1. Pasaport bitiş tarihi < 3 ay → `danger`
2. Reddedilen belge varsa → `warning`
3. Eksik zorunlu belge varsa → `warning`
4. Yanıt bekleyen ticket varsa → `info`
5. Okunmamış DM varsa → `info`

---

## Temel İşlevler

### 1. Belge Yönetimi
- Belge yükleme (MIME + magic bytes doğrulama ile)
- Zorunlu belge kontrol listesi (application_type bazlı)
- Durum takibi: `uploaded → approved / rejected`
- Kategori bazlı gruplama (`DocumentCategory.top_category_code`)
- **Dosyalar:** `StudentPortalController::registrationDocuments()`, `resources/views/student/registration-documents.blade.php`

### 2. Randevular
- Senior'ın oluşturduğu randevuları görüntüleme
- Takvim provider badge (Google/Cal.com/Calendly)
- Meeting URL linki
- **Dosya:** `resources/views/student/appointments.blade.php`

### 3. Sözleşme
- Manager'ın atadığı sözleşme şablonunu görüntüleme
- İmza durumu takibi (`contract_status`)
- **Dosya:** `resources/views/student/contract.blade.php`, `public/js/student-contract.js`

### 4. Ödemeler & Döviz Kuru
- Ödeme planı görüntüleme
- EUR tutarının TRY karşılığı: `CurrencyRateService::getRate('EUR','TRY')` → cache 3600s
- `≈ ₺ X.XXX (1 EUR = XX,XX TRY · bugün)` chip widget
- **Dosya:** `resources/views/student/payments.blade.php`

### 5. Kargo Takibi
- Senior'ın `is_visible_to_student = true` ayarladığı kargolar görünür
- Durum: `preparing / shipped / in_transit / delivered / returned / lost`
- Yön: `outgoing / incoming`
- **Dosya:** `resources/views/student/shipments.blade.php`

### 6. Mesajlaşma (DM)
- `DmThread / DmMessage` modelleri üzerinden birebir mesajlaşma
- Okunmamış mesaj sayacı dashboard'da görünür
- **Dosya:** `resources/views/student/messages.blade.php`, `public/js/student-messages.js`

### 7. Destek Talepleri (Tickets)
- `GuestTicket` modeli (`EnsureStudentRole` + `EnsureGuestOwnsTicket`)
- Departman bazlı yönlendirme
- SLA takibi
- **Dosya:** `resources/views/student/tickets.blade.php`, `public/js/student-tickets.js`

### 8. Üniversite Başvuru Takibi
- Senior'ın `is_visible_to_student = true` yaptığı başvurular görünür
- Üniversite, program, başvuru durumu, deadline
- **Dosya:** `resources/views/student/university-applications.blade.php`

### 9. Kurumsal Belge Takibi
- `StudentInstitutionDocument` — `config/institution_document_catalog.php` (~80 belge kodu)
- Görünürlük filtreli
- **Dosya:** `resources/views/student/institution-documents.blade.php`

### 10. CV Builder (React)
- `resources/js/student-cv-builder/App.jsx` — React SPA
- Bölüm bazlı CV oluşturma
- **Dosya:** `resources/views/student/cv-builder.blade.php`

### 11. Rozet Sistemi
- `StudentAchievementService::checkAndAward()` — dashboard yüklenince kontrol
- Puan bazlı gamification
- **Dosya:** `app/Services/StudentAchievementService.php`

---

## Model İlişkileri

```
User (role=student)
  └── student_id (string) ──► GuestApplication.converted_student_id
  └── StudentAssignment.student_id ──► assigned_senior_email
  └── Document.student_id
  └── DmThread.student_id
  └── StudentAppointment.student_id
  └── StudentShipment.student_id
  └── StudentUniversityApplication.student_id
  └── StudentInstitutionDocument.student_id
  └── StudentChecklist.student_id
  └── StudentRevenue.student_id
```

---

## Bağımlı Servisler

| Servis | Kullanım |
|--------|----------|
| `StudentGuestResolver` | User → GuestApplication eşleştirme |
| `CurrencyRateService` | EUR/TRY kuru |
| `GuestRegistrationFieldSchemaService` | Kayıt formu alan şeması |
| `AccountVaultService` | Vault erişimi |
| `StudentAchievementService` | Rozet sistemi |
| `EventLogService` | Aktivite loglama |

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller (Dashboard) | `app/Http/Controllers/StudentDashboardController.php` |
| Controller (Portal) | `app/Http/Controllers/StudentPortalController.php` |
| Controller (Workflow) | `app/Http/Controllers/StudentWorkflowController.php` |
| Middleware | `app/Http/Middleware/EnsureStudentRole.php` |
| Layout | `resources/views/student/layouts/app.blade.php` |
| JS (Kayıt Formu) | `public/js/student-registration-form.js` |
| JS (Belgeler) | `public/js/student-registration-documents.js` |
| JS (Mesajlar) | `public/js/student-messages.js` |
| JS (Tickets) | `public/js/student-tickets.js` |
| JS (Sözleşme) | `public/js/student-contract.js` |
| JS (CV Builder) | `resources/js/student-cv-builder/App.jsx` |
