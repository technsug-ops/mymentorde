# MentorDE — Sözleşme Süreci Teknik Dokümantasyonu

**Son güncelleme:** 2026-03-07
**Stack:** Laravel 12 / PHP 8.4 / MySQL

---

## 1. Genel Bakış

Sözleşme süreci, bir **Guest** başvurusunun **Onaylı Öğrenci** statüsüne geçişini yöneten, çok adımlı durum makinesidir.
Sürece 6 portal katılır: **Guest → Manager → Student · Senior · Dealer · Marketing Admin**

---

## 2. Durum Makinesi (Status Machine)

```
not_requested
      │
      ▼  (Guest: "Sözleşme Talep Et")
pending_manager
      │
      ▼  (Manager: "Sözleşmeyi Gönder / Manuel Başlat")
requested
      │
      ▼  (Guest: imzalı dosyayı yükler ve gönderir)
signed_uploaded
      │
      ├─► approved   (Manager: Onayla)
      ├─► rejected   (Manager: Reddet → guest yeniden yükler → signed_uploaded'a döner)
      └─► cancelled  (Manager: herhangi bir aktif durumdan iptal edebilir)
```

### Status Açıklamaları

| Status | Türkçe Etiketi | Tetikleyen |
|---|---|---|
| `not_requested` | Talep Edilmedi | — (başlangıç) |
| `pending_manager` | Danışman Hazırlıyor | Guest → `requestContract()` |
| `requested` | Sözleşme Gönderildi — İmza Bekleniyor | Manager → `startContract()` |
| `signed_uploaded` | İmzalı Dosya Yüklendi | Guest → `uploadSignedContract()` |
| `approved` | Onaylandı | Manager → `decideContract(approve)` |
| `rejected` | Reddedildi | Manager → `decideContract(reject)` |
| `cancelled` | İptal Edildi | Manager → `cancelContract()` |

---

## 3. Veritabanı — `guest_applications` Tablosu

### Sözleşme Alanları

| Kolon | Tip | Açıklama |
|---|---|---|
| `contract_status` | string | Mevcut durum (yukarıdaki 7 değerden biri) |
| `contract_requested_at` | timestamp | Guest'in ilk talep zamanı |
| `contract_template_id` | FK int | Kullanılan şablonun ID'si |
| `contract_template_code` | string | Şablon kodu (snapshot için) |
| `contract_snapshot_text` | text | Sözleşme metni snapshot'ı (Manager gönderdiğinde oluşur) |
| `contract_annex_kvkk_text` | text | Ek-1 KVKK metni |
| `contract_annex_commitment_text` | text | Ek-2 Taahhütname metni |
| `contract_generated_at` | timestamp | Snapshot oluşturulma zamanı |
| `contract_signed_file_path` | string | Guest'in yüklediği imzalı dosyanın storage path'i |
| `contract_signed_at` | timestamp | İmzalı dosya yükleme zamanı |
| `contract_approved_at` | timestamp | Manager onay zamanı |
| `contract_cancel_category` | string(64) | İptal ana kategorisi (config key) |
| `contract_cancel_reason_code` | string(64) | İptal neden kodu |
| `contract_cancel_note` | text | İptal açıklaması |
| `contract_cancel_attachment_path` | string | İptal eki dosya path'i |
| `contract_cancelled_at` | timestamp | İptal zamanı |
| `contract_cancelled_by` | string(180) | İptal eden kullanıcı email'i |

### İlgili Diğer Alanlar (Sözleşme Sürecini Etkiler)

| Kolon | Açıklama |
|---|---|
| `converted_to_student` | bool — Manager onayı sonrası true yapılır |
| `converted_student_id` | string — Atanan öğrenci ID'si (örn: STU-00000001) |
| `selected_package_code/title/price` | Sözleşmede yer alan paket bilgileri |
| `selected_extra_services` | JSON — Ek hizmetler |
| `package_selected_at` | Paket seçim zamanı |

---

## 4. Migrations


| Dosya | İçerik |
|---|---|
| `2026_02_15_000011_create_guest_applications_table.php` | Ana tablo |
| `2026_02_16_000340_add_guest_contract_and_extras_fields.php` | contract_* alanları |
| `2026_02_20_230000_create_contract_templates_table.php` | ContractTemplate tablosu |
| `2026_02_20_230100_add_contract_snapshot_fields_to_guest_applications.php` | Snapshot alanları |
| `2026_02_21_120000_add_student_notification_channels_and_contract_indexes.php` | İndeksler |
| `2026_03_07_100000_add_contract_cancel_fields_to_guest_applications.php` | İptal alanları |

---

## 5. Model: `GuestApplication`

**İlgili cast'lar:** `selected_extra_services` → array, tüm `_at` alanları → Carbon
**İlgili scope'lar:** `forCompany($companyId)`

---

## 6. Config Dosyaları

### `config/contract_cancel_reasons.php`
4 ana kategori, 12 neden kodu — iptal analizi için kayıp segmentasyonu:

