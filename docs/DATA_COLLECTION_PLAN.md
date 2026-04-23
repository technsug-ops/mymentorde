# 📊 MentorDE — Veri Toplama Planı (30 Gün)

> **Tarih:** 23 Nisan 2026
> **Hedef:** Sıfırdan doğru veri altyapısı — PostHog + Metabase + Özel dev
> **Kapsam:** 9 eksik → 30 günde çözüldü

---

## 🎯 Karar Özeti

| Karar | Seçim | Neden |
|---|---|---|
| Event platform | **PostHog** (EU cloud veya self-host) | Open source, GDPR-friendly, Almanya-uyumlu, tek araçta event + A/B + session replay + feature flag |
| BI tool | **Metabase** (self-host Docker) | Ücretsiz, 30 dk kurulum, SQL + drag-drop |
| Cost data | **Custom `marketing_spend` tablosu** + Meta/Google Ads API | Kendi reklam verin ücretsiz API |
| NPS/CSAT | **Custom modül** (tablolar + popup) | PostHog survey'lerden daha entegre |
| Identity | PostHog built-in (`identify` + `alias`) | Ayrı tool gereksiz |

**Toplam maliyet:** 0 € altyapı + 4 hafta dev

---

## 📅 30 GÜNLÜK ROADMAP

```
HAFTA 1 ─────────────── HAFTA 2 ─────────────── HAFTA 3 ─────────────── HAFTA 4
  ALTYAPI                BACKEND EVENTS          COST DATA                NPS/CSAT
  ───────                ──────────────          ─────────                ────────
  PostHog                Laravel Observer'lar    marketing_spend tablo    nps_surveys tablo
  Metabase               25 event backend'i      Meta Ads API sync        Popup UI
  Consent banner         Dashboard v1            Google Ads API sync      Trigger logic
  Frontend SDK           Identity stitching      CAC hesaplama            Segment analizi
```

---

## 🗓️ HAFTA 1 — Altyapı Kurulumu

### Gün 1 — Hesap + Temel Kurulum

