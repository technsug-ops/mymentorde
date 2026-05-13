<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Public REST API'ye erişen kardeş site / iş ortağı.
 *
 * Key formatı: mtde_live_<32 char hex>
 * DB'de sadece sha256 hash + ilk 16 char "prefix" (masked görünüm için).
 *
 * Plaintext key sadece bir kez döndürülür (generateNewKey()). Manager UI'da
 * bir daha gösterilmez — sadece rotate edilebilir.
 */
class ApiPartner extends Model
{
    protected $table = 'api_partners';

    protected $fillable = [
        'name', 'slug',
        'api_key_prefix', 'api_key_hash',
        'contact_email', 'website',
        'is_active', 'rate_limit_per_hour',
        'notes',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'rate_limit_per_hour' => 'integer',
        'total_requests'      => 'integer',
        'last_used_at'        => 'datetime',
    ];

    /** Plaintext key DB'de tutulmaz — JSON dışı bırak. */
    protected $hidden = ['api_key_hash'];

    public function requests(): HasMany
    {
        return $this->hasMany(ApiPartnerRequest::class, 'api_partner_id');
    }

    /**
     * Yeni bir partner oluştur + plaintext key döndür (sadece bir kez).
     *
     * @return array{partner: self, plaintext_key: string}
     */
    public static function provision(array $attrs): array
    {
        $plain  = self::generatePlaintextKey();
        $prefix = substr($plain, 0, 16) . '…';
        $hash   = hash('sha256', $plain);

        $partner = self::query()->create(array_merge($attrs, [
            'slug'           => $attrs['slug'] ?? Str::slug($attrs['name']),
            'api_key_prefix' => $prefix,
            'api_key_hash'   => $hash,
            'is_active'      => $attrs['is_active'] ?? true,
        ]));

        return ['partner' => $partner, 'plaintext_key' => $plain];
    }

    /**
     * Var olan partner'a yeni key ver (eski hash invalidate olur).
     */
    public function rotateKey(): string
    {
        $plain  = self::generatePlaintextKey();
        $prefix = substr($plain, 0, 16) . '…';
        $this->update([
            'api_key_prefix' => $prefix,
            'api_key_hash'   => hash('sha256', $plain),
        ]);
        return $plain;
    }

    /**
     * Bearer token'dan partner bul. Tek sorgu, indexed unique hash lookup.
     */
    public static function findByPlaintextKey(string $plain): ?self
    {
        $hash = hash('sha256', $plain);
        return self::query()->where('api_key_hash', $hash)->first();
    }

    /** Stripe/SendGrid tarzı: prefix + 32 char hex = 41 char total. */
    private static function generatePlaintextKey(): string
    {
        return 'mtde_live_' . bin2hex(random_bytes(16));
    }

    /** Usage istatistiği için — middleware her başarılı request sonrası çağırır. */
    public function recordHit(): void
    {
        $this->increment('total_requests');
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