| Kategori Key | Başlık | Neden Kodları |
|---|---|---|
| `student` | Öğrenci Kaynaklı | AKAD_YETER, DIL_EKSIK, FINANS, VAZGEC, SAHTE_EVRAK |
| `institution` | Resmi Kurum Kaynaklı | VIZE_REDDI, KABUL_ALAMAMA, MEVZUAT |
| `firm` | Danışmanlık Firması Kaynaklı | TARIH_KACIR, HIZMET_IHLAL |
| `legal` | Hukuki ve Finansal | ODEME_AKSATMA, MUCBIR_SEBEP |

---

## 7. Servisler

### `ContractTemplateService`
- `resolveActiveTemplate($companyId)` — aktif şablonu getirir
- `buildSnapshot($guest, $companyId)` — değişken yerine koyarak snapshot oluşturur
- `buildSnapshotCached($guest, $companyId)` — cache'li önizleme
- `buildPreviewVariables($guest)` — `{{placeholder}}` değişken haritası
- `renderText($text, $vars)` — placeholder'ları metne işler

**Değişkenler (`placeholders`):**
`contract_date`, `advisor_company_name`, `advisor_company_address`, `advisor_tax_info`,
`student_full_name`, `student_id`, `student_email`, `student_phone`, `student_identity_no`,
`application_country`, `application_type`, `service_total_price`, `service_scope`,
`payment_plan`, `jurisdiction_city`

### `TaskAutomationService`
- `ensureContractReviewTask($guest)` — sözleşme inceleme görevi oluşturur
- `ensureSignedContractTask($guest)` — imzalı sözleşme yükleme görevi oluşturur
- `markTasksDoneBySource($source, $guestId)` — ilgili görevleri tamamlanmış işaretler

### `EventLogService`
Sözleşme sürecinde kaydedilen event tipleri:
- `guest_contract_requested`
- `guest_contract_signed_uploaded`
- `manager_contract_started`
- `manager_contract_approved`
- `manager_contract_rejected`
- `manager_contract_cancelled`

---

## 8. Controller'lar ve Metodlar

### `App\Http\Controllers\Guest\WorkflowController`

| Metod | Route | Açıklama |
|---|---|---|
| `requestContract()` | POST `/guest/contract/request` | `not_requested` → `pending_manager`. Snapshot oluşturmaz. |
| `uploadSignedContract()` | POST `/guest/contract/upload-signed` | `requested\|rejected` → `signed_uploaded`. Dosyayı `storage/app/public/guest-contracts/{id}/` altına kaydeder. `DocumentCategory::firstOrCreate('SOZLESME_IMZALI')` + `Document::create()` ile öğrenci kartına belge ekler. |
| `requestContractUpdate()` | POST `/guest/contract/update-request` | `requested\|signed_uploaded\|rejected` durumunda paket/hizmet değişikliği talebi. |

### `App\Http\Controllers\Guest\PortalController`

| Metod | Route | Açıklama |
|---|---|---|
| `contract()` | GET `/guest/contract` | Sözleşme sayfası — tüm contractUi verilerini view'a gönderir. |
| `contractSignedThanks()` | GET `/guest/contract/signed-thanks` | İmzalı dosya yükledikten sonra gösterilen teşekkür sayfası. |

### `App\Http\Controllers\Manager\ContractTemplateController`

| Metod | Route | Açıklama |
|---|---|---|
| `show()` | GET `/manager/contract-template` | Öğrenci listesi + sözleşme detayı + template editörü. |
| `startContract()` | POST `.../start-contract` | `not_requested\|pending_manager\|rejected` → `requested`. Snapshot oluşturulur. |
| `decideContract()` | POST `.../decision` | `approve` → `approved` (öğrenciye bildirim). `reject` → `rejected`. |
| `cancelContract()` | POST `.../cancel` | Herhangi bir aktif durumdan → `cancelled`. Kategori + neden + not + dosya kaydeder. |
| `saveStudentServices()` | POST `.../student-services` | Manager paket/hizmet ataması. |
| `saveCompanySettings()` | POST `.../company-settings` | Firma bilgilerini günceller (sözleşme değişkenlerine yansır). |

### `App\Http\Controllers\StudentPortalController`

| Metod | İlgili Kısım | Açıklama |
|---|---|---|
| `contract()` | GET `/student/contract` | `contractUiState()` ile UI durumu hesaplanır. |
| `contractUiState()` | private | `canRequestAddendum`, `showSnapshotPanel`, `showCurrentContractPanel`, `canOpenSignedFile`, `inconsistencies` gibi UI flag'lerini döner. |
| `addendumRequest()` | POST `/student/contract/addendum-request` | Değişiklik/ek talebi — Operations ticket'ına dönüşür. |
| `downloadSignedContract()` | GET `/student/contract/download-signed` | `approved` durumunda imzalı dosyayı indir. |

### `App\Http\Controllers\StudentDashboardController`

- `index()` — `$guestApplication->contract_approved_at` 30 gün içindeyse view'a hoş geldin banner tetikler.

---

## 9. Route'lar

