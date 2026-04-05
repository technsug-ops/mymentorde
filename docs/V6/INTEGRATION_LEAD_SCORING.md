# Entegrasyon: Lead Scoring & Senior Performans Analitik

---

## Amaç

Guest başvurularını otomatik olarak puanlandırarak önceliklendirme yapma ve senior danışmanların aylık performans anlık görüntülerini kayıt altına alma.

---

## Lead Scoring

### LeadScoreService

**Dosya:** `app/Services/LeadScoreService.php`
Her puan ekleme işlemi `GuestApplication.lead_score` alanını günceller ve tier hesaplar.

### Puanlama Faktörleri (8 faktör)

| Faktör | Açıklama | Puan Aralığı |
|--------|----------|--------------|
| `form_completed` | Kayıt formu tamamlandı | +20 |
| `documents_uploaded` | Belge yüklendi | +10 per belge |
| `contract_signed` | Sözleşme imzalandı | +30 |
| `package_selected` | Paket seçildi | +15 |
| `responded_quickly` | 24h içinde yanıt | +10 |
| `high_value_country` | Hedef ülke premium | +5 |
| `workflow_bonus` | Workflow node bonus | Konfigüre edilebilir |
| `dealer_referred` | Bayi referansı | +5 |

### Lead Score Tier Sistemi

| Tier | Puan Aralığı | Renk |
|------|-------------|------|
| `cold` | 0-25 | Gri |
| `warm` | 26-50 | Mavi |
| `hot` | 51-75 | Turuncu |
| `vip` | 76-100 | Altın |

`GuestApplication.lead_score_tier` her puan güncellemesinde otomatik hesaplanır.

### addScore() Kullanımı

```php
app(LeadScoreService::class)->addScore(
    $guestId,
    'form_completed',          // action_code
    ['source' => 'portal']     // meta
);
```

**WorkflowEngineService** `add_score` node tipinde bu servisi çağırır.

---

## Cron: leads:recalculate-scores

**Schedule:** Her gün `02:30`
**Amaç:** Tüm aktif başvuruların puanlarını yeniden hesapla (süresi dolan faktörleri düş, yeni etkileşimleri ekle).

---

## Pipeline Analitik (PipelineProgressService)

**Dosya:** `app/Services/PipelineProgressService.php`
Lead'lerin huni aşamaları arasındaki geçiş sürelerini ve dönüşüm oranlarını hesaplar.

**Marketing Admin'de kullanım:**
- `/mktg-admin/pipeline` — Pipeline genel görünümü
- `/mktg-admin/pipeline/conversion-time` — Dönüşüm süreleri
- `/mktg-admin/pipeline/value` — Pipeline değeri
- `/mktg-admin/pipeline/loss` — Kayıp analizi

---

## Senior Performance Snapshots

### SeniorPerformanceService

**Dosya:** `app/Services/SeniorPerformanceService.php`
Senior danışmanların aylık KPI'larını hesaplar ve `senior_performance_snapshots` tablosuna kaydeder.

### Hesaplanan Metrikler

| Metrik | Açıklama |
|--------|----------|
| `active_students` | Aktif öğrenci sayısı |
| `new_conversions` | Bu ay yeni öğrenciye dönüşen adaylar |
| `avg_response_hours` | Ortalama yanıt süresi |
| `documents_approved` | Onaylanan belge sayısı |
| `appointments_held` | Gerçekleşen randevu sayısı |
| `contracts_signed` | İmzalanan sözleşme sayısı |

### SQLite/MySQL Uyumluluk

`SeniorPerformanceService::dateDiffExpr()`:
```php
$isSqlite ? 'julianday(now()) - julianday(created_at)' : 'DATEDIFF(NOW(), created_at)'
```

---

## Cron: senior:snapshot-performance

**Schedule:** Her ayın 1'i `03:30`
**Tablo:** `senior_performance_snapshots`

Her senior için aylık snapshot oluşturur. Manager performans raporlarında kullanılır.

---

## Marketing Analitik Entegrasyonu

**Dosya:** `app/Services/Marketing/ExternalMetrics/ExternalMetricsSyncService.php`

Harici pazarlama metriklerini (`MarketingExternalMetric` tablosu) çeker ve KPI dashboard'ında gösterir.

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Service | `app/Services/LeadScoreService.php` |
| Service | `app/Services/PipelineProgressService.php` |
| Service | `app/Services/SeniorPerformanceService.php` |
| Service | `app/Services/Marketing/ExternalMetrics/ExternalMetricsSyncService.php` |
| Model | `app/Models/MarketingExternalMetric.php` |
| Migration | `database/migrations/*_create_senior_performance_snapshots_table.php` |
| Command | `leads:recalculate-scores` |
| Command | `senior:snapshot-performance` |
