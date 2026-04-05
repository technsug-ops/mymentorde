# Modül: Manager Portal

---

## Amaç

Manager portal, sistem yöneticilerinin tüm öğrenci, bayi, senior, guest süreçlerini izleyip yönettiği merkezi kontrol panelidir. RBAC yönetimi, raporlama, sözleşme onayı, konfigürasyon ve analitik işlemleri bu portal üzerinden yürütülür.

---

## URL & Controller'lar

| Controller | URL Prefix | Açıklama |
|-----------|-----------|----------|
| `ManagerDashboardController` | `/manager` | Ana dashboard, KPI, raporlar |
| `Manager\ManagerPortalController` | `/manager/*` | Öğrenci/senior/bayi/guest listesi, detay |
| `Manager\ContractTemplateController` | `/manager/contract-template` | Sözleşme şablonu yönetimi |
| `Manager\ThemeController` | `/manager/theme` | Portal tema konfigürasyonu |
| `ManagerPortalPreviewController` | `/manager/portal-preview` | Portal önizleme |
| `ManagerRequestController` | `/manager/requests` | Manager talep sistemi |

---

## Dashboard KPI'ları

**Controller:** `ManagerDashboardController::index()`
**Service:** `DashboardKPIService` (Cache 300s)

### Filtreleme Parametreleri
- `month_start` / `month_end` — tarih aralığı
- `senior` — belirli senior filtresi
- Snapshot filter — performans snapshot karşılaştırması

### DashboardKPIService Metodları (Cache 300s)

| Metod | Açıklama |
|-------|----------|
| `managerStatsAndFunnel()` | Temel istatistikler + funnel dönüşüm |
| `managerTrend()` | Aylık trend verileri |
| `seniorPerformance()` | Senior başına performans metrikleri |
| `pendingApprovals()` | FieldRuleApproval + bekleyen sözleşmeler |
| `dmSummary()` | Okunmamış DM sayısı |

### Hesaplanan Metrikler

| Metrik | Açıklama |
|--------|----------|
| Aktif öğrenci sayısı | Arşivlenmemiş student assignments |
| Yeni guest başvuru | Seçilen ay içinde oluşturulan guest_applications |
| Dönüşüm oranı | guest → student dönüşüm % |
| Gelir toplamı | StudentRevenue + DealerStudentRevenue |
| Bekleyen onaylar | FieldRuleApproval + imzalı sözleşmeler |
| GDPR uyum | ConsentRecord + DataRetentionPolicy durumu |
| Sistem event log | SystemEventLog son kayıtlar |
| Senior performans snapshot | SeniorPerformanceSnapshot aylık karşılaştırma |

### CSV Export
`GET /manager/export-csv` — tüm dashboard verisini CSV olarak indir.

---

## Sayfalar

| Sayfa | Route | Açıklama |
|-------|-------|----------|
| Dashboard | `/manager` | KPI kartları + grafik |
| Öğrenciler | `/manager/students` | Filtrelenmiş öğrenci listesi |
| Öğrenci Detay | `/manager/students/{id}` | 360° öğrenci profil |
| Seniorlar | `/manager/seniors` | Senior listesi + atama bilgisi |
| Senior Detay | `/manager/seniors/{id}` | KPI + öğrenci listesi |
| Bayiler | `/manager/dealers` | Bayi listesi |
| Bayi Detay | `/manager/dealers/{id}` | Komisyon + lead istatistikleri |
| Guestler | `/manager/guests` | Aday başvuru listesi |
| Guest Detay | `/manager/guests/{id}` | Başvuru detayı + belge durumu |
| Sözleşme Şablonu | `/manager/contract-template` | Aktif şablon + versiyon geçmişi |
| Raporlar | `/manager/reports` | Aylık yönetim raporu |
| Rapor Print | `/manager/reports/{id}/print` | Yazdırılabilir rapor |
| Snapshot | `/manager/snapshots/{id}` | Öğrenci process snapshot |
| Tema | `/manager/theme` | Portal renk/logo ayarı |
| Talepler | `/manager/requests` | Manager request sistemi |
| GDPR Dashboard | `/manager/gdpr-dashboard` | GDPR uyum merkezi |
| Komisyonlar | `/manager/commissions` | Komisyon hesaplama |

---

## Öğrenci & Senior Yönetimi

### Öğrenci Listesi (StudentListService)
- `filteredQuery()` — branch, risk level, payment_status, senior filtreleri
- `kpis()` — aktif/arşivlenen/yeni öğrenci sayıları
- `filterOptions()` — dropdown seçenekleri

### Senior Atama
- Senior → öğrenci ataması `student_assignments` tablosu
- Transfer işlemi `AuditTrail` observer ile loglanır

---

## Sözleşme Süreci

`ContractTemplateController` detayı: [INTEGRATION_CONTRACT.md](INTEGRATION_CONTRACT.md)

**Onay Akışı:**
1. Guest sözleşme talebinde bulunur
2. Manager şablonu hazırlar/portale yükler
3. Guest imzalar ve yükler (`signed_uploaded`)
4. Manager onaylar (`approved`) → öğrenci statüsüne geçiş

---

## RBAC & İzin Yönetimi

**Controller:** `Api\RbacController`

- Rol şablonu oluşturma/düzenleme
- Kullanıcıya bireysel izin atama
- `permissionUsageReport()` — izin kullanım analizi

Detay: [INTEGRATION_RBAC_SECURITY.md](INTEGRATION_RBAC_SECURITY.md)

---

## Raporlama

### ManagerReport
- `manager_reports` tablosu
- Seçilen ay + filtreler → PDF/print view
- `sent_to` alanı ile email gönderimi

### Scheduled Reports
- `ManagerScheduledReport` modeli
- Periyodik otomatik rapor gönderimi

### Alert Rules
- `ManagerAlertRule` modeli
- Eşik değer aşıldığında bildirim

### Performance Targets
- `ManagerPerformanceTarget` modeli
- Senior/bayi için hedef KPI tanımı

---

## GDPR Dashboard

**Route:** `GET /manager/gdpr-dashboard`

- Aktif consent kayıtları
- Data retention politika listesi
- Bekleyen erasure talepleri
- GDPR: Art.17 anonimleştirme + Art.20 export

Detay: [INTEGRATION_GDPR.md](INTEGRATION_GDPR.md)

---

## Manager Request Sistemi

**Model:** `ManagerRequest`
**Route:** `/manager/requests`

- Request types: `finance`, `operations`, `approval`, `advisory`, `system`, `marketing`
- `TaskAutomationService::ensureManagerRequestTask()` — otomatik görev oluşturma
- Priority: `low / normal / high / urgent`

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller (Dashboard) | `app/Http/Controllers/ManagerDashboardController.php` |
| Controller (Portal) | `app/Http/Controllers/Manager/ManagerPortalController.php` |
| Controller (Sözleşme) | `app/Http/Controllers/Manager/ContractTemplateController.php` |
| Controller (Tema) | `app/Http/Controllers/Manager/ThemeController.php` |
| Service (KPI) | `app/Services/DashboardKPIService.php` |
| Service (Öğrenci) | `app/Services/StudentListService.php` |
| Layout | `resources/views/manager/layouts/app.blade.php` |
| Dashboard View | `resources/views/manager/dashboard.blade.php` |
