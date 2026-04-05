# Entegrasyon: Sözleşme Sistemi

---

## Amaç

Guest başvurularının öğrenci statüsüne geçiş sürecindeki sözleşme hazırlama, imzalama, onaylama ve audit log işlemlerini yönetir.

---

## Sözleşme Durumu Akışı

```
not_requested
  → (guest requestContract() ile talep eder) → pending_manager
      → (guest withdrawContractRequest() ile geri çeker) → not_requested
      → (manager startContract() ile hazırlar) → requested
          → (guest uploadSignedContract() veya signContractDigital()) → signed_uploaded
              → (manager decideContract(approve)) → approved → öğrenci statüsüne geçiş
              → (manager decideContract(reject))  → rejected
                  → (guest tekrar yükler) → signed_uploaded
          → (guest requestContractUpdate() ile güncelleme talep eder) → requested (sıfırlanır)
  → (herhangi bir aşamada, not_requested ve cancelled hariç) → cancelled
      → (guest requestReopen()) → reopen_requested
```

**Alan:** `guest_applications.contract_status`

**Durum değerleri:** `not_requested`, `pending_manager`, `requested`, `signed_uploaded`, `approved`, `rejected`, `cancelled`, `reopen_requested`

### İlgili GuestApplication Alanları

| Alan | Açıklama |
|------|----------|
| `contract_template_id` | Kullanılan şablon ID |
| `contract_template_code` | Şablon kodu (snapshot anında) |
| `contract_snapshot_text` | Body metni snapshot |
| `contract_annex_kvkk_text` | KVKK ek snapshot |
| `contract_annex_commitment_text` | Kapsam ek snapshot |
| `contract_annex_payment_text` | Ödeme ek snapshot |
| `contract_requested_at` | Talep tarihi |
| `contract_generated_at` | Snapshot oluşturulma tarihi |
| `contract_signed_at` | İmza tarihi |
| `contract_approved_at` | Manager onay tarihi |
| `contract_signed_file_path` | Yüklenen imzalı belge yolu |
| `contract_digital_signature_data` | JSON dijital imza verisi |
| `contract_digital_signed_at` | Dijital imza zamanı |
| `contract_digital_sign_ip` | Dijital imza IP adresi |
| `contract_cancel_category` | İptal kategorisi |
| `contract_cancel_reason_code` | İptal sebep kodu |
| `contract_cancel_note` | İptal notu |
| `contract_cancel_attachment_path` | İptal eki dosya yolu |
| `contract_cancelled_at` / `contract_cancelled_by` | İptal kayıtları |
| `reopen_reason` / `reopen_requested_at` | Yeniden açma bilgisi |
| `converted_to_student` | Onay sonrası true |
| `converted_student_id` | Atanan öğrenci ID (STU-XXXXXXXX) |

---

## ContractTemplate Modeli

**Dosya:** `app/Models/ContractTemplate.php`
**Tablo:** `contract_templates`
**Trait:** `BelongsToCompany`, `SoftDeletes`

| Alan | Açıklama |
|------|----------|
| `company_id` | Firma bağlantısı |
| `code` | Şablon kodu |
| `name` | Şablon adı |
| `version` | Versiyon numarası |
| `parent_version_id` | Üst versiyon FK (versiyonlama zinciri) |
| `change_log` | Değişiklik notu |
| `is_active` | Aktif şablon bayrağı |
| `body_text` | Ana sözleşme metni (`{{placeholder}}` destekli) |
| `annex_kvkk_text` | Ek-1: KVKK/DSGVO aydınlatma metni |
| `annex_commitment_text` | Ek-2: Hizmet paketi detay ve kapsam |
| `annex_payment_text` | Ek-3: Ödeme planı ve banka bilgileri |
| `print_header_html` | PDF başlık HTML'i |
| `print_footer_html` | PDF alt bilgi HTML'i |
| `notes` | Manager notları |

**Kural:** Firma başına tek aktif şablon (`is_active = true`).
**Yeni versiyon:** `save()` metodunda `new_version=true` gönderilirse — mevcut şablon `is_active=false`, yeni şablon `version+1` ile oluşturulur, `parent_version_id` ile zincirlenir.

---

## ContractTemplateService

**Dosya:** `app/Services/ContractTemplateService.php`

| Metod | Açıklama |
|-------|----------|
| `resolveActiveTemplate(int $companyId)` | Aktif şablonu getirir (yoksa varsayılan oluşturur) |
| `buildSnapshot($guest, $companyId)` | Şablon + guest verisini birleştirir → snapshot array |
| `buildSnapshotCached($guest, $companyId)` | `Cache::remember()` 45s cache'li snapshot |
| `buildPreviewVariables($guest)` | Manager önizlemesi için değişken listesi |
| `generatePdf($guest, $companyId)` | `barryvdh/laravel-dompdf` ile A4 PDF binary |
| `renderText(string $text, array $vars)` | `strtr()` ile `{{anahtar}}` → değer değiştirme |
| `buildVariables(GuestApplication $guest)` | Placeholder değişken map oluşturma |

