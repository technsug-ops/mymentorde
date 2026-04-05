# Modül: Marketing Admin Portal

---

## Amaç

Marketing Admin portalı, pazarlama kampanyaları, CMS içerikleri, e-posta pazarlama, sosyal medya, lead kaynağı takibi, etkinlik yönetimi ve sales pipeline analizini kapsayan kapsamlı bir pazarlama yönetim sistemidir.

---

## Roller

| Rol | Erişim |
|-----|--------|
| `marketing_admin` | Tam erişim |
| `sales_admin` | Sales dashboard + pipeline |
| `marketing_staff` | Kampanya + içerik (kısıtlı) |
| `sales_staff` | Lead + pipeline (kısıtlı) |

**Panel Modu:** `session('mktg_panel_mode') === 'sales'` → Sales dashboard

---

## URL & Controller'lar

| Controller | URL Prefix | Açıklama |
|-----------|-----------|----------|
| `MarketingAdmin\DashboardController` | `/mktg-admin/dashboard` | Ana dashboard + sales view |
| `MarketingAdmin\CampaignController` | `/mktg-admin/campaigns` | Kampanya CRUD |
| `MarketingAdmin\CMSContentController` | `/mktg-admin/content` | CMS içerik yönetimi |
| `MarketingAdmin\CMSCategoryController` | `/mktg-admin/content/categories` | İçerik kategorileri |
| `MarketingAdmin\CMSMediaController` | `/mktg-admin/content/media` | Medya kütüphanesi |
| `MarketingAdmin\EmailCampaignController` | `/mktg-admin/email/campaigns` | E-posta kampanyaları |
| `MarketingAdmin\EmailSegmentController` | `/mktg-admin/email/segments` | E-posta segmentleri |
| `MarketingAdmin\EmailTemplateController` | `/mktg-admin/email/templates` | E-posta şablonları |
| `MarketingAdmin\EventController` | `/mktg-admin/events` | Etkinlik yönetimi |
| `MarketingAdmin\SocialPostController` | `/mktg-admin/social/posts` | Sosyal medya gönderileri |
| `MarketingAdmin\SocialAccountController` | `/mktg-admin/social/accounts` | Sosyal hesaplar |
| `MarketingAdmin\SocialMetricsController` | `/mktg-admin/social/metrics` | Sosyal metrikler |
| `MarketingAdmin\KPIReportController` | `/mktg-admin/kpi` | KPI raporları |
| `MarketingAdmin\SalesPipelineController` | `/mktg-admin/pipeline` | Pipeline analizi |
| `MarketingAdmin\LeadSourceController` | `/mktg-admin/lead-sources` | Lead kaynağı yönetimi |
| `MarketingAdmin\TrackingLinkController` | `/mktg-admin/tracking-links` | UTM link takibi |
| `MarketingAdmin\TaskController` | `/mktg-admin/tasks` | Marketing görevleri |
| `MarketingAdmin\TeamController` | `/mktg-admin/team` | Ekip yönetimi |
| `MarketingAdmin\DealerRelationsController` | `/mktg-admin/dealers` | Bayi ilişkileri |
| `MarketingAdmin\BudgetController` | `/mktg-admin/budget` | Bütçe yönetimi |
| `MarketingAdmin\IntegrationController` | `/mktg-admin/integrations` | Entegrasyon ayarları |
| `MarketingAdmin\SettingsController` | `/mktg-admin/settings` | Marketing ayarları |
| `MarketingAdmin\NotificationController` | `/mktg-admin/notifications` | Bildirim merkezi |
| `MarketingAdmin\ProfileController` | `/mktg-admin/profile` | Profil ayarları |

---

## Dashboard KPI'ları

**Cache:** 300 saniye (company bazlı: `kpi:mktg:main:c{id}`)

### Marketing Dashboard
| KPI | Açıklama |
|-----|----------|
| `guest_count` | Son 30 gün lead sayısı |
| `conversion_rate` | Lead → doğrulanmış öğrenci % |
| `cpa` | Başına edinme maliyeti |
| `roi` | Pazarlama ROI'si |
| `verified_count` | Doğrulanmış kaynak sayısı |
| `campaign_count` | Aktif kampanya sayısı |
| `source_match_rate` | Kaynak eşleşme oranı % |
| `external_spend` | Dış platform toplam harcama |
| `external_clicks` | Toplam tıklama |
| `external_conversions` | Dış platform dönüşümleri |

### Benchmark (30g vs önceki 30g)
- `guests`, `conversions`, `conv_rate`, `spend`, `ext_conv`
- `delta()` metodu: `{val, prev, delta%, up bool}`

### Görselleştirmeler
- `sourcePerformance` — kaynak × guest/student dönüşüm (top 8)
- `topCampaigns` — UTM kampanya performansı (top 5)
- `externalByProvider` — Google/Meta/diğer harcama karşılaştırması
- `externalTopCampaigns` — dış kampanya detayı (top 6)

---

## Sales Dashboard

**Cache:** `kpi:mktg:sales:c{id}` 300 saniye

| KPI | Açıklama |
|-----|----------|
| `newLeads` | Son 30g yeni guest başvuruları |
| `converted` | `contract_status = approved` olanlar |
| `convRate` | Dönüşüm oranı |
| `pipelineStages` | Sözleşme statüsüne göre pipeline |
| `sourceBreakdown` | Kaynak × dönüşüm (top 8) |
| `monthlyRevenue` | Öğrenci gelirleri (son 30g) |
| `scoreTierRows` | Lead score tier dağılımı (cold/warm/hot/vip) |
| `avgLeadScore` | Ortalama lead skoru |

