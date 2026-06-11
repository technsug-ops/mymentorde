<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Platform Owner global key-value settings (cross-tenant).
 *
 * Faz 2 Sistem sayfaları (Platform Ayarları + Güvenlik) için kullanılır.
 * Cache TTL: 60 dakika; set() çağrısı cache invalidate eder.
 *
 * Örnek:
 *   $email = PlatformSetting::get('platform.support_email', 'support@mentorde.com');
 *   PlatformSetting::set('security.max_login_attempts', 7, 'security');
 */
class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'category',
        'is_secret',
        'updated_by_user_id',
    ];

    protected $casts = [
        'value'     => 'array',
        'is_secret' => 'bool',
    ];

    /** Cache TTL: 60 dakika */
    private const CACHE_TTL = 3600;
    private const CACHE_PREFIX = 'platform_setting:';

    /**
     * Setting değerini cache'li getirir.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        try {
            return Cache::remember(
                self::CACHE_PREFIX . $key,
                self::CACHE_TTL,
                function () use ($key, $default) {
                    $row = static::where('key', $key)->first();
                    if (!$row) {
                        return $default;
                    }
                    // value JSON casted to array; gerçek değer 'value' key'inde değil — cast'li dönüş
                    return $row->value;
                }
            );
        } catch (\Throwable $e) {
            // Cache/DB sorunu — sessizce default'a düş
            return $default;
        }
    }

    /**
     * Setting değerini kaydeder (upsert) ve cache'i temizler.
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  string $category
     * @return self
     */
    public static function set(string $key, $value, string $category = 'system'): self
    {
        $row = static::updateOrCreate(
            ['key' => $key],
            [
                'value'              => $value,
                'category'           => $category,
                'updated_by_user_id' => auth()->id(),
            ]
        );

        Cache::forget(self::CACHE_PREFIX . $key);

        return $row;
    }

    /** Tüm platform setting cache'lerini temizle. */
    public static function flushCache(): void
    {
        try {
            foreach (static::query()->pluck('key') as $key) {
                Cache::forget(self::CACHE_PREFIX . $key);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /** Kategori bazlı toplu fetch (UI grid'lerinde kullanılır). */
    public static function allByCategory(string $category): \Illuminate\Support\Collection
    {
        return static::where('category', $category)
            ->orderBy('key')
            ->get();
    }
}
