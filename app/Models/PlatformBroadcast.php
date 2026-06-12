<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform Owner Broadcast (Duyuru).
 *
 * Tum musteri company'lere veya secili segment'e cross-tenant duyuru gonderimi.
 * Channel: email / in_app / both. Target: all / trial / paid / specific.
 *
 * Lifecycle:
 *   draft → scheduled → sending → sent  (veya cancelled)
 */
class PlatformBroadcast extends Model
{
    use HasFactory;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING   = 'sending';
    public const STATUS_SENT      = 'sent';
    public const STATUS_CANCELLED = 'cancelled';

    public const CHANNEL_EMAIL  = 'email';
    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_BOTH   = 'both';

    public const SEGMENT_ALL      = 'all';
    public const SEGMENT_TRIAL    = 'trial';
    public const SEGMENT_PAID     = 'paid';
    public const SEGMENT_SPECIFIC = 'specific';

    protected $fillable = [
        'title',
        'body',
        'channel',
        'target_segment',
        'target_tiers',
        'target_company_ids',
        'scheduled_for',
        'sent_at',
        'status',
        'cta_label',
        'cta_url',
        'sent_count',
        'opened_count',
        'clicked_count',
        'created_by_user_id',
    ];

    protected $casts = [
        'target_tiers'       => 'array',
        'target_company_ids' => 'array',
        'scheduled_for'      => 'datetime',
        'sent_at'            => 'datetime',
        'sent_count'         => 'integer',
        'opened_count'       => 'integer',
        'clicked_count'      => 'integer',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(PlatformBroadcastRecipient::class, 'broadcast_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** Status badge CSS class kisayolu — view'da kullanilir. */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'plat-badge-inactive',
            self::STATUS_SCHEDULED => 'plat-badge-trial',
            self::STATUS_SENDING   => 'plat-badge-gold',
            self::STATUS_SENT      => 'plat-badge-active',
            self::STATUS_CANCELLED => 'plat-badge-inactive',
            default                => 'plat-badge-inactive',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'Taslak',
            self::STATUS_SCHEDULED => 'Zamanlandi',
            self::STATUS_SENDING   => 'Gonderiliyor',
            self::STATUS_SENT      => 'Gonderildi',
            self::STATUS_CANCELLED => 'Iptal',
            default                => $this->status,
        };
    }

    public function openRate(): float
    {
        if ($this->sent_count <= 0) return 0.0;
        return round(($this->opened_count / $this->sent_count) * 100, 1);
    }

    public function clickRate(): float
    {
        if ($this->sent_count <= 0) return 0.0;
        return round(($this->clicked_count / $this->sent_count) * 100, 1);
    }
}