**Kullanıcıya özel (cache dışı):**
- `myTasks` — atanan görevler (vade sıralı, limit 20)
- `myNotifications` — okunmamış in-app bildirimler (limit 10)

---

## Audience Suggestions

**Route:** `GET /mktg-admin/api/audience-suggestions?campaign_type=X`
**Cache:** 3600s

Kaynak × dönüşüm oranı matrisinden kampanya tipi önerisi üretir.

| Kaynak | Öneri Tipi |
|--------|-----------|
| organic/seo | awareness |
| paid/google/meta | lead_gen |
| dönüşüm >= %20 | conversion |
| referral/dealer/email | retention |

---

## CMS (İçerik Yönetimi)

**Modeller:** `CmsContent`, `CmsCategory`, `CmsMedia`, `CmsContentRevision`

| Özellik | Açıklama |
|---------|----------|
| Çok dilli | `title_tr`, `title_de`, `title_en` |
| Revizyon | `CmsContentRevision` — her kayıt öncesi snapshot |
| Medya | `CmsMedia` kütüphanesi |
| Zamanlama | `scheduled_at`, `archived_at` |
| SEO | `seo_canonical_url`, `slug`, `meta_*` |
| Senior banner | `category = 'senior_banner'` → senior dashboard'da görünür |

---

## E-posta Pazarlama

**Modeller:** `EmailTemplate`, `EmailSegment`, `EmailCampaign`, `EmailSendLog`

| Özellik | Açıklama |
|---------|----------|
| Segmentasyon | Kural bazlı dinamik segment |
| Şablon | HTML e-posta şablonları |
| Kampanya | Segment + şablon → kitleye gönderim |
| Log | `email_send_log` — her gönderim kaydı |
| Preview | Segment önizleme + istatistik |

---

## Sosyal Medya

**Modeller:** `SocialMediaAccount`, `SocialMediaPost`, `SocialMediaMonthlyMetric`

| Özellik | Açıklama |
|---------|----------|
| Hesap Bağlantısı | `sync_token`, `last_synced_at` |
| Gönderi Takvimi | `social/calendar` — aylık takvim görünümü |
| Aylık Metrikler | Takipçi, beğeni, erişim, tıklama |
| Sync | `SocialMetricsSyncCommand` — periyodik güncelleme |

---

## Etkinlik Yönetimi

**Modeller:** `MarketingEvent`, `EventRegistration`

- Etkinlik oluşturma/düzenleme
- Kayıt formu + katılımcı listesi
- Anket sonuçları
- Etkinlik raporu

---

## Pipeline Analizi

**Controller:** `SalesPipelineController`
**Service:** `PipelineProgressService`

| Route | Açıklama |
|-------|----------|
| `/mktg-admin/pipeline` | Genel görünüm |
| `/mktg-admin/pipeline/conversion-time` | Aşamalar arası geçiş süreleri |
| `/mktg-admin/pipeline/value` | Pipeline değeri |
| `/mktg-admin/pipeline/loss` | Kayıp analizi |

Detay: [INTEGRATION_LEAD_SCORING.md](INTEGRATION_LEAD_SCORING.md)

---

## Lead Kaynağı Takibi

**Model:** `LeadSourceDatum`, `LeadSourceOption`

- UTM parametrelerini otomatik yakalar
- `initial_source`, `verified_source`, `source_match` alanları
- `funnel_converted` boolean — dönüşüm takibi
- `/mktg-admin/tracking-links` — UTM link oluşturma

---

## Marketing Görevleri

**Model:** `MarketingTask`

5 departman: `operations`, `finance`, `advisory`, `marketing`, `system`

**Kanban View:** `/tasks` (TaskBoardController)
Detay: [INTEGRATION_TASK_BOARD.md](INTEGRATION_TASK_BOARD.md)

---

## Entegrasyon Yönetimi

**Controller:** `IntegrationController`
**Route:** `/mktg-admin/integrations`

Desteklenen entegrasyonlar:
- Takvim: Google Calendar, Cal.com, Calendly
- E-imza: DocuSign, HelloSign, PandaDoc
- E-posta: Mailchimp, SendGrid, Zoho
- Proje: ClickUp, Monday, Notion
- Video: Zoom, Google Meet, Teams
- Harici metrikler: `MarketingExternalMetric` → Google Ads, Meta Ads, LinkedIn

Detay: [INTEGRATION_CALENDAR.md](INTEGRATION_CALENDAR.md)

---

## Bütçe Yönetimi

**Model:** `MarketingBudget`
**Route:** `/mktg-admin/budget`

- Aylık/yıllık bütçe planlama
- Kampanya harcama takibi
- Bütçe vs gerçekleşen karşılaştırma

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Controller (Dashboard) | `app/Http/Controllers/MarketingAdmin/DashboardController.php` |
| Service (Pipeline) | `app/Services/PipelineProgressService.php` |
| Service (Lead Score) | `app/Services/LeadScoreService.php` |
| Service (External Metrics) | `app/Services/Marketing/ExternalMetrics/ExternalMetricsSyncService.php` |
| Layout | `resources/views/marketing-admin/layouts/app.blade.php` |
| Dashboard View | `resources/views/marketing-admin/dashboard/index.blade.php` |
| Sales Dashboard | `resources/views/marketing-admin/dashboard/sales.blade.php` |
