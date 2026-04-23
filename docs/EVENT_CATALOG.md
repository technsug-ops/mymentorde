# 📡 MentorDE — Event Catalog

> **Amaç:** Tüm platformda yakalanacak event'lerin tek kaynağı.
> **Kural:** Yeni event eklenmeden önce bu dosyaya yazılmalı. Rename edilen event yeni event sayılır (eski event deprecated işaretlenir, 90 gün sonra silinir).
> **Tarih:** 23 Nisan 2026
> **Platform:** PostHog

---

## 📐 Naming Convention

### Kurallar

| Kural | Doğru | Yanlış |
|---|---|---|
| snake_case | `booking_created` | `bookingCreated`, `BookingCreated` |
| object_verb formatı | `lesson_completed` | `complete_lesson`, `lessonComplete` |
| Geçmiş zaman | `payment_succeeded` | `payment_success`, `succeed_payment` |
| Özet + net | `form_abandoned` | `user_abandoned_lead_registration_form` |
| Ingilizce | `quiz_attempted` | `quiz_denendi` |

### Event İsimleri = Değişmez Kontrat

Event adı değiştirilemez. Değişecekse:
1. Eski event `deprecated_` prefix'iyle işaretlenir
2. Yeni event paralel olarak çalışır
3. 90 gün sonra eski event kaldırılır
4. Dashboard'lar yeni event'e migrate edilir

---

## 🏷️ Zorunlu Property'ler (Her Event'te)

Her event'te **her zaman** şu property'ler bulunmalı:

```json
{
  "user_id": "usr_123 | null (anonim ise)",
  "session_id": "ses_abc",
  "distinct_id": "PostHog otomatik (anonim → user_id stitch)",
  "timestamp": "2026-04-23T14:30:00Z (PostHog otomatik)",
  "environment": "dev | staging | prod",
  "source": "web | mobile | api | webhook",
  "portal": "guest | student | senior | dealer | manager | marketing_admin | public",
  "company_id": "tenant ID (multi-tenant için)"
}
```

---

## 🔐 PII & GDPR Kuralları

**Event property'sinde ASLA:**
- ❌ Email adresi (raw) → `email_hash: sha256(email)` kullan
- ❌ Telefon numarası (raw) → `phone_hash: sha256(phone)` kullan
- ❌ Şifre, kredi kartı, TC kimlik
- ❌ Ev adresi, açık konum

**Kullanılabilir:**
- ✅ user_id (internal ID)
- ✅ email_domain (ör: `gmail.com`, `outlook.com`)
- ✅ country, city (şehir bazlı)
- ✅ device_type, browser
- ✅ hashed identifiers

**Identify çağrısı** (PostHog `identify()`) ayrı kanal — PII orada yine hash'li tutulur.

---

# 📋 CORE EVENTS — 25 Adet

## 1️⃣ Traffic & Navigation (5 event)

### `page_viewed`
Her sayfa yüklenişi.

| Property | Tip | Örnek | Açıklama |
|---|---|---|---|
| `page_path` | string | `/sss` | URL path |
| `page_title` | string | `"Sık Sorulan Sorular"` | HTML title |
| `referrer` | string | `"https://google.com"` | Önceki sayfa |
| `utm_source` | string | `"google"` | URL'den parse |
| `utm_medium` | string | `"cpc"` | URL'den parse |
| `utm_campaign` | string | `"spring_launch"` | URL'den parse |
| `load_time_ms` | int | `1250` | Performance API |

**Trigger:** PostHog JS snippet otomatik.

---

### `link_clicked`
Dış link tıklaması.

| Property | Tip | Örnek |
|---|---|---|
| `link_url` | string | `"https://youtube.com/..."` |
| `link_text` | string | `"Tanıtım videosu"` |
| `link_location` | string | `"footer"`, `"hero"`, `"blog_post"` |

**Trigger:** `[data-track="link_click"]` veya PostHog auto-capture.

---

### `cta_clicked`
CTA (Call-to-Action) butonu tıklaması.

| Property | Tip | Örnek |
|---|---|---|
| `cta_name` | string | `"book_demo"` |
| `cta_location` | string | `"hero"`, `"pricing_page"`, `"sticky_footer"` |
| `cta_text` | string | `"Ücretsiz Demo Al"` |
| `cta_variant` | string | `"primary"`, `"secondary"` (A/B için) |

