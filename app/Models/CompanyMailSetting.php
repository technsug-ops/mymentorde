<?php

namespace App\Models;

use App\Models\Concerns\SharedBetweenTwoCompanies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Firmanın kendi mail taşıyıcısı.
 *
 * "Kendi sunucumu / kendi Resend hesabımı kullanın" diyen firmaya verilen
 * cevap. Tanımlanmamışsa marka katmanı bağlı olunan portala, o da yoksa
 * platforma düşer (bkz. Brand::applyMailIdentity).
 *
 * ⚠ ŞİFRELER. `password` ve `api_key` şifreli saklanıyor; panelde bir daha
 * GÖSTERİLMİYOR. Bu tablo bilerek `companies`'ten ayrı: firma satırı her
 * istekte okunuyor ve önbelleklerde dolaşıyor, kimlik bilgilerinin orada
 * işi yok.
 *
 * ⚠ SharedBetweenTwoCompanies: kayıt tek firmaya ait ama üst firma da
 * yönetebiliyor (platform konsolu). Global kapsam uygulanmıyor, sınır
 * sorgularda kuruluyor.
 */
class CompanyMailSetting extends Model
{
    use SharedBetweenTwoCompanies;

    public const DRIVER_SMTP   = 'smtp';
    public const DRIVER_RESEND = 'resend';

    public const DRIVERS = [
        self::DRIVER_SMTP   => 'SMTP — kendi mail sunucusu',
        self::DRIVER_RESEND => 'Resend — firmanın kendi hesabı',
    ];

    /** Hangi firmalarda taşıyıcı tanımlı — SIR İÇERMEZ, önbelleklenebilir. */
    private const ACTIVE_IDS_CACHE_KEY = 'company_mail_settings:active_ids';

    protected $fillable = [
        'company_id', 'driver', 'host', 'port', 'username', 'encryption',
        'password', 'api_key', 'from_address', 'is_active',
        'last_tested_at', 'last_test_error', 'updated_by',
    ];

    protected $casts = [
        // Laravel'in encrypted cast'i: veritabanında şifreli, kodda düz.
        'password'       => 'encrypted',
        'api_key'        => 'encrypted',
        'is_active'      => 'boolean',
        'port'           => 'integer',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = ['password', 'api_key'];

    protected static function booted(): void
    {
        // Aktiflik değişince "kimde tanımlı" listesi tazelensin.
        static::saved(fn () => self::flushActiveIds());
        static::deleted(fn () => self::flushActiveIds());
    }

    /**
     * Taşıyıcısı AKTİF olan firma id'leri.
     *
     * Marka katmanı her istekte çalışıyor; her istekte bu tabloya sorgu
     * atmamak için önce bu hafif liste okunuyor. Liste yalnızca id içerir,
     * sır taşımaz — önbelleklenmesi güvenli.
     *
     * @return array<int,true>
     */
    public static function activeCompanyIds(): array
    {
        return Cache::remember(self::ACTIVE_IDS_CACHE_KEY, 300, static function (): array {
            try {
                return self::query()
                    ->where('is_active', true)
                    ->pluck('company_id')
                    ->mapWithKeys(static fn ($id): array => [(int) $id => true])
                    ->all();
            } catch (\Throwable $e) {
                // Tablo yoksa (migration öncesi) mail gönderimi durmamalı.
                return [];
            }
        });
    }

    public static function flushActiveIds(): void
    {
        Cache::forget(self::ACTIVE_IDS_CACHE_KEY);
    }

    /** Bu firmanın AKTİF taşıyıcısı — yoksa null. */
    public static function activeFor(int $companyId): ?self
    {
        if ($companyId <= 0 || !isset(self::activeCompanyIds()[$companyId])) {
            return null;
        }

        return self::query()->where('company_id', $companyId)->where('is_active', true)->first();
    }

    /**
     * Laravel mailer yapılandırması — `config('mail.mailers.*')` şeklinde.
     *
     * Resend'de anahtar mailer'da değil `services.resend.key` içinde durur;
     * onu çağıran taraf ayarlar (bkz. Brand::applyMailIdentity).
     *
     * @return array<string,mixed>
     */
    public function mailerConfig(): array
    {
        if ($this->driver === self::DRIVER_RESEND) {
            return ['transport' => 'resend'];
        }

        return [
            'transport'    => 'smtp',
            'host'         => (string) $this->host,
            'port'         => (int) ($this->port ?: 587),
            'username'     => $this->username ?: null,
            'password'     => $this->password ?: null,
            // Laravel 11+ scheme ile TLS'i belirler; 'tls' → smtps zorunlu değil,
            // STARTTLS varsayılan. Boş bırakılırsa sunucu ne sunuyorsa o.
            'scheme'       => $this->encryption === 'ssl' ? 'smtps' : null,
            'timeout'      => 15,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
        ];
    }

    /** Eksik alan var mı — aktifleştirmeden önce bakılır. */
    public function isComplete(): bool
    {
        if ($this->driver === self::DRIVER_RESEND) {
            return trim((string) $this->api_key) !== '';
        }

        return trim((string) $this->host) !== '' && (int) $this->port > 0;
    }
}
