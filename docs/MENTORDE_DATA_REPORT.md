# 📊 MentorDE — Veri Mimarisi & Analiz Raporu

> **Hedef kitle:** Data analisti
> **Amaç:** Firma verisinin ne içerdiğini, nasıl aktığını ve hangi analizlerin yapılabileceğini hızlıca kavramak
> **Tarih:** 23 Nisan 2026
> **Platform:** MentorDE — Laravel 12 / PHP 8.4 / MySQL 8 multi-portal SaaS

---

## İÇİNDEKİLER

**BÖLÜM A — GÖRSEL ÖZET**
1. Sistem Mimarisi — Kuşbakışı
2. Veri Kategorileri — 209 Tablo / 20 Kategori
3. Lead → Customer Funnel
4. Aktif Dashboards — 15 Analiz Modülü
5. Veri Akış Diyagramı — Entegrasyonlar
6. Entity Relationship — Core Tables
7. Veri Kalitesi & Kontrol Mekanizmaları
8. Analiz Önerileri (Quick Win / Deep / ML)
9. Veri Hacmi — Production Snapshot
10. Teslim Paketi Checklist

**BÖLÜM B — DETAYLI TABLO ŞEMALARI**
1. CRM & Leads
2. Academic
3. Bookings & Calendar
4. Payments & Billing
5. Dealer Network
6. Marketing & Attribution
7. AI Labs
8. Audit & Compliance
9. View/Materialized View Önerileri
10. SQL Sorgu Örnekleri (Cheatsheet)
11. Data Quality Alarm Dashboard

---

# BÖLÜM A — GÖRSEL ÖZET

## 1. Sistem Mimarisi — Kuşbakışı

```
┌────────────────────────────────────────────────────────────────────────┐
│                         MENTORDE PLATFORM                              │
│                  (Laravel 12 / PHP 8.4 / MySQL 8)                      │
└────────────────────────────────────────────────────────────────────────┘
                                   │
        ┌──────────────────────────┼──────────────────────────┐
        │                          │                          │
   ┌────▼─────┐              ┌─────▼─────┐              ┌─────▼─────┐
   │ PORTALS  │              │  DOMAIN   │              │ ANALYTICS │
   │ (6 rol)  │              │   DATA    │              │   LAYER   │
   └────┬─────┘              └─────┬─────┘              └─────┬─────┘
        │                          │                          │
   ┌────┴────┬────┬────┬────┬────┐ │                    ┌─────┴─────┐
   │ Student │Gues│Seni│Deal│Mana│ │                    │ Dashboards│
   │  Guest  │ t  │ or │ er │ger │ │                    │ Reports   │
   │ Senior  │    │    │    │    │ │                    │ Exports   │
   │ Dealer  │    │    │    │    │ │                    └───────────┘
   │ Manager │    │    │    │    │ │
   │Mkt-Admin│    │    │    │    │ │
   └─────────┴────┴────┴────┴────┘ │
                                    │
            ┌───────────────────────┼───────────────────────┐
            │                       │                       │
        ┌───▼───┐              ┌────▼────┐             ┌────▼────┐
        │  CRM  │              │  ACAD   │             │  OPS    │
        │ Leads │              │ Courses │             │Bookings │
        │Contact│              │ Modules │             │Contracts│
        │Pipeline│             │ Progress│             │ Payments│
        └───────┘              └─────────┘             └─────────┘
```

---

## 2. Veri Kategorileri — 209 Tablo / 20 Kategori

