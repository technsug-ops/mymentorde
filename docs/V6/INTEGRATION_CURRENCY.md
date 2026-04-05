# Entegrasyon: Döviz Kuru Takibi

---

## Amaç

EUR/TRY ve EUR/USD kurlarını günlük olarak ücretsiz bir API'den çekerek veritabanında saklamak ve öğrenci ödeme sayfasında TRY karşılığını göstermek.

---

## API Kaynağı

**URL:** `https://open.er-api.com/v6/latest/EUR`
**Özellikler:** Ücretsiz, API key gerektirmez, günde 1500 istek limiti
**Yanıt:**
```json
{
    "result": "success",
    "base_code": "EUR",
    "rates": {
        "TRY": 43.12,
        "USD": 1.08,
        "GBP": 0.86,
        ...
    },
    "time_last_update_utc": "Thu, 19 Mar 2026 00:00:00 +0000"
}
```

---

## CurrencyRateService

**Dosya:** `app/Services/CurrencyRateService.php`

### sync()

```php
sync(): array  // ['TRY' => 43.12, 'USD' => 1.08, 'GBP' => 0.86]
```

- `EUR/TRY`, `EUR/USD`, `EUR/GBP` kayıtlarını günceller
- `CurrencyRate::updateOrCreate()` — günde bir unique kayıt
- Her sync sonrası `Cache::forget("currency_rate_EUR_TRY")` ile cache temizlenir

### getRate()

```php
getRate(string $from = 'EUR', string $to = 'TRY'): ?float
```

```php
Cache::remember("currency_rate_{$from}_{$to}", 3600, fn() =>
    CurrencyRate::where(base=$from)->where(target=$to)->latest('fetched_at')->value('rate')
)
```

Cache süresi: **3600 saniye (1 saat)**.

### getRateDate()

```php
getRateDate(string $from = 'EUR', string $to = 'TRY'): ?string  // "19.03.2026"
```

---

## CurrencyRate Modeli

**Dosya:** `app/Models/CurrencyRate.php`
**Tablo:** `currency_rates`

| Alan | Tip | Açıklama |
|------|-----|----------|
| `base_currency` | char(3) | `EUR` |
| `target_currency` | char(3) | `TRY`, `USD`, `GBP` |
| `rate` | decimal(12,6) | Kur değeri |
| `fetched_at` | date | Çekilme tarihi |
| `source` | string | `open.er-api.com` |

**Unique kısıt:** `[base_currency, target_currency, fetched_at]`

---

## Cron Görevi

**Komut:** `php artisan currency:sync-rates`
**Dosya:** `app/Console/Commands/SyncCurrencyRatesCommand.php`
**Schedule:** Her gün `06:00` → `routes/console.php`

```php
Schedule::command('currency:sync-rates')->dailyAt('06:00');
```

---

## Student Payments Widget

**Dosya:** `resources/views/student/payments.blade.php`

```php
$rate = app(CurrencyRateService::class)->getRate('EUR', 'TRY');
$rateDate = app(CurrencyRateService::class)->getRateDate('EUR', 'TRY');
$tryAmount = $eurAmount * $rate;
```

**Görünüm:**
```
€ 2.500,00 Program Ücreti
≈ ₺ 107.800  (1 EUR = 43,12 TRY · 19.03.2026)
```

Kur verisi yoksa chip gösterilmez (null-safe).

---

## Dosya Referansları

| Tür | Dosya |
|-----|-------|
| Service | `app/Services/CurrencyRateService.php` |
| Model | `app/Models/CurrencyRate.php` |
| Command | `app/Console/Commands/SyncCurrencyRatesCommand.php` |
| Migration | `database/migrations/2026_03_11_120000_create_currency_rates_table.php` |
| View | `resources/views/student/payments.blade.php` |
