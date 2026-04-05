# Entegrasyon: GDPR & Veri Gizliliği

---

## Yasal Dayanak

| Madde | Hak | Uygulama |
|-------|-----|----------|
| GDPR Madde 17 | Unutulma hakkı (silme) | Anonimleştirme talebi → Manager onayı |
| GDPR Madde 20 | Taşınabilirlik hakkı (export) | JSON indirme |

---

## Kapsam

Kişisel veri işlenen tüm roller:
- **Student** → kullanıcı profili, belgeler, randevular, mesajlar
- **Guest** → başvuru formu, belgeler, sözleşme verileri
- **Dealer** → profil, lead verileri (dolaylı)

---

## Veri Dışa Aktarma (Export)

### Student Export
**Endpoint:** `GET /student/gdpr/export`
**Controller:** `GdprController::exportStudentData()`

`PersonalDataExportService::exportForStudent($user)` çağrılır:
- User profili (ad, email, telefon)
- Documents listesi
- StudentAssignment
- StudentAppointments
- NotificationDispatch geçmişi

JSON formatında (`mentorde-kisisel-verilerim-YYYYMMDD.json`) stream download.

### Guest Export
**Endpoint:** `GET /guest/gdpr/export`
**Controller:** `GdprController::exportGuestData()`

`PersonalDataExportService::exportForGuest($user, $app)`:
- GuestApplication tüm alanları (UTM dahil)
- Documents
- GuestTickets
- Sözleşme snapshot

Her iki export da `EventLogService` ile loglanır: `gdpr.data_export`.

---

## Veri Silme / Anonimleştirme

### Talep Akışı (GDPR Madde 17)

```
Kullanıcı → POST /student/gdpr/erasure veya /guest/gdpr/erasure
  └── ManagerRequest::create(request_type='gdpr_erasure', status='pending', priority='high')
        └── Manager onaylar → AnonymizationService::anonymize()
```

- Açık bekleyen talep varsa ikinci talep reddedilir
- Manager `/manager/requests` sayfasından onaylar
- Log: `gdpr.erasure_request`

### AnonymizationService

**Dosya:** `app/Services/AnonymizationService.php`

Anonimleştirilen veriler:
- `User`: name → `[Anonimleştirildi]`, email → `anon_{id}@deleted.local`, phone → null
- `GuestApplication`: first_name, last_name, email, phone → null/placeholder
- `Dealer`: bağlantılı kişisel veriler
- `StudentAssignment`: kişisel tanımlayıcılar

Anonimleştirme **SoftDelete değildir** — veriler fiziksel olarak maskelenir, ID'ler ve ilişkiler korunur (istatistiksel bütünlük için).

---

## Rıza Kayıtları (ConsentRecord)

**Dosya:** `app/Models/ConsentRecord.php`
**Tablo:** `consent_records`

| Alan | Açıklama |
|------|----------|
| `user_id` | Rızayı veren kullanıcı |
| `consent_type` | `kvkk`, `marketing`, `profiling` vb. |
| `given_at` | Rıza tarihi |
| `withdrawn_at` | Geri çekme tarihi (null = aktif) |
| `ip_address` | IP adresi |
| `consent_text` | Rızanın tam metni (snapshot) |

---

## Veri Saklama Politikaları (DataRetentionPolicy)

**Dosya:** `app/Models/DataRetentionPolicy.php`
**Tablo:** `data_retention_policies`

| Alan | Açıklama |
|------|----------|
| `entity_type` | `user`, `guest_application`, `document` vb. |
| `retention_days` | Kaç gün saklanacak |
| `action` | `anonymize` veya `delete` |
| `is_active` | Politika aktif mi |

---

## Cron: gdpr:enforce-retention

**Dosya:** `app/Console/Commands/EnforceDataRetentionCommand.php`
**Schedule:** Her gece `03:00` → `routes/console.php`

```bash
php artisan gdpr:enforce-retention
php artisan gdpr:enforce-retention --dry-run   # Kaç kayıt etkilenir — değişiklik yok
```

### Akış
```
DataRetentionPolicy (is_active=true) tümünü al
  └── Her policy için:
        ├── entity_type'a göre sorgu
        ├── created_at < now() - retention_days filtresi
        ├── --dry-run: sadece say ve raporla
        └── Gerçek mod: AnonymizationService veya soft-delete
```

Her çalışma `EventLogService` ile loglanır.

---

## SoftDeletes Stratejisi

### SoftDeletes Olan Tablolar

| Model | Tablo |
|-------|-------|
| `User` | `users` |
| `GuestApplication` | `guest_applications` |
| `Dealer` | `dealers` |
| `StudentAssignment` | `student_assignments` |
| `Document` | `documents` |
| `GuestTicket` | `guest_tickets` |
| `MarketingTask` | `marketing_tasks` |
| `StudentShipment` | `student_shipments` |
| `StudentUniversityApplication` | `student_university_applications` |
| `StudentInstitutionDocument` | `student_institution_documents` |
| `AutomationWorkflow` | `automation_workflows` |

### SoftDeletes Olmayan (tasarım gereği)
Loglama/audit tablolar fiziksel kayıt tutar — `AuditTrail`, `SystemEventLog`, `EscalationEvent`.

---

## GDPR Dashboard

**URL:** `/manager/gdpr-dashboard`
**Middleware:** `EnsureManagerRole`

Manager görünümü:
- Bekleyen silme talepleri (`ManagerRequest.request_type = gdpr_erasure`)
- Aktif consent kayıtları özeti
- Veri saklama politikaları listesi
- Son retention çalışması logları

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller | `app/Http/Controllers/GdprController.php` |
| Service (Export) | `app/Services/PersonalDataExportService.php` |
| Service (Anonim) | `app/Services/AnonymizationService.php` |
| Model | `app/Models/ConsentRecord.php` |
| Model | `app/Models/DataRetentionPolicy.php` |
| Command | `app/Console/Commands/EnforceDataRetentionCommand.php` |
| Migration | `database/migrations/2026_02_26_100002_create_consent_records_table.php` |
| Migration | `database/migrations/2026_02_26_100003_create_data_retention_policies_table.php` |
| Migration (SoftDeletes) | `database/migrations/2026_02_26_100001_add_soft_deletes_to_key_tables.php` |
| Migration (SoftDeletes v2) | `database/migrations/2026_03_19_000002_add_soft_deletes_to_documents_and_tickets.php` |