```
╔═══════════════════════════════════╤═══════╤═══════════════════════════╗
║ KATEGORİ                          │ TABLO │ ANA FONKSİYON             ║
╠═══════════════════════════════════╪═══════╪═══════════════════════════╣
║ 🔐 Auth & Users                   │   12  │ users, roles, permissions ║
║ 👥 CRM & Leads                    │   18  │ lead pipeline, scoring    ║
║ 🎓 Academic (Courses/Modules)     │   24  │ curriculum, lessons       ║
║ 📅 Bookings & Calendar            │   15  │ randevu, Google Calendar  ║
║ 💰 Payments & Billing             │   11  │ Stripe, invoices, payouts ║
║ 📝 Contracts                      │    8  │ dealer + student sözleşme ║
║ 📧 Communications                 │   14  │ email, mail campaigns     ║
║ 📊 Analytics & Reports            │   22  │ dashboards, KPI, funnel   ║
║ 🏢 Dealer Network                 │   16  │ bayi yönetimi, partner    ║
║ 🤖 AI Labs                        │    7  │ knowledge_sources, chat   ║
║ 📢 Marketing & Attribution        │   13  │ UTM, campaigns, touches   ║
║ 🗃️ Documents & Media              │    9  │ dosyalar, storage         ║
║ 🔔 Notifications                  │    6  │ bildirimler, uyarılar     ║
║ 📋 Audit & Compliance             │    8  │ audit_trails, logs        ║
║ 🎯 Goals & Progress               │    5  │ hedef, ilerleme takibi    ║
║ ⚙️ Settings & Config              │   10  │ tenant, portal ayarları   ║
║ 💬 Chat & Messaging               │    6  │ support, internal msg     ║
║ 🏷️ Tags & Metadata                │    5  │ etiketler, kategoriler    ║
║ 🌐 Public Site & CMS              │    7  │ landing, blog, theme      ║
║ 🔧 System & Migrations            │    3  │ jobs, cache, sessions     ║
╚═══════════════════════════════════╧═══════╧═══════════════════════════╝
                          TOPLAM:   209
```

---

## 3. Lead → Customer Funnel (En Önemli Conversion Akışı)

```
        ┌─────────────────────────────────────────────────┐
        │              TRAFIC SOURCES                     │
        │   Organic | Paid | Referral | Dealer | Direct   │
        └───────────────────┬─────────────────────────────┘
                            ▼
        ╔═══════════════════════════════════════╗
        ║   LANDING PAGE (Guest Portal)         ║   ← marketing_touches
        ║   → UTM tracking + session            ║     utm_source/medium/campaign
        ╚═══════════════════╤═══════════════════╝
                            ▼
                    ┌───────────────┐
                    │  LEAD CREATED │      score: 0
                    │  (Form / Tel) │
                    └───────┬───────┘
                            ▼
        ┌───────────────────┴───────────────────┐
        │        LEAD SCORING ENGINE            │    lead_score_logs
        │                                       │
        │   ❄️ COLD      (0-20)   Yeni lead     │
        │   🔥 WARM      (21-50)  İlgilendi     │
        │   🌡️ HOT       (51-75)  Görüşme       │
        │   ✅ SALES-RDY (76-90)  Satış için    │
        │   🏆 CHAMPION  (91+)    Sadık müşteri │
        └───────────────────┬───────────────────┘
                            ▼
                    ┌───────────────┐
                    │   BOOKING     │   ← ilk randevu
                    │ (Senior call) │
                    └───────┬───────┘
                            ▼
                    ┌───────────────┐
                    │   CONTRACT    │
                    │    SIGNED     │
                    └───────┬───────┘
                            ▼
                    ┌───────────────┐
                    │    PAYMENT    │   Stripe + invoices
                    │  (subscription│
                    │   or one-off) │
                    └───────┬───────┘
                            ▼
                    ┌───────────────┐
                    │   ONBOARDED   │   → Student Portal
                    │  (active user)│
                    └───────────────┘

🔍 KEY METRICS:
   • Lead → Booking conversion
   • Booking → Contract conversion
   • Contract → Payment conversion
   • CAC (Customer Acquisition Cost) per channel
   • LTV (Lifetime Value) per source
```

---

## 4. Aktif Dashboards — 15 Analiz Modülü

