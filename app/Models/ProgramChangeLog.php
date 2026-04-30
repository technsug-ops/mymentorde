<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Source diff yakalama logu — manager dashboard widget için kaynak.
 *
 * Severity:
 *  - info     → küçük metin değişikliği (course name typo düzelt)
 *  - warning  → field değeri değişti (deadline kaydı, tuition güncel)
 *  - critical → kritik field değişti (university_name değişti, program kapandı)
 *
 * Reviewer action:
 *  - accepted        → manager kabul etti, canonical güncellendi
 *  - rejected        → manager reddetti, canonical değişmez
 *  - manual_override → manager kendi value yazdı (curated)
 *  - ignored         → görmezden gelindi
 */
class ProgramChangeLog extends Model
{
    protected $table = 'program_change_logs';

    public const SEV_INFO     = 'info';
    public const SEV_WARNING  = 'warning';
    public const SEV_CRITICAL = 'critical';

    public const ACTION_ACCEPTED        = 'accepted';
    public const ACTION_REJECTED        = 'rejected';
    public const ACTION_MANUAL_OVERRIDE = 'manual_override';
    public const ACTION_IGNORED         = 'ignored';

    protected $fillable = [
        'program_id', 'source', 'field_changed',
        'old_value', 'new_value', 'severity',
        'detected_at', 'reviewed_by_user_id', 'reviewed_at',
        'reviewer_action', 'reviewer_note',
    ];

    protected $casts = [
        'old_value'    => 'array',
        'new_value'    => 'array',
        'detected_at'  => 'datetime',
        'reviewed_at'  => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function scopeUnreviewed(Builder $q): Builder
    {
        return $q->whereNull('reviewed_at');
    }

    public function scopeCritical(Builder $q): Builder
    {
        return $q->where('severity', self::SEV_CRITICAL);
    }

    public function scopeRecent(Builder $q, int $days = 7): Builder
    {
        return $q->where('detected_at', '>=', now()->subDays($days));
    }
}
