# Entegrasyon: Bildirim Sistemi

---

## Amaç

Sistem genelinde email, in-app ve WhatsApp kanalları üzerinden bildirim gönderme altyapısı. Her bildirim `NotificationDispatch` modeline kaydedilir ve `SendNotificationJob` ile asenkron olarak işlenir.

---

## Kanallar

| Kanal | Mekanizma | Durum |
|-------|-----------|-------|
| `email` | Laravel Mail → SMTP | Çalışıyor (`MAIL_MAILER=smtp` gerekli) |
| `in_app` | DB kaydı → frontend polling | Çalışıyor |
| `whatsapp` | Meta Cloud API v19.0 | Env vars gerekli |

---

## NotificationDispatch Modeli

**Dosya:** `app/Models/NotificationDispatch.php`
**Tablo:** `notification_dispatches`

### Önemli Alanlar

| Alan | Açıklama |
|------|----------|
| `channel` | `email / in_app / whatsapp` |
| `category` | Bildirim kategorisi (workflow_email, escalation vb.) |
| `status` | `queued → sent / failed` |
| `recipient_email` | Email adresi |
| `recipient_phone` | WhatsApp için telefon |
| `subject` | Email konusu |
| `body` | Mesaj gövdesi |
| `template_id` | `MessageTemplate` FK (opsiyonel) |
| `source_type` | Tetikleyici kaynak türü |
| `source_id` | Tetikleyici kaynak ID |
| `triggered_by` | `system / user / workflow` |
| `student_id` | Öğrenci bağlantısı (in-app filtreleme için) |
| `user_id` | Kullanıcı bağlantısı |
| `is_read` / `read_at` | In-app okunma durumu |
| `sent_at` / `failed_at` / `fail_reason` | Sonuç alanları |

### Performans İndeksi
`(status, sent_at)` — 24h count sorguları için
`app/Http/Controllers/Api/NotificationDispatchController.php`

---

## SendNotificationJob

**Dosya:** `app/Jobs/SendNotificationJob.php`

```
Queue: default
$tries  = 3
$backoff = 60s (deneme arası bekleme)
```

### Akış

```
NotificationDispatch::create() → SendNotificationJob::dispatch($id)
  → handle()
      ├── channel=email   → Mail::to()->send(NotificationMail)
      │     └── Hata: throw $e → kuyruk yeniden dener (3x)
      │         Kalıcı hata (email eksik): hemen failed yaz
      ├── channel=in_app  → sadece DB kaydı yeterli (polling)
      └── channel=whatsapp → WhatsAppService::sendTemplate()
            ├── API false döndü: hemen failed yaz (kalıcı)
            └── Exception: throw $e → kuyruk yeniden dener (3x)
  → failed() callback → son deneme sonrası failed yaz
```

---

## Bildirim API Endpoint'leri

**Dosya:** `app/Http/Controllers/Api/NotificationDispatchController.php`

| Endpoint | Metod | Açıklama |
|----------|-------|----------|
| `/api/v1/notifications` | GET | Bildirim listesi (rol filtreli) |
| `/api/v1/notifications/{id}/read` | POST | Okundu işaretle |
| `/api/v1/notifications/read-all` | POST | Tümünü okundu yap |

`NotificationScopeService` → rol bazlı erişim kısıtlaması uygular.

---

## Escalation → Bildirim Akışı

**Dosya:** `app/Services/EscalationService.php`

```
EscalationRule (is_active=true)
  └── getTargets() → bekleyen varlıkları bul
        └── escalation_steps[] her biri:
              ├── after_hours geçtiyse
              ├── EscalationEvent daha yoksa
              └── NotificationService::send() çağrısı
                    → email veya in_app kanalı
```

`EscalationRule` → `entity_type`, `escalation_steps` (JSON), `target_roles`
`EscalationEvent` → tetiklenen her adım kaydı (tekrar tetiklenmeyi önler)

---

## Zamanlı Bildirimler

**Cron:** `notifications:process-scheduled` — `routes/console.php`

Zamanlı bildirim kuyruğundaki kayıtları işler.

---

## In-App Bildirim (Polling)

- Frontend `setInterval` ile `/api/v1/notifications?channel=in_app&status=queued` endpoint'ini periyodik olarak sorgular
- WebSocket/Pusher entegrasyonu V3 roadmap'te
- `NotificationScopeService` — kullanıcının sadece kendi bildirimlerini görmesini sağlar

---

## NotificationMail

**Dosya:** `app/Mail/NotificationMail.php`
**View:** `resources/views/mail/notification.blade.php`

Basit subject + body parametreli mail. Template sistemi V2'de genişletilecek.

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Model | `app/Models/NotificationDispatch.php` |
| Job | `app/Jobs/SendNotificationJob.php` |
| Controller (API) | `app/Http/Controllers/Api/NotificationDispatchController.php` |
| Service (Escalation) | `app/Services/EscalationService.php` |
| Service (Scope) | `app/Services/NotificationScopeService.php` |
| Mail | `app/Mail/NotificationMail.php` |
| Mail View | `resources/views/mail/notification.blade.php` |
