<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTimeEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id', 'user_id', 'started_at', 'ended_at',
        'duration_minutes', 'note', 'created_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(MarketingTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Süre tamamlandıysa dakika hesapla */
    public function getDurationHoursAttribute(): ?float
    {
        if ($this->duration_minutes === null) return null;
        return round($this->duration_minutes / 60, 2);
    }
}
