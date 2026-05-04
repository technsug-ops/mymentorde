<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Canonical Almanya üniversite kataloğu (kaynak-bağımsız).
 *
 * İZOLASYON: SHARED — company_id YOK. Tüm company'ler ortak okur.
 * Çünkü bu referans veri (zaten public Almanya program kataloğu).
 *
 * Source linklerini program_source_links üzerinden tutmak yerine,
 * üniversiteler için doğrudan name match yapılır (basit + idempotent).
 */
class University extends Model
{
    use HasUuids;

    protected $table = 'universities';

    protected $fillable = [
        'id', 'name', 'city', 'state', 'type', 'is_public',
        'image_path', 'video_url', 'video_caption',
        'metadata', 'is_manually_curated', 'is_active',
        'is_uni_assist_member', 'uni_assist_id',
    ];

    protected $casts = [
        'metadata'             => 'array',
        'is_public'            => 'boolean',
        'is_manually_curated'  => 'boolean',
        'is_active'            => 'boolean',
        'is_uni_assist_member' => 'boolean',
        'uni_assist_id'        => 'integer',
    ];

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'university_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * YouTube/Vimeo URL'sinden embed URL uretir.
     * Desteklenen formatlar:
     *   - https://www.youtube.com/watch?v=ID
     *   - https://youtu.be/ID
     *   - https://www.youtube.com/embed/ID
     *   - https://vimeo.com/ID
     * Tani(n)mayan URL: olduğu gibi döner (admin direk embed URL girmiş olabilir).
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        $url = trim((string) ($this->attributes['video_url'] ?? ''));
        if ($url === '') return null;

        // YouTube watch?v=
        if (preg_match('#youtube\.com/watch\?v=([A-Za-z0-9_-]{6,})#', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        // youtu.be/ID
        if (preg_match('#youtu\.be/([A-Za-z0-9_-]{6,})#', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        // Already YouTube embed
        if (preg_match('#youtube\.com/embed/([A-Za-z0-9_-]{6,})#', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        // Vimeo
        if (preg_match('#vimeo\.com/(\d+)#', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        // Bilinmeyen — admin'in girdigi URL'yi olduğu gibi kullan
        return $url;
    }

    /** Üniversite name'inden fetch — adapter'lar bunu kullanır. */
    public static function findOrCreateByName(string $name, array $extra = []): self
    {
        $name = trim($name);
        $existing = static::query()->where('name', $name)->first();
        if ($existing) {
            // Manuel curation öncelikli — manager dokunduysa override etme
            if (! $existing->is_manually_curated && ! empty($extra)) {
                $existing->fill($extra)->save();
            }
            return $existing;
        }

        return static::query()->create(array_merge(
            ['name' => $name, 'is_active' => true],
            $extra
        ));
    }
}
