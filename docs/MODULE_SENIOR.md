# Modül: Senior Portal

---

## Amaç

Senior (danışman) portalı, Almanya'daki yükseköğretim süreçlerini yöneten danışmanların öğrenci takip, belge yönetimi, randevu, sözleşme ve kargo işlemlerini gerçekleştirdiği portaldır.

---

## URL & Controller'lar

| Controller | URL Prefix | Açıklama |
|-----------|-----------|----------|
| `SeniorDashboardController` | `/senior` | Dashboard + Document Builder |
| `SeniorPortalController` | `/senior/*` | Tüm senior portal işlemleri |

---

## Dashboard (Smart Command Center)

**Controller:** `SeniorDashboardController::index()`
**KPI servisi:** `DashboardKPIService::seniorKPIs($userId, $seniorEmail)` — Cache 300s

### Cached KPI'lar (DashboardKPIService — 5 dk TTL)
| KPI | Açıklama |
|-----|----------|
| `activeStudentCount` | Atanmış aktif öğrenci |
| `archivedStudentCount` | Arşivlenmiş öğrenci |
| `pendingApprovalCount` | Onay bekleyen öğe |
| `taskSummary` | Görev özeti (todo/in_progress/done sayıları) |
| `dmSummary` | Okunmamış mesaj sayısı |

### Real-time Veriler (cache yok)
| Veri | Açıklama |
|------|----------|
| `recentStudents` | Son 10 öğrenci (risk, payment status) |
| `recentOutcomes` | Son 8 process outcome |
| `recentNotes` | Son 8 internal note |
| `recentNotifications` | Son 8 bildirim gönderimi |
| `recentTasks` | Son 8 atanmış görev |
| `pendingContracts` | Sözleşme bekleyen öğrenciler |
| `banners` | CMS `senior_banner` kategorisi |

### Smart Command Center (K1)
| Widget | Açıklama |
|--------|----------|
| `todayAppointments` | Bugünün randevuları |
| `todayTasks` | Bugün vadesi dolacak görevler |
| `pendingTickets` | Açık destek talepleri |
| `riskRadar` | Yüksek/kritik risk öğrenciler |
| `criticalActions` | Toplu aksiyon gereken öğeler (5 kaynak) |
| `weeklyPerformance` | Bu hafta: outcome + onaylanan belge + tamamlanan görev |

**Critical Actions Kaynakları:**
1. Geciken tasklar
2. Bekleyen sözleşmeler
3. İnceleme bekleyen belgeler
4. Okunmamış DM mesajları
5. Açık ticketlar

---

## Sayfalar

| Sayfa | Route | Açıklama |
|-------|-------|----------|
| Dashboard | `/senior` | Smart Command Center |
| Öğrenciler | `/senior/students` | Atanmış öğrenci listesi |
| Öğrenci 360° | `/senior/students/{id}` | 9-tab öğrenci detay |
| Süreç Takip | `/senior/process-tracking` | Process outcome listesi |
| Belgeler | `/senior/documents` | Öğrenci belge yönetimi |
| Batch Review | `/senior/batch-review` | Toplu belge inceleme (A/R/N klavye) |
| Pipeline Kanban | `/senior/pipeline` | 6 sütunlu drag-drop kanban |
| Randevular | `/senior/appointments` | Randevu takvimi + takvim entegrasyonu |
| Sözleşmeler | `/senior/contracts` | Sözleşme listesi + onay |
| Mesajlar | `/senior/messages` | DM split-panel |
| Notlar | `/senior/notes` | Internal note listesi |
| Ticketlar | `/senior/tickets` | Öğrenci ticket listesi |
| Vault | `/senior/vault` | Şifreli hesap erişim kasası |
| Kargo | `/senior/shipments` | Kargo takip sistemi |
| Üniversite Başvuruları | `/senior/university-applications` | Üniversite başvuru takibi |
| Kurumsal Belgeler | `/senior/institution-documents` | Kurum belge takibi |
| Document Builder | `/senior/document-builder` | CV/motivasyon üretici |
| Malzemeler | `/senior/materials` | Eğitim materyalleri |
| Bilgi Tabanı | `/senior/knowledge-base` | KB makale listesi |
| Performans | `/senior/performance` | Aylık performans raporu |
| Ayarlar | `/senior/settings` | Profil + bildirim tercihleri |

---

## Öğrenci 360° Profil (K1)

**9 Tab yapısı:**
1. Genel Bilgi — kayıt form draft, kişisel bilgiler
2. Belgeler — yüklenmiş belgeler + durum
3. Process Outcomes — akış adımları
4. Notlar — internal notes (pinlenen önce)
5. Randevular — takvim görünümü
6. Mesajlar — DM kanalı
7. Sözleşme — sözleşme durumu
8. Kargo — gelen/giden kargolar
9. Üniversite Başvuruları — başvuru listesi

---

## Batch Review (K1)

**Route:** `/senior/batch-review`

- Atanmış öğrencilere ait `status = 'uploaded'` belgeler
- Klavye kısayolları: `A` (approve), `R` (reject), `N` (next)
- Toplu onay/ret işlemi
- Verimliliği ~%40 artırır

---

## Pipeline Kanban (K2)

**Route:** `/senior/pipeline`

6 sütun:
1. Başvuru Formu (applied)
2. Ön Değerlendirme (pre_review)
3. Belge Toplama (doc_collection)
4. Sözleşme (contract)
5. Üniversite Başvuru (uni_application)
6. Tamamlandı (completed)

- HTML5 drag-drop
- GuestApplication/StudentAssignment listesi

---

## Document Builder

**Controller:** `SeniorDashboardController::documentBuilder()` ve `generateDocumentBuilderFile()`

Detay: [INTEGRATION_DOCUMENT_BUILDER.md](INTEGRATION_DOCUMENT_BUILDER.md)

---

## Kargo Takip

**Routes:** `GET/POST/PUT/DELETE /senior/shipments`

Detay: [INTEGRATION_SHIPMENT.md](INTEGRATION_SHIPMENT.md)

---

## Takvim Entegrasyonu

**Route:** `POST /senior/appointments`

Detay: [INTEGRATION_CALENDAR.md](INTEGRATION_CALENDAR.md)

---

## Senior Reminders (K2)

**Command:** `senior:send-reminders`
**Schedule:** Her gün 08:30

Hatırlatıcı türleri:
- Bugün vadesi dolan görevler
- Geciken belgeler
- Yaklaşan randevular (24h)
- Yanıtsız ticketlar (48h)

---

## Performans Hedefleri (K2)

**Route:** `/senior/performance`

- `SeniorPerformanceService` — aylık KPI hesaplama
- `senior_performance_snapshots` tablosu
- Metrикler: aktif öğrenci, yeni dönüşüm, ortalama yanıt süresi, onaylanan belge, randevu, imzalanan sözleşme

---

## Şablon Yanıtlar (K2)

- Hızlı mesaj şablonları (DM)
- Ticket yanıt şablonları
- `MessageTemplate` modeli

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller (Dashboard) | `app/Http/Controllers/SeniorDashboardController.php` |
| Controller (Portal) | `app/Http/Controllers/SeniorPortalController.php` |
| Service (KPI) | `app/Services/DashboardKPIService.php` |
| Service (Performance) | `app/Services/SeniorPerformanceService.php` |
| Layout | `resources/views/senior/layouts/app.blade.php` |
| Dashboard View | `resources/views/senior/dashboard.blade.php` |
| Sidebar | `resources/views/senior/layouts/_partials/sidebar.blade.php` |
