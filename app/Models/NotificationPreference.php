<?php

namespace App\Models;

use App\Models\Concerns\OwnedBySubjectCompany;
use App\Models\Contracts\ResolvesOwnCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model implements ResolvesOwnCompany
{
    use OwnedBySubjectCompany;

    protected $fillable = [
        'user_id',
        'guest_id',
        'student_id',
        'company_id',
        'channel',
        'category',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