```
┌─────────────────────────────────────────────────────────────────────┐
│                       MANAGER PORTAL                                │
├─────────────────────────────────────────────────────────────────────┤
│  1. Executive Dashboard        → Genel KPI, MRR, aktif kullanıcı    │
│  2. Lead Analytics             → Funnel + score + conversion        │
│  3. Platform Analytics         → DAU/MAU, retention, engagement     │
│  4. Marketing Attribution      → UTM + multi-touch attribution      │
│  5. Dealer Health              → Bayi performansı, ciro, komisyon   │
│  6. Document Pipeline          → Sözleşme imza süreleri             │
│  7. Payment Analytics          → Stripe + churn + MRR breakdown     │
│  8. AI Labs Usage              → Token, fatura, popüler sorgular    │
│  9. Email Campaign Analytics   → Open/click/bounce rate             │
│ 10. Support Tickets            → SLA, resolution time               │
├─────────────────────────────────────────────────────────────────────┤
│                       DEALER PORTAL                                 │
├─────────────────────────────────────────────────────────────────────┤
│ 11. Dealer Dashboard           → Kendi leadleri + komisyon          │
│ 12. Dealer Revenue             → Aylık/yıllık ciro grafiği          │
├─────────────────────────────────────────────────────────────────────┤
│                       SENIOR PORTAL                                 │
├─────────────────────────────────────────────────────────────────────┤
│ 13. Senior Performance         → Atanan öğrenci, booking sayısı     │
├─────────────────────────────────────────────────────────────────────┤
│                    MARKETING ADMIN PORTAL                           │
├─────────────────────────────────────────────────────────────────────┤
│ 14. Campaign Performance       → Kampanya bazlı ROI                 │
│ 15. Content Analytics          → Blog view, CTR, engagement         │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 5. Veri Akış Diyagramı — Entegrasyonlar

```
                    ┌──────────────────────────────┐
                    │         EXTERNAL             │
                    │                              │
     ┌──────────────┤  • Google Calendar (2-way)   │
     │              │  • Stripe (payments)         │
     │              │  • Gemini API (AI Labs)      │
     │              │  • Resend (email)            │
     │              │  • Giphy (media)             │
     │              └──────────────┬───────────────┘
     │                             │
     ▼                             ▼
┌─────────────┐           ┌──────────────────┐
│  Bookings   │◄─sync────►│  calendar_events │
│   table     │           │  (Google ↔ DB)   │
└─────────────┘           └──────────────────┘

┌─────────────┐           ┌──────────────────┐
│  payments   │◄─webhook──│  Stripe events   │
│  invoices   │           │  (subscription,  │
│   payouts   │           │   one-off, churn)│
└─────────────┘           └──────────────────┘

┌─────────────┐           ┌──────────────────┐
│knowledge_   │──upload──►│ Gemini File API  │
│sources      │           │ (RAG context)    │
│(PDF/URL/IMG)│◄─query────│                  │
└─────────────┘           └──────────────────┘

┌─────────────┐           ┌──────────────────┐
│email_       │──send────►│  Resend SMTP     │
│campaigns    │◄─bounce───│  (delivery log)  │
└─────────────┘           └──────────────────┘
```

---

## 6. Entity Relationship — Core Tables (Basitleştirilmiş ERD)

```
┌──────────┐       ┌──────────┐       ┌──────────────┐
│  users   │───1:N►│  leads   │───1:N►│lead_score_   │
│          │       │          │       │    logs      │
│ id       │       │ id       │       │              │
│ role     │       │ email    │       │ old_score    │
│ company_ │       │ phone    │       │ new_score    │
│   id     │       │ score    │       │ reason       │
│ email    │       │ tier     │       │ created_at   │
└────┬─────┘       │ source   │       └──────────────┘
     │             │ utm_*    │
     │             └────┬─────┘
     │                  │
     │             1:N  ▼
     │        ┌──────────────┐      ┌──────────────┐
     │        │   bookings   │──1:N►│  contracts   │
     │        │              │      │              │
     │        │ senior_id    │      │ status       │
     │        │ student_id   │      │ signed_at    │
     │        │ scheduled_at │      │ amount       │
     │        │ status       │      └──────┬───────┘
     │        │ google_evt_id│             │
     │        └──────────────┘        1:N  ▼
     │                           ┌──────────────┐
     │                           │   payments   │
     │                           │              │
     │                           │ stripe_id    │
     │                           │ amount       │
     │                           │ status       │
     │                           └──────────────┘
     │
     │        ┌──────────────┐      ┌──────────────┐
     └──1:N──►│  dealers     │──1:N►│dealer_       │
              │              │      │ commissions  │
              │ company_name │      │              │
              │ tier         │      │ percent      │
              │ manager_id   │      │ amount       │
              └──────────────┘      └──────────────┘
```

---

## 7. Veri Kalitesi & Kontrol Mekanizmaları

```
┌──────────────────────────────────────────────────────────────┐
│                    DATA INTEGRITY LAYERS                     │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────┐     ┌──────────────────┐              │
│  │ Database Level   │     │ Application      │              │
│  │                  │     │ Level            │              │
│  │ • UNIQUE idx     │     │                  │              │
│  │ • FK constraints │     │ • Laravel Valid. │              │
│  │ • NOT NULL       │     │ • Form Requests  │              │
│  │ • ENUM types     │     │ • Policies (auth)│              │
│  │ • Cascade rules  │     │ • Role scopes    │              │
│  └──────────────────┘     └──────────────────┘              │
│                                                              │
│  ┌──────────────────┐     ┌──────────────────┐              │
│  │ Audit Layer      │     │ Observer Hooks   │              │
│  │                  │     │                  │              │
│  │ • audit_trails   │     │ • On create      │              │
│  │ • user_id        │     │ • On update      │              │
│  │ • old/new values │     │ • On delete      │              │
│  │ • IP + UA        │     │ • Score recalc   │              │
│  └──────────────────┘     └──────────────────┘              │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## 8. Analiz Önerileri

