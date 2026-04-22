<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiLabsResponseCache extends Model
{
    protected $table = 'ai_labs_response_cache';

    protected $fillable = [
        'company_id',
        'role',
        'cache_key',
        'question',
        'response_json',
        'hit_count',
        'last_hit_at',
        'expires_at',
    ];

    protected $casts = [
        'last_hit_at' => 'datetime',
        'expires_at'  => 'datetime',
        'hit_count'   => 'integer',
    ];

    /**
     * Soru + rol + kaynak fingerprint'inden deterministik cache anahtarı üretir.
     */
    public static function buildKey(int $companyId, string $role, string $question, string $sourcesFingerprint): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $question)));
        return hash('sha256', "{$companyId}|{$role}|{$normalized}|{$sourcesFingerprint}");
    }
}