### Placeholder Değişkenleri (38 adet)

`buildVariables()` şu kaynaklardan veri toplar:
- `Company` modeli — firma adı
- `MarketingAdminSetting` — ödeme, banka, yetkili kişi, adres
- `GuestApplication` — öğrenci bilgileri, seçilen paket, ekstra hizmetler
- `registration_form_draft` JSON — pasaport, doğum tarihi, adres, vasi bilgileri
- `now()` — sözleşme tarihi; sözleşme numarası: `MDE-{yıl}-{id:06}`

| Grup | Değişkenler |
|------|-------------|
| Sözleşme | `contract_number`, `contract_date` |
| Danışman | `advisor_company_name`, `advisor_company_address`, `advisor_tax_info`, `advisor_authorized_person`, `advisor_phone`, `advisor_email`, `advisor_website`, `jurisdiction_city` |
| Öğrenci | `student_full_name`, `student_id`, `student_email`, `student_phone`, `student_identity_no`, `student_birth_date`, `student_address` |
| Vasi | `guardian_full_name`, `guardian_identity_no`, `guardian_relation` |
| Başvuru | `application_country`, `application_type`, `education_level`, `max_university_count` |
| Paket | `package_name`, `service_total_price`, `service_scope`, `extra_services`, `tax_status` |
| Ödeme | `payment_plan`, `installment_1_amount`, `installment_2_date_or_condition`, `installment_2_amount`, `installment_3_date_or_condition`, `installment_3_amount`, `bank_name`, `bank_branch`, `bank_iban` |

---

## ContractTemplateController

**Dosya:** `app/Http/Controllers/Manager/ContractTemplateController.php`
**Route Prefix:** `/manager/contract-template`

| Metod | HTTP | Route | Açıklama |
|-------|------|-------|----------|
| `show()` | GET | `/manager/contract-template` | Aktif şablon + öğrenci sözleşme listesi (status filter + arama) |
| `save()` | POST | `/manager/contract-template` | Şablon kaydet veya yeni versiyon oluştur |
| `saveStudentServices()` | POST | `/manager/contract-template/services` | Guest için paket + ekstra servis seç |
| `startContract()` | POST | `/manager/contract-template/start` | Manuel sözleşme başlat (`not_requested/pending_manager/rejected` → `requested`) |
| `refreshSnapshot()` | POST | `/manager/contract-template/refresh-snapshot` | Aktif şablondan taslak yenile |
| `saveCompanySettings()` | POST | `/manager/contract-template/company-settings` | Firma ve `MarketingAdminSetting` kaydet |
| `decideContract()` | POST | `/manager/contract-template/decide` | Onay/red (`signed_uploaded` → `approved` veya `rejected`) |
| `cancelContract()` | POST | `/manager/contract-template/cancel` | Sözleşmeyi iptal et (kategori + sebep kodu zorunlu) |
| `streamContract()` | GET | `/manager/contract-template/stream/{guestId}` | PDF stream (tarayıcıda görüntüle) |
| `downloadContract()` | GET | `/manager/contract-template/download/{guestId}` | PDF indir |

### Show() Sayfa Verileri

| Veri | Açıklama |
|------|----------|
| `pendingManagerCount` | Hazırlanmayı bekleyen (pending_manager) |
| `requestedCount` | İmzalanmayı bekleyen (requested) |
| `signedUploadedCount` | Onaylanmayı bekleyen (signed_uploaded) |
| `reopenRequestedCount` | Yeniden açma talepleri |
| `totalConvertedCount` | Dönüştürülen toplam öğrenci |
| `contractEvents` | `SystemEventLog` sözleşme olayları (son 15) |
| `cancelReasons` | `config/contract_cancel_reasons.php` |
| `placeholders` | 38 placeholder kodu listesi |

### decideContract() Onay Mantığı

```
approve:
  → currentStatus === 'signed_uploaded' zorunlu
  → contract_signed_file_path boş olmamalı
  → contract_signed_at boş olmamalı
  → contract_status = 'approved'
  → converted_to_student = true
  → converted_student_id = 'STU-{id:08}'
  → lead_status = 'converted'
  → User::ROLE_GUEST → User::ROLE_STUDENT (email ile eşleştirme)
  → TaskAutomationService::markTasksDoneBySource() (3 source_type)

reject:
  → currentStatus === 'signed_uploaded' zorunlu
  → contract_status = 'rejected'
  → contract_approved_at = null
```

---

## Guest: Sözleşme İşlemleri

**Controller:** `App\Http\Controllers\Guest\WorkflowController`

| Metod | Route | Açıklama |
|-------|-------|----------|
| `requestContract()` | POST `/guest/contract/request` | `not_requested` → `pending_manager` |
| `withdrawContractRequest()` | POST `/guest/contract/withdraw` | `pending_manager` → `not_requested` (yalnızca manager başlatmadıysa) |
| `uploadSignedContract()` | POST `/guest/contract/sign` | `requested/rejected` → `signed_uploaded` (dosya yükleme) |
| `signContractDigital()` | POST `/guest/contract/sign-digital` | `requested/rejected` → `signed_uploaded` (dijital imza JSON) |
| `requestContractUpdate()` | POST `/guest/contract/request-update` | `requested/signed_uploaded/rejected` → `requested` (imzalı veriler sıfırlanır) |
| `requestReopen()` | POST `/guest/contract/reopen` | `cancelled` → `reopen_requested` |