### 🎯 QUICK WIN ANALİZLERİ (1-3 gün)

1. **Lead source ROI** — hangi kanal en düşük CAC?
2. **Senior workload** — randevu/senior dengesi var mı?
3. **Payment failure patterns** — hangi müşteri segmenti?
4. **Email campaign benchmarks** — sektör ort. ile kıyas
5. **Dealer top 10 vs bottom 10** — ne fark yaratıyor?

### 🔬 DERİN ANALİZ (1-3 hafta)

6. **Cohort retention** — aylık cohort 3/6/12 ay retention
7. **Churn prediction** — hangi sinyaller churn'den önce var?
8. **LTV modeling** — source × tier × geography
9. **AI Labs kullanım korelasyonu** — AI kullanan müşteri daha uzun kalıyor mu?
10. **Multi-touch attribution** — Shapley/Markov model

### 🚀 ML/FORECAST FIRSATLARI

11. **Lead scoring ML modeli** — mevcut heuristic yerine
12. **Senior-student matching** — başarı rate'e göre öneri
13. **Churn risk score** — gerçek zamanlı uyarı sistemi
14. **Content recommendation** — öğrenci davranışına göre
15. **MRR forecast** — seasonality + trend

---

## 9. Veri Hacmi — Production Snapshot (23 Nisan 2026)

| TABLO | SATIR | BÜYÜME TEMPOSU |
|---|---|---|
| users | ~11 | Yavaş (canonical users) |
| leads | ~50 | Hızlı (günlük büyüme) |
| bookings | ~30 | Orta |
| contracts | ~15 | Yavaş |
| payments | ~20 | Orta |
| knowledge_sources | ~22 | Manuel (admin ekler) |
| audit_trails | ~1000+ | Çok hızlı (her işlem) |
| marketing_touches | ~200+ | Hızlı (her sayfa ziyareti) |
| lead_score_logs | ~100+ | Her score değişiminde |
| calendar_events | ~30 | Booking ile senkron |

**Not:** Demo/test data. Prod'da 1-3 ay içinde 10x büyüme beklenir.

---

## 10. Teslim Paketi Checklist

- [ ] Read-only DB kullanıcısı oluştur (SELECT yetkisiyle)
- [ ] Staging/anonymize DB dump çıkar (GDPR uyumu)
- [ ] Bu görseli + schema dump (`mysqldump --no-data`) paylaş
- [ ] Önemli view'lar: `v_lead_funnel`, `v_mrr_summary`, `v_dealer_rank`
- [ ] Metabase/Superset kurulumu öner (free, self-host)
- [ ] İlk 5 "Quick Win" analizinden başlamasını iste
- [ ] Haftalık review cadence belirle (1x30 dk)

---

---

# BÖLÜM B — DETAYLI TABLO ŞEMALARI

## 1. CRM & LEADS — Kategori Detayı

### 1.1 `leads` tablosu (ana tablo)

| Kolon | Tip | Açıklama | Analiz Değeri |
|---|---|---|---|
| `id` | BIGINT PK | Benzersiz ID | Join anahtarı |
| `email` | VARCHAR(255) | Email (unique) | Dedup, kişi bazlı analiz |
| `phone` | VARCHAR(50) | Telefon | İletişim oranı |
| `full_name` | VARCHAR(255) | Ad soyad | Raporlamada kullanıcı adı |
| `source` | ENUM | organic/paid/referral/dealer/direct | **CAC analizi** |
| `utm_source` | VARCHAR | Trafik kaynağı (google, fb, ig) | **Channel ROI** |
| `utm_medium` | VARCHAR | cpc, organic, email | Kampanya tipi |
| `utm_campaign` | VARCHAR | Kampanya adı | **Kampanya perf.** |
| `utm_term` | VARCHAR | Keyword | Keyword ROI |
| `utm_content` | VARCHAR | Ad variant | A/B test |
| `score` | INT | 0-100 puanı | **Lead kalitesi** |
| `tier` | ENUM | cold/warm/hot/sales_ready/champion | Segmentasyon |
| `status` | ENUM | new/contacted/qualified/converted/lost | **Funnel stage** |
| `assigned_senior_id` | BIGINT FK | Atanan danışman | Workload analizi |
| `dealer_id` | BIGINT FK | Bayi referansı | **Dealer perf.** |
| `converted_at` | TIMESTAMP | Müşteri oldu mu? | **Conversion zamanı** |
| `lost_reason` | VARCHAR | Kaybedilme nedeni | Churn root cause |
| `company_id` | BIGINT FK | Multi-tenant | Tenant izolasyonu |
| `created_at` | TIMESTAMP | Kayıt zamanı | Cohort başlangıcı |
| `updated_at` | TIMESTAMP | Son değişiklik | Staleness |

