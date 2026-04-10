<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DigitalAsset extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $table = 'digital_assets';

    protected $fillable = [
        'company_id',
        'folder_id',
        'uuid',
        'source_type',
        'external_url',
        'doc_code',
        'name',
        'original_filename',
        'mime_type',
        'extension',
        'size_bytes',
        'disk',
        'path',
        'thumbnail_path',
        'category',
        'tags',
        'description',
        'metadata',
        'download_count',
        'last_downloaded_at',
        'is_pinned',
        'legacy_source',
        'legacy_source_id',
        'created_by',
    ];

    protected $casts = [
        'tags'               => 'array',
        'metadata'           => 'array',
        'size_bytes'         => 'integer',
        'download_count'     => 'integer',
        'last_downloaded_at' => 'datetime',
        'is_pinned'          => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $asset): void {
            if (empty($asset->uuid)) {
                $asset->uuid = (string) Str::uuid();
            }
        });
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DigitalAssetFolder::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function favoritedBy(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'digital_asset_favorites', 'asset_id', 'user_id')
            ->withTimestamps();
    }

    public function isFavoritedBy(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        // Eğer eager-load edilmişse onu kullan, yoksa direkt sorgula
        if ($this->relationLoaded('favoritedBy')) {
            return $this->favoritedBy->contains('id', $userId);
        }
        return $this->favoritedBy()->where('users.id', $userId)->exists();
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }

    public function getIsImageAttribute(): bool
    {
        return $this->category === 'image';
    }

    public function getIsLinkAttribute(): bool
    {
        return $this->source_type === 'link';
    }

    public function getIsFileAttribute(): bool
    {
        return $this->source_type !== 'link';
    }

    public function scopeInFolder($query, ?int $folderId)
    {
        return $folderId === null
            ? $query->whereNull('folder_id')
            : $query->where('folder_id', $folderId);
    }
}