**Trigger:** Blade'e `data-track="cta_clicked"` attribute + JS handler.

---

### `form_started`
Kullanıcı formun ilk input'una odaklandı.

| Property | Tip | Örnek |
|---|---|---|
| `form_name` | string | `"lead_signup"`, `"contact_us"` |
| `form_location` | string | `"hero"`, `"contact_page"` |

**Trigger:** İlk input `focus` event'i.

---

### `form_abandoned`
Form başlandı ama submit edilmeden çıkıldı.

| Property | Tip | Örnek |
|---|---|---|
| `form_name` | string | `"lead_signup"` |
| `filled_fields` | array | `["name", "email"]` |
| `abandoned_field` | string | `"phone"` (en son dokunulan) |
| `time_spent_seconds` | int | `47` |

**Trigger:** `beforeunload` + 30 sn inactivity + input değişmiş ama submit edilmemiş.

---

## 2️⃣ Lead & Conversion (6 event)

### `lead_created`
Yeni lead kaydı oluştu.

| Property | Tip | Örnek |
|---|---|---|
| `lead_id` | int | `42` |
| `source` | string | `"organic"`, `"paid"`, `"referral"`, `"dealer"` |
| `utm_source` | string | `"google"` |
| `utm_campaign` | string | `"spring_launch"` |
| `dealer_id` | int\|null | `7` |
| `initial_score` | int | `15` |
| `form_name` | string | `"lead_signup"` |

**Trigger:** `app/Observers/LeadObserver@created`

---

### `lead_contacted`
Senior lead ile iletişim kurdu.

| Property | Tip | Örnek |
|---|---|---|
| `lead_id` | int | `42` |
| `senior_id` | int | `5` |
| `contact_method` | string | `"phone"`, `"email"`, `"whatsapp"` |
| `was_reached` | bool | `true` |
| `duration_seconds` | int\|null | `180` (aramada konuşulduysa) |

**Trigger:** Senior portal'da "İletişim kuruldu" aksiyonu.

---

### `lead_qualified`
Lead hot/sales_ready tier'ına geçti.

| Property | Tip | Örnek |
|---|---|---|
| `lead_id` | int | `42` |
| `old_tier` | string | `"warm"` |
| `new_tier` | string | `"hot"` |
| `score_delta` | int | `+25` |
| `reason` | string | `"demo_scheduled"` |

**Trigger:** `Lead` model observer, tier değişimi.

---

### `lead_converted`
Lead müşteriye dönüştü (ödeme tamamlandı).