### 1.2 `lead_score_logs` tablosu (puan geçmişi)

| Kolon | Tip | Açıklama |
|---|---|---|
| `lead_id` | BIGINT FK | Hangi lead |
| `old_score` | INT | Eski puan |
| `new_score` | INT | Yeni puan |
| `delta` | INT | Fark (+/−) |
| `reason` | VARCHAR | "email_opened", "booking_scheduled", vb. |
| `actor_user_id` | BIGINT FK | Manuel değişiklikse kim |
| `created_at` | TIMESTAMP | Ne zaman |

**Analiz fırsatları:**
- Skor sinyalleri hangileri gerçek conversion'a götürüyor? (feature importance)
- Tipik "cold → hot" geçiş süresi nedir?

### 1.3 Funnel Metrik Formülleri

| Metrik | Formül |
|---|---|
| **Lead → Booking rate** | `COUNT(bookings) / COUNT(leads) × 100` |
| **Booking → Contract rate** | `COUNT(contracts) / COUNT(bookings) × 100` |
| **Contract → Payment rate** | `COUNT(payments WHERE status='succeeded') / COUNT(contracts)` |
| **CAC (channel)** | `SUM(marketing_spend WHERE utm_source=X) / COUNT(converted WHERE utm_source=X)` |
| **Time to conversion** | `AVG(converted_at - created_at)` gün cinsinden |
| **Score accuracy** | `AVG(score) WHERE status='converted'` vs `AVG(score) WHERE status='lost'` |

---

## 2. ACADEMIC — Kurs & Modül Yapısı

### 2.1 Tablo Hiyerarşisi

```
courses (ana kurs)
  └─ course_modules (modüller, örn: "Almanca A1")
       └─ course_lessons (dersler)
            └─ lesson_completions (öğrenci tamamlama kayıtları)
                 └─ lesson_quiz_attempts (quiz sonuçları)
```

### 2.2 `course_lessons` kolonları

| Kolon | Açıklama | Analiz |
|---|---|---|
| `module_id` | Hangi modül | Modül perf. |
| `title` | Ders adı | Popüler içerik |
| `content_type` | video/text/quiz/assignment | Format tercihi |
| `estimated_duration` | Dakika | Tahmini vs gerçek |
| `order` | Sıra | Drop-off noktası |

### 2.3 `lesson_completions` — Öğrenci İlerleme

| Kolon | Analiz Değeri |
|---|---|
| `user_id` | Öğrenci bazlı |
| `lesson_id` | Ders bazlı |
| `started_at` | Başlama zamanı |
| `completed_at` | Bitirme zamanı |
| `duration_seconds` | **Gerçek harcanan süre** → engagement |
| `progress_percent` | Yarım bırakan var mı? |

**Analiz fırsatları:**
- Hangi ders drop-off yaratıyor? (`completed_at IS NULL` oranı)
- Ortalama tamamlama süresi planla ne kadar örtüşüyor?
- Hangi öğrenci profili hızlı ilerliyor?

---

## 3. BOOKINGS & CALENDAR

### 3.1 `bookings` kolonları

| Kolon | Tip | Analiz |
|---|---|---|
| `senior_id` | FK | Danışman yüklemesi |
| `student_id` / `guest_id` / `lead_id` | FK | Kim için |
| `scheduled_at` | DATETIME | Zaman bazlı dağılım |
| `duration_minutes` | INT | Ort. görüşme süresi |
| `status` | ENUM | scheduled/done/cancelled/no_show |
| `cancellation_reason` | ENUM | 8 standart kategori |
| `google_event_id` | VARCHAR | GCal sync |
| `meeting_type` | ENUM | online/offline/phone |
| `notes` | TEXT | Senior notu (NLP analizi fırsat) |

