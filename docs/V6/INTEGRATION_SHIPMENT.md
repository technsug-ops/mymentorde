# Entegrasyon: Kargo Takip Sistemi

---

## Amaç

Senior danışmanların öğrencilere gönderilen veya öğrencilerden alınan kargoları (pasaport, vize, sözleşme, diploma vb.) sisteme kaydetmesi ve öğrencilerin kendi kargolarını takip edebilmesi.

---

## Veri Modeli

**Model:** `app/Models/StudentShipment.php`
**Tablo:** `student_shipments`
**Trait'ler:** `SoftDeletes`

### Alanlar

| Alan | Tip | Açıklama |
|------|-----|----------|
| `company_id` | string(64) | Firma bağlantısı |
| `student_id` | string(64) | Öğrenci ID |
| `direction` | enum | `outgoing` (öğrenciye) / `incoming` (öğrenciden) |
| `carrier` | string | Kargo firması |
| `tracking_number` | string | Takip numarası |
| `content_description` | string | İçerik açıklaması |
| `sent_at` | date | Gönderim tarihi |
| `estimated_delivery` | date | Tahmini teslim |
| `delivered_at` | date | Gerçek teslim tarihi |
| `status` | enum | Kargo durumu |
| `notes` | text | Notlar |
| `is_visible_to_student` | boolean | Öğrenci görebilir mi? |
| `added_by` | unsignedBigInteger | Ekleyen senior ID |

---

## Kargo Durumları

| Durum | Etiket | Badge |
|-------|--------|-------|
| `preparing` | Hazırlanıyor | `pending` (sarı) |
| `shipped` | Gönderildi | `info` (mavi) |
| `in_transit` | Transfer | `info` (mavi) |
| `delivered` | Teslim Edildi | `ok` (yeşil) |
| `returned` | İade | `warn` (turuncu) |
| `lost` | Kayıp | `danger` (kırmızı) |

---

## Desteklenen Kargo Firmaları

| Kod | Etiket |
|-----|--------|
| `PTT` | PTT |
| `DHL` | DHL |
| `UPS` | UPS |
| `Yurtiçi` | Yurtiçi Kargo |
| `FedEx` | FedEx |
| `other` | Diğer |

---

## Senior: Kargo Yönetimi

**URL:** `/senior/shipments`
**Controller:** `app/Http/Controllers/SeniorPortalController.php`

| Metod | Route | Açıklama |
|-------|-------|----------|
| `shipments()` | `GET /senior/shipments` | Atanan öğrenci filtreli liste + paginate(40) |
| `shipmentStore()` | `POST /senior/shipments` | Yeni kargo ekle |
| `shipmentUpdate()` | `PUT /senior/shipments/{id}` | Durum/tarih/not güncelle |
| `shipmentDelete()` | `DELETE /senior/shipments/{id}` | Soft delete |
| `shipmentToggleVisibility()` | `POST /senior/shipments/{id}/toggle-visibility` | Öğrenci görünürlük toggle |

**Validation:**
- `student_id` → senior'ın atanmış öğrencileri arasında olmalı
- `carrier`, `tracking_number`, `content_description`, `direction`, `status` zorunlu

---

## Student: Kargo Görüntüleme

**URL:** `/student/shipments`
**Controller:** `app/Http/Controllers/StudentPortalController.php`

```php
StudentShipment::where('student_id', $studentId)
    ->visibleToStudent()   // is_visible_to_student = true
    ->orderBy('sent_at', 'desc')
    ->get()
```

Öğrenci sadece `is_visible_to_student = true` olan kargoları görür. Durum güncelleme yapamaz (read-only).

---

## Görünürlük Akışı

```
Senior → kargo ekle (is_visible_to_student = false varsayılan)
       → toggle visibility → is_visible_to_student = true
             → Öğrenci /student/shipments'te görür
```

---

## Index Yapısı

**Migration:** `database/migrations/2026_03_11_100000_create_student_shipments_table.php`

```sql
INDEX student_shipments_student_id_index (student_id)
INDEX student_shipments_student_visible_index (student_id, is_visible_to_student)
```

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Model | `app/Models/StudentShipment.php` |
| Controller (Senior) | `app/Http/Controllers/SeniorPortalController.php` |
| Controller (Student) | `app/Http/Controllers/StudentPortalController.php` |
| View (Senior) | `resources/views/senior/shipments.blade.php` |
| View (Student) | `resources/views/student/shipments.blade.php` |
| JS | `public/js/shipments.js` |
| Migration | `database/migrations/2026_03_11_100000_create_student_shipments_table.php` |
