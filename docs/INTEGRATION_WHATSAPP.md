# Entegrasyon: WhatsApp (Meta Cloud API)

---

## Amaç

Meta Cloud API v19.0 üzerinden WhatsApp template ve metin mesajı gönderme altyapısı. `SendNotificationJob`'ın `whatsapp` kanalını destekler.

---

## Teknik Altyapı

**API:** `https://graph.facebook.com/v19.0/{phone_number_id}/messages`
**Auth:** `Authorization: Bearer {access_token}`
**Dosya:** `app/Services/WhatsAppService.php`

---

## WhatsAppService Metodları

### sendTemplate()

```php
sendTemplate(
    string $to,           // E.164 format: +905551234567
    string $templateName, // Meta Business'ta kayıtlı template adı
    array  $bodyParams,   // {{1}}, {{2}}... parametreleri
    string $languageCode  // default: 'tr'
): bool
```

**Payload yapısı:**
```json
{
    "messaging_product": "whatsapp",
    "to": "+905551234567",
    "type": "template",
    "template": {
        "name": "mentorde_notification",
        "language": { "code": "tr" },
        "components": [{
            "type": "body",
            "parameters": [{ "type": "text", "text": "..." }]
        }]
    }
}
```

### sendText()

```php
sendText(string $to, string $body): bool
```

Serbest metin mesajı — yalnızca 24 saat içinde mesaj gönderilmiş kullanıcılar veya test numaraları için çalışır.

### normalizePhone()

`+905551234567` formatına normalize eder (boşluk, tire, parantez temizler).

---

## Konfigürasyon

**Dosya:** `config/services.php`

```php
'whatsapp' => [
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'token'           => env('WHATSAPP_ACCESS_TOKEN'),
    'verify_token'    => env('WHATSAPP_VERIFY_TOKEN', 'mentorde_verify'),
    'api_version'     => env('WHATSAPP_API_VERSION', 'v19.0'),
],
```

**`.env.example`:**
```
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_VERIFY_TOKEN=mentorde_verify
WHATSAPP_API_VERSION=v19.0
```

---

## SendNotificationJob Entegrasyonu

**Dosya:** `app/Jobs/SendNotificationJob.php`

```
channel = 'whatsapp'
  → recipient_phone boşsa → hemen failed (kalıcı)
  → WhatsAppService::sendTemplate($phone, 'mentorde_notification', [$body])
      → false döndüyse → hemen failed (API kabul etmedi — kalıcı)
      → exception → throw (kuyruk 3 kez yeniden dener, backoff 60s)
```

---

## Meta Template Onay Süreci

1. Meta Business Manager → WhatsApp Manager → Message Templates
2. Template adı: `mentorde_notification` (Türkçe, `tr` dil kodu)
3. Template kategorisi: `UTILITY` veya `MARKETING`
4. Onay: 24-48 saat
5. Onaylanmadan önce API `invalid_parameter` hatası döner

---

## Mevcut Durum & Limitler

| Durum | Açıklama |
|-------|----------|
| Env vars boşsa | `sendTemplate()` `false` döner, işlem yapılmaz (silent skip) |
| Template onaylı değilse | API 400 → `whatsapp_send_failed` |
| 24h window dışı serbest metin | `sendText()` çalışmaz (403) |
| Retry | 3 deneme, 60s aralıklı |

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Service | `app/Services/WhatsAppService.php` |
| Job | `app/Jobs/SendNotificationJob.php` |
| Config | `config/services.php` |
| Env | `.env.example` |