### 3.2 Cancellation Reason Kategorileri (8 adet)

| Kategori | Açıklama |
|---|---|
| `student_unavailable` | Öğrenci müsait değil |
| `senior_unavailable` | Danışman müsait değil |
| `rescheduled` | Ertelendi |
| `student_not_interested` | Öğrenci vazgeçti |
| `technical_issue` | Teknik sorun |
| `duplicate` | Çift rezervasyon |
| `emergency` | Acil durum |
| `other` | Diğer (açıklama zorunlu) |

**Analiz fırsatları:**
- No-show rate hangi senior'da yüksek?
- İptal oranı gün/saat'e göre nasıl değişiyor?
- Optimal görüşme saati nedir? (conversion ile korelasyon)

---

## 4. PAYMENTS & BILLING

### 4.1 Ana Tablolar

```
payments ───────┐
                │
subscriptions ──┤
                ├──► invoices ──► invoice_items
dealer_payouts ─┤
                │
refunds ────────┘
```

### 4.2 `payments` kolonları

| Kolon | Analiz |
|---|---|
| `stripe_payment_intent_id` | Stripe join |
| `amount` | Ciro |
| `currency` | EUR/TRY/USD |
| `status` | succeeded/failed/refunded/disputed |
| `payment_method` | card/sepa/bank_transfer |
| `failure_reason` | **Churn nedeni** |
| `paid_at` | Zaman analizi |
| `customer_id` | FK → users |
| `subscription_id` | FK (opsiyonel) |
| `contract_id` | FK |

### 4.3 MRR / Revenue Metrikleri

| Metrik | Formül |
|---|---|
| **MRR** | `SUM(monthly_recurring_revenue WHERE status='active')` |
| **ARR** | `MRR × 12` |
| **Churn rate** | `COUNT(cancelled THIS MONTH) / COUNT(active START OF MONTH)` |
| **Net MRR growth** | `(new_MRR + expansion_MRR) - (churned_MRR + contraction_MRR)` |
| **Payment success rate** | `COUNT(succeeded) / COUNT(total_attempts)` |
| **Average revenue per user (ARPU)** | `SUM(payments) / COUNT(DISTINCT customer_id)` |

---

## 5. DEALER NETWORK

### 5.1 `dealers` tablosu

| Kolon | Açıklama |
|---|---|
| `company_name` | Bayi firma adı |
| `tier` | basic/gold/premium |
| `commission_percent` | Komisyon oranı |
| `manager_user_id` | Hesap yöneticisi |
| `region` | Coğrafi bölge |
| `status` | active/suspended/churned |
| `onboarded_at` | Başlangıç tarihi |
| `monthly_quota` | Hedef lead sayısı |

### 5.2 `dealer_commissions` — Komisyon Kayıtları

| Kolon | Analiz |
|---|---|
| `dealer_id` | FK |
| `payment_id` | Hangi ödeme |
| `base_amount` | Komisyon hesap bazı |
| `percent` | Oran |
| `amount` | Nihai komisyon |
| `status` | pending/paid/disputed |
| `paid_at` | Ödeme zamanı |

### 5.3 Dealer Performans Matrisi

| Metrik | Hesap |
|---|---|
| Dealer conversion rate | `converted_leads / total_leads` |
| Revenue per dealer | `SUM(payments WHERE dealer_id=X)` |
| Avg deal size | `AVG(amount) per dealer` |
| Time to first conversion | `MIN(converted_at - onboarded_at)` |
| Quota attainment | `actual_leads / monthly_quota × 100` |

---

## 6. MARKETING & ATTRIBUTION

### 6.1 `marketing_touches` tablosu

| Kolon | Açıklama |
|---|---|
| `session_id` | Tarayıcı session |
| `user_id` | Giriş yaptıysa |
| `lead_id` | Lead oluştuysa |
| `touch_type` | impression/click/pageview/conversion |
| `utm_source` | Kanal |
| `utm_medium` | Tip |
| `utm_campaign` | Kampanya |
| `landing_page` | İlk inen sayfa |
| `referrer_url` | Önceki sayfa |
| `device_type` | desktop/mobile/tablet |
| `browser` | Chrome/Firefox/Safari |
| `country` | Coğrafi |
| `touched_at` | Zaman |

