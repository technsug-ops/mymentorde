<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeniorAvailabilityPattern extends Model
{
    use BelongsToCompany;

    protected $table = 'senior_availability_patterns';

    protected $fillable = [
        'company_id',
        'senior_user_id',
        'weekday',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'weekday'   => 'integer',
        'is_active' => 'boolean',
    ];

    /** ISO weekday labels (0=Pzt ... 6=Paz) */
    public const WEEKDAY_LABELS_TR = [
        0 => 'Pazartesi',
        1 => 'Salı',
        2 => 'Çarşamba',
        3 => 'Perşembe',
        4 => 'Cuma',
        5 => 'Cumartesi',
        6 => 'Pazar',
    ];

    public function senior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_user_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForSenior($q, int $seniorUserId)
    {
        return $q->where('senior_user_id', $seniorUserId);
    }
}
