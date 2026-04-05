# MentorDE — Bildirim Sistemi (Notification System)

**Versiyon:** 2.0
**Son Güncelleme:** 2026-03-08
**Stack:** Laravel 12 / PHP 8.4 / MySQL

> **Uyumluluk:** Sözleşme Sistemi v5.0 · Marketing & Sales v3.0 · Task Board v2.0

---

## 1. Genel Mimari

MentorDE bildirim sistemi **veritabanı-merkezli** (DB-first) bir yaklaşım kullanır.
Her bildirim önce `notification_dispatches` tablosuna yazılır, ardından Job aracılığıyla gönderilir.

```
Tetikleyici (Controller / Service / Command)
    │
    ▼
NotificationService::send()                  ← Deduplicate + opt-out kontrolü
    │
    ├── Duplicate? → skip (log + return)
    ├── Opt-out?   → status: 'skipped', skip_reason kaydedilir
    │
    ▼
NotificationDispatch::create()               ← status: 'pending'
    │
    ▼
SendNotificationJob::dispatch()              ← status: 'queued'
    │
    ▼
SendNotificationJob::handle()
    ├── email     → Mail::to()->send(NotificationMail)
    ├── in_app    → status: 'sent' (DB kaydı yeterli, frontend polling ile gösterilir)
    └── whatsapp  → WhatsAppService::sendTemplate($phone, 'mentorde_notification', [$body])
    │         ├── API false → hemen failed (kalıcı)
    │         └── Exception → throw $e → kuyruk 3x dener
    │
    ▼
status: 'sent' veya 'failed'
```

### 1.1 v1 → v2 Temel Değişiklikler