### 6.2 Attribution Modelleri (uygulanabilir)

| Model | Açıklama | Zorluk |
|---|---|---|
| **First-touch** | İlk dokunuş %100 | Kolay |
| **Last-touch** | Son dokunuş %100 | Kolay |
| **Linear** | Eşit paylaştır | Kolay |
| **Time decay** | Son dokunuş daha değerli | Orta |
| **U-shaped** | İlk + son %40, orta %20 | Orta |
| **Data-driven (Markov)** | Geçiş olasılığı | Zor |
| **Shapley value** | Kooperatif oyun teorisi | Zor |

---

## 7. AI LABS — Kullanım Analitiği

### 7.1 `knowledge_sources` tablosu

| Kolon | Açıklama |
|---|---|
| `type` | pdf/image/url/text/document |
| `title` | Kaynak başlığı |
| `file_path` | Storage yolu |
| `content_hash` | SHA256 dedup |
| `gemini_file_id` | Gemini File API ID |
| `visible_to_roles` | JSON: [guest, student, senior] |
| `is_active` | Aktif mi |
| `company_id` | Tenant |

### 7.2 `ai_chat_sessions` + `ai_chat_messages`

| Kolon | Analiz |
|---|---|
| `user_id` | Kim kullandı |
| `role` | Hangi portal'dan |
| `prompt_tokens` | Input maliyet |
| `completion_tokens` | Output maliyet |
| `total_cost_usd` | Toplam fatura |
| `source_ids_used` | JSON array — hangi kaynaklar |
| `feedback_rating` | thumbs up/down |
| `response_time_ms` | Performans |

**AI Kullanım Metrikleri:**
- Tenant başına aylık token tüketimi
- Hangi kaynak en çok referans alıyor?
- Feedback rate (sadece negatif mi rapor ediliyor?)
- Response time SLA ihlali sayısı

---

## 8. AUDIT & COMPLIANCE

### 8.1 `audit_trails` tablosu (her model değişimi)

| Kolon | Açıklama |
|---|---|
| `auditable_type` | Model sınıfı (App\Models\Lead) |
| `auditable_id` | Kayıt ID |
| `event` | created/updated/deleted/restored |
| `old_values` | JSON |
| `new_values` | JSON |
| `user_id` | Kim yaptı |
| `ip_address` | Nereden |
| `user_agent` | Tarayıcı |
| `created_at` | Ne zaman |

**Compliance/Forensic analiz fırsatları:**
- GDPR right-to-access: tek sorguda kullanıcı tüm veri erişimi
- Anomali tespiti: gece yarısı çoklu silme
- Yetki suistimali: admin'in öğrenci verisine kaç kez baktığı

---

## 9. Öncelikli View/Materialized View Önerileri

```sql
-- v_lead_funnel: günlük funnel snapshot
CREATE VIEW v_lead_funnel AS
SELECT
    DATE(l.created_at) AS date,
    l.utm_source,
    COUNT(*) AS total_leads,
    SUM(CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END) AS booked,
    SUM(CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END) AS contracted,
    SUM(CASE WHEN p.status='succeeded' THEN 1 ELSE 0 END) AS paid
FROM leads l
LEFT JOIN bookings b ON b.lead_id = l.id
LEFT JOIN contracts c ON c.lead_id = l.id
LEFT JOIN payments p ON p.contract_id = c.id
GROUP BY DATE(l.created_at), l.utm_source;
```

| View | Amaç | Refresh |
|---|---|---|
| `v_lead_funnel` | Günlük kanal funnel | Gerçek zamanlı |
| `v_mrr_summary` | Aylık MRR breakdown | Günlük |
| `v_dealer_leaderboard` | Bayi sıralaması | Saatlik |
| `v_senior_workload` | Danışman yüklemesi | Gerçek zamanlı |
| `v_cohort_retention` | Cohort retention matrix | Haftalık |
| `v_ai_cost_per_tenant` | AI tenant maliyeti | Günlük |

---

## 10. SQL Sorgu Örnekleri (Data Analyst Cheatsheet)

### 10.1 Channel ROI (son 30 gün)

```sql
SELECT
    utm_source,
    COUNT(*) AS leads,
    SUM(CASE WHEN status='converted' THEN 1 ELSE 0 END) AS converted,
    ROUND(100.0 * SUM(CASE WHEN status='converted' THEN 1 ELSE 0 END) / COUNT(*), 2) AS conv_rate
FROM leads
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY utm_source
ORDER BY conv_rate DESC;
```