| Property | Tip | Örnek |
|---|---|---|
| `lead_id` | int | `42` |
| `user_id` | int | `99` (yeni oluşturulan) |
| `contract_id` | int | `15` |
| `amount` | int | `2500` (EUR cents) |
| `days_to_convert` | int | `12` (lead_created'tan bu yana) |
| `touches_count` | int | `8` (marketing_touches üzerinden) |

**Trigger:** `Payment::succeeded` observer.

---

### `lead_lost`
Lead kaybedildi (manuel veya timeout).

| Property | Tip | Örnek |
|---|---|---|
| `lead_id` | int | `42` |
| `lost_reason` | string | `"not_interested"`, `"budget"`, `"competitor"`, `"timeout_30d"` |
| `last_score` | int | `35` |
| `last_tier` | string | `"warm"` |
| `days_active` | int | `45` |

**Trigger:** Senior manuel "Kaybedildi" veya cron job (30 gün no activity).

---

### `lead_score_changed`
Skor değişimi (her delta için event).

| Property | Tip | Örnek |
|---|---|---|
| `lead_id` | int | `42` |
| `old_score` | int | `30` |
| `new_score` | int | `55` |
| `delta` | int | `+25` |
| `reason` | string | `"demo_completed"`, `"email_opened"` |
| `actor` | string | `"system"`, `"senior_manual"` |

**Trigger:** `LeadScoreLog` model observer.

---

## 3️⃣ Booking (4 event)

### `booking_scheduled`
Randevu oluşturuldu.

| Property | Tip | Örnek |
|---|---|---|
| `booking_id` | int | `88` |
| `senior_id` | int | `5` |
| `lead_id` | int\|null | `42` |
| `student_id` | int\|null | `99` |
| `scheduled_at` | timestamp | `2026-04-25T14:00:00Z` |
| `lead_time_hours` | int | `48` (şimdi ile scheduled_at arası) |
| `meeting_type` | string | `"online"`, `"phone"`, `"offline"` |
| `source` | string | `"self_service"`, `"senior_manual"` |

**Trigger:** `BookingObserver@created`

---

### `booking_reminded`
Randevu hatırlatma email/SMS gönderildi.

| Property | Tip | Örnek |
|---|---|---|
| `booking_id` | int | `88` |
| `reminder_type` | string | `"email_24h"`, `"email_1h"`, `"sms_1h"` |
| `channel` | string | `"email"`, `"sms"`, `"push"` |

**Trigger:** Reminder job.

---

### `booking_completed`
Randevu "done" olarak işaretlendi.

| Property | Tip | Örnek |
|---|---|---|
| `booking_id` | int | `88` |
| `actual_duration_minutes` | int | `45` |
| `planned_duration_minutes` | int | `30` |
| `outcome` | string | `"qualified"`, `"not_qualified"`, `"needs_followup"` |
| `notes_length` | int | `245` (karakter sayısı, içerik değil) |

**Trigger:** Senior "Tamamlandı" aksiyonu.

---

### `booking_cancelled`
Randevu iptal edildi.

| Property | Tip | Örnek |
|---|---|---|
| `booking_id` | int | `88` |
| `cancellation_reason` | string | `"student_unavailable"`, vb. (8 kategori) |
| `cancelled_by` | string | `"student"`, `"senior"`, `"system"` |
| `hours_before_scheduled` | int | `6` (ne kadar önce iptal) |
| `was_rescheduled` | bool | `true` |

**Trigger:** Booking iptal aksiyonu.

---

## 4️⃣ Payment (4 event)

### `checkout_started`
Stripe checkout sayfası açıldı.

| Property | Tip | Örnek |
|---|---|---|
| `contract_id` | int | `15` |
| `plan_name` | string | `"gold_monthly"` |
| `amount` | int | `2500` (EUR cents) |
| `currency` | string | `"EUR"` |

**Trigger:** Checkout redirect öncesi.

---

### `payment_method_added`
Kullanıcı kart bilgisini ekledi.

| Property | Tip | Örnek |
|---|---|---|
| `payment_method_type` | string | `"card"`, `"sepa"` |
| `card_brand` | string | `"visa"`, `"mastercard"` |
| `country` | string | `"DE"` |

**Trigger:** Stripe webhook `payment_method.attached`.

---

### `payment_succeeded`
Ödeme başarılı.

| Property | Tip | Örnek |
|---|---|---|
| `payment_id` | int | `120` |
| `contract_id` | int | `15` |
| `amount` | int | `2500` |
| `currency` | string | `"EUR"` |
| `payment_method` | string | `"card"` |
| `is_first_payment` | bool | `true` |

**Trigger:** Stripe webhook `payment_intent.succeeded`.

---

### `payment_failed`
Ödeme başarısız.

| Property | Tip | Örnek |
|---|---|---|
| `contract_id` | int | `15` |
| `amount` | int | `2500` |
| `failure_code` | string | `"card_declined"`, `"insufficient_funds"` |
| `failure_message` | string | `"Your card was declined."` |
| `attempt_number` | int | `1` |

**Trigger:** Stripe webhook `payment_intent.payment_failed`.

---

## 5️⃣ Academic & AI (6 event)

### `lesson_started`
Öğrenci derse başladı.

| Property | Tip | Örnek |
|---|---|---|
| `lesson_id` | int | `33` |
| `course_id` | int | `5` |
| `module_id` | int | `12` |
| `content_type` | string | `"video"`, `"text"`, `"quiz"` |
| `estimated_duration_min` | int | `15` |

**Trigger:** Ders sayfası yükleme.

---

### `lesson_completed`
Ders %100 tamamlandı.

| Property | Tip | Örnek |
|---|---|---|
| `lesson_id` | int | `33` |
| `actual_duration_seconds` | int | `720` |
| `estimated_duration_seconds` | int | `900` |
| `completion_ratio` | float | `1.0` |
| `attempts` | int | `1` (daha önce başladıysa) |

**Trigger:** Video bittiği veya quiz geçildiği an.

---

### `lesson_abandoned`
Ders yarıda bırakıldı (%50'den az + 7 gün geri dönüş yok).

| Property | Tip | Örnek |
|---|---|---|
| `lesson_id` | int | `33` |
| `progress_percent` | float | `0.35` |
| `days_since_last_activity` | int | `8` |
| `last_position_seconds` | int | `210` |

**Trigger:** Günlük cron job.

---

### `quiz_attempted`
Quiz cevabı gönderildi.

| Property | Tip | Örnek |
|---|---|---|
| `quiz_id` | int | `77` |
| `lesson_id` | int | `33` |
| `attempt_number` | int | `2` |
| `score` | float | `0.85` |
| `passed` | bool | `true` |
| `time_spent_seconds` | int | `180` |

**Trigger:** Quiz submit.

---

### `ai_query_submitted`
Kullanıcı AI asistana soru sordu.

| Property | Tip | Örnek |
|---|---|---|
| `session_id` | string | `"ai_sess_abc"` |
| `portal` | string | `"student"`, `"senior"`, `"guest"` |
| `prompt_length` | int | `145` (karakter) |
| `source_ids_used` | array | `[3, 7, 12]` |
| `prompt_tokens` | int | `320` |
| `completion_tokens` | int | `850` |
| `response_time_ms` | int | `2400` |
| `model` | string | `"gemini-2.5-flash"` |

**Trigger:** AI response tamamlandıktan sonra.

---

### `ai_feedback_given`
AI cevabına geri bildirim.

| Property | Tip | Örnek |
|---|---|---|
| `session_id` | string | `"ai_sess_abc"` |
| `message_id` | int | `4521` |
| `feedback` | string | `"thumbs_up"`, `"thumbs_down"` |
| `feedback_text` | string\|null | `"cevap yüzeyseldi"` |

**Trigger:** Feedback butonu tıklama.

---

# 🔄 Identity Stitching

### `identify()` Çağrısı

Kayıt olma, giriş yapma, lead oluşturma anlarında:

```javascript
// Frontend (JS)
posthog.identify(user.id, {
    email_hash: sha256(user.email),
    email_domain: user.email.split('@')[1],
    role: user.role,
    company_id: user.company_id,
    signup_date: user.created_at,
    plan: user.plan,
});
```

```php
// Backend (PHP)
PostHog::identify([
    'distinctId' => $user->id,
    'properties' => [
        'email_hash' => hash('sha256', $user->email),
        'email_domain' => explode('@', $user->email)[1],
        'role' => $user->role,
        'company_id' => $user->company_id,
    ],
]);
```

### `alias()` — Anonymous → Identified

```javascript
// Lead oluşunca: anonim cookie'yi lead_id'ye bağla
posthog.alias(`lead_${leadId}`);

// Kayıt olunca: lead_id'yi user_id'ye bağla
posthog.alias(`user_${userId}`, `lead_${leadId}`);
```

---

# 🎬 Super Properties (Her Event'e Otomatik Eklenir)

PostHog'da "super properties" — her event'te otomatik eklenecek:

```javascript
posthog.register({
    app_version: "1.0.0",
    portal: "student",
    company_id: 1,
    is_impersonated: false,
});
```

---

# 📊 Dashboards (PostHog'da Kurulacak)

| Dashboard | Ana Event'ler | Amaç |
|---|---|---|
| **Acquisition Funnel** | page_viewed → form_started → lead_created | Trafik → Lead |
| **Activation Funnel** | lead_created → lead_contacted → booking_completed → payment_succeeded | Lead → Paid |
| **Engagement** | lesson_started, lesson_completed, ai_query_submitted | Kullanım |
| **Retention** | payment_succeeded (2+ ay) | Yenileme |
| **Drop-off** | form_abandoned, lesson_abandoned, booking_cancelled | Kayıp |
| **AI Usage** | ai_query_submitted, ai_feedback_given | AI ROI |

---

# ⚠️ Event Ekleme Süreci

1. Bu dosyaya event schema'sı eklenir (property tipleri + örnekler)
2. Backend/frontend kodu yazılır
3. PR açılmadan önce QA checklist:
   - [ ] `environment` property doğru set ediliyor mu?
   - [ ] PII içermiyor mu?
   - [ ] Naming convention'a uyuyor mu?
   - [ ] PostHog'da event görünüyor mu?
4. PR merge sonrası dashboard'da görünüm doğrulanır

---

# 📝 Sürüm Geçmişi

| Tarih | Değişiklik | Yazan |
|---|---|---|
| 2026-04-23 | İlk sürüm — 25 core event | Claude + technsug |

---

*Kaynak doküman. Değişiklik önerileri için PR açın.*
