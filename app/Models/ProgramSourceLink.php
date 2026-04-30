<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canonical Program ↔ Source (Expatrio/HK/manual) link tablosu.
 *
 * Bir program birden fazla kaynakta olabilir (gelecekte Expatrio + HK
 * aynı programı listelerse). Her kaynak ayrı bir link kaydı.
 *
 * İZOLASYON: SHARED.
 *
 * checksum: SHA256(raw_data) — change detection için. Yeni sync'te
 * eski checksum ile karşılaştırılır, fark varsa change_log üretilir.
 */
class ProgramSourceLink extends Model
{
    protected $table = 'program_source_links';

    protected $fillable = [
        'program_id', 'source', 'external_id',
        'raw_data', 'checksum',
        'last_synced_at', 'is_primary',
    ];

    protected $casts = [
        'raw_data'        => 'array',
        'last_synced_at'  => 'datetime',
        'is_primary'      => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /** Verilen raw_data için deterministic checksum üret. */
    public static function computeChecksum(array $raw): string
    {
        // ksort recursive + json_encode → deterministic
        $normalized = self::normalizeForChecksum($raw);
        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function normalizeForChecksum(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);
            return array_map([self::class, 'normalizeForChecksum'], $value);
        }
        return $value;
    }
}
