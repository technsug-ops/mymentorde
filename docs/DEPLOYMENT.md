# MentorDE — Production Deployment Rehberi

Son güncelleme: 2026-02-27 | Laravel 12 / PHP 8.4 / MySQL 8+

---

## 1. Sunucu Gereksinimleri

| Bileşen | Minimum | Önerilen |
|---|---|---|
| PHP | 8.2 | 8.4 |
| MySQL | 8.0 | 8.0+ |
| Redis | 6.x | 7.x |
| Nginx | 1.18+ | 1.24+ |
| Node.js | 18 LTS | 20 LTS |
| RAM | 2 GB | 4 GB |
| Disk | 20 GB | 50 GB |

PHP uzantıları (zorunlu):
```
php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl
php8.4-zip php8.4-bcmath php8.4-redis php8.4-gd php8.4-intl
php8.4-fileinfo php8.4-pcntl
```

---

## 2. İlk Kurulum

### 2.1 Kodu al ve bağımlılıkları kur

```bash
cd /var/www
git clone <repo-url> mentorde
cd mentorde

# Composer bağımlılıkları (dev olmadan)
composer install --no-dev --optimize-autoloader

# Node bağımlılıkları + production build
npm ci --omit=dev
npm run build
```

### 2.2 .env oluştur ve düzenle

```bash
cp .env.example .env
php artisan key:generate
```

`.env` üzerinde mutlaka değiştirilmesi gerekenler:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mentorde.yourdomain.com

