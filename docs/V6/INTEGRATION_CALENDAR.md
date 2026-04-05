# Entegrasyon: Takvim Entegrasyonu

---

## Amaç

Senior bir öğrenci randevusu oluştururken aktif takvim entegrasyonu varsa harici takvimde otomatik event oluşturulur. Google Meet linki de otomatik eklenir.

---

## Desteklenen Sağlayıcılar

| Sağlayıcı | Adapter | Provider Kodu |
|-----------|---------|---------------|
| Google Calendar | `GoogleCalendarAdapter` | `google_calendar` |
| Cal.com | `CalComAdapter` | `cal_com` |
| Calendly | `CalendlyAdapter` | `calendly` |

---

## IntegrationFactory

**Dosya:** `app/Services/Integrations/IntegrationFactory.php`

```php
IntegrationFactory::getCalendarService()
  → IntegrationConfig (type='calendar', company_id) → provider seç
  → match: google_calendar | cal_com | calendly → adapter döndür
```

`IntegrationConfig` modeli DB'de aktif sağlayıcıyı tutar. Firma bazlı farklı entegrasyonlar desteklenir.

---

## GoogleCalendarAdapter

**Dosya:** `app/Services/Integrations/Adapters/Calendar/GoogleCalendarAdapter.php`

### createEvent()

```php
createEvent(array $data): string  // event ID döner
```

**Parametre yapısı:**
```php
[
    'title'       => 'Randevu Başlığı',
    'description' => '...',
    'start_time'  => '2026-03-20T14:00:00Z',  // RFC3339
    'end_time'    => '2026-03-20T15:00:00Z',
    'attendees'   => ['ogrenci@email.com'],
    'add_meet'    => true,  // Google Meet linki ekle
]
```

**API:** `POST https://www.googleapis.com/calendar/v3/calendars/primary/events`
`conferenceDataVersion=1` → otomatik Meet linki

### cancelEvent()

```php
cancelEvent(string $eventId): bool
```

**API:** `DELETE .../events/{eventId}`

### Hata Yönetimi
- Token yoksa → `parent::createEvent()` (stub) → boş string döner
- API hatası → `parent::createEvent()` → silently skip
- Exception → `parent::createEvent()`

Randevu her durumda kaydedilir. Takvim entegrasyonu hata verse de randevu silinmez.

---

## Senior Randevu Akışı

**Dosya:** `app/Http/Controllers/SeniorPortalController.php`

```
POST /senior/appointments
  └── Validate (student_id, title, start_time, end_time, type)
        └── StudentAppointment::create()
              └── try {
                    $factory = app(IntegrationFactory::class);
                    $adapter = $factory->getCalendarService();  // company_id bazlı
                    $eventId = $adapter->createEvent([...]);
                    if ($eventId) {
                        $appointment->update([
                            'external_event_id' => $eventId,
                            'calendar_provider' => $adapter->providerCode(),
                            'meeting_url'       => $adapter->getMeetUrl($eventId),
                        ]);
                    }
                  } catch (\Throwable) {
                    // silently skip — randevu zaten kaydedildi
                  }
```

---

## StudentAppointment Modeli

**Dosya:** `app/Models/StudentAppointment.php`

| Alan | Açıklama |
|------|----------|
| `student_id` | Öğrenci ID |
| `senior_email` | Danışman email |
| `title` | Randevu başlığı |
| `start_time` | Başlangıç (datetime) |
| `end_time` | Bitiş (datetime) |
| `type` | `online / in_person / phone` |
| `meeting_url` | Video link (otomatik veya manuel) |
| `external_event_id` | Takvim sağlayıcısındaki event ID |
| `calendar_provider` | `google / calcom / calendly` |
| `status` | `scheduled / completed / cancelled` |

---

## View'da Badge

`resources/views/senior/appointments.blade.php`'de `calendar_provider` varsa badge gösterilir:
- `Google` badge + `external_event_id` varsa "Takvimde Görüntüle" linki
- Cal.com / Calendly benzer şekilde

---

## Desteklenen Diğer Adapter Kategorileri

`IntegrationFactory` aynı zamanda şu adapter kategorilerini destekler:

| Kategori | Adapter'lar |
|----------|------------|
| Email Marketing | Mailchimp, SendGrid, Zoho |
| E-Sign | DocuSign, HelloSign, PandaDoc |
| Project Management | ClickUp, Monday, Notion |
| Video Conference | Zoom, Google Meet, Teams |

Bunların hepsinin contract'ı `app/Services/Integrations/Contracts/` altında tanımlı.

---

## Konfigürasyon

**Model:** `app/Models/IntegrationConfig.php`
**Tablo:** `integration_configs`

```php
[
    'company_id'  => ...,
    'type'        => 'calendar',  // calendar|email_marketing|esign|...
    'provider'    => 'google_calendar',
    'credentials' => ['access_token' => '...', 'refresh_token' => '...'],
    'is_active'   => true,
]
```

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Factory | `app/Services/Integrations/IntegrationFactory.php` |
| Adapter (Google) | `app/Services/Integrations/Adapters/Calendar/GoogleCalendarAdapter.php` |
| Adapter (Cal.com) | `app/Services/Integrations/Adapters/Calendar/CalComAdapter.php` |
| Adapter (Calendly) | `app/Services/Integrations/Adapters/Calendar/CalendlyAdapter.php` |
| Abstract | `app/Services/Integrations/Adapters/Calendar/AbstractCalendarAdapter.php` |
| Contract | `app/Services/Integrations/Contracts/CalendarIntegrationInterface.php` |
| Model | `app/Models/StudentAppointment.php` |
| Model | `app/Models/IntegrationConfig.php` |