| Değişiklik | v1 | v2 |
|---|---|---|
| Bildirim oluşturma | Doğrudan `create()` | `NotificationService::send()` üzerinden |
| Deduplicate | Sadece due-reminder'da | Tüm bildirim oluşturma noktalarında |
| Okunma takibi | `status=pending` (semantik hata) | Ayrı `is_read` kolonu |
| Template engine | Yok (placeholder'lar replace edilmiyordu) | `TemplateRenderer::render()` aktif |
| Kategori | 6 genel | 22 spesifik (modül bazlı) |
| Opt-out | Sadece job içinde (kayıt yine oluşuyordu) | Kayıt öncesi kontrol, skip kaydı |
| Bildirim görünürlüğü | Şirket bazlı (herkes her şeyi görüyor) | `NotificationScopeService` ile rol+sahiplik filtresi |
| Bildirim validation | Yok (boş user_id ile kayıt oluşuyor) | Kanal bazlı zorunlu alan kontrolü |
| Retention | Yok | 90 gün + cleanup scheduler |
| Scheduler isimleri | `tasks:*` (tutarsız) | `task:*` (Task Board v2.0 ile uyumlu) |

---

## 2. Veritabanı Şeması

### 2.1 `notification_dispatches` Tablosu

| Sütun | Tip | Nullable | Default | Açıklama |
|-------|-----|----------|---------|----------|
| `id` | bigint PK | — | — | — |
| `user_id` | bigint FK→users | ✓ | NULL | Staff/admin bildirimleri için hedef kullanıcı |
| `company_id` | bigint | ✓ | NULL | Şirket izolasyonu |
| `template_id` | bigint FK→message_templates | ✓ | NULL | MessageTemplate ilişkisi |
| `channel` | string(32) | ✗ | — | `email` · `in_app` · `whatsapp` |
| `category` | string(64) | ✗ | — | Bildirim kategorisi (bakınız §2.4) |
| `student_id` | string(64) | ✓ | NULL | Öğrenci bildirimleri için |
| `guest_id` | string(64) | ✓ | NULL | Guest bildirimleri için |
| `recipient_email` | string | ✓ | NULL | Email kanalı alıcısı |
| `recipient_phone` | string(60) | ✓ | NULL | WhatsApp kanalı alıcısı |
| `recipient_name` | string(190) | ✓ | NULL | Alıcı adı |
| `subject` | string | ✓ | NULL | Bildirim başlığı |
| `body` | longText | ✗ | — | Bildirim içeriği (render edilmiş) |
| `variables` | JSON | ✓ | NULL | Template değişkenleri (orijinal) |
| `status` | string(16) | ✗ | `pending` | `pending` · `queued` · `sent` · `failed` · `skipped` |
| `is_read` | boolean | ✗ | false | In-app bildirimlerde okundu işareti |
| `read_at` | datetime | ✓ | NULL | Okunma zamanı |
| `queued_at` | datetime | ✓ | NULL | Queue zamanı |
| `sent_at` | datetime | ✓ | NULL | Gönderim zamanı |
| `failed_at` | datetime | ✓ | NULL | Başarısızlık zamanı |
| `fail_reason` | text | ✓ | NULL | Hata detayı (max 500 char) |
| `skip_reason` | string(64) | ✓ | NULL | Atlanma nedeni (opt-out, duplicate vb.) |
| `source_type` | string(64) | ✓ | NULL | Tetikleyen event tipi |
| `source_id` | string(64) | ✓ | NULL | Tetikleyen kaydın ID'si |
| `triggered_by` | string | ✓ | NULL | Tetikleyen kullanıcı/sistem |
| `created_at` | datetime | — | — | — |
| `updated_at` | datetime | — | — | — |

**v1'den farklar:** `guest_id` eklendi (Guest bildirimleri için), `is_read` + `read_at` eklendi (okunma takibi), `skip_reason` eklendi (opt-out/deduplicate kayıt), `status` enum'a `skipped` eklendi.

**Indexler:**

```
INDEX (status, channel)                    — status + channel filtreleri
INDEX (student_id, category)               — öğrenci bildirim sorguları
INDEX (guest_id, category)                 — guest bildirim sorguları
INDEX (source_type, source_id)             — kaynak takibi
INDEX (user_id, channel, is_read)          — staff/admin okunmamış sorguları
INDEX (company_id)                         — şirket izolasyonu
INDEX (created_at)                         — retention cleanup
INDEX (category, source_type, source_id,
       user_id, status)                    — deduplicate sorgusu
```

### 2.2 `notification_preferences` Tablosu (YENİ)

Kullanıcı bazlı bildirim tercihleri.

| Sütun | Tip | Nullable | Default | Açıklama |
|-------|-----|----------|---------|----------|
| `id` | bigint PK | — | — | — |
| `user_id` | bigint FK→users | ✓ | NULL | Staff/admin kullanıcı |
| `guest_id` | string(64) | ✓ | NULL | Guest kullanıcı |
| `student_id` | string(64) | ✓ | NULL | Student kullanıcı |
| `company_id` | bigint | ✓ | NULL | Şirket izolasyonu |
| `channel` | string(32) | ✗ | — | `email` · `in_app` · `whatsapp` |
| `category` | string(64) | ✗ | `*` | Kategori (`*` = tümü) |
| `is_enabled` | boolean | ✗ | true | Bu kanal+kategori aktif mi? |
| `created_at` | datetime | — | — | — |
| `updated_at` | datetime | — | — | — |

**Index:** `UNIQUE (user_id, guest_id, student_id, channel, category)`

**Mantık:** `notification_preferences` tablosunda kayıt yoksa → varsayılan davranış (enabled). Kayıt varsa ve `is_enabled=false` → o kategori+kanal devre dışı. Bu tablo `guest_applications` tablosundaki `notify_email/whatsapp/inapp` alanlarının yerini alır — tek tablo, tek mantık.

**Devre dışı bırakılamayan kategoriler:** `task_escalation_level3`, `contract_approved`, `contract_rejected` — bu bildirimler her zaman gönderilir.

### 2.3 Migrations

| Dosya | İçerik |
|---|---|
| (mevcut) `create_notification_dispatches_table` | Ana tablo |
| `2026_03_08_000010_add_v2_fields_to_notification_dispatches` | `guest_id`, `is_read`, `read_at`, `skip_reason` ekleme, `status` enum güncelleme |
| `2026_03_08_000011_create_notification_preferences_table` | Tercih tablosu |
| `2026_03_08_000012_migrate_guest_notify_preferences` | `guest_applications.notify_*` → `notification_preferences` veri göçü |

### 2.4 Kategori Kataloğu (22 Kategori)

**Task Bildirimleri (7):**

| Kategori | Kanal | Tetikleyen | Alıcı |
|----------|-------|-----------|-------|
| `task_assigned` | in_app + email | TaskBoardController | Atanan kişi |
| `task_status_changed` | in_app | TaskBoardController | Oluşturan + atanan |
| `task_comment_added` | in_app | TaskCommentController | Atanan (kendi yorumu hariç) |
| `task_due_reminder` | in_app + email | SendTaskDueRemindersCommand | Atanan kişi |
| `task_escalation_level1` | in_app + email | TaskEscalationService | Atanan + dept. admin |
| `task_escalation_level2` | in_app + email | TaskEscalationService | Dept. admin + manager |
| `task_escalation_level3` | in_app + email | TaskEscalationService | Manager (devre dışı bırakılamaz) |
| `task_dependency_completed` | in_app | TaskBoardController (mark-done) | Bağımlı task'ın atananı |

**Sözleşme Bildirimleri (6):**

| Kategori | Kanal | Tetikleyen | Alıcı |
|----------|-------|-----------|-------|
| `contract_requested` | in_app + email | GuestWorkflowController | Atanan senior + operations_admin |
| `contract_sent_to_guest` | in_app + email | GuestWorkflowController | Guest (email) |
| `contract_signed_uploaded` | in_app + email | GuestPortalController | Operations_admin + manager |
| `contract_approved` | in_app + email | ContractApprovalController | Guest + atanan senior (devre dışı bırakılamaz) |
| `contract_rejected` | in_app + email | ContractApprovalController | Guest + atanan senior (devre dışı bırakılamaz) |
| `contract_cancelled` | in_app + email | ContractCancelController | Guest + atanan senior |

**Marketing & Sales Bildirimleri (5):**

| Kategori | Kanal | Tetikleyen | Alıcı |
|----------|-------|-----------|-------|
| `lead_score_tier_change` | in_app | LeadScoringService | Sales_admin + ilgili staff |
| `ab_test_significant` | in_app | ABTestingService | Marketing_admin |
| `email_campaign` | email | ProcessEmailQueueCommand | Kampanya aboneleri |
| `workflow_notification` | in_app + email | WorkflowEngineService | Workflow node tanımındaki alıcı |
| `campaign_completed` | in_app | ProcessEmailQueueCommand | Marketing_admin |

**Öğrenci/Guest Bildirimleri (3):**

| Kategori | Kanal | Tetikleyen | Alıcı |
|----------|-------|-----------|-------|
| `institution_document_shared` | email | SeniorPortalController | Öğrenci |
| `student_onboarding_welcome` | email | StudentActivationService | Öğrenci |
| `guest_registration_confirmation` | email | GuestRegistrationController | Guest |

**Bayi Bildirimleri (1):**

| Kategori | Kanal | Tetikleyen | Alıcı |
|----------|-------|-----------|-------|
| `dealer_payout_pending` | in_app + email | PayoutService | Manager (onay talebi) |

---

## 3. Durum Makinesi (Status Flow)

```
              ┌──────────────┐
              │   pending    │ ← NotificationService::send() ile oluşturulur
              └──────┬───────┘
                     │ SendNotificationJob::dispatch()
                     ▼
              ┌──────────────┐
              │   queued     │ ← Job kuyruğa alındı
              └──────┬───────┘
                     │ Job çalıştı
          ┌──────────┼──────────┐
          ▼          │          ▼
   ┌────────────┐    │   ┌────────────┐
   │    sent    │    │   │   failed   │
   └────────────┘    │   └──────┬─────┘
                     │          │ retryFailed()
                     │          ▼
                     │   ┌────────────┐
                     │   │   queued   │ ← tekrar kuyruğa
                     │   └────────────┘
                     │
                     ▼
              ┌──────────────┐
              │   skipped    │ ← Opt-out veya duplicate (kayıt oluşur, gönderilmez)
              └──────────────┘
```

### Status Semantiği

| Status | Anlamı | Geçiş Yapan |
|--------|--------|-------------|
| `pending` | Oluşturuldu, henüz queue'ye alınmadı | `NotificationService::send()` |
| `queued` | `SendNotificationJob` kuyruğa alındı | `NotificationService::send()` |
| `sent` | Başarıyla gönderildi | `SendNotificationJob::handle()` |
| `failed` | Hata oluştu — `fail_reason` alanında detay var | `SendNotificationJob::handle()` |
| `skipped` | Opt-out veya duplicate — `skip_reason` alanında detay var | `NotificationService::send()` |

### `fail_reason` Değerleri

| Değer | Anlamı |
|-------|--------|
| `recipient_missing` | `recipient_email` veya `recipient_phone` boş |
| `mail_send_error: <msg>` | SMTP hatası |
| `template_render_error` | Template render başarısız |

### `skip_reason` Değerleri

| Değer | Anlamı |
|-------|--------|
| `opt_out:{channel}` | Kullanıcı bu kanalı devre dışı bırakmış |
| `opt_out:{channel}:{category}` | Kullanıcı bu kanal+kategoriyi devre dışı bırakmış |
| `duplicate` | Aynı bildirim zaten gönderilmiş (deduplicate) |

---

## 4. Merkezi Bildirim Servisi (YENİ)

v1'de her modül doğrudan `NotificationDispatch::create()` çağırıyordu. v2'de tüm bildirimler `NotificationService` üzerinden geçer.

### 4.1 NotificationService

**Dosya:** `app/Services/NotificationService.php`

```php
class NotificationService
{
    /**
     * Ana gönderim metodu. Tüm bildirimler buradan geçer.
     *
     * @param array $params [
     *   'channel'        => 'email'|'in_app'|'whatsapp',
     *   'category'       => string (§2.4 kataloğundan),
     *   'user_id'        => ?int,
     *   'guest_id'       => ?string,
     *   'student_id'     => ?string,
     *   'company_id'     => ?int,
     *   'recipient_email'=> ?string,
     *   'recipient_name' => ?string,
     *   'subject'        => ?string,
     *   'body'           => ?string (template_id yoksa zorunlu),
     *   'template_id'    => ?int (varsa body + subject template'den render edilir),
     *   'variables'      => ?array (template değişkenleri),
     *   'source_type'    => ?string,
     *   'source_id'      => ?string,
     *   'triggered_by'   => ?string,
     * ]
     */
    public function send(array $params): ?NotificationDispatch
    {
        // 1. Deduplicate kontrolü
        // 2. Opt-out kontrolü (notification_preferences)
        // 3. Template render (template_id varsa)
        // 4. NotificationDispatch::create()
        // 5. SendNotificationJob::dispatch()
        // 6. Return dispatch kaydı
    }

    /**
     * Çoklu alıcıya gönderim (eskalasyon, toplu güncelleme).
     */
    public function sendToMany(array $userIds, array $params): Collection { ... }
}
```

### 4.2 Deduplicate Mantığı

Her `send()` çağrısında kontrol edilir:

```php
$exists = NotificationDispatch::query()
    ->where('category', $params['category'])
    ->where('source_type', $params['source_type'])
    ->where('source_id', $params['source_id'])
    ->where('user_id', $params['user_id'])
    ->whereIn('status', ['pending', 'queued', 'sent'])
    ->where('created_at', '>=', now()->subHours(24))
    ->exists();
```

Eğer `$exists === true` → kayıt oluşturulur ama `status='skipped'`, `skip_reason='duplicate'`.

**İstisna:** `task_due_reminder` kategorisi daily olduğundan deduplicate penceresi 24 saat değil, aynı gün (`whereDate('created_at', today())`) olarak kontrol edilir.

### 4.3 Opt-Out Kontrolü

```php
NotificationPreferenceService::isEnabled(
    userId: $params['user_id'],
    guestId: $params['guest_id'],
    studentId: $params['student_id'],
    channel: $params['channel'],
    category: $params['category']
): bool
```

**Kontrol sırası:**
1. Kategori `NON_DISMISSABLE_CATEGORIES` listesindeyse → her zaman enabled
2. `notification_preferences` tablosunda bu kanal+kategori için `is_enabled=false` kaydı varsa → disabled
3. `notification_preferences` tablosunda bu kanal için `category='*'` ve `is_enabled=false` kaydı varsa → disabled
4. Kayıt yoksa → varsayılan enabled

**Devre dışı bırakılamayan kategoriler:**
```php
const NON_DISMISSABLE_CATEGORIES = [
    'task_escalation_level3',
    'contract_approved',
    'contract_rejected',
];
```

---

## 5. Template Engine (YENİ)

### 5.1 TemplateRenderer

**Dosya:** `app/Services/TemplateRenderer.php`

```php
class TemplateRenderer
{
    /**
     * Template body içindeki {{variable}} placeholder'larını replace eder.
     *
     * @param string $template  — body_tr, body_de veya body_en
     * @param array  $variables — ['student_name' => 'Ahmet', 'due_date' => '2026-03-15', ...]
     * @return string           — render edilmiş body
     */
    public function render(string $template, array $variables): string
    {
        // 1. {{variable}} pattern match
        // 2. XSS koruması: e($value) uygula
        // 3. Bilinmeyen variable → boş string + log warning
        // 4. Return rendered string
    }
}
```

### 5.2 MessageTemplate Alanları

| Alan | Açıklama |
|------|----------|
| `name` | Template adı (unique per company) |
| `channel` | `email` · `whatsapp` · `in_app` |
| `category` | Kategori (§2.4 ile eşleşmeli) |
| `subject_tr` / `subject_de` / `subject_en` | Dile göre başlık |
| `body_tr` / `body_de` / `body_en` | Dile göre içerik (HTML destekli) |
| `variables` | JSON — kullanılan `{{variable}}` listesi |
| `is_active` | Aktif mi? |
| `company_id` | Şirket izolasyonu |

**Dil seçimi:** Guest/Student'ın `preferred_language` alanına göre. Yoksa → `tr` varsayılan.

### 5.3 Standart Değişkenler

Her template'de kullanılabilecek ortak değişkenler:

| Değişken | Açıklama | Örnek |
|----------|----------|-------|
| `{{recipient_name}}` | Alıcı adı soyadı | Ahmet Yılmaz |
| `{{company_name}}` | Şirket adı | MentorDE |
| `{{platform_url}}` | Platform ana URL | https://app.mentorde.com |
| `{{current_date}}` | Bugünün tarihi | 08.03.2026 |

**Kategori bazlı ek değişkenler örnekleri:**

Task: `{{task_title}}`, `{{task_id}}`, `{{task_due_date}}`, `{{task_url}}`, `{{assigner_name}}`
Sözleşme: `{{contract_status}}`, `{{guest_name}}`, `{{senior_name}}`, `{{contract_url}}`
Marketing: `{{lead_name}}`, `{{lead_score}}`, `{{lead_tier}}`, `{{test_name}}`, `{{winner_variant}}`

**API:** `GET/POST/PUT /api/message-templates`
**Middleware:** `permission:notification.manage`

---

## 6. Kanal Sistemi

### 6.1 Email Kanalı (`channel = 'email'`)

**Aktif — Production'da SMTP gerektirir.**

```
NotificationDispatch (channel=email, recipient_email dolu)
    │
    ▼
SendNotificationJob::handle()
    │
    ├─ recipient_email boş? → status='failed', fail_reason='recipient_missing'
    │
    └─ Mail::to($recipientEmail)->send(new NotificationMail($subject, $body))
           │
           └─ resources/views/mail/notification.blade.php
```

**Mail Template (`mail/notification.blade.php`) Yapısı:**
- Header: `#1a3c6b` koyu mavi, "MentorDE — Almanya Danışmanlık Platformu"
- Body: `{!! nl2br(e($mailBody)) !!}` (newline desteği, XSS korumalı)
- Footer: platform URL + unsubscribe linki (kategori bazlı)

**Unsubscribe Linki (YENİ):**
```
{{platform_url}}/notifications/unsubscribe?token={{unsubscribe_token}}&category={{category}}
```
Token: `hash(user_id + category + app_key)` — stateless, DB sorgusu gerektirmez.

**Mail Config (`.env`):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@mentorde.com
MAIL_PASSWORD=secret
MAIL_FROM_ADDRESS=noreply@mentorde.com
MAIL_FROM_NAME="MentorDE"
```
> Development'ta `MAIL_MAILER=log` (varsayılan).

### 6.2 In-App Kanalı (`channel = 'in_app'`)

**Aktif — Polling bazlı.**

In-app bildirimler `user_id` (staff/admin) veya `guest_id`/`student_id` (portal kullanıcıları) alanı dolu olan kayıtlardır. Frontend, dashboard yüklendiğinde ve belirli aralıklarla DB sorgusu ile çeker.

**Okunmamış bildirim sorgusu (düzeltilmiş):**

```php
// Staff/Admin Dashboard
$unreadNotifications = NotificationDispatch::query()
    ->where('user_id', $userId)
    ->where('channel', 'in_app')
    ->where('is_read', false)
    ->whereIn('status', ['sent', 'pending', 'queued'])
    ->latest()
    ->limit(20)
    ->get(['id', 'category', 'subject', 'body', 'source_type', 'source_id', 'created_at']);

// Student Portal
$notifications = NotificationDispatch::query()
    ->where('student_id', $studentId)
    ->where('channel', 'in_app')
    ->where('is_read', false)
    ->whereIn('status', ['sent', 'pending', 'queued'])
    ->latest()
    ->limit(20)
    ->get();
```

**v1 farkı:** `is_read=false` filtresi kullanılıyor (v1'de `status=pending` kullanılıyordu — semantik hata).

**Polling stratejisi:**
- Dashboard yüklendiğinde → ilk sorgu
- 60 saniyede bir → polling (aktif sekme iken)
- Bildirim badge: okunmamış sayısı (navbar'da gösterilir)
- Gelecek: Pusher/WebSocket entegrasyonu ile gerçek zamanlı push

**Okundu İşaretleme:**

```
POST /api/notifications/{id}/mark-read          → tek bildirim
POST /api/notifications/mark-all-read            → tüm okunmamış
```

```php
// Tek bildirim
$dispatch->update(['is_read' => true, 'read_at' => now()]);

// Toplu
NotificationDispatch::query()
    ->where('user_id', $userId)
    ->where('channel', 'in_app')
    ->where('is_read', false)
    ->update(['is_read' => true, 'read_at' => now()]);
```

### 6.3 WhatsApp Kanalı (`channel = 'whatsapp'`)

**Pasif — Entegre edilmemiş.**

Job içinde log seviyesinde kaydedilir, gönderilmez. Gelecek entegrasyon için altyapı hazır.
Önerilen entegrasyon: Twilio WhatsApp API veya Meta Business API.

---

## 7. Bildirim Oluşturan Modüller

### 7.1 Task Board Bildirimleri

**Dosya:** `TaskBoardController`, `TaskCommentController`, `TaskEscalationService`, `SendTaskDueRemindersCommand`

#### 7.1.1 Görev Atandı

**Ne zaman:** Task oluşturulduğunda veya atanan değiştirildiğinde
**Tetikleyen:** `TaskBoardController::store()`, `TaskBoardController::update()`

```php
app(NotificationService::class)->send([
    'channel'     => 'in_app',
    'category'    => 'task_assigned',
    'user_id'     => $task->assigned_user_id,
    'company_id'  => $task->company_id,
    'subject'     => "Yeni Görev: {$task->title}",
    'body'        => "{$assigner->name} size bir görev atadı: #{$task->id} — {$task->title}",
    'source_type' => 'task_assigned',
    'source_id'   => (string) $task->id,
    'triggered_by'=> (string) auth()->id(),
]);

// Email kanalı da gönder
app(NotificationService::class)->send([
    'channel'        => 'email',
    'category'       => 'task_assigned',
    'user_id'        => $task->assigned_user_id,
    'recipient_email'=> $assignedUser->email,
    'recipient_name' => $assignedUser->name,
    'company_id'     => $task->company_id,
    'template_id'    => MessageTemplate::findByCategory('task_assigned')?->id,
    'variables'      => [
        'task_title'  => $task->title,
        'task_id'     => $task->id,
        'task_url'    => url("/tasks/{$task->department}#{$task->id}"),
        'assigner_name'=> $assigner->name,
    ],
    'source_type' => 'task_assigned',
    'source_id'   => (string) $task->id,
]);
```

#### 7.1.2 Görev Durumu Değişti

**Ne zaman:** Status güncellendiğinde
**Kime:** Oluşturan + atanan (değişikliği yapan kişi hariç)

```php
$recipients = collect([$task->created_by_user_id, $task->assigned_user_id])
    ->unique()
    ->reject(fn($id) => $id === auth()->id());

app(NotificationService::class)->sendToMany($recipients->toArray(), [
    'channel'     => 'in_app',
    'category'    => 'task_status_changed',
    'company_id'  => $task->company_id,
    'subject'     => "Task #{$task->id} Durumu Değişti",
    'body'        => "#{$task->id} '{$task->title}': {$oldStatus} → {$newStatus}",
    'source_type' => 'task_status_changed',
    'source_id'   => (string) $task->id,
]);
```

#### 7.1.3 Yorum Eklendi

**Ne zaman:** Task'a yorum eklendiğinde
**Kime:** Task'a atanan kişi (kendi yorumu hariç)

```php
if ($task->assigned_user_id && $task->assigned_user_id !== auth()->id()) {
    app(NotificationService::class)->send([
        'channel'     => 'in_app',
        'category'    => 'task_comment_added',
        'user_id'     => $task->assigned_user_id,
        'company_id'  => $task->company_id,
        'subject'     => 'Yeni Task Yorumu',
        'body'        => "Task #{$task->id} '{$task->title}' için " . auth()->user()->name . " yorum ekledi.",
        'source_type' => 'task_comment_added',
        'source_id'   => (string) $task->id,
    ]);
}
```

#### 7.1.4 Due Date Hatırlatma

**Ne zaman:** Her gün 08:00 (scheduler)
**Kime:** Task'a atanan kişi
**Deduplicate:** Aynı gün, aynı task, aynı kullanıcı — tekrar gönderilmez.

```php
// SendTaskDueRemindersCommand (scheduler: task:send-due-reminders)
// Yarın vadeli → category: task_due_reminder, body: "[YARIN]..."
// Bugün vadeli → category: task_due_reminder, body: "[BUGÜN]..."
// Geçmiş vadeli + status != done → category: task_due_reminder, body: "[GECİKTİ]..."
```

Bugün ve geçmiş vadeli olanlara ek olarak email kanalı da gönderilir.

#### 7.1.5 Eskalasyon Bildirimleri

**Ne zaman:** SLA süresi aşıldığında (her 30 dakikada kontrol)
**Kime:** Seviyeye göre değişir (bakınız §2.4)

```php
// TaskEscalationService::sendEscalationNotifications()
// Seviye 1: in_app + email → atanan + dept.admin
// Seviye 2: in_app + email → dept.admin + manager
// Seviye 3: in_app + email → manager (devre dışı bırakılamaz) + priority→urgent
```

#### 7.1.6 Bağımlılık Tamamlandı

**Ne zaman:** Bağımlılık task'ı `done` olduğunda
**Kime:** Bağımlı task'ın atananı

```php
// TaskBoardController::markDone() içinde
$dependentTasks = MarketingTask::where('depends_on_task_id', $task->id)->get();
foreach ($dependentTasks as $depTask) {
    $depTask->update(['status' => 'todo']);
    app(NotificationService::class)->send([
        'channel'     => 'in_app',
        'category'    => 'task_dependency_completed',
        'user_id'     => $depTask->assigned_user_id,
        'company_id'  => $depTask->company_id,
        'subject'     => "Bağımlılık Tamamlandı",
        'body'        => "Task #{$task->id} tamamlandı. #{$depTask->id} '{$depTask->title}' artık başlayabilir.",
        'source_type' => 'task_dependency_completed',
        'source_id'   => (string) $depTask->id,
    ]);
}
```

---

### 7.2 Sözleşme Bildirimleri

**Dosya:** `GuestWorkflowController`, `GuestPortalController`, `ContractApprovalController`, `ContractCancelController`

#### 7.2.1 Sözleşme Talep Edildi

**Ne zaman:** Guest sözleşme talep butonu tıklandığında
**Kime:** Atanan senior + operations_admin

```php
app(NotificationService::class)->sendToMany([$seniorUserId, $opsAdminUserId], [
    'channel'     => 'in_app',
    'category'    => 'contract_requested',
    'company_id'  => $guest->company_id,
    'subject'     => "Sözleşme Talebi: {$guest->first_name} {$guest->last_name}",
    'body'        => "Guest #{$guest->id} sözleşme talep etti.",
    'source_type' => 'guest_contract_requested',
    'source_id'   => (string) $guest->id,
]);

// Email kanalı — template ile
app(NotificationService::class)->send([
    'channel'        => 'email',
    'category'       => 'contract_requested',
    'user_id'        => $seniorUserId,
    'recipient_email'=> $senior->email,
    'template_id'    => MessageTemplate::findByCategory('contract_requested')?->id,
    'variables'      => ['guest_name' => $guest->full_name, 'guest_id' => $guest->id],
    'source_type'    => 'guest_contract_requested',
    'source_id'      => (string) $guest->id,
]);
```

#### 7.2.2 Sözleşme Guest'e Gönderildi

**Ne zaman:** Manager sözleşmeyi guest'e gönderdiğinde
**Kime:** Guest (email üzerinden)

```php
app(NotificationService::class)->send([
    'channel'        => 'email',
    'category'       => 'contract_sent_to_guest',
    'guest_id'       => $guest->id,
    'company_id'     => $guest->company_id,
    'recipient_email'=> $guest->email,
    'recipient_name' => $guest->full_name,
    'template_id'    => MessageTemplate::findByCategory('contract_sent_to_guest')?->id,
    'variables'      => [
        'guest_name'    => $guest->first_name,
        'contract_url'  => url("/guest/contract"),
        'company_name'  => $company->name,
    ],
    'source_type' => 'guest_contract_sent',
    'source_id'   => (string) $guest->id,
]);
```

#### 7.2.3 İmzalı Sözleşme Yüklendi

**Ne zaman:** Guest imzalı sözleşmeyi yükleyip gönderdiğinde
**Kime:** Operations_admin + manager

#### 7.2.4 Sözleşme Onaylandı

**Ne zaman:** Yetkili sözleşmeyi onayladığında
**Kime:** Guest (email) + atanan senior (in_app)
**Not:** Bu bildirim devre dışı bırakılamaz.

#### 7.2.5 Sözleşme Reddedildi

**Ne zaman:** Yetkili sözleşmeyi reddettiğinde
**Kime:** Guest (email) + atanan senior (in_app)
**Not:** Bu bildirim devre dışı bırakılamaz.

#### 7.2.6 Sözleşme İptal Edildi

**Ne zaman:** Sözleşme iptal edildiğinde
**Kime:** Guest (email) + atanan senior (in_app)

---

### 7.3 Marketing & Sales Bildirimleri

#### 7.3.1 Lead Score Tier Değişimi

**Ne zaman:** Lead tier'ı `sales_ready` veya `champion`'a çıktığında
**Kime:** `sales_admin` + lead'e atanmış `sales_staff` (company_id bazlı)

```php
$salesAdmins = User::where('role', 'sales_admin')
    ->where('company_id', $guest->company_id)
    ->pluck('id');

app(NotificationService::class)->sendToMany($salesAdmins->toArray(), [
    'channel'     => 'in_app',
    'category'    => 'lead_score_tier_change',
    'company_id'  => $guest->company_id,
    'subject'     => "Lead Puanı Yüksek: {$guest->first_name} {$guest->last_name}",
    'body'        => "Tier: {$tierLabel} (Score: {$guest->lead_score})",
    'source_type' => 'lead_scoring_tier_change',
    'source_id'   => (string) $guest->id,
]);
```

#### 7.3.2 A/B Test Anlamlı Sonuç

**Ne zaman:** A/B test istatistiksel anlamlılığa ulaştığında
**Kime:** `marketing_admin` (company_id bazlı)

```php
$marketingAdmins = User::where('role', 'marketing_admin')
    ->where('company_id', $test->company_id)
    ->pluck('id');

app(NotificationService::class)->sendToMany($marketingAdmins->toArray(), [
    'channel'     => 'in_app',
    'category'    => 'ab_test_significant',
    'company_id'  => $test->company_id,
    'subject'     => "A/B Test Anlamlı: {$test->name}",
    'body'        => "Kazanan: {$result['winner']}. Lütfen sonucu inceleyin.",
    'source_type' => 'ab_test_significant',
    'source_id'   => (string) $test->id,
]);
```

#### 7.3.3 Email Kampanyası

**Ne zaman:** Email kampanyası gönderim kuyruğu işlendiğinde
**Kime:** Kampanya aboneleri
**Kanal:** email

#### 7.3.4 Workflow Bildirim Node'u

**Ne zaman:** Marketing workflow'daki `send_notification` node'u tetiklendiğinde
**Kime:** Node tanımındaki `recipient_config` (role bazlı veya spesifik user)

```php
// WorkflowEngineService içinde — node tipi: send_notification
app(NotificationService::class)->send([
    'channel'     => $node->config['channel'] ?? 'in_app',
    'category'    => 'workflow_notification',
    'user_id'     => $resolvedUserId,
    'company_id'  => $workflow->company_id,
    'subject'     => $node->config['subject'],
    'body'        => TemplateRenderer::render($node->config['body'], $workflowVariables),
    'source_type' => 'workflow_automation',
    'source_id'   => (string) $workflow->id,
]);
```

#### 7.3.5 Kampanya Tamamlandı

**Ne zaman:** Email kampanya kuyruğu tamamen işlendiğinde
**Kime:** Marketing_admin (kampanyayı oluşturan)
**Kanal:** in_app

---

### 7.4 Kurumsal Belge Paylaşımı

**Dosya:** `SeniorPortalController`
**Ne zaman:** Senior, institution document'i öğrenciye paylaştığında
**Kime:** Öğrenci (email)

```php
app(NotificationService::class)->send([
    'channel'        => 'email',
    'category'       => 'institution_document_shared',
    'student_id'     => $institutionDoc->student_id,
    'company_id'     => $request->user()->company_id,
    'recipient_email'=> $student->email,
    'recipient_name' => $student->full_name,
    'template_id'    => MessageTemplate::findByCategory('institution_document_shared')?->id,
    'variables'      => [
        'student_name'   => $student->first_name,
        'document_title' => $institutionDoc->title,
        'portal_url'     => url('/student/documents'),
    ],
    'source_type' => 'institution_document_shared',
    'source_id'   => (string) $institutionDoc->id,
]);
```

**v1 düzeltmesi:** `payload` alanı → `body` + `variables` ayrımı. `status: 'queued'` ile başlatma kaldırıldı → `NotificationService::send()` üzerinden `pending → queued` geçişi yapılır.

---

### 7.5 Bayi Payout Onay Talebi

**Ne zaman:** Aylık bayi payout periyodu oluşturulduğunda
**Kime:** Manager (onay yetkisi olan)
**Kanal:** in_app + email

```php
app(NotificationService::class)->send([
    'channel'     => 'in_app',
    'category'    => 'dealer_payout_pending',
    'user_id'     => $managerUserId,
    'company_id'  => $payout->company_id,
    'subject'     => "Bayi Payout Onay Bekliyor",
    'body'        => "{$payout->period} dönemi için {$payoutCount} adet bayi ödemesi onayınızı bekliyor.",
    'source_type' => 'dealer_payout_pending',
    'source_id'   => (string) $payout->id,
]);
```

---

## 8. Frontend Gösterimi

### 8.1 Bildirim Bell (Navbar — Tüm Paneller)

Her authenticated dashboard'da navbar'da bildirim ikonu + okunmamış sayı badge'i.

```php
// Shared via ViewComposer veya middleware
$unreadCount = NotificationDispatch::query()
    ->where('user_id', auth()->id()) // Staff/admin
    ->where('channel', 'in_app')
    ->where('is_read', false)
    ->whereIn('status', ['sent', 'pending', 'queued'])
    ->count();
```

Tıklandığında dropdown açılır — son 10 bildirim gösterilir. "Tümünü gör" linki → `/notifications`.

### 8.2 Staff/Admin Dashboard Paneli

**Controller:** `DashboardController`

Son 10 okunmamış bildirim. Satır tıklandığında:
- `source_type` + `source_id` üzerinden ilgili sayfaya yönlendirme
- Bildirim otomatik okundu işaretlenir

**Yönlendirme haritası:**

| source_type pattern | Yönlendirme |
|---|---|
| `task_*` | `/tasks/{department}#{task_id}` |
| `guest_contract_*` | `/guests/{guest_id}/contract` |
| `lead_scoring_*` | `/mktg-admin/leads/{lead_id}` |
| `ab_test_*` | `/mktg-admin/ab-tests/{test_id}` |
| `dealer_payout_*` | `/manager/payouts/{payout_id}` |
| (diğer) | `/notifications` |

### 8.3 Student Dashboard

**Controller:** `StudentDashboardController::dashboard()`

```php
$notifications = NotificationDispatch::query()
    ->where('student_id', $studentId)
    ->where('channel', 'in_app')
    ->where('is_read', false)
    ->latest()
    ->limit(10)
    ->get();
```

### 8.4 Student Bildirimler Sayfası

**URL:** `/student/notifications`
**Controller:** `StudentPortalController::notifications()`

Filtreler: `?channel=email&category=contract_approved`
Sayfalama: cursor-based pagination, sayfa başı 50 kayıt.

### 8.5 Senior Dashboard

```php
$recentNotifications = NotificationDispatch::query()
    ->whereIn('student_id', $seniorStudentIds)
    ->where('channel', 'in_app')
    ->latest()
    ->limit(8)
    ->get();
```

Senior'un sorumlu olduğu öğrencilere giden bildirimleri görür (read-only).

### 8.6 Manager KPI Dashboard

**Controller:** `DashboardKPIService`

```php
'notification_queued'     => NotificationDispatch::where('status', 'queued')->count(),
'notification_failed'     => NotificationDispatch::where('status', 'failed')
    ->where('failed_at', '>=', now()->subDay())->count(),
'notification_sent_24h'   => NotificationDispatch::where('status', 'sent')
    ->where('sent_at', '>=', now()->subDay())->count(),
'notification_skipped_24h'=> NotificationDispatch::where('status', 'skipped')
    ->where('created_at', '>=', now()->subDay())->count(),
```

---

## 9. `user_id` vs `student_id` vs `guest_id` Semantiği

| Alan | Ne zaman dolu | Kullanım |
|------|--------------|----------|
| `user_id` | Staff/admin hedefli bildirimler (task, eskalasyon, lead score, A/B test) | Staff dashboard gösterimi, okundu takibi |
| `student_id` | Öğrenci hedefli bildirimler (belge paylaşımı, sözleşme onay/red, onboarding) | Öğrenci dashboard filtreleme |
| `guest_id` | Guest hedefli bildirimler (sözleşme gönderildi, kayıt onayı) | Guest portal filtreleme |

**Kurallar:**
- `user_id` + `student_id` aynı anda dolu olabilir (örn: öğrenciyle ilgili bir staff bildirimi)
- `guest_id` ve `student_id` aynı anda dolu olamaz (Guest → Student dönüşümü sonrası guest_id kullanılmaz)
- In-app kanal için bu alanlardan en az biri dolu olmalıdır

---

## 10. API Referansı

### 10.1 Bildirim Yönetim API'si

Tüm endpoint'ler `auth:sanctum` + `permission:notification.manage` middleware gerektirir.

```
GET    /api/notification-dispatches                  → Bildirim listesi
POST   /api/notification-dispatches/dispatch-now      → Kuyruk işle
POST   /api/notification-dispatches/retry-failed      → Başarısızları tekrar kuyruğa al
POST   /api/notification-dispatches/{id}/mark-sent    → Manuel gönderildi işaretle
POST   /api/notification-dispatches/{id}/mark-failed  → Manuel başarısız işaretle
DELETE /api/notification-dispatches/cleanup            → Retention temizliği (manuel)
GET    /api/notification-dispatches/stats              → İstatistikler (YENİ)
```

#### `GET /api/notification-dispatches`

**Query Params:**

| Param | Tip | Açıklama |
|-------|-----|----------|
| `status` | string | `pending\|queued\|sent\|failed\|skipped` filtresi |
| `channel` | string | `email\|in_app\|whatsapp` filtresi |
| `category` | string | Kategori filtresi |
| `student_id` | string | Öğrenci ID filtresi |
| `guest_id` | string | Guest ID filtresi |
| `user_id` | int | Staff/admin ID filtresi |
| `date_from` | date | Tarih aralığı başlangıç |
| `date_to` | date | Tarih aralığı bitiş |
| `page` | int | Sayfa numarası |
| `per_page` | int | Sayfa başı kayıt (default: 50, max: 200) |

**Response:** Cursor-based pagination ile döner.

#### `GET /api/notification-dispatches/stats` (YENİ)

```json
{
  "total_24h": 156,
  "sent_24h": 142,
  "failed_24h": 8,
  "skipped_24h": 6,
  "by_channel": {
    "email": { "sent": 98, "failed": 5 },
    "in_app": { "sent": 44, "failed": 3 }
  },
  "by_category_top5": [
    { "category": "task_due_reminder", "count": 45 },
    { "category": "task_assigned", "count": 32 }
  ],
  "avg_queue_time_seconds": 2.3
}
```

#### `POST /api/notification-dispatches/dispatch-now`

| Param | Default | Max |
|-------|---------|-----|
| `limit` | 100 | 500 |

**Akış:**
1. `status = 'queued'` olanları al (oldest first)
2. `recipient_email` veya `recipient_phone` boş → `status = 'failed'`, `fail_reason = 'recipient_missing'`
3. Değilse → `SendNotificationJob::dispatch($id)`

**Response:**
```json
{
  "processed": 45,
  "sent": 43,
  "failed": 2,
  "remaining_queued": 0
}
```

### 10.2 Kullanıcı Bildirim API'si

Kullanıcının kendi bildirimleri. `auth:sanctum` yeterli (ek permission gerekmez).

```
GET    /api/notifications                    → Kendi bildirimlerim
POST   /api/notifications/{id}/mark-read     → Okundu işaretle
POST   /api/notifications/mark-all-read      → Tümünü okundu işaretle
GET    /api/notifications/unread-count        → Okunmamış sayısı
```

#### `GET /api/notifications`

Otomatik `user_id = auth()->id()` filtresi. Guest/Student portalda `guest_id`/`student_id` bazlı.

| Param | Tip | Default |
|-------|-----|---------|
| `is_read` | boolean | (tümü) |
| `category` | string | (tümü) |
| `per_page` | int | 20 |

### 10.3 Bildirim Tercihleri API'si

```
GET    /api/notification-preferences                → Tercih listesi
PUT    /api/notification-preferences                → Tercih güncelle
```

#### `PUT /api/notification-preferences`

```json
{
  "preferences": [
    { "channel": "email", "category": "task_due_reminder", "is_enabled": false },
    { "channel": "in_app", "category": "*", "is_enabled": true }
  ]
}
```

**Kısıt:** `NON_DISMISSABLE_CATEGORIES` listesindeki kategoriler devre dışı bırakılamaz — API 422 döner.

### 10.4 Template CRUD API'si

```
GET    /api/message-templates                → Liste
POST   /api/message-templates                → Oluştur
PUT    /api/message-templates/{id}           → Güncelle
DELETE /api/message-templates/{id}           → Sil (soft)
POST   /api/message-templates/{id}/preview   → Preview (variables ile render)
```

**Middleware:** `permission:notification.manage`

---

## 11. Scheduler Komutları

| Komut | Frekans | Açıklama |
|-------|---------|----------|
| `task:send-due-reminders` | Her gün 08:00 | Görev vade hatırlatıcıları |
| `task:check-escalations` | Her 30 dakika | SLA aşımı + eskalasyon bildirimleri |
| `email:process-queue` | Her 5 dakika | Email kampanya kuyruğunu işle |
| `notification:cleanup` | Her gün 03:00 (YENİ) | 90 günden eski bildirimleri sil |
| `notification:retry-stuck` | Her saat (YENİ) | 1 saatten fazla `queued` statüsünde kalmış kayıtları tekrar kuyruğa al |

### `task:send-due-reminders` Detayı

```bash
php artisan task:send-due-reminders            # normal çalışma
php artisan task:send-due-reminders --dry-run   # test (DB'ye yazmaz)
```

**Kapsadığı task'lar:**
- `due_date = yarın` → urgency: `YARIN` (in_app)
- `due_date = bugün` → urgency: `BUGÜN` (in_app + email)
- `due_date < bugün` + `status != done` → urgency: `GECİKTİ` (in_app + email)

### `notification:cleanup` Detayı (YENİ)

```bash
php artisan notification:cleanup               # 90 gün
php artisan notification:cleanup --days=30     # özel süre
php artisan notification:cleanup --dry-run     # sadece sayı göster
```

**Silme kuralları:**
- `status IN ('sent', 'skipped')` VE `created_at < now() - retention_days` → hard delete
- `status = 'failed'` → 180 gün (daha uzun tutulur, analiz için)
- `status IN ('pending', 'queued')` → asla silinmez (stuck detection için)

### `notification:retry-stuck` Detayı (YENİ)

```bash
php artisan notification:retry-stuck
```

**Mantık:**
1. `status = 'queued'` VE `queued_at < now() - 1 hour` olanları bul
2. `status = 'pending'` yap, yeni `SendNotificationJob::dispatch()` oluştur
3. 3 kez retry'dan sonra hâlâ stuck → `status = 'failed'`, `fail_reason = 'stuck_timeout'`

---

## 12. Dosya Yapısı

| Dosya | Rol |
|-------|-----|
| `app/Services/NotificationService.php` | **Merkezi gönderim servisi (YENİ)** |
| `app/Services/NotificationScopeService.php` | **Rol bazlı bildirim filtre servisi (YENİ)** |
| `app/Services/NotificationPreferenceService.php` | **Opt-out kontrol servisi (YENİ)** |
| `app/Services/TemplateRenderer.php` | **Template render engine (YENİ)** |
| `app/Models/NotificationDispatch.php` | Model |
| `app/Models/NotificationPreference.php` | **Tercih modeli (YENİ)** |
| `app/Models/MessageTemplate.php` | Template modeli |
| `app/Jobs/SendNotificationJob.php` | Async gönderim job'u |
| `app/Mail/NotificationMail.php` | Mailable class |
| `resources/views/mail/notification.blade.php` | Email HTML template |
| `app/Http/Controllers/Api/NotificationDispatchController.php` | Admin API controller |
| `app/Http/Controllers/Api/NotificationController.php` | **Kullanıcı bildirim API (YENİ)** |
| `app/Http/Controllers/Api/NotificationPreferenceController.php` | **Tercih API (YENİ)** |
| `app/Http/Controllers/Api/MessageTemplateController.php` | Template CRUD |
| `app/Console/Commands/SendTaskDueRemindersCommand.php` | Görev hatırlatıcı |
| `app/Console/Commands/CheckTaskEscalationsCommand.php` | Eskalasyon kontrolü |
| `app/Console/Commands/ProcessEmailQueueCommand.php` | Email kampanya kuyruğu |
| `app/Console/Commands/CleanupNotificationsCommand.php` | **Retention temizliği (YENİ)** |
| `app/Console/Commands/RetryStuckNotificationsCommand.php` | **Stuck retry (YENİ)** |
| `app/Services/TaskEscalationService.php` | Eskalasyon bildirimleri |

---

## 13. Güvenlik, İzolasyon ve Bildirim Scope

### 13.1 Mevcut Durumdaki Problem

Ekran görüntüsündeki sorun: Sales Staff (`sales.staff@mentorde.local`) kullanıcısı bildirimler sayfasında `guest_contract_update` (sözleşme bildirimi) ve başka kullanıcılara ait `task_due_reminder` kayıtlarını görüyor. Bu kullanıcı:

- Sözleşme yönetimiyle ilgili değil → `guest_contract_update` görmemeli
- Task hatırlatmaları kendisine ait değil → `task_due_reminder` görmemeli (senior2'ye giden email)
- `STU-00000023` / `GST-00000023` bildirimleri bu staff'ın sorumluluğunda değilse → görmemeli

**Kök neden:** Bildirimler sayfası (`NotificationDispatchController::index()`) hiçbir rol/kullanıcı filtresi uygulamıyor. Muhtemelen şöyle bir sorgu çalışıyor:

```php
// YANLIŞ — v1'deki mevcut kod (güvenlik açığı)
$dispatches = NotificationDispatch::query()
    ->where('company_id', $companyId)
    ->latest()
    ->paginate(50);
```

Bu sorgu şirket bazlı filtreliyor ama rol/kullanıcı bazlı değil. Tüm şirket bildirimleri herkese görünüyor.

### 13.2 Çözüm: NotificationScopeService

**Dosya:** `app/Services/NotificationScopeService.php`

Her bildirim sorgusu bu servis üzerinden geçer. Rol bazlı otomatik filtre uygular.

```php
<?php

namespace App\Services;

use App\Models\NotificationDispatch;
use App\Models\StudentAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NotificationScopeService
{
    /**
     * Rol-Kategori eşleme haritası.
     * Her rol yalnızca bu kategorilerdeki bildirimleri görebilir.
     */
    private const ROLE_CATEGORY_MAP = [
        // --- Süper Roller: tüm kategoriler ---
        'manager'      => '*',
        'system_admin' => '*',

        // --- Admin Roller: kendi departman kategorileri ---
        'operations_admin' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_escalation_level1', 'task_escalation_level2',
            'task_escalation_level3', 'task_dependency_completed',
            'contract_requested', 'contract_signed_uploaded',
            'contract_approved', 'contract_rejected', 'contract_cancelled',
            'guest_registration_confirmation',
        ],
        'finance_admin' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_escalation_level1', 'task_escalation_level2',
            'task_escalation_level3', 'task_dependency_completed',
            'dealer_payout_pending',
        ],
        'marketing_admin' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_escalation_level1', 'task_escalation_level2',
            'task_escalation_level3', 'task_dependency_completed',
            'lead_score_tier_change', 'ab_test_significant',
            'email_campaign', 'workflow_notification', 'campaign_completed',
        ],
        'sales_admin' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_escalation_level1', 'task_escalation_level2',
            'task_escalation_level3', 'task_dependency_completed',
            'lead_score_tier_change', 'workflow_notification',
        ],

        // --- Staff Roller: yalnızca kendine ait task + kendi alan bildirimleri ---
        'operations_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
        ],
        'finance_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
        ],
        'marketing_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
            'workflow_notification',
        ],
        'sales_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
            'lead_score_tier_change',
        ],
        'system_staff' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
        ],

        // --- Danışman Roller ---
        'senior' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
            'contract_requested', 'contract_approved', 'contract_rejected',
            'contract_cancelled', 'institution_document_shared',
        ],
        'mentor' => [
            'task_assigned', 'task_status_changed', 'task_comment_added',
            'task_due_reminder', 'task_dependency_completed',
        ],
    ];

    /**
     * Bildirim listesi sorgusuna rol bazlı scope uygular.
     *
     * Bu metot, her sorguya 2 katmanlı filtre uygular:
     *   Katman 1: Kategori filtresi (bu rol hangi kategorileri görebilir?)
     *   Katman 2: Sahiplik filtresi (bu bildirim bu kullanıcıya mı ait?)
     */
    public function applyScope(Builder $query, User $user): Builder
    {
        $role = $user->role;
        $userId = $user->id;
        $companyId = $user->company_id;

        // Şirket izolasyonu — her zaman
        $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhereNull('company_id');
        });

        // Süper roller → şirket bazlı tüm bildirimler
        if (in_array($role, ['manager', 'system_admin'])) {
            return $query;
        }

        // Kategori filtresi
        $allowedCategories = self::ROLE_CATEGORY_MAP[$role] ?? [];
        if ($allowedCategories === '*') {
            return $query; // Süper rol zaten yukarıda yakalandı, güvenlik için
        }
        if (empty($allowedCategories)) {
            return $query->whereRaw('1 = 0'); // Tanımsız rol → hiçbir şey göremez
        }

        $query->whereIn('category', $allowedCategories);

        // Sahiplik filtresi — rol tipine göre
        if ($this->isStaffRole($role)) {
            // Staff: YALNIZCA user_id = self olan bildirimler
            $query->where('user_id', $userId);

        } elseif ($this->isAdminRole($role)) {
            // Admin: user_id = self VEYA kendi departmanındaki kullanıcılara ait
            $deptUserIds = $this->getDepartmentUserIds($role, $companyId);
            $query->where(function ($q) use ($userId, $deptUserIds) {
                $q->where('user_id', $userId)
                  ->orWhereIn('user_id', $deptUserIds);
            });

        } elseif ($role === 'senior') {
            // Senior: user_id = self VEYA kendi öğrencilerine ait bildirimler
            $studentIds = StudentAssignment::where('senior_email', $user->email)
                ->pluck('student_id')
                ->toArray();
            $query->where(function ($q) use ($userId, $studentIds) {
                $q->where('user_id', $userId)
                  ->orWhereIn('student_id', $studentIds);
            });

        } elseif ($role === 'mentor') {
            // Mentor: yalnızca user_id = self
            $query->where('user_id', $userId);
        }

        return $query;
    }

    /**
     * Departman bazlı kullanıcı ID'lerini döndürür.
     */
    private function getDepartmentUserIds(string $adminRole, ?int $companyId): array
    {
        $deptRoles = match ($adminRole) {
            'operations_admin' => ['operations_admin', 'operations_staff'],
            'finance_admin'    => ['finance_admin', 'finance_staff'],
            'marketing_admin'  => ['marketing_admin', 'marketing_staff', 'sales_admin', 'sales_staff'],
            'sales_admin'      => ['sales_admin', 'sales_staff'],
            default            => [],
        };

        return User::whereIn('role', $deptRoles)
            ->where('company_id', $companyId)
            ->pluck('id')
            ->toArray();
    }

    private function isStaffRole(string $role): bool
    {
        return in_array($role, [
            'operations_staff', 'finance_staff',
            'marketing_staff', 'sales_staff', 'system_staff',
        ]);
    }

    private function isAdminRole(string $role): bool
    {
        return in_array($role, [
            'operations_admin', 'finance_admin',
            'marketing_admin', 'sales_admin',
        ]);
    }
}
```

### 13.3 Controller'da Kullanım

**Mevcut kod (YANLIŞ):**
```php
// NotificationDispatchController::index() — v1
public function index(Request $request)
{
    $dispatches = NotificationDispatch::query()
        ->where('company_id', $request->user()->company_id)
        ->latest()
        ->paginate(50);

    return view('marketing-admin.notifications.index', compact('dispatches'));
}
```

**Düzeltilmiş kod (DOĞRU):**
```php
// NotificationDispatchController::index() — v2
public function index(Request $request, NotificationScopeService $scopeService)
{
    $query = NotificationDispatch::query();

    // Rol bazlı scope uygula
    $scopeService->applyScope($query, $request->user());

    // Ek filtreler (query params)
    if ($status = $request->query('status')) {
        $query->where('status', $status);
    }
    if ($channel = $request->query('channel')) {
        $query->where('channel', $channel);
    }
    if ($category = $request->query('category')) {
        $query->where('category', $category);
    }

    $dispatches = $query->latest()->paginate(50);

    return view('marketing-admin.notifications.index', compact('dispatches'));
}
```

### 13.4 Ekran Görüntüsündeki Spesifik Sorunlar ve Çözümleri

**Sorun 1:** Sales Staff, `guest_contract_update` kategorisini görüyor.

Çözüm: `sales_staff` rolünün `ROLE_CATEGORY_MAP`'inde `guest_contract_update` yok. Scope uygulandığında bu kayıtlar filtrelenir. Ayrıca v1'deki `guest_contract_update` kategorisi v2'de 6 spesifik kategoriye ayrıldı (`contract_requested`, `contract_sent_to_guest`, vb.) — sales_staff bunların hiçbirini göremez.

**Sorun 2:** Sales Staff, `task_due_reminder` email bildirimlerini görüyor (senior2'ye ait).

Çözüm: `user_id` filtresi. Sales Staff'ın `user_id`'si ile bu bildirimlerin `user_id`'si farklı → scope tarafından filtrelenir. Staff roller yalnızca `user_id = self` koşulunu geçen bildirimleri görebilir.

**Sorun 3:** `#184 in_app / guest_contract_update → STU-00000023` ve `#182 in_app / guest_contract_update → GST-00000023` — "recipient missing" ile fail olmuş.

Çözüm: Bu bildirimler `user_id` boş (student/guest hedefli) — sales staff'ın `user_id`'si ile eşleşmez. Ayrıca in_app kanalda `user_id` boşsa bu bildirim zaten kime gösterilecek belirsiz — bu bir oluşturma hatası (§13.7'de açıklandı).

**Sorun 4:** `#185 in_app / task → Pending` — `user_id`, `student_id` hepsi boş.

Çözüm: Bu bildirim eksik veriyle oluşturulmuş — `NotificationService::send()` validation'ı bunu engelleyecek (in_app kanal için `user_id` veya `student_id` veya `guest_id`'den en az biri zorunlu).

### 13.5 Bildirim Oluşturma Validation (YENİ)

`NotificationService::send()` içinde, bildirim oluşturulmadan önce zorunlu alan kontrolü:

```php
// NotificationService::send() — validation bölümü
private function validateParams(array $params): void
{
    // Kanal bazlı zorunlu alan kontrolü
    if ($params['channel'] === 'email' && empty($params['recipient_email'])) {
        throw new InvalidArgumentException(
            "Email kanalı için recipient_email zorunludur. " .
            "Category: {$params['category']}, source: {$params['source_type']}:{$params['source_id']}"
        );
    }

    if ($params['channel'] === 'in_app') {
        if (empty($params['user_id']) && empty($params['student_id']) && empty($params['guest_id'])) {
            throw new InvalidArgumentException(
                "In-app kanalı için user_id, student_id veya guest_id'den en az biri zorunludur. " .
                "Category: {$params['category']}, source: {$params['source_type']}:{$params['source_id']}"
            );
        }
    }

    if ($params['channel'] === 'whatsapp' && empty($params['recipient_phone'])) {
        throw new InvalidArgumentException(
            "WhatsApp kanalı için recipient_phone zorunludur."
        );
    }

    // Kategori kontrolü
    if (!in_array($params['category'], self::VALID_CATEGORIES)) {
        Log::warning("Bilinmeyen bildirim kategorisi: {$params['category']}");
    }
}
```

Bu validation sayesinde ekran görüntüsündeki `#185` (boş user_id), `#182` ve `#184` (recipient missing) gibi sorunlu kayıtlar oluşturulma aşamasında engellenecek.

### 13.6 Rol-Kategori Erişim Matrisi (Tam Tablo)

| Kategori | manager | sys_admin | ops_admin | fin_admin | mktg_admin | sales_admin | ops_staff | fin_staff | mktg_staff | sales_staff | sys_staff | senior | mentor |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `task_assigned` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* |
| `task_status_changed` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* |
| `task_comment_added` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* |
| `task_due_reminder` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* |
| `task_escalation_level1` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — | — | — | — |
| `task_escalation_level2` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — | — | — | — | — |
| `task_escalation_level3` | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — | — |
| `task_dependency_completed` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* | ✓* |
| `contract_requested` | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | ✓* | — |
| `contract_sent_to_guest` | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — |
| `contract_signed_uploaded` | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — |
| `contract_approved` | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | ✓* | — |
| `contract_rejected` | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | ✓* | — |
| `contract_cancelled` | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | ✓* | — |
| `lead_score_tier_change` | ✓ | ✓ | — | — | ✓ | ✓ | — | — | — | ✓* | — | — | — |
| `ab_test_significant` | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| `email_campaign` | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| `workflow_notification` | ✓ | ✓ | — | — | ✓ | ✓ | — | — | ✓* | — | — | — | — |
| `campaign_completed` | ✓ | ✓ | — | — | ✓ | — | — | — | — | — | — | — | — |
| `institution_doc_shared` | ✓ | ✓ | — | — | — | — | — | — | — | — | — | ✓* | — |
| `dealer_payout_pending` | ✓ | ✓ | — | ✓ | — | — | — | — | — | — | — | — | — |
| `guest_reg_confirmation` | ✓ | ✓ | ✓ | — | — | — | — | — | — | — | — | — | — |

**✓** = Kategorideki tüm bildirimleri görebilir
**✓*** = Yalnızca `user_id = self` olan (kendine ait) bildirimleri görebilir
**—** = Bu kategoriyi hiç göremez

### 13.7 Bildirim Oluşturma Kuralları — `user_id` Zorunluluğu

Ekran görüntüsündeki sorunların kökeni: `user_id` alanı boş bırakılan in_app bildirimler. Bu kayıtlar kimseye gösterilemiyor çünkü scope filtresi `user_id` bazlı çalışıyor.

**Kural:** In-app kanal için `user_id` MUTLAKA doldurulmalıdır (staff/admin hedefli bildirimler).

| Durum | Doğru Yaklaşım |
|---|---|
| Guest'e in_app bildirim | `guest_id` doldur → Guest Portal'da göster |
| Student'a in_app bildirim | `student_id` doldur → Student Portal'da göster |
| Staff/Admin'e in_app bildirim | `user_id` doldur → Staff Dashboard'da göster |
| Guest'e email bildirim | `guest_id` + `recipient_email` doldur |
| Student'a email bildirim | `student_id` + `recipient_email` doldur |

**Yanlış:** `guest_contract_update` kategorisinde `student_id = STU-00000023` ile in_app bildirim oluşturup `user_id` boş bırakmak → bu bildirim Staff Dashboard'da kimseye gösterilmez, Student Portal'da gösterilir (ama student_id dolu olduğu için).

### 13.8 Şirket İzolasyonu

Her bildirim sorgusu `company_id` bazlı scope gerektirir. `NotificationScopeService::applyScope()` otomatik olarak `$user->company_id` filtresini uygular.

### 13.9 Rate Limiting

| Kısıt | Değer | Açıklama |
|-------|-------|----------|
| Aynı alıcıya maks bildirim / saat | 20 | Spam koruması |
| Aynı kategoriden aynı alıcıya / gün | 5 | Kategori bazlı limit |
| Email kampanya / dakika | 100 | SMTP throttle |

`NotificationService::send()` içinde kontrol edilir. Limit aşılırsa → `status='skipped'`, `skip_reason='rate_limited'`.

---

## 14. v1 → v2 Geçiş Planı

### Aşama 1: Altyapı (Breaking change yok)

1. `notification_dispatches` tablosuna yeni kolonlar ekle (`guest_id`, `is_read`, `read_at`, `skip_reason`)
2. `notification_preferences` tablosunu oluştur
3. `guest_applications.notify_*` verilerini `notification_preferences`'a göç et
4. `NotificationService`, `NotificationPreferenceService`, `TemplateRenderer` oluştur

### Aşama 2: Entegrasyon (Gradual migration)

5. Her modüldeki doğrudan `NotificationDispatch::create()` çağrılarını → `NotificationService::send()` ile değiştir
6. Frontend'de `status=pending` filtresini → `is_read=false` ile değiştir
7. Eksik task bildirimlerini ekle (assigned, status_changed, dependency_completed)
8. Sözleşme bildirimlerini ekle (6 kategori)

### Aşama 3: Temizlik

9. `guest_applications.notify_*` kolonlarını deprecate et (soft — bir süre daha tut)
10. Scheduler komut isimlerini `task:*` olarak standardize et
11. Retention cleanup scheduler'ını aktive et
12. Template engine'i aktive et, varsayılan template'ler oluştur

---

## 15. Bilinen Sınırlamalar

| # | Konu | Durum | Plan |
|---|------|-------|------|
| 1 | In-app gerçek zamanlı push yok | Polling bazlı (60 sn) | Pusher/WebSocket entegrasyonu — Aşama 4 |
| 2 | WhatsApp pasif | Log seviyesinde | Twilio/Meta API entegrasyonu — Aşama 4 |
| 3 | Email bounce tracking yok | Gönderim başarısı SMTP cevabına bağlı | Postmark/SES webhook entegrasyonu — Aşama 5 |
| 4 | Bildirim gruplama yok | Her event ayrı bildirim | Digest mode (günlük özet email) — Aşama 5 |
| 5 | i18n template seçimi otomatik değil | `preferred_language` kontrol edilmiyor | `TemplateRenderer`'da dil seçim mantığı — Aşama 2 |

---

*Bu doküman MentorDE Bildirim Sistemi master referansıdır (v2.0). Task Board v2.0, Sözleşme v5.0 ve Marketing & Sales v3.0 ile tam uyumludur.*