# Veritabanı — SQLite YERİNE MySQL kullan
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mentorde_prod
DB_USERNAME=mentorde_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Cache/Queue/Session — Redis kullan (production'da zorunlu)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=480

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=REDIS_PASSWORD_IF_ANY
REDIS_PORT=6379

# Log — production'da single veya daily
LOG_CHANNEL=daily
LOG_LEVEL=warning

# Mail — gerçek SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=noreply@mentorde.com
MAIL_PASSWORD=MAIL_PASSWORD
MAIL_FROM_ADDRESS=noreply@mentorde.com
MAIL_FROM_NAME="MentorDE"

# Dosya depolama — production'da public disk
FILESYSTEM_DISK=public

# Güvenlik
BCRYPT_ROUNDS=12
SESSION_ENCRYPT=true
```

### 2.3 Veritabanı ve migration

```bash
# Veritabanını oluştur (MySQL'de)
mysql -u root -p -e "CREATE DATABASE mentorde_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'mentorde_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON mentorde_prod.* TO 'mentorde_user'@'localhost';"

# Migration'ları çalıştır
php artisan migrate --force

# Seeder'ları çalıştır (ilk kurulumda)
php artisan db:seed --force
```

### 2.4 Storage ve önbellek

```bash
# Storage symlink oluştur (public upload'lar için zorunlu)
php artisan storage:link

# Cache'i temizle ve yeniden oluştur
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Autoload optimize
composer dump-autoload --optimize
```

---

## 3. Nginx Yapılandırması

`scripts/nginx-mentorde.conf` dosyasını kullan:

```bash
sudo cp scripts/nginx-mentorde.conf /etc/nginx/sites-available/mentorde
sudo ln -s /etc/nginx/sites-available/mentorde /etc/nginx/sites-enabled/mentorde
sudo nginx -t && sudo systemctl reload nginx
```

SSL için Certbot:
```bash
sudo certbot --nginx -d mentorde.yourdomain.com
```

---

## 4. Queue Workers — Supervisor

`scripts/supervisor-mentorde.conf` dosyasını kullan:

```bash
sudo cp scripts/supervisor-mentorde.conf /etc/supervisor/conf.d/mentorde.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mentorde:*
```

Durumu kontrol et:
```bash
sudo supervisorctl status
```

---

## 5. Scheduler Cron

```bash
sudo crontab -e
```

Şu satırı ekle:
```
* * * * * www-data cd /var/www/mentorde && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduler'da çalışan komutlar

| Komut | Sıklık | Açıklama |
|---|---|---|
| `marketing:sync-external-metrics` | Günlük 06:00 | Meta/GA4/Google Ads/TikTok/LinkedIn/Instagram metrik sync |
| `social:sync-metrics` | Günlük 07:00 | Sosyal medya hesap metrikleri sync |
| `tasks:process-automation` | Saatlik | Tekrarlayan task oluşturma + deadline bildirimleri |
| `gdpr:enforce-retention` | Günlük 03:00 | GDPR veri saklama politikası uygulama |
| `manager:report-snapshot` | Aylık | Yönetici rapor özeti |

---

## 6. Dosya İzinleri

```bash
sudo chown -R www-data:www-data /var/www/mentorde
sudo chmod -R 755 /var/www/mentorde
sudo chmod -R 775 /var/www/mentorde/storage
sudo chmod -R 775 /var/www/mentorde/bootstrap/cache
```

---

## 7. Firebase / Google Credentials

Firebase kullanıyorsan service account JSON dosyasını sunucuya kopyala:

```bash
scp firebase-service-account.json user@server:/var/www/mentorde/
```

`.env` içinde:
```dotenv
GOOGLE_APPLICATION_CREDENTIALS=/var/www/mentorde/firebase-service-account.json
FIREBASE_PROJECT_ID=mentordeprof
FIREBASE_STORAGE_BUCKET=mentordeprof.firebasestorage.app
```

---

## 8. Marketing API Anahtarları

Kullanılacak provider'lar için `.env` içinde doldurun:

### Meta Ads
```dotenv
MKTG_META_ENABLED=true
MKTG_META_AD_ACCOUNT_ID=act_XXXXXXXXX
MKTG_META_ACCESS_TOKEN=EAAxxxxxxxxxxxxx
```

### Google Analytics 4
```dotenv
MKTG_GA4_ENABLED=true
MKTG_GA4_PROPERTY_ID=XXXXXXXXX
MKTG_GA4_CREDENTIALS=/var/www/mentorde/ga4-service-account.json
```

### Google Ads
```dotenv
MKTG_GOOGLE_ADS_ENABLED=true
MKTG_GOOGLE_ADS_CUSTOMER_ID=XXX-XXX-XXXX
MKTG_GOOGLE_ADS_DEVELOPER_TOKEN=XXXXXXXXX
MKTG_GOOGLE_ADS_ACCESS_TOKEN=ya29.xxxxxxxxxxxxx
```

### TikTok Ads
```dotenv
MKTG_TIKTOK_ENABLED=true
MKTG_TIKTOK_ADVERTISER_ID=XXXXXXXXXXXXXXXXXX
MKTG_TIKTOK_ACCESS_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### LinkedIn Ads
```dotenv
MKTG_LINKEDIN_ENABLED=true
MKTG_LINKEDIN_AD_ACCOUNT_ID=XXXXXXXX
MKTG_LINKEDIN_CLIENT_ID=XXXXXXXXXXXXXXXX
MKTG_LINKEDIN_CLIENT_SECRET=XXXXXXXXXXXXXXXX
MKTG_LINKEDIN_ACCESS_TOKEN=AQXxxxxxxxxxxxxxx
MKTG_LINKEDIN_REFRESH_TOKEN=AQXxxxxxxxxxxxxxx
```

### Instagram Insights (Meta token paylaşır)
```dotenv
MKTG_INSTAGRAM_ENABLED=true
MKTG_INSTAGRAM_USER_ID=XXXXXXXXXXXXXXXXXX
# access_token boşsa Meta token'ı kullanır
MKTG_INSTAGRAM_ACCESS_TOKEN=
```

---

## 9. Güvenlik Kontrol Listesi

- [ ] `APP_DEBUG=false` — kesinlikle production'da false
- [ ] `APP_KEY` set edildi (`php artisan key:generate`)
- [ ] Güçlü `DB_PASSWORD` (en az 20 karakter)
- [ ] `SESSION_ENCRYPT=true`
- [ ] Redis şifreli (`REDIS_PASSWORD`)
- [ ] Nginx SSL/TLS aktif (HTTPS zorunlu)
- [ ] `storage/` ve `bootstrap/cache/` web'den erişilemez (Nginx config ile)
- [ ] `.env` dosyası web'den erişilemez
- [ ] `composer.lock` commitlendi
- [ ] Firebase service account JSON `/var/www/` dışında veya `.gitignore`'da
- [ ] `LOG_LEVEL=warning` (debug log production'da performans sorunu)
- [ ] Cron job `www-data` kullanıcısıyla çalışıyor

---

## 10. Güncelleme (Deploy) Adımları

```bash
cd /var/www/mentorde

# 1. Maintenance mode aç
php artisan down --retry=60

# 2. Kodu çek
git pull origin main

# 3. Bağımlılıkları güncelle
composer install --no-dev --optimize-autoloader

# 4. Assets build
npm ci --omit=dev && npm run build

# 5. Migration (varsa)
php artisan migrate --force

# 6. Cache temizle ve yeniden oluştur
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Queue worker'ları yeniden başlat
php artisan queue:restart
sudo supervisorctl restart mentorde:*

# 8. Maintenance mode kapat
php artisan up
```

---

## 11. Sağlık Kontrolleri

```bash
# Entegrasyon sağlığı
php artisan integrations:health-check

# Queue durumu
php artisan queue:monitor

# Log takibi
tail -f storage/logs/laravel.log

# Supervisor durumu
sudo supervisorctl status mentorde:*

# Social sync dry-run
php artisan social:sync-metrics --dry-run

# GDPR dry-run
php artisan gdpr:enforce-retention --dry-run
```

---

## 12. Yedekleme

```bash
# Günlük veritabanı yedekleme (cron'a ekle)
0 2 * * * mysqldump -u mentorde_user -pPASSWORD mentorde_prod | gzip > /backups/mentorde_$(date +\%Y\%m\%d).sql.gz

# 30 günden eski yedekleri sil
0 3 * * * find /backups -name "mentorde_*.sql.gz" -mtime +30 -delete

# Storage yedekleme
0 4 * * * rsync -av /var/www/mentorde/storage/app/public/ /backups/storage/
```

---

## Cloudflare Free Tier Kurulumu (CDN + DDoS Koruması)

Hostinger'da çalışan MentorDE için Cloudflare free tier kullanımı önerilir.
Static dosyalar (CSS/JS/görsel) Cloudflare edge'den sunulur, origin sunucuya istek gitmez.

### 1. DNS Ayarı (Cloudflare Dashboard)
1. Domain'i Cloudflare'e ekle → nameserver'ları domain registrar'da güncelle
2. DNS A kaydı: `netsparen.de` → Hostinger IP → **Proxied (turuncu bulut)** yap
3. SSL/TLS → **Full (strict)** mod seç (Hostinger'da Let's Encrypt aktifse)

### 2. Cache Kuralları (Rules → Cache Rules)
```
URL eşleşme: netsparen.de/css/*
  Cache Level: Cache Everything
  Edge TTL: 1 month
  Browser TTL: 1 month

URL eşleşme: netsparen.de/js/*
  Cache Level: Cache Everything
  Edge TTL: 1 month
  Browser TTL: 1 month

URL eşleşme: netsparen.de/build/*
  Cache Level: Cache Everything
  Edge TTL: 1 year   (Vite fingerprinted dosyalar)
  Browser TTL: 1 year
```

### 3. Page Rules (eski arayüz kullanıcıları için)
```
*netsparen.de/css/*   → Cache Level: Cache Everything, Edge TTL: 1 month
*netsparen.de/js/*    → Cache Level: Cache Everything, Edge TTL: 1 month
*netsparen.de/storage/* → Cache Level: Cache Everything, Edge TTL: 7 days
```

### 4. Laravel ile Uyum
`.env` üzerinde **APP_URL** Cloudflare domain olmalı:
```dotenv
APP_URL=https://netsparen.de
TRUSTED_PROXIES=*          # Cloudflare IP range'leri için
```

`AppServiceProvider` veya middleware'de Cloudflare proxy'sini güven al:
```php
// app/Http/Middleware/TrustProxies.php içinde (Laravel 11+ app/Http/Middleware/HandleAppRequests.php)
protected $proxies = '*';
```

### 5. Cache Bust (deploy sonrası)
Her deployment'ta Cloudflare cache'i temizle:
```bash
# Cloudflare API ile (Zone ID ve Token gerekli)
curl -X POST "https://api.cloudflare.com/client/v4/zones/ZONE_ID/purge_cache" \
  -H "Authorization: Bearer CF_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

Veya Cloudflare Dashboard → Caching → Purge Everything.

---

## JSON Bridge Performans Notu

`window.__xxx = @json(...)` paterni (Blade → JS veri köprüsü) doğru kullanıldığında
verimlidir. Aşağıdaki kurallara uyulmalı:

### Uygun Kullanım (kalabilir)
- Sayfa açılışında hemen gereken DB verisi (charts, form seçenekleri)
- CSRF token, kullanıcı kimliği gibi auth bilgileri
- Görece küçük veri (<5 KB): `window.__kpiSources`, `window.__guestProgress` vb.

### Sorunlu Kullanım (API'ye taşı)
- Sayfa altında kalan, ilk render'da görünmeyen büyük listeler (>20 KB)
- Aynı veri birden fazla sayfada tekrarlıyorsa (config'i JS'e taşı)

### Statik Sabitler → JS'e Gömülü Sabite Dönüştür
```blade
{{-- ÖNCE: global pollution + PHP'ye bağımlılık --}}
window.__processCatalog = @json(\App\Models\MarketingTask::WORKFLOW_STAGES);

{{-- SONRA: local var, sadece ihtiyaç duyulan scope'ta --}}
(function() {
    var catalog = @json(\App\Models\MarketingTask::WORKFLOW_STAGES);
    // kullan...
})();
```

### Büyük Config → API Endpoint + Cache
```php
// routes/api.php
Route::get('institution-catalog', fn() =>
    response()->json(config('institution_document_catalog.categories', []))
              ->withHeaders(['Cache-Control' => 'public, max-age=86400'])
)->name('api.institution-catalog');
```
```js
// İlk yüklemede lazy fetch, localStorage'da cache
var ptCatalog = null;
async function getInstitutionCatalog() {
    if (ptCatalog) return ptCatalog;
    var cached = localStorage.getItem('pt_catalog_v1');
    if (cached) return (ptCatalog = JSON.parse(cached));
    var r = await fetch('/api/institution-catalog');
    ptCatalog = await r.json();
    localStorage.setItem('pt_catalog_v1', JSON.stringify(ptCatalog));
    return ptCatalog;
}
```

---

## Hızlı Referans

| İşlem | Komut |
|---|---|
| Cache temizle | `php artisan optimize:clear` |
| Queue worker yeniden başlat | `php artisan queue:restart` |
| Scheduler manuel çalıştır | `php artisan schedule:run` |
| Maintenance aç | `php artisan down` |
| Maintenance kapat | `php artisan up` |
| Test çalıştır | `composer test` |
| Smoke test | `php artisan test --filter=SmokeRoutes` |