**Sabah (2 saat):**
- [ ] PostHog Cloud hesabı aç: https://eu.posthog.com/signup (EU bölgesi seç!)
- [ ] Project oluştur: `mentorde-prod`
- [ ] API key al (`POSTHOG_API_KEY`, `POSTHOG_HOST`)
- [ ] `.env` dosyasına ekle (prod/staging/dev ayrı key'ler)

**Öğleden sonra (2 saat):**
- [ ] Metabase Docker kurulumu:
  ```bash
  docker run -d -p 3000:3000 \
    --name metabase \
    -v metabase-data:/metabase.db \
    metabase/metabase
  ```
- [ ] MySQL'e read-only user oluştur (`analytics_ro`)
- [ ] Metabase'i MySQL'e bağla (salt okunur)
- [ ] İlk admin hesabı

**Akşam (1 saat):**
- [ ] `config/services.php` → `posthog` konfig bloğu
- [ ] `composer require posthog/posthog-php`

### Gün 2 — Frontend SDK + Consent

- [ ] GDPR consent banner (Almanya için ZORUNLU):
  - Reddet → hiçbir event gönderme
  - Kabul et → PostHog JS init
- [ ] Blade layout'lara PostHog JS snippet ekle:
  ```blade
  @if(cookie('analytics_consent') === 'true')
  <script>
    !function(t,e){...PostHog snippet...}(document,"posthog");
    posthog.init('{{ config('services.posthog.key') }}', {
        api_host: 'https://eu.posthog.com',
        capture_pageview: true,
        person_profiles: 'identified_only',
    });
  </script>
  @endif
  ```
- [ ] Super properties set et (portal, company_id, app_version)

### Gün 3 — Identity Service

- [ ] `app/Services/Analytics/AnalyticsService.php` oluştur
- [ ] Laravel event listener'ları:
  - `Login` → `identify()` çağrısı
  - `Registered` → `alias()` çağrısı
  - `Logout` → `reset()` çağrısı
- [ ] Lead formu submit → `alias('lead_{id}')` + `lead_created` event

### Gün 4-5 — İlk 10 Event'i Devreye Al

**Frontend events (JS):**
- [ ] `page_viewed` (otomatik)
- [ ] `cta_clicked` — `data-track` attribute handler
- [ ] `form_started` / `form_abandoned`

**Backend events (Observer):**
- [ ] `lead_created` → `LeadObserver@created`
- [ ] `lead_score_changed` → `LeadScoreLog` observer
- [ ] `booking_scheduled` → `BookingObserver@created`
- [ ] `payment_succeeded` → Stripe webhook
- [ ] `payment_failed` → Stripe webhook

**QA:**
- [ ] PostHog Live Events panel'inde event'lerin geldiğini gör
- [ ] Property'ler doğru mu?
- [ ] `environment` ayrımı çalışıyor mu?

### Hafta 1 Sonu Deliverable

✅ PostHog çalışıyor, ilk 10 event geliyor
✅ Metabase kurulu, MySQL bağlı
✅ Consent banner prod'da
✅ Identity stitching çalışıyor

---

## 🗓️ HAFTA 2 — Backend Events + İlk Dashboard

### Gün 6-7 — Kalan 15 Event

**Booking (3):**
- [ ] `booking_reminded` → reminder job
- [ ] `booking_completed` → BookingObserver
- [ ] `booking_cancelled` → BookingObserver

**Lead lifecycle (4):**
- [ ] `lead_contacted` → senior action
- [ ] `lead_qualified` → tier değişim observer
- [ ] `lead_converted` → Payment success trigger
- [ ] `lead_lost` → manuel veya cron

**Academic (4):**
- [ ] `lesson_started` → ders sayfası yükleme
- [ ] `lesson_completed` → video end / quiz pass
- [ ] `lesson_abandoned` → günlük cron
- [ ] `quiz_attempted` → quiz submit

**AI (2):**
- [ ] `ai_query_submitted` → Gemini response sonrası
- [ ] `ai_feedback_given` → feedback butonu

**Payment (2):**
- [ ] `checkout_started` → Stripe redirect öncesi
- [ ] `payment_method_added` → Stripe webhook

### Gün 8-9 — Metabase Dashboard v1

**Dashboard 1: Executive Overview**
- Aktif kullanıcı (günlük/haftalık/aylık)
- Yeni lead sayısı
- MRR trend
- Conversion rate (lead → paid)

**Dashboard 2: Acquisition Funnel**
- Channel breakdown (utm_source bazında)
- Sayfa ziyaretinden lead'e dönüşüm
- CTA tıklama heatmap

**Dashboard 3: Engagement**
- Lesson completion rate
- Average session duration
- AI query volume

**Dashboard 4: Booking Operations**
- No-show rate (senior bazında)
- Cancellation reason dağılımı
- Booking lead time distribution

### Gün 10 — PostHog Funnel'ları

- [ ] **Acquisition Funnel:** page_viewed → form_started → lead_created
- [ ] **Activation Funnel:** lead_created → lead_contacted → booking_completed → payment_succeeded
- [ ] **AI Engagement:** page_viewed (ai_labs) → ai_query_submitted → ai_feedback_given

### Hafta 2 Sonu Deliverable

✅ 25 core event çalışıyor
✅ 4 Metabase dashboard aktif
✅ 3 PostHog funnel aktif
✅ Session replay aktif (isteğe bağlı)

---

## 🗓️ HAFTA 3 — Cost Data Entegrasyonu (Eksik #6)

### Gün 11-12 — Database Layer

- [ ] Migration: `marketing_spend` tablosu
  ```php
  Schema::create('marketing_spend', function (Blueprint $t) {
      $t->id();
      $t->date('spend_date');
      $t->string('platform', 50);       // meta, google, linkedin, tiktok
      $t->string('account_id')->nullable();
      $t->string('campaign_id')->nullable();
      $t->string('campaign_name');
      $t->string('utm_source')->nullable();
      $t->string('utm_medium')->nullable();
      $t->string('utm_campaign')->nullable();
      $t->bigInteger('spend_cents');    // EUR cents
      $t->string('currency', 3)->default('EUR');
      $t->bigInteger('impressions')->nullable();
      $t->bigInteger('clicks')->nullable();
      $t->bigInteger('conversions')->nullable();
      $t->unsignedBigInteger('company_id');
      $t->timestamps();

      $t->unique(['spend_date', 'platform', 'campaign_id', 'company_id']);
      $t->index(['utm_source', 'utm_campaign']);
  });
  ```
- [ ] Model: `App\Models\MarketingSpend`
- [ ] Manuel CSV upload UI (Marketing Admin portal'a)

### Gün 13-14 — Meta Ads API

- [ ] Meta Business App + App Review
- [ ] `facebook-business-sdk-php` composer package
- [ ] `App\Services\Ads\MetaAdsSyncService`
- [ ] Günlük cron: `fetch_meta_spend_daily`
- [ ] Webhook (opsiyonel) — bütçe aşımı alarmı

### Gün 15 — Google Ads API

- [ ] Google Ads API developer token
- [ ] `google/ads-api-php` composer package
- [ ] `App\Services\Ads\GoogleAdsSyncService`
- [ ] Günlük cron: `fetch_google_spend_daily`

### Gün 15 Akşam — CAC Dashboard

- [ ] Metabase'de yeni dashboard: **Channel ROI**
  - CAC per channel = `spend / converted_leads`
  - LTV per channel = `avg_payment × retention_months`
  - Payback period = `CAC / monthly_revenue`
  - ROAS (Return on Ad Spend)

### Hafta 3 Sonu Deliverable

✅ Meta + Google Ads harcaması otomatik sync oluyor
✅ CAC dashboard çalışıyor
✅ Channel ROI görülebiliyor
✅ Eksik #6 ÇÖZÜLDÜ

---

## 🗓️ HAFTA 4 — NPS/CSAT Modülü (Eksik #7)

### Gün 16-17 — Database + Model

- [ ] Migration: `nps_surveys` tablosu
  ```php
  Schema::create('nps_surveys', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('user_id');
      $t->tinyInteger('score');              // 0-10
      $t->text('feedback_text')->nullable();
      $t->enum('context', [
          'post_booking', 'post_lesson', 'post_payment',
          'quarterly', 'churn_exit', 'custom'
      ]);
      $t->string('trigger_event')->nullable(); // hangi event tetikledi
      $t->boolean('is_promoter')->default(false); // score >= 9
      $t->boolean('is_detractor')->default(false); // score <= 6
      $t->timestamp('triggered_at');
      $t->timestamp('responded_at')->nullable();
      $t->unsignedBigInteger('company_id');
      $t->timestamps();

      $t->index(['user_id', 'context']);
      $t->index('score');
  });
  ```
- [ ] Migration: `csat_surveys` tablosu (1-5 ölçek, feature-bazlı)
- [ ] Model + Observer (is_promoter/is_detractor otomatik set)

### Gün 18 — Trigger Logic

- [ ] Trigger kuralları:
  - 3. lesson_completed sonrası (ilk NPS)
  - 10. lesson_completed sonrası (ikinci NPS)
  - post_booking_completed (CSAT — görüşme kalitesi)
  - booking_cancelled (churn exit survey)
  - 30 gün arayla maksimum 1 kez (spam koruma)
- [ ] `App\Services\Surveys\SurveyTriggerService`
- [ ] Queue job: `TriggerSurveyJob`

### Gün 19 — UI (Popup Component)

- [ ] `resources/views/components/nps-popup.blade.php`
  - 0-10 skor butonları
  - "Neden?" textarea (opsiyonel)
  - "Şimdi sorma" butonu (30 gün snooze)
- [ ] CSP-safe JS (nonce'lu)
- [ ] Mobile-friendly bottom sheet
- [ ] Submit → API endpoint

### Gün 20 — Dashboards + Analiz

- [ ] Metabase dashboard: **Customer Satisfaction**
  - NPS score trend (haftalık)
  - Promoter / Passive / Detractor breakdown
  - CSAT per feature (lesson, booking, AI, support)
  - Negatif feedback ham liste (detractor comments)
- [ ] Event: `nps_survey_responded` → PostHog'a da gönder

### Hafta 4 Sonu Deliverable

✅ NPS + CSAT çalışıyor
✅ Otomatik trigger'lar aktif
✅ Customer Satisfaction dashboard
✅ Eksik #7 ÇÖZÜLDÜ

---

## 🎯 AY 4-6 — Pasif Çözümler (Eksik #8, #9)

### Eksik #8: No-show Prediction (3-6 ay)

Veri birikimi gerekli — en az 500 booking olunca:

1. **Feature engineering** (PostHog event'lerinden):
   - `booking_reminded` email açıldı mı?
   - Önceki booking'te no-show oldu mu?
   - Lead created'tan booking'e kaç gün geçti?
   - Lead score booking anında kaçtı?
   - Device type (mobil vs desktop)
2. **Model:** Logistic regression veya XGBoost (scikit-learn, 100 satır Python)
3. **Deployment:** Laravel'de tahmin endpoint'i, yüksek risk → senior'a alarm
4. **Expected outcome:** No-show rate %15 → %8

### Eksik #9: Cohort Attribution (Ay 4)

Eksik #6 bitince otomatik başlar:

1. PostHog funnel + `marketing_spend` birleşimi
2. Multi-touch attribution model seçimi (Shapley recommended)
3. Metabase dashboard: **Cohort LTV by Channel**
   - Her aylık cohort'un channel breakdown'u
   - Cost-adjusted LTV
   - Payback curve
4. Marketing bütçe kararları için karar matrisi

---

## 📊 Özet Tablo — 9 Eksik Checklist

| # | Eksik | Hafta | Durum |
|---|---|---|---|
| 1 | Event-level tracking | Hafta 1-2 | PostHog ile çözülür |
| 2 | Feature usage telemetry | Hafta 1-2 | PostHog ile çözülür |
| 3 | Session recording | Hafta 1 | PostHog toggle ile |
| 4 | A/B test altyapısı | Hafta 1 | PostHog Feature Flags |
| 5 | Analytics warehouse | Hafta 1 | Metabase + read-only MySQL |
| 6 | Cost data | Hafta 3 | Özel dev (marketing_spend + API) |
| 7 | NPS/CSAT | Hafta 4 | Özel dev (tablolar + popup) |
| 8 | No-show prediction | Ay 4-6 | Veri birikimi + ML |
| 9 | Cohort attribution | Ay 4 | #6 bitince otomatik |

---

## ⚠️ Risk ve Dikkat Edilecekler

| Risk | Önlem |
|---|---|
| GDPR ihlali | EU region PostHog + consent banner + PII hash |
| Event spam (1M+/ay) | Super property + throttling + gerçekten gerekli event'ler |
| Property drift | Event catalog'u PR'da zorunlu kontrol |
| Prod DB yavaşlama | Metabase için **mutlaka** read-replica veya ayrı kullanıcı |
| Test data prod'a sızmalı | `environment: dev/staging/prod` property zorunlu |
| API key sızması | `.env` gitignore'da + rotate policy |
| Meta/Google API quota | Günlük bir kez sync yeterli (gerçek zamanlı gerek yok) |

---

## 🎓 Kaynaklar (Data Analyst Arkadaşına)

| Konu | Link |
|---|---|
| PostHog docs | https://posthog.com/docs |
| Event taxonomy | https://segment.com/docs/connections/spec/ |
| Metabase tutorials | https://www.metabase.com/learn |
| Attribution models | https://segment.com/docs/connections/spec/ecommerce/v2/ |
| NPS methodology | https://en.wikipedia.org/wiki/Net_promoter_score |

---

## 📝 Değişiklik Geçmişi

| Tarih | Değişiklik |
|---|---|
| 2026-04-23 | İlk plan — 30 günlük roadmap |

---

*Bu plan yaşayan bir dokümandır. Her hafta sonu güncellenir.*