### requestContract() Ön Koşullar

```php
// Sadece not_requested durumundan talep edilebilir
if ($currentStatus !== 'not_requested') → 422

// Snapshot manager tarafından startContract() ile üretilir
// Guest talep eder → pending_manager → manager sözleşmeyi hazırlayıp startContract() → requested
```

### uploadSignedContract()

```
validate: file (pdf/jpg/jpeg/png/doc/docx/webp, max 10MB, ValidFileMagicBytes)
path: guest-contracts/{guest_id}/{guestId}_contract_{Ymd_His}.{ext}
→ contract_status = 'signed_uploaded'
→ contract_signed_at = now()
→ contract_signed_file_path = path
→ TaskAutomationService::ensureSignedContractTask()
→ EventLogService::log('student_contract_signed_uploaded')
→ NotificationService → 'contract_signed'
```

---

## ContractAuditLog

**Model:** `App\Models\ContractAuditLog`
**Tablo:** `contract_audit_logs`

Her sözleşme durum geçişinde `ContractAuditLog::log()` statik metodu çağrılır:

| Alan | Açıklama |
|------|----------|
| `guest_application_id` | İlgili başvuru |
| `old_status` | Önceki durum |
| `new_status` | Yeni durum |
| `changed_by` | İşlemi yapan email |
| `note` | Açıklama notu |
| `ip` | İşlem IP adresi |

**EventLogService** ile çapraz kayıt — `event_type` prefixleri: `guest_contract_`, `manager_contract_`, `student_contract_`

---

## İptal Konfigürasyonu

**Dosya:** `config/contract_cancel_reasons.php`

`cancelContract()` validasyonu:
```php
'cancel_category'    → required, in: config anahtarları
'cancel_reason_code' → required, in: tüm kategori reason kodları
'cancel_note'        → required, max:2000
'cancel_attachment'  → nullable, file (pdf/img/doc, max 20MB)
```

---

## Görev Otomasyonu

`TaskAutomationService` sözleşme olaylarında görev oluşturur:

| Olay / source_type | Görev | Öncelik |
|--------------------|-------|---------|
| `guest_contract_requested` | Sözleşme hazırlama (operations) + Satış görüşmesi (marketing) | high |
| `guest_contract_signed_uploaded` | İmzalı sözleşme onayı (operations) | urgent |

**markTasksDoneBySource()** — onayda şu source_type'lar tamamlandı: `guest_contract_signed_uploaded`, `guest_contract_requested`, `guest_contract_sales_followup`

---

## Bildirimler

| Tetikleyici | source_type | Kanal |
|-------------|-------------|-------|
| `pending_manager` (guest talep) | `guest_contract_update` | in_app |
| `requested` (manager başlattı) | `manager_contract_started` | email + in_app |
| `signed_uploaded` (imzalandı) | `student_contract_signed_uploaded` | email + in_app |
| `approved` (onaylandı) | `manager_contract_approved` | email + in_app + whatsapp |
| `rejected` (reddedildi) | `manager_contract_rejected` | email + in_app |
| `cancelled` (iptal) | `manager_contract_cancelled` | email + in_app |

---

## Senior: Sözleşme Listesi

**Route:** `GET /senior/contracts`

- Atanmış öğrencilerin sözleşme durumu listesi
- `pending_manager` ve `signed_uploaded` durumlar önde
- Delegated manager yetkisi varsa onay/red aksiyonları

---

## Dijital İmza (V2 Roadmap)

Adapters mevcut: `DocuSignAdapter`, `HelloSignAdapter`, `PandaDocAdapter`

**V2'de eklenecek:**
- `GuestApplication.contract_status` akışına `esign_pending` → `esign_completed` adımı
- `ContractTemplateController::sendForESign()` metodu
- Webhook endpoint: `/webhooks/esign/{provider}`

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller (Manager) | `app/Http/Controllers/Manager/ContractTemplateController.php` |
| Controller (Guest) | `app/Http/Controllers/Guest/WorkflowController.php` |
| Service | `app/Services/ContractTemplateService.php` |
| Model (Şablon) | `app/Models/ContractTemplate.php` |
| Model (Audit) | `app/Models/ContractAuditLog.php` |
| Model (Snapshot) | `app/Models/GuestRegistrationSnapshot.php` |
| Config (İptal) | `config/contract_cancel_reasons.php` |
| Adapter (DocuSign) | `app/Services/Integrations/Adapters/ESign/DocuSignAdapter.php` |
| View (Manager) | `resources/views/manager/contract-template.blade.php` |
| View (Guest) | `resources/views/guest/contract.blade.php` |
| View (Senior) | `resources/views/senior/contracts.blade.php` |
| Migration | `database/migrations/2026_02_20_230000_create_contract_templates_table.php` |
