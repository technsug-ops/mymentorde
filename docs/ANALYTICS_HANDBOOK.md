# 📊 MentorDE — Analytics El Kitabı

> **Hedef Kitle:** Manager / Admin kullanıcıları
> **Versiyon:** 1.0 — 24 Nisan 2026
> **Kapsam:** Panel içi analitik bölümlerinin tamamı + PostHog event tracking

---

## İÇİNDEKİLER

1. [Genel Bakış — Hangi Dashboard Ne İçin?](#1-genel-bakış)
2. [Kullanıcı Aktivite İstihbaratı](#2-kullanıcı-aktivite-i̇stihbaratı) — `/manager/user-intelligence` ⭐
3. [AI Labs Intelligence](#3-ai-labs-intelligence) — `/manager/ai-labs/analytics`
4. [Mevcut Pipeline Dashboards](#4-mevcut-pipeline-dashboards)
5. [PostHog Event Tracking](#5-posthog-event-tracking)
6. [KPI Sözlüğü](#6-kpi-sözlüğü)
7. [Günlük / Haftalık / Aylık İş Akışı](#7-günlük-haftalık-aylık-i̇ş-akışı)
8. [Veri Altyapısı (Teknik)](#8-veri-altyapısı-teknik)
9. [Sıkça Sorulan Sorular](#9-sıkça-sorulan-sorular)

---

## 1. Genel Bakış

MentorDE'de **iki ana intelligence dashboard** ve **14+ alt analiz sayfası** vardır.

### İki Ana Intelligence Dashboard

| Dashboard | URL | Ne İçin |
|---|---|---|
| 👥 **Kullanıcı Aktivitesi** | `/manager/user-intelligence` | Aday/öğrenci platformda ne yapıyor — engagement ve risk |
| 🤖 **AI Labs Analytics** | `/manager/ai-labs/analytics` | AI asistana ne soruyorlar — intent + FAQ + konu analizi |

### Ne Zaman Hangisine Bakarsın?

- **Sabah kahvesi rutini:** Kullanıcı Aktivitesi → dormant alarm var mı?
- **Yeni kampanya/reklam sonrası:** Kullanıcı Aktivitesi → Kampanya Etki Ölçümü
- **İçerik stratejisi planlaması:** AI Labs Analytics → FAQ Adayları + Konu Kategorileri
- **Haftalık review:** Her iki dashboard + Dönüşüm Hunisi

---

## 2. Kullanıcı Aktivite İstihbaratı

**URL:** `/manager/user-intelligence`
**Erişim:** Admin panel rolleri (manager, system_admin, operations_admin, finance_admin, marketing_admin)
**Sidebar:** Analitik & Raporlar → 👥 Kullanıcı Aktivitesi

### 2.1 Period Selector (Zaman Aralığı)

Dashboardun en üstündeki pills:

| Seçenek | Kullanım |
|---|---|
| 1 Hafta | Anlık trend — son 7 günde ne oldu |
| 15 Gün | Kısa trend |
| 1 Ay (default) | Aylık view — standart raporlama |
| 3 Ay | Çeyrek raporu |
| 6 Ay | Uzun vadeli retention analizi |
| 1 Yıl | Yıllık büyüme görünümü |
| 🗓️ Custom | İki tarih arası (from-to) — kampanya dönemi analizi için |

**Etkilediği bölümler:** Sparkline trend, Top Aktif Kullanıcılar, Top Users sıralama.
**Etkilemediği bölümler:** Top KPI kartları (standart 7/30 gün), Dormant alarmlar (sabit 14 gün).

### 2.2 Top KPI Kartları (4 Adet)

| Kart | Ne Gösterir | Hedef |
|---|---|---|
| 🎓 Toplam Öğrenci | Tüm öğrenciler + aktif 7 gün yüzdesi | %60+ aktif hedeflenir |
| 🙋 Toplam Aday | Tüm adaylar + aktif 7 gün yüzdesi | Kampanya dönemlerinde %70+ |
| ⚠️ Dormant Öğrenci | 30+ gündür inaktif öğrenciler | <%15 hedeflenir |
| ⚠️ Dormant Aday | 30+ gündür inaktif adaylar | <%30 hedeflenir |

### 2.3 Engagement Tier Segmentasyonu

**3 tier, hem öğrenci hem aday için ayrı:**

- 🟢 **Active:** Son 7 günde aktivite var
- 🟡 **At-risk:** 7-30 gün arası son aktivite
- 🔴 **Dormant:** 30+ gün veya hiç aktivite yok

**Guest "aktivite" tanımı:** Senior aksiyonu + AI soru + yeni kayıt tarihi (sessions yok, login olmuyorlar).
**Student "aktivite" tanımı:** `last_activity_at` (UpdateUserPresence middleware).

### 2.4 📈 Sparkline Trendi

Seçilen aralıkta günlük farklı aktif kullanıcı sayısı.
- Mavi gradient bar'lar → o günün total unique user
- Boş (gri) bar → o gün kimse aktif değildi
- Hover: günlük breakdown (kaç öğrenci + kaç aday)

### 2.5 🚨 Yüksek Skor + Dormant Alarmı (Guest)

**Kritik alarm:** Lead score ≥ 40 olan adaylar, 14+ gündür senior aksiyonu yok.

**Ne yapmalı?** O gün/o hafta bu adaylara senior mutlaka dönmeli — yüksek potansiyel kaybediyorsun.

Tablo kolonları:
- Aday adı + email (tıklayınca timeline)
- Lead Score (sarı rozet)
- Tier (warm/hot/sales_ready)
- Atanmış Senior (yoksa "⚠️ Atanmamış")
- Son Aksiyondan bu yana gün sayısı (kırmızı bold)

**Boş durumda:** "✅ Tüm yüksek-skor adaylarla aktif iletişim var" mesajı.

### 2.6 ⚠️ Öğrenci Risk Alarmı

Öğrenciler için eşdeğer alarm — risk skoru hesaplaması:
- 14+ gün `last_activity_at` yok → +20
- Bekleyen/overdue ödeme var → +40
- Yaklaşan randevu yok → +20
- Son 7 gün 0 audit aksiyon → +20
- `last_activity_at` NULL (hiç giriş yapmamış) → +20

Risk skoru ≥ 40 olanlar listelenir, yüksekten düşüğe sıralı.

**Renk kodu:** 80+ kırmızı, 60-80 sarı, 40-60 gri.

**Ne yapmalı?**
- Ödemesi bekleyen öğrenci → finans bölümü iletişime geçsin
- Yaklaşan randevu yok + inaktif → senior re-engagement (motivasyon email, yeni hedef)

### 2.7 📣 Kampanya / İçerik Etki Ölçümü

**Problem:** Yeni bir reklam/blog/duyuru çıktığında etkisini nasıl ölçerim?

**Çözüm:** Tarih + pencere gir → öncesi vs sonrası karşılaştırma

**Input:**
- **Yayın/Etkinlik Tarihi:** Kampanya, içerik, duyuru tarihi
- **Pencere:** ±3 gün / ±1 hafta / ±2 hafta / ±1 ay

**Output (4 metrik):**

| Metrik | Ne Ölçer |
|---|---|
| 🙋 Yeni Aday | Etkinlikten sonra kaç yeni aday kaydı geldi |
| 🤖 AI Soruları | AI asistana sorulan soru sayısı değişti mi |
| 📅 Booking | Randevu oluşturma değişti mi |
| 🎓 Aktif Öğrenci | Öğrenci etkileşimi arttı mı |

**Delta %:**
- 🟢 ↑ %+X → artış (yeşil, >%5 eşik)
- 🔴 ↓ %-X → azalış (kırmızı, <-%5 eşik)
- ⚫ → %0 → değişmedi (gri, ±%5 içi)

**Örnek senaryolar:**

1. **Instagram reklamı çıktın, 5 gün sürdü:**
   - event_date = reklam başlangıcı, window = ±7 gün
   - new_leads ↑ %50 → reklam çalışıyor
   - ai_queries → %0 → reklam indirdiği kullanıcılar AI'yla etkileşmiyor (landing optimizasyonu gerek)

2. **Yeni blog post yayınladın:**
   - event_date = yayın tarihi, window = ±14 gün
   - bookings ↑ %80 → içerik booking'e götürüyor, güçlü
   - ai_queries ↑ %200 → konu AI'da da trend oluyor, FAQ adayı

3. **Sistem güncellemesi duyurdun:**
   - event_date = güncelleme tarihi, window = ±3 gün
   - student_active ↑ → öğrenciler yeni özelliği denedi
   - Negatif delta varsa → duyuru kötü karşılandı veya kullanıcı şaşırdı

### 2.8 🔥 En Aktif Kullanıcılar (Top Users)

**Sıralama seçenekleri (pills):**

| Sıra Şekli | Ne İşe Yarar |
|---|---|
| 🔥 Aktivite Skoru (default) | Karma skor: audit + appointment + msg + lead_score ağırlıklı |
| ⭐ Lead Skoru | Yüksek lead scoredan düşüğe |
| ⏱️ Son Aktivite | En son hareket eden en üstte |
| ❓ Soru Sayısı | AI'ya en çok soru soran en üstte |
| 🔤 İsim | A-Z alfabetik |

**Tablo kolonları:**
- Tip (🎓 Öğrenci purple / 🙋 Aday blue rozet)
- İsim + email (tıklanabilir → timeline)
- Durum (öğrenci: presence_status, aday: lead tier)
- ⭐ Lead skor
- ❓ Soru sayısı (seçilen aralıkta AI sorusu)
- 🔥 Aktivite skoru
- Son aktivite (diffForHumans)

### 2.9 Per-User Timeline

Tablodan bir kullanıcıya tıklayınca tek kişi detay sayfası açılır.

#### Aday Timeline (`/guest/{id}`)

**Header:**
- Kişisel bilgi (email, telefon, kayıt tarihi)
- Lead skor + tier + atanan senior
- UTM source / campaign / last action

**Özet stat'ler (4 kart):**
- Toplam olay sayısı
- AI sorusu adedi
- Funnel adımı adedi
- Senior aksiyonu adedi

**Kronolojik Timeline:**
- 🆕 Kayıt oluşturuldu (source bilgisi)
- 🟢 Form tamamlandı (`funnel_form_completed_at`)
- 📄 Belgeler yüklendi (`funnel_documents_uploaded_at`)
- 🎯 Paket seçildi (`funnel_package_selected_at`)
- ✍️ Sözleşme imzalandı (`funnel_contract_signed_at`)
- ✅ Müşteriye dönüştü (`funnel_converted_at`)
- 🤖 AI'ya soru sordu (sorulan soru preview)
- 👥 Senior aksiyon aldı
- 📝 Kayıt güncellemeleri (audit trail)

Renkli sol-border dot'lar: AI mavi, funnel yeşil, senior turuncu, audit gri, created kırmızı.

#### Öğrenci Timeline (`/student/{id}`)

**Header:**
- Presence durumu + last_activity diffForHumans
- Login security (failed attempts, locked_until)
- Company ID

**3 tablo:**
- 📅 Son 20 randevu (tarih, durum, iptal zamanı)
- 💰 Son 10 ödeme (fatura, tutar, durum, vade, paid_at)
- 📝 Son 30 audit aksiyonu (create/update/delete/login + IP)

---

## 3. AI Labs Intelligence

**URL:** `/manager/ai-labs/analytics`
**Erişim:** Admin panel rolleri (senior engellenir)
**Sidebar:** AI Labs → 📊 Analytics

### 3.1 Üst KPI'lar (4 Kart)

- 💬 Bu ay toplam soru sayısı
- 💰 Gemini token maliyeti (USD/EUR yaklaşık)
- 🎯 Response mode (source-based / external / refused) dağılımı
- 😊 Memnuniyet oranı (thumbs up/down)

### 3.2 🔥 Hot Leads Tablosu

AI kullanan adayların öncelik listesi. **Hotness skoru:**
```
hotness = soru_sayısı × 2
        + lead_score × 0.5
        + recency_bonus (24s içindeyse +20, 72s içindeyse +10)
        + tier_bonus (hot=+25, sales_ready=+40)
```

Sıralama hotness'a göre, en ateşli üstte.

**Tablo kolonları:**
- # sıra
- Aday (email altında) — tıklayınca per-lead detail
- 🔥 Hotness
- Lead Score (renkli progress bar)
- Tier (renkli rozet)
- ❓ Soru sayısı
- Konuştuğu Konular (top 3 kategori rozet)
- Son Soru (diffForHumans)
- Durum (✅ müşteri / 👥 senior atanmış / ⚠️ atanmamış)

### 3.3 🏷️ Konu Kategorileri

10 domain kategorisi, keyword matching:

| Kategori | Türkçe + Almanca Anahtar Kelimeler |
|---|---|
| vize | vize, visum, visa, schengen, konsolosluk |
| üniversite | üniversite, university, uni, universität, tu, lmu, rwth, bewerbung |
| barınma | ev, konut, wohnung, yurt, wohnheim, kira, zimmer |
| dil | almanca, dil, deutsch, b1-c1, a1-a2, sprachkurs, testdaf, dsh |
| maliyet | fiyat, maliyet, kosten, harç, burs, stipendium, euro |
| sigorta | sigorta, versicherung, krankenversicherung, tk, aok |
| banka | banka, sperrkonto, bloke hesap, kontoeröffnung |
| iş | iş, çalışma, minijob, werkstudent, praktikum, staj |
| blokhesap | sperrkonto, blok hesap, expatrio, fintiba, coracle |
| ulaşım | semester ticket, deutschlandticket, metro, s-bahn, u-bahn |

Bar chart olarak görselleştirilir, en çok sorulan üstte.

### 3.4 📊 Converted vs Not-Converted İntent Analizi

**En güçlü strateji aracı.** Müşteri olmuş adayların soru konuları vs olmayanlar karşılaştırması.

**Sinyal = (converted %) - (not_converted %)**

- 🟢 Pozitif (>+5): Bu konuyu soran → daha sık müşteri olmuş (good intent)
- 🔴 Negatif (<-5): Bu konuyu soran → sık müşteri olmamış (objection/kaçış sinyali)
- ⚫ Nötr: Konverjans yok

**Örnek yorum:**
- "vize" sinyal = +15 → vize sorularına iyi cevap veriliyor, dönüştürüyor
- "maliyet" sinyal = -20 → fiyat hassasiyeti olan adaylar dönmüyor — paket stratejisi gözden geçir

### 3.5 💡 FAQ Adayları

Son 60 günde 2+ kez sorulmuş benzer sorular (ilk 6 kelime normalize ile gruplandı).

**Kolonlar:**
- Sayı (× N rozet)
- Örnek Soru (limit 120 karakter)
- Kim Sordu (rol breakdown)
- Son Soruluş (diffForHumans)

**Aksiyon:**
- 🔗 **CSV indir** → Excel'de açıp blog / FAQ / KnowledgeSource içeriği için hammadde
- 🔗 **Kaynaklara ekle** → AI Labs knowledge base'e ekle → AI cevapları daha iyi olur

### 3.6 😊 Feedback + Problem Cevaplar

- **Memnuniyet %:** good / (good+bad) oranı
- **Problem cevaplar:** thumbs down verilmiş son sorular (kaynak eksikliği veya prompt iyileştirme fırsatı)

### 3.7 Kaynak Kullanımı

- **📚 En Çok Kullanılan Kaynaklar:** AI hangi kaynakları en çok cite ediyor (citation count)
- **📦 Kullanılmayan Kaynaklar:** 30 gündür hiç kullanılmayan aktif kaynaklar → pasifleştir veya güncelle

---

## 4. Mevcut Pipeline Dashboards

Aşağıdakiler **lead pipeline ve senior performans** odaklı — user intelligence ile birlikte kullan.

### 4.1 Manager Panel

| Route | Ne Gösterir |
|---|---|
| `/manager/dashboard` | Genel KPI + funnel + senior perf + revenue trend |
| `/manager/conversion-funnel` | 🎯 Dönüşüm hunisi stage breakdown |
| `/manager/senior-performance` | 👤 Her danışmanın conversion/feedback/revenue skorları |
| `/manager/revenue-analytics` | 💰 Paket, senior, aylık ciro + collection rate |
| `/manager/feedback-analytics` | 💬 NPS + rating dağılımı + düşük puan alanları |
| `/manager/ticket-analytics` | 🎫 Guest ticket KPI + SLA + resolution time |
| `/manager/gdpr-dashboard` | 🔒 Erasure requests + PII erişim logları |

### 4.2 Marketing Admin Panel

| Route | Ne Gösterir |
|---|---|
| `/mktg-admin/dashboard` | Son 30 gün: aday, conversion, CPA, ROI, kanal performansı |
| `/mktg-admin/sales-pipeline` | Pipeline overview: açık/arşivli/dönüşmüş |
| `/mktg-admin/pipeline/value` | Weighted funnel value (paket fiyatı × ihtimal) |
| `/mktg-admin/pipeline/loss` | Loss analysis (stale + archived by reason) |
| `/mktg-admin/pipeline/conversion-time` | Avg/median/p90 dönüşüm süresi |
| `/mktg-admin/pipeline/re-engagement` | 90+ gün inaktif + yüksek skor = re-engagement pool |
| `/mktg-admin/pipeline/score-analysis` | Lead score dağılımı + tier conversion rate |
| `/mktg-admin/pipeline/kanban` | Görsel pipeline — drag to move stages |

---

## 5. PostHog Event Tracking

**URL:** https://eu.posthog.com/project/165227
**Region:** EU (GDPR uyumlu)
**Plan:** Pay-as-you-go + $10/ay billing limit

### 5.1 Aktif Event'ler (24 adet)

#### Otomatik (PostHog snippet)
- `$pageview` — her sayfa yükleme
- `$pageleave` — sayfa terk etme
- `$web_vitals` — Core Web Vitals (LCP, FID, CLS)
- `$identify` / `$set_person_properties` — login olunca

#### Frontend (Blade)
- `cta_clicked` — data-track="cta_clicked" attribute'lı butonlar
- `form_started` — form ilk input focus
- `form_abandoned` — form başladı ama submit yok (+ filled_fields, time_spent)

#### Backend (Observer)
- `lead_created` — GuestApplication@created
- `lead_score_changed` — lead_score delta
- `lead_qualified` — lead_score_tier değişimi
- `lead_assigned` — assigned_senior_email atandı
- `lead_contacted` — last_senior_action_at güncellendi
- `lead_converted` — converted_to_student=true
- `booking_scheduled` — PublicBooking@created
- `booking_completed` — status=completed
- `booking_cancelled` — status=canceled/no_show
- `payment_succeeded` — Stripe webhook
- `payment_failed` — Stripe webhook
- `ai_query_submitted` — AiLabsAssistantService@ask
- `ai_feedback_given` — AiLabsFeedbackController

### 5.2 Consent Banner

Her portal layout'unda GDPR uyumlu banner:
- Cookie: `analytics_consent=true|false`
- **Kabul edilmedikçe** PostHog yüklenmez
- "Reddet" → 1 yıl cookie, banner tekrar çıkmaz

### 5.3 Identity Stitching

Lead oluşurken → `distinct_id = lead_{id}`
Lead müşteri olunca → alias oluşturulur → `user_{id}` ile zincirlenir
Aynı kişinin anonymous → lead → user yolculuğu PostHog'ta tek person altında görülür.

### 5.4 Feature Flags

`AnalyticsService::isFeatureEnabled('flag_name')` kullanılır.
PostHog UI'den feature flag oluştur → kod'da kontrol et → yüzde 10 rollout yap.

### 5.5 Session Replay

Tüm consent verilen kullanıcılar kaydediliyor (anonim mode). PostHog dashboard'da:
- **Sessions** sekmesi → tüm session'ları listele
- Tek session → kullanıcının ekran videosu + click/scroll hareketleri

UX sorunu raporlandığında: filter ile session bul, izle, ne yaptığını gör.

---

## 6. KPI Sözlüğü

### Engagement Metrikleri

| Terim | Tanım |
|---|---|
| Active User | Son 7 günde aktivite olan kullanıcı |
| At-risk User | 7-30 gün arası son aktivite |
| Dormant User | 30+ gün inaktif veya hiç aktivite yok |
| Activity Score | Karma: audit + appointment + msg + lead_score ağırlıklı |
| Risk Score | 4 sinyal bazlı öğrenci risk puanı (0-100) |
| Hotness Score | AI kullanan aday öncelik skoru |

### Conversion Metrikleri

| Terim | Tanım |
|---|---|
| Lead → Booking Rate | `booking / guest_application × 100` |
| Booking → Contract Rate | `contract / booking × 100` |
| Contract → Payment Rate | `paid / contract × 100` |
| Time to Conversion | `converted_at - created_at` (gün) |
| CAC | Customer Acquisition Cost (marketing_spend / converted) |

### AI Metrikleri

| Terim | Tanım |
|---|---|
| Intent Key | İlk 6 kelime normalize edilmiş intent grouping |
| Signal Strength | converted_pct - not_converted_pct (conversion prediction) |
| FAQ Candidate | 2+ kez sorulmuş benzer intent |
| Token Cost | Gemini 2.5 Flash: $0.30/1M input, $2.50/1M output |

### Campaign Metrikleri

| Terim | Tanım |
|---|---|
| Before Period | Event_date'ten window gün öncesi |
| After Period | Event_date'ten window gün sonrası |
| Delta % | `(after - before) / before × 100` |
| Lift Threshold | ±5% nötr, üstü pozitif/negatif sinyal |

---

## 7. Günlük / Haftalık / Aylık İş Akışı

### 🌅 Sabah Rutini (5 dk)

1. `/manager/user-intelligence` aç
2. **Dormant alarmlara bak** — yüksek skor + 14+ gün inaktif aday var mı?
3. **Öğrenci risk alarmına bak** — overdue payment + inaktif öğrenci var mı?
4. **Hot Leads**'e göz at — bugün senior'lara dağıtılmaya değer aday var mı?

### 📅 Haftalık (30 dk)

1. `/manager/user-intelligence` → Period **1 Hafta**
2. **Sparkline trend** — bu hafta vs geçen hafta
3. **Top Aktif kullanıcılar → Lead Skoru sıralama** — en yüksek skorlu 10'u gör
4. `/manager/ai-labs/analytics` → **FAQ Adayları** — yeni soru pattern'leri var mı?
5. Yüksek frekanslı FAQ adayı → Knowledge Source olarak ekle veya blog yazısı planla

### 🗓️ Aylık (1-2 saat)

1. Period **1 Ay** seç
2. **Engagement Tiers** — aylık dormant % artıyor mu?
3. **Converted vs Not-Converted Intent Analizi** — hangi konular conversion'a götürüyor?
4. **Kampanya Etki Ölçümü** — geçen ay çıkan tüm kampanya/içerik için tek tek ölçüm
5. **Token cost** (AI Labs) — beklenen aralıkta mı?
6. Sonuçlar → marketing stratejisi + content plan güncellemesi

---

## 8. Veri Altyapısı (Teknik)

### Ana Veri Kaynakları

| Tablo | Kullanım |
|---|---|
| `users` | `last_activity_at`, `presence_status` (UpdateUserPresence middleware) |
| `guest_applications` | Lead scoring, funnel fields, converted_to_student |
| `lead_source_data` | UTM + funnel_*_at timestamps |
| `public_bookings` | Booking events |
| `audit_trails` | Her model değişikliği (create/update/delete/login) |
| `guest_ai_conversations` | Guest AI soru/cevap |
| `senior_ai_conversations` | Senior AI kullanımı |
| `staff_ai_conversations` | Admin/manager AI kullanımı |
| `ai_labs_feedback` | Thumbs up/down |
| `student_appointments` | Randevular |
| `student_payments` | Ödemeler |
| `dm_messages` | İç iletişim |

### Cache / Archive

- **`audit_trails` archival:** Her Pazar 03:30 cron — 90+ gün eski kayıtlar `storage/app/backups/audit-trails/*.jsonl.gz` olarak arşivlenir + DB'den silinir
- **View composers:** brand, ai_labs_name, senior_sidebar_kpi 5 dk cache'te

### PostHog Integration

- `app/Services/Analytics/AnalyticsService.php` — wrapper (capture, identify, alias, feature flag)
- `app/Observers/Analytics/*` — GuestApplication + PublicBooking observers
- `resources/views/components/analytics/posthog-snippet.blade.php` — frontend JS snippet
- `resources/views/components/analytics/consent-banner.blade.php` — GDPR banner
- CSP whitelist: `eu.posthog.com`, `eu-assets.i.posthog.com`, `eu.i.posthog.com`, `*.posthog.com`
- .env: `POSTHOG_API_KEY`, `POSTHOG_HOST`, `POSTHOG_PROJECT_ID`

---

## 9. Sıkça Sorulan Sorular

### "PostHog'da aday görünmüyor"

Adaylar anonim distinct_id ile girer. `lead_{id}` olarak identify edilir lead_created event'inde. PostHog → Persons → search: `lead_`

### "Consent banner çıkmıyor"

- Cookie `analytics_consent` set edilmiş olabilir (önceden kabul/red edilmiş)
- Browser DevTools → Application → Cookies → sil → sayfa yenile → banner tekrar gelir

### "Activity score nasıl hesaplanıyor?"

**Öğrenci:** `audit_trails × 1 + appointment × 3 + dm_messages × 1`
**Aday:** `ai_sorusu × 2 + audit_trail × 1 + lead_score × 0.3`

### "Kampanya etki analizi 0 gösteriyor"

- Veri yetersiz olabilir (küçük window + az trafik)
- `event_date`'i yanlış format girmiş olabilirsin (YYYY-MM-DD kullan)
- Event date çok yakın/çok eski ise before window negatif olur → önceki benzer periyot referans alınır

### "Top users tablosunda aynı kişi görünüyor"

Guest'ken oluşturulup sonra student'a dönüşenler iki kez görünebilir. **Fix planı:** converted_to_student=true olanları guest listesinden çıkar.

### "FAQ Candidates boş görünüyor"

- Yeterli AI soru yok henüz (min 2 kez sorulmuş gerek)
- Son 60 gün filtresi — daha önceki veri sayılmaz
- CSV indirmek için en az 1 aday olmalı

### "Öğrenci risk alarmı çok yanlış pozitif veriyor"

Risk eşiğini yükselt: Controller'da `studentDormantAlerts(cid, 14, 20)` yerine `studentDormantAlerts(cid, 21, 20)` yap (21 gün).

### "Campaign impact metrics negatif delta gösteriyor, bu kötü mü?"

Mutlaka değil. Düşüş normal olabilir:
- Event sonrası hafta sonu/tatil
- Event kendi içinde absorbs traffic (bir sonraki hafta düşer)
- Baseline zaten yüksekti

Mühim olan **trend** — 3-4 event karşılaştır, ortalama çıkar.

---

## 📞 İletişim

Sorular için: technsug@gmail.com
Teknik destek: `docs/DEPLOYMENT.md`, `docs/SYSTEM_OVERVIEW.md`
Event catalog: `docs/EVENT_CATALOG.md` (geliştiriciler için)
Data roadmap: `docs/DATA_COLLECTION_PLAN.md`

---

**Son güncelleme:** 24 Nisan 2026
**Versiyon:** 1.0
**Yazan:** MentorDE Analytics Takımı
