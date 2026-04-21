<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeniorAvailabilityException extends Model
{
    use BelongsToCompany;

    protected $table = 'senior_availability_exceptions';

    protected $fillable = [
        'company_id',
        'senior_user_id',
        'date',
        'is_blocked',
        'override_start_time',
        'override_end_time',
        'reason',
    ];

    protected $casts = [
        'date'       => 'date',
        'is_blocked' => 'boolean',
    ];

    public function senior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_user_id');
    }

    public function scopeForSenior($q, int $seniorUserId)
    {
        return $q->where('senior_user_id', $seniorUserId);
    }

    public function scopeOnOrAfter($q, string $date)
    {
        return $q->where('date', '>=', $date);
    }
}