### 10.2 Senior workload dengesizliği

```sql
SELECT
    u.name AS senior,
    COUNT(b.id) AS total_bookings,
    SUM(CASE WHEN b.status='done' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN b.status='no_show' THEN 1 ELSE 0 END) AS no_shows
FROM users u
LEFT JOIN bookings b ON b.senior_id = u.id
WHERE u.role = 'senior'
GROUP BY u.id
ORDER BY total_bookings DESC;
```

### 10.3 Cohort retention (aylık)

```sql
SELECT
    DATE_FORMAT(u.created_at, '%Y-%m') AS cohort,
    COUNT(DISTINCT u.id) AS cohort_size,
    COUNT(DISTINCT CASE WHEN p1.paid_at >= DATE_ADD(u.created_at, INTERVAL 1 MONTH) THEN u.id END) AS month_1,
    COUNT(DISTINCT CASE WHEN p3.paid_at >= DATE_ADD(u.created_at, INTERVAL 3 MONTH) THEN u.id END) AS month_3,
    COUNT(DISTINCT CASE WHEN p6.paid_at >= DATE_ADD(u.created_at, INTERVAL 6 MONTH) THEN u.id END) AS month_6
FROM users u
LEFT JOIN payments p1 ON p1.customer_id = u.id AND p1.status='succeeded'
LEFT JOIN payments p3 ON p3.customer_id = u.id AND p3.status='succeeded'
LEFT JOIN payments p6 ON p6.customer_id = u.id AND p6.status='succeeded'
WHERE u.role='student'
GROUP BY cohort
ORDER BY cohort;
```

### 10.4 AI Labs maliyet per tenant

```sql
SELECT
    c.name AS tenant,
    SUM(m.prompt_tokens + m.completion_tokens) AS total_tokens,
    SUM(m.total_cost_usd) AS cost_usd,
    COUNT(DISTINCT s.id) AS sessions,
    AVG(m.response_time_ms) AS avg_latency_ms
FROM ai_chat_messages m
JOIN ai_chat_sessions s ON s.id = m.session_id
JOIN companies c ON c.id = s.company_id
WHERE m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY c.id
ORDER BY cost_usd DESC;
```

---

## 11. Data Quality Alarm Dashboard

| Kontrol | Threshold | Aksiyon |
|---|---|---|
| Lead email unique violation | > 0 | ETL hata |
| Lead with NULL utm_source | > 5% | Tracking kırık |
| Booking without senior | > 0 | Atama bug |
| Payment failure spike | > 2σ ortalama | Stripe sorunu |
| Senior with 0 booking (30 gün) | Liste | Kapasite boşa |
| Audit trail gap | Saatlik 0 kayıt | Logger down |
| Score < 0 veya > 100 | > 0 | Hesaplama bug |
| Stale lead (`updated_at` > 30 gün) | Liste | Manuel review |

---

## Teslim Paketi Güncellemesi

- [ ] SQL dump (schema only, no data): `mysqldump --no-data --routines --triggers`
- [ ] Anonymized sample data (100 lead, 50 booking, 20 payment)
- [ ] Bu 11 bölümlük Markdown dosyası
- [ ] Read-only DB credentials (`.env.analyst`)
- [ ] Dashboard kurulum önerisi (Metabase free, 30 dk setup)
- [ ] İletişim kanalı (Slack/Discord/email)
- [ ] İlk sprint: 5 "Quick Win" analizi — 1 hafta

---

## Ek Notlar

**Kullanım:**
- Bu dosyayı Markdown olarak açabilen her editörde (VS Code, Typora, Obsidian, Notion, GitHub) renkli/düzgün görünür.
- PDF'e çevirmek için: VS Code + "Markdown PDF" eklentisi, veya Typora → Export → PDF.
- Tüm tablolar kopyala-yapıştır ile Excel/Google Sheets'e aktarılabilir.

**Sonraki adım:**
Arkadaşına bu dosyayı gönder + read-only DB credentials + mysqldump schema. İlk haftada 5 quick-win analizinden başlasın. Haftalık 30 dk review ile ilerleyin.

---

*Son güncelleme: 23 Nisan 2026*
*Platform: MentorDE v1.0 — my.mentorde.com*
