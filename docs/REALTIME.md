# Real-time Bildirim Sistemi (Pusher)

MentorDE SaaS satış aracı — anlık bildirim altyapısı. Pusher Channels Free tier
üzerinden 100 eş zamanlı bağlantı + 200K mesaj/gün ile başlangıç için yeter.
Pusher down olursa mevcut email + DB polling fallback'i devrede kalır.

## Mimari

```
PHP (Event broadcast)
      │
      ▼
Pusher REST API ──► Pusher Channels (WSS)
      │
      ▼
laravel-echo + pusher-js (browser)
      │
      ▼
notifications.js → toast + sound + browser-notification
```

## 1. Pusher Hesabı Kurulumu

1. https://pusher.com/signup → Free plan
2. **Channels** ürününü seç (Beams DEĞİL)
3. **Create app** → cluster: **eu** (Frankfurt, en yakın)
4. **App Keys** sekmesinden:
   - `app_id`
   - `key`
   - `secret`
   - `cluster`
5. Bu 4 değeri `.env` dosyasına yapıştır:

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=xxxxx
PUSHER_APP_KEY=xxxxxxxxxxxxxxxx
PUSHER_APP_SECRET=xxxxxxxxxxxxxxxx
PUSHER_APP_CLUSTER=eu

# Vite client-side'a gomulur — değişiklikten sonra `npm run build` zorunlu
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

## 2. Composer / npm install

İlk deploy'da:

```bash
composer install
npm install
npm run build
```

`composer require pusher/pusher-php-server` + `npm install laravel-echo pusher-js`
zaten `composer.json` ve `package.json`'a kayıtlı — direkt install yeter.

## 3. Kanal Mimarisi

`routes/channels.php` 4 private channel tanımlar:

| Kanal | Subscribe edebilen | Kullanım |
|-------|--------------------|----------|
| `user.{id}` | sahibinin kendisi | chat mesajı, kişisel toast |
| `senior.{seniorUserId}` | role=senior + id eşleşmesi | yeni booking, iptal, ödeme |
| `manager.{companyId}` | role∈{manager, marketing_admin, platform_owner} + company eşleşmesi | yeni lead, payment, tier change |
| `platform.owner` | role=platform_owner | platform-wide tier audit |

Auth endpoint: `/broadcasting/auth` (auto-register `BroadcastServiceProvider` ile).

## 4. Event Sınıfları

`app/Events/` altında 6 event:

| Event | Tetik noktası | Hedef kanal |
|-------|---------------|-------------|
| `NewBookingReceived` | `BookingConfirmationService::confirm` + Stripe paid webhook | senior.{id} |
| `BookingCanceled` | `BookingConfirmationService::cancel` | senior.{id} + user.{id} + manager.{cid} |
| `NewLeadCreated` | `GuestApplicationController::store` | manager.{cid} |
| `PaymentReceived` | Stripe `checkout.session.completed` webhook | senior.{id} + manager.{cid} |
| `MessageReceived` | `MessageCenterController::send` | user.{recipientId} |
| `TierUpgraded` | `PlatformController::updateTier` | manager.{cid} + platform.owner |

Her event `ShouldBroadcast` implements eder; `broadcast()` helper'i ile dispatch
edilir. Trigger noktaları try/catch içinde — Pusher down olursa booking/lead/payment
akışı asla bozulmaz.

## 5. Frontend Entegrasyonu

- `resources/js/echo.js` — Echo + Pusher init (key boşsa skip)
- `resources/js/notifications.js` — auto-subscribe + toast + sound + browser notification
- `resources/views/partials/notification-toast.blade.php` — layout include'una düşer
- `resources/views/partials/notification-center.blade.php` — bell dropdown (son 10 DB notif)

5 layout'a (manager, senior, guest, student, platform) `@include('partials.notification-toast')`
otomatik dahil.

## 6. Test

### Tinker ile event broadcast:

```bash
php artisan tinker
>>> broadcast(new \App\Events\NewBookingReceived(\App\Models\PublicBooking::first()));
>>> broadcast(new \App\Events\NewLeadCreated(\App\Models\GuestApplication::first()));
```

Browser'da ilgili kullanıcı login'liyse:
1. Sağ üstte toast belirir (6 sn)
2. notif.mp3 çalar (sessize alınabilir)
3. Sekme arka plandaysa browser notification (izin verildiyse)

### CSP / WebSocket teşhisi

Browser console:
```js
window.__PUSHER_DEBUG = true;
// sayfa yenile, state geçişlerini logla
```

Beklenen: `connecting → connected`. Eğer `connecting → unavailable` görürsen:
- CSP `connect-src` `wss://*.pusher.com` içermiyor (SecurityHeaders.php)
- Pusher key/cluster yanlış
- Pusher hesabı dondurulmuş / quota aşıldı

## 7. Free Tier Limits

| Limit | Free | Şartlar |
|-------|------|---------|
| Concurrent connections | 100 | 100 aktif user maksimum |
| Mesaj/gün | 200,000 | ~2K user × 100 event/gün rahat |
| Mesaj boyutu | 10 KB | broadcastWith() payload küçük tutulmalı |

Aşıldığında Pusher mesajları **drop** eder (hata vermez). Email + DB polling
fallback'i her zaman çalışır.

## 8. Production Deploy Checklist

- [ ] `.env`: `PUSHER_*` 4 değer dolu
- [ ] `.env`: `VITE_PUSHER_APP_KEY` + `VITE_PUSHER_APP_CLUSTER` dolu
- [ ] `npm run build` çalıştırıldı (Vite key'i bundle'a göm)
- [ ] `php artisan config:cache` (broadcasting config'i cache'le)
- [ ] `php artisan route:cache` (broadcasting/auth route'u cache'le)
- [ ] `public/sounds/notif.mp3` gerçek bir ses dosyasıyla değiştirildi
- [ ] Pusher dashboard → Debug Console açık tutuldu, test broadcast yapıldı

## 9. Geriye Düşme (Fallback) Davranışı

Pusher devre dışıyken:
- Echo `null` olur, `notifications.js` skip eder
- Toast düşmez, ses çalmaz
- Email bildirimleri (`NotificationService`) çalışmaya devam eder
- Sayfa yenilemede bell dropdown güncel kalır
- Sayfa **asla bozulmaz** — hiçbir event broadcast hatası kullanıcıya yansımaz

Tüm broadcast call'ları `try/catch` içinde — `\Log::warning` ile audit'e düşer,
booking/payment/registration akışlarına dokunmaz.
