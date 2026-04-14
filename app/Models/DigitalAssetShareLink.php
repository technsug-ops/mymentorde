<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DigitalAssetShareLink extends Model
{
    protected $table = 'digital_asset_share_links';

    protected $fillable = [
        'asset_id',
        'token',
        'password_hash',
        'created_by_user_id',
        'expires_at',
        'download_count',
        'max_downloads',
        'last_accessed_at',
        'last_accessed_ip',
        'is_revoked',
    ];

    protected $casts = [
        'expires_at'       => 'datetime',
        'last_accessed_at' => 'datetime',
        'is_revoked'       => 'boolean',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(DigitalAsset::class, 'asset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public static function generateToken(): string
    {
        return Str::random(48);
    }

    public function isExpired(): bool
    {
        if ($this->is_revoked) {
            return true;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }
        if ($this->max_downloads !== null && $this->download_count >= $this->max_downloads) {
            return true;
        }
        return false;
    }

    public function requiresPassword(): bool
    {
        return !empty($this->password_hash);
    }

    public function checkPassword(string $plain): bool
    {
        return \Illuminate\Support\Facades\Hash::check($plain, (string) $this->password_hash);
    }
}
