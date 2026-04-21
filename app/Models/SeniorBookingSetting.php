<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeniorBookingSetting extends Model
{
    use BelongsToCompany;

    protected $table = 'senior_booking_settings';

    protected $fillable = [
        'company_id',
        'senior_user_id',
        'slot_duration',
        'buffer_minutes',
        'min_notice_hours',
        'max_future_days',
        'timezone',
        'is_public',
        'public_slug',
        'display_name',
        'welcome_message',
        'is_active',
    ];

    protected $casts = [
        'slot_duration'    => 'integer',
        'buffer_minutes'   => 'integer',
        'min_notice_hours' => 'integer',
        'max_future_days'  => 'integer',
        'is_public'        => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function senior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_user_id');
    }

    public function patterns(): HasMany
    {
        return $this->hasMany(SeniorAvailabilityPattern::class, 'senior_user_id', 'senior_user_id');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(SeniorAvailabilityException::class, 'senior_user_id', 'senior_user_id');
    }
}