### Guest Grubu (`/guest`, middleware: `guest.role`)
```
GET  /guest/contract                  → guest.contract
POST /guest/contract/request          → guest.contract.request
POST /guest/contract/update-request   → guest.contract.update-request
POST /guest/contract/upload-signed    → guest.contract.upload-signed
GET  /guest/contract/signed-thanks    → guest.contract.signed-thanks
```

### Manager Grubu (`/manager`, middleware: `manager.or.permission:student.assignment.manage`)
```
GET  /manager/contract-template                     → manager.contract-template.show
POST /manager/contract-template                     → manager.contract-template.save
POST /manager/contract-template/student-services    → manager.contract-template.student-services
POST /manager/contract-template/start-contract      → manager.contract-template.start-contract
POST /manager/contract-template/decision            → manager.contract-template.decision
POST /manager/contract-template/cancel              → manager.contract-template.cancel
POST /manager/contract-template/company-settings    → manager.contract-template.company-settings
```

### Student Grubu (`/student`, middleware: `student.role`)
```
GET  /student/contract                              → student.contract
POST /student/contract/addendum-request             → student.contract.addendum-request
GET  /student/contract/download-signed              → student.contract.download-signed
```

---

## 10. View Dosyaları

| View | Portal | Açıklama |
|---|---|---|
| `guest/contract.blade.php` | Guest | Süreç takibi, talep butonu, imzalı yükleme, sözleşme metni |
| `guest/contract-signed-thanks.blade.php` | Guest | Upload sonrası teşekkür/bilgilendirme sayfası |
| `student/contract.blade.php` | Student | Durum özeti, mevcut sözleşme, ek talep formu |
| `manager/contract-template.blade.php` | Manager | Şablon editörü + öğrenci sözleşme yönetimi + iptal paneli |
| `student/dashboard.blade.php` | Student | Yeni öğrenci hoş geldin banner'ı (`contract_approved_at` ≤ 30 gün) |

---

## 11. Dosya Depolama

| Dosya Türü | Storage Path | Disk |
|---|---|---|
| Guest'in yüklediği imzalı sözleşme | `guest-contracts/{guest_id}/signed_contract_YYYYMMDD_HHmmss.{ext}` | `public` |
| İptal eki belgesi | `contract-cancellations/{guest_id}/contract_cancel_YYYYMMDD_HHmmss.{ext}` | `public` |

---

## 12. Belge Entegrasyonu (Documents Tablosu)

Guest imzalı sözleşme yüklediğinde (`uploadSignedContract()`):
- `DocumentCategory::firstOrCreate(['code' => 'SOZLESME_IMZALI'])` ile kategori güvence altına alınır
- `Document::create(...)` ile belge kaydı oluşturulur
- `document_id` formatı: `DOC-CONTRACT-{6 haneli sıralı numara}`
- `student_id`: `converted_student_id` varsa onu kullanır, yoksa `GST-{8 haneli guest id}`
- Bu kayıt sayesinde belge → Senior belge görünürlüğü, Manager öğrenci kartı entegrasyonu sağlanır

---

## 13. NormalizeContractStatus — 3 Kontrolörde Tutulur

Geçerli status değerleri (her üç kontrolörde de aynı liste):
```php
['not_requested', 'pending_manager', 'requested', 'signed_uploaded', 'approved', 'rejected', 'cancelled']
```

**Bulunduğu dosyalar:**
- `app/Http/Controllers/Guest/WorkflowController.php`
- `app/Http/Controllers/Manager/ContractTemplateController.php`
- `app/Http/Controllers/StudentPortalController.php`

---

## 14. İptal Analizi ve Segmentasyon

İptal edilen sözleşmelerin raporlanması için `guest_applications` üzerinden sorgu:
```sql
SELECT
    contract_cancel_category,
    contract_cancel_reason_code,
    COUNT(*) as total,
    MIN(contract_cancelled_at) as first,
    MAX(contract_cancelled_at) as last
FROM guest_applications
WHERE contract_status = 'cancelled'
GROUP BY contract_cancel_category, contract_cancel_reason_code
ORDER BY total DESC;
```

---

## 15. Etkilenen Diğer Modüller

| Modül | Etki |
|---|---|
| **Senior Portal → Servisler** | `approved` durumunda hizmet kalemleri fiyatsız gösterilir |
| **Dealer → Lead Detail** | `is_visible_to_dealer = true` belgeler görünür (imzalı sözleşme dahil olabilir) |
| **Task Board** | `ensureContractReviewTask` / `ensureSignedContractTask` ile görevler otomatik oluşur |
| **Notification Dispatch** | Onay sonrası öğrenciye bildirim + email |
| **System Event Log** | Tüm contract_* olayları `system_event_logs` tablosuna kaydedilir |
| **Student Card** | Manager öğrenci kartında imzalı sözleşme belgesi görünür |
| **GDPR** | SoftDeletes aktif — `gdpr:enforce-retention` imzalı dosyaları da kapsar |
| **Marketing Analytics** | İptal nedeni kodları ileride kayıp analizi segmentlerinde kullanılabilir |
