<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTwoFactor extends Model
{
    public $timestamps = false;

    protected $table = 'user_two_factor';

    protected $fillable = [
        'user_id', 'secret', 'recovery_codes', 'enabled_at', 'last_used_at',
    ];

    protected $casts = [
        'recovery_codes' => 'array',
        'enabled_at'     => 'datetime',
        'last_used_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isEnabled(): bool
    {
        return $this->enabled_at !== null;
    }
}
